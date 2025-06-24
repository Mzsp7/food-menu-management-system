<?php
/**
 * List Categories API Endpoint
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
    $includeItemCount = isset($_GET['include_item_count']) && $_GET['include_item_count'] === 'true';
    
    // Base query
    $sql = "
        SELECT 
            c.id,
            c.name,
            c.description,
            c.created_at,
            c.updated_at
    ";
    
    // Add item count if requested
    if ($includeItemCount) {
        $sql .= ",
            (SELECT COUNT(*) FROM menu_items mi WHERE mi.category_id = c.id AND mi.is_available = 1) as item_count
        ";
    }
    
    $sql .= "
        FROM categories c
        WHERE c.is_active = 1
        ORDER BY c.name ASC
    ";
    
    $categories = $database->fetchAll($sql);
    
    // Format the response
    $formattedCategories = array_map(function($category) use ($includeItemCount) {
        $formatted = [
            'id' => (int)$category['id'],
            'name' => $category['name'],
            'description' => $category['description'],
            'created_at' => $category['created_at'],
            'updated_at' => $category['updated_at']
        ];
        
        if ($includeItemCount) {
            $formatted['item_count'] = (int)$category['item_count'];
        }
        
        return $formatted;
    }, $categories);
    
    Response::success('Categories retrieved successfully', ['categories' => $formattedCategories]);
    
} catch (Exception $e) {
    error_log("Categories list error: " . $e->getMessage());
    Response::serverError('An error occurred while retrieving categories');
}
?>
