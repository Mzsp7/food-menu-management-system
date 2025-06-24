<?php
/**
 * List Menu Items API Endpoint
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
    
    // Get query parameters
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
    $search = trim($_GET['search'] ?? '');
    $categoryId = $_GET['category_id'] ?? '';
    $sortBy = $_GET['sort_by'] ?? 'name';
    $sortOrder = strtoupper($_GET['sort_order'] ?? 'ASC');
    
    // Validate sort parameters
    $allowedSortFields = ['name', 'price', 'created_at', 'updated_at'];
    $allowedSortOrders = ['ASC', 'DESC'];
    
    if (!in_array($sortBy, $allowedSortFields)) {
        $sortBy = 'name';
    }
    
    if (!in_array($sortOrder, $allowedSortOrders)) {
        $sortOrder = 'ASC';
    }
    
    // Build query
    $whereConditions = ['mi.is_available = 1'];
    $params = [];
    
    // Add search condition
    if (!empty($search)) {
        $whereConditions[] = '(mi.name LIKE ? OR mi.description LIKE ?)';
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Add category filter
    if (!empty($categoryId) && is_numeric($categoryId)) {
        $whereConditions[] = 'mi.category_id = ?';
        $params[] = $categoryId;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Get total count
    $countSql = "
        SELECT COUNT(*) as total
        FROM menu_items mi
        LEFT JOIN categories c ON mi.category_id = c.id
        WHERE {$whereClause}
    ";
    
    $totalResult = $database->fetchOne($countSql, $params);
    $total = $totalResult['total'];
    
    // Get items with pagination
    $offset = ($page - 1) * $perPage;
    
    $sql = "
        SELECT 
            mi.id,
            mi.name,
            mi.description,
            mi.price,
            mi.image_url,
            mi.is_available,
            mi.created_at,
            mi.updated_at,
            c.id as category_id,
            c.name as category_name,
            u.username as created_by_username
        FROM menu_items mi
        LEFT JOIN categories c ON mi.category_id = c.id
        LEFT JOIN users u ON mi.created_by = u.id
        WHERE {$whereClause}
        ORDER BY mi.{$sortBy} {$sortOrder}
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $perPage;
    $params[] = $offset;
    
    $items = $database->fetchAll($sql, $params);
    
    // Format the response
    $formattedItems = array_map(function($item) {
        return [
            'id' => (int)$item['id'],
            'name' => $item['name'],
            'description' => $item['description'],
            'price' => (float)$item['price'],
            'image_url' => $item['image_url'],
            'is_available' => (bool)$item['is_available'],
            'category_id' => $item['category_id'] ? (int)$item['category_id'] : null,
            'category_name' => $item['category_name'],
            'created_by_username' => $item['created_by_username'],
            'created_at' => $item['created_at'],
            'updated_at' => $item['updated_at']
        ];
    }, $items);
    
    // Return paginated response
    Response::paginated($formattedItems, $total, $page, $perPage, 'Menu items retrieved successfully');
    
} catch (Exception $e) {
    error_log("Menu list error: " . $e->getMessage());
    Response::serverError('An error occurred while retrieving menu items');
}
?>
