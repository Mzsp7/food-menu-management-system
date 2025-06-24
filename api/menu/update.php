<?php
/**
 * Update Menu Item API Endpoint
 */

require_once '../../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/response.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Allow POST and PUT requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
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
        ->numeric('id', 'Menu item ID must be a valid number')
        ->required('name', 'Menu item name is required')
        ->maxLength('name', 150, 'Name must not exceed 150 characters')
        ->required('price', 'Price is required')
        ->numeric('price', 'Price must be a valid number')
        ->positive('price', 'Price must be greater than 0')
        ->maxLength('description', 1000, 'Description must not exceed 1000 characters')
        ->url('image_url', 'Image URL must be a valid URL');
    
    $validator->validate();
    
    $itemId = (int)$input['id'];
    
    // Check if menu item exists
    $existingItemSql = "SELECT * FROM menu_items WHERE id = ? AND is_available = 1";
    $existingItem = $database->fetchOne($existingItemSql, [$itemId]);
    
    if (!$existingItem) {
        Response::notFound('Menu item not found');
    }
    
    // Validate category if provided
    if (!empty($input['category_id'])) {
        $validator = new Validator($input);
        $validator->numeric('category_id', 'Category ID must be a valid number');
        
        // Check if category exists
        $categorySql = "SELECT id FROM categories WHERE id = ? AND is_active = 1";
        $category = $database->fetchOne($categorySql, [$input['category_id']]);
        
        if (!$category) {
            Response::validationError(['category_id' => 'Selected category does not exist']);
        }
    }
    
    // Get current user
    $currentUser = $auth->getCurrentUser();
    
    // Prepare data for update
    $name = trim($input['name']);
    $description = trim($input['description'] ?? '');
    $price = (float)$input['price'];
    $categoryId = !empty($input['category_id']) ? (int)$input['category_id'] : null;
    $imageUrl = trim($input['image_url'] ?? '');
    
    // Store old values for logging
    $oldValues = [
        'name' => $existingItem['name'],
        'description' => $existingItem['description'],
        'price' => $existingItem['price'],
        'category_id' => $existingItem['category_id'],
        'image_url' => $existingItem['image_url']
    ];
    
    // Update menu item
    $sql = "UPDATE menu_items 
            SET name = ?, description = ?, price = ?, category_id = ?, image_url = ?, updated_at = NOW()
            WHERE id = ?";
    
    $stmt = $database->execute($sql, [
        $name,
        $description,
        $price,
        $categoryId,
        $imageUrl ?: null,
        $itemId
    ]);
    
    // Log activity
    $newValues = [
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'category_id' => $categoryId,
        'image_url' => $imageUrl
    ];
    
    $auth->logActivity(
        $currentUser['id'],
        'UPDATE',
        'menu_items',
        $itemId,
        $oldValues,
        $newValues
    );
    
    // Get the updated item with category information
    $updatedItemSql = "
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
            c.name as category_name
        FROM menu_items mi
        LEFT JOIN categories c ON mi.category_id = c.id
        WHERE mi.id = ?
    ";
    
    $updatedItem = $database->fetchOne($updatedItemSql, [$itemId]);
    
    // Format response
    $formattedItem = [
        'id' => (int)$updatedItem['id'],
        'name' => $updatedItem['name'],
        'description' => $updatedItem['description'],
        'price' => (float)$updatedItem['price'],
        'image_url' => $updatedItem['image_url'],
        'is_available' => (bool)$updatedItem['is_available'],
        'category_id' => $updatedItem['category_id'] ? (int)$updatedItem['category_id'] : null,
        'category_name' => $updatedItem['category_name'],
        'created_at' => $updatedItem['created_at'],
        'updated_at' => $updatedItem['updated_at']
    ];
    
    Response::success('Menu item updated successfully', $formattedItem);
    
} catch (Exception $e) {
    error_log("Menu update error: " . $e->getMessage());
    Response::serverError('An error occurred while updating the menu item');
}
?>
