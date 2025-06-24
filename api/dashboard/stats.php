<?php
/**
 * Dashboard Statistics API Endpoint
 */

require_once '../../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/response.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::methodNotAllowed();
}

try {
    session_start();
    
    // Initialize database and auth
    global $database;
    $auth = new Auth($database);
    
    // Require authentication
    $auth->requireAuth();
    
    // Get basic statistics
    $stats = [];
    
    // Total menu items
    $itemsSql = "SELECT COUNT(*) as count FROM menu_items WHERE is_available = 1";
    $itemsResult = $database->fetchOne($itemsSql);
    $stats['total_items'] = (int)$itemsResult['count'];
    
    // Total categories
    $categoriesSql = "SELECT COUNT(*) as count FROM categories WHERE is_active = 1";
    $categoriesResult = $database->fetchOne($categoriesSql);
    $stats['total_categories'] = (int)$categoriesResult['count'];
    
    // Total users (only for admin/manager)
    if ($auth->hasRole('manager')) {
        $usersSql = "SELECT COUNT(*) as count FROM users WHERE is_active = 1";
        $usersResult = $database->fetchOne($usersSql);
        $stats['total_users'] = (int)$usersResult['count'];
    }
    
    // Average price
    $avgPriceSql = "SELECT AVG(price) as avg_price FROM menu_items WHERE is_available = 1";
    $avgPriceResult = $database->fetchOne($avgPriceSql);
    $stats['average_price'] = $avgPriceResult['avg_price'] ? round((float)$avgPriceResult['avg_price'], 2) : 0;
    
    // Most expensive item
    $maxPriceSql = "SELECT MAX(price) as max_price FROM menu_items WHERE is_available = 1";
    $maxPriceResult = $database->fetchOne($maxPriceSql);
    $stats['max_price'] = $maxPriceResult['max_price'] ? (float)$maxPriceResult['max_price'] : 0;
    
    // Least expensive item
    $minPriceSql = "SELECT MIN(price) as min_price FROM menu_items WHERE is_available = 1";
    $minPriceResult = $database->fetchOne($minPriceSql);
    $stats['min_price'] = $minPriceResult['min_price'] ? (float)$minPriceResult['min_price'] : 0;
    
    // Items by category
    $categoryStatsSql = "
        SELECT 
            c.name as category_name,
            COUNT(mi.id) as item_count
        FROM categories c
        LEFT JOIN menu_items mi ON c.id = mi.category_id AND mi.is_available = 1
        WHERE c.is_active = 1
        GROUP BY c.id, c.name
        ORDER BY item_count DESC, c.name ASC
    ";
    
    $categoryStats = $database->fetchAll($categoryStatsSql);
    $stats['items_by_category'] = array_map(function($row) {
        return [
            'category_name' => $row['category_name'],
            'item_count' => (int)$row['item_count']
        ];
    }, $categoryStats);
    
    // Recent activity (last 10 items added)
    $recentItemsSql = "
        SELECT 
            mi.name,
            mi.price,
            c.name as category_name,
            mi.created_at
        FROM menu_items mi
        LEFT JOIN categories c ON mi.category_id = c.id
        WHERE mi.is_available = 1
        ORDER BY mi.created_at DESC
        LIMIT 10
    ";
    
    $recentItems = $database->fetchAll($recentItemsSql);
    $stats['recent_items'] = array_map(function($item) {
        return [
            'name' => $item['name'],
            'price' => (float)$item['price'],
            'category_name' => $item['category_name'],
            'created_at' => $item['created_at']
        ];
    }, $recentItems);
    
    // Price distribution (in Indian Rupees)
    $priceRanges = [
        ['min' => 0, 'max' => 100, 'label' => '₹0 - ₹100'],
        ['min' => 100, 'max' => 250, 'label' => '₹100 - ₹250'],
        ['min' => 250, 'max' => 400, 'label' => '₹250 - ₹400'],
        ['min' => 400, 'max' => 999999, 'label' => '₹400+']
    ];
    
    $priceDistribution = [];
    foreach ($priceRanges as $range) {
        $rangeSql = "
            SELECT COUNT(*) as count 
            FROM menu_items 
            WHERE is_available = 1 
            AND price >= ? 
            AND price < ?
        ";
        
        $rangeResult = $database->fetchOne($rangeSql, [$range['min'], $range['max']]);
        $priceDistribution[] = [
            'label' => $range['label'],
            'count' => (int)$rangeResult['count']
        ];
    }
    
    $stats['price_distribution'] = $priceDistribution;
    
    // User activity summary (for admin/manager)
    if ($auth->hasRole('manager')) {
        $activitySql = "
            SELECT 
                action,
                COUNT(*) as count
            FROM activity_log
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY action
            ORDER BY count DESC
        ";
        
        $activityStats = $database->fetchAll($activitySql);
        $stats['recent_activity'] = array_map(function($activity) {
            return [
                'action' => $activity['action'],
                'count' => (int)$activity['count']
            ];
        }, $activityStats);
    }
    
    Response::success('Dashboard statistics retrieved successfully', ['stats' => $stats]);
    
} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    Response::serverError('An error occurred while retrieving dashboard statistics');
}
?>
