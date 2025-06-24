<?php
/**
 * Create Menu Item API Endpoint
 */

require_once '../../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/response.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
        ->required('name', 'Menu item name is required')
        ->maxLength('name', 150, 'Name must not exceed 150 characters')
        ->required('price', 'Price is required')
        ->numeric('price', 'Price must be a valid number')
        ->positive('price', 'Price must be greater than 0')
        ->maxLength('description', 1000, 'Description must not exceed 1000 characters')
        ->url('image_url', 'Image URL must be a valid URL');
    
    // Validate category if provided
    if (!empty($input['category_id'])) {
        $validator->numeric('category_id', 'Category ID must be a valid number');
        
        // Check if category exists
        $categorySql = "SELECT id FROM categories WHERE id = ? AND is_active = 1";
        $category = $database->fetchOne($categorySql, [$input['category_id']]);
        
        if (!$category) {
            $validator->errors()['category_id'] = 'Selected category does not exist';
        }
    }
    
    $validator->validate();
    
    // Get current user
    $currentUser = $auth->getCurrentUser();
    
    // Prepare data for insertion
    $name = trim($input['name']);
    $description = trim($input['description'] ?? '');
    $price = (float)$input['price'];
    $categoryId = !empty($input['category_id']) ? (int)$input['category_id'] : null;
    $imageUrl = trim($input['image_url'] ?? '');
    
    // Insert menu item
    $sql = "INSERT INTO menu_items (name, description, price, category_id, image_url, created_by) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $database->execute($sql, [
        $name,
        $description,
        $price,
        $categoryId,
        $imageUrl ?: null,
        $currentUser['id']
    ]);
    
    $itemId = $database->lastInsertId();
    
    // Log activity
    $auth->logActivity(
        $currentUser['id'],
        'CREATE',
        'menu_items',
        $itemId,
        null,
        [
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'category_id' => $categoryId
        ]
    );
    
    // Get the created item with category information
    $createdItemSql = "
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
    
    $createdItem = $database->fetchOne($createdItemSql, [$itemId]);
    
    // Format response
    $formattedItem = [
        'id' => (int)$createdItem['id'],
        'name' => $createdItem['name'],
        'description' => $createdItem['description'],
        'price' => (float)$createdItem['price'],
        'image_url' => $createdItem['image_url'],
        'is_available' => (bool)$createdItem['is_available'],
        'category_id' => $createdItem['category_id'] ? (int)$createdItem['category_id'] : null,
        'category_name' => $createdItem['category_name'],
        'created_at' => $createdItem['created_at'],
        'updated_at' => $createdItem['updated_at']
    ];
    
    Response::success('Menu item created successfully', $formattedItem, 201);
    
} catch (Exception $e) {
    error_log("Menu create error: " . $e->getMessage());
    Response::serverError('An error occurred while creating the menu item');
}
?>
