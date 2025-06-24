<?php
/**
 * Create Category API Endpoint
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
        ->required('name', 'Category name is required')
        ->maxLength('name', 100, 'Name must not exceed 100 characters')
        ->maxLength('description', 500, 'Description must not exceed 500 characters');
    
    $validator->validate();
    
    // Check if category name already exists
    $existingCategorySql = "SELECT id FROM categories WHERE name = ? AND is_active = 1";
    $existingCategory = $database->fetchOne($existingCategorySql, [trim($input['name'])]);
    
    if ($existingCategory) {
        Response::validationError(['name' => 'A category with this name already exists']);
    }
    
    // Get current user
    $currentUser = $auth->getCurrentUser();
    
    // Prepare data for insertion
    $name = trim($input['name']);
    $description = trim($input['description'] ?? '');
    
    // Insert category
    $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
    $stmt = $database->execute($sql, [$name, $description]);
    
    $categoryId = $database->lastInsertId();
    
    // Log activity
    $auth->logActivity(
        $currentUser['id'],
        'CREATE',
        'categories',
        $categoryId,
        null,
        [
            'name' => $name,
            'description' => $description
        ]
    );
    
    // Get the created category
    $createdCategorySql = "SELECT id, name, description, created_at, updated_at FROM categories WHERE id = ?";
    $createdCategory = $database->fetchOne($createdCategorySql, [$categoryId]);
    
    // Format response
    $formattedCategory = [
        'id' => (int)$createdCategory['id'],
        'name' => $createdCategory['name'],
        'description' => $createdCategory['description'],
        'created_at' => $createdCategory['created_at'],
        'updated_at' => $createdCategory['updated_at']
    ];
    
    Response::success('Category created successfully', $formattedCategory, 201);
    
} catch (Exception $e) {
    error_log("Category create error: " . $e->getMessage());
    Response::serverError('An error occurred while creating the category');
}
?>
