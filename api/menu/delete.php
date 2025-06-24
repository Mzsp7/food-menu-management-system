<?php
/**
 * Delete Menu Item API Endpoint
 */

require_once '../../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/response.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Allow POST and DELETE requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
    Response::methodNotAllowed();
}

try {
    session_start();
    
    // Initialize database and auth
    global $database;
    $auth = new Auth($database);
    
    // Require authentication
    $auth->requireAuth();
    
    // Get input data
    $input = Request::all();
    
    // Validate input
    $validator = new Validator($input);
    $validator
        ->required('id', 'Menu item ID is required')
        ->numeric('id', 'Menu item ID must be a valid number');
    
    $validator->validate();
    
    $itemId = (int)$input['id'];
    
    // Check if menu item exists
    $existingItemSql = "SELECT * FROM menu_items WHERE id = ? AND is_available = 1";
    $existingItem = $database->fetchOne($existingItemSql, [$itemId]);
    
    if (!$existingItem) {
        Response::notFound('Menu item not found');
    }
    
    // Get current user
    $currentUser = $auth->getCurrentUser();
    
    // Check permissions - only admin/manager can delete, or the creator
    if (!$auth->hasRole('manager') && $existingItem['created_by'] != $currentUser['id']) {
        Response::forbidden('You do not have permission to delete this menu item');
    }
    
    // Store item data for logging before deletion
    $itemData = [
        'name' => $existingItem['name'],
        'description' => $existingItem['description'],
        'price' => $existingItem['price'],
        'category_id' => $existingItem['category_id'],
        'image_url' => $existingItem['image_url']
    ];
    
    // Soft delete - set is_available to false instead of actual deletion
    $sql = "UPDATE menu_items SET is_available = 0, updated_at = NOW() WHERE id = ?";
    $stmt = $database->execute($sql, [$itemId]);
    
    if ($database->rowCount($stmt) === 0) {
        Response::serverError('Failed to delete menu item');
    }
    
    // Log activity
    $auth->logActivity(
        $currentUser['id'],
        'DELETE',
        'menu_items',
        $itemId,
        $itemData,
        null
    );
    
    Response::success('Menu item deleted successfully');
    
} catch (Exception $e) {
    error_log("Menu delete error: " . $e->getMessage());
    Response::serverError('An error occurred while deleting the menu item');
}
?>
