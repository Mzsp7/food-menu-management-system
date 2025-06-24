<?php
/**
 * Update Category API Endpoint
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
        ->required('id', 'Category ID is required')
        ->numeric('id', 'Category ID must be a valid number')
        ->required('name', 'Category name is required')
        ->maxLength('name', 100, 'Name must not exceed 100 characters')
        ->maxLength('description', 500, 'Description must not exceed 500 characters');
    
    $validator->validate();
    
    $categoryId = (int)$input['id'];
    
    // Check if category exists
    $existingCategorySql = "SELECT * FROM categories WHERE id = ? AND is_active = 1";
    $existingCategory = $database->fetchOne($existingCategorySql, [$categoryId]);
    
    if (!$existingCategory) {
        Response::notFound('Category not found');
    }
    
    // Check if another category with the same name exists (excluding current category)
    $duplicateNameSql = "SELECT id FROM categories WHERE name = ? AND id != ? AND is_active = 1";
    $duplicateName = $database->fetchOne($duplicateNameSql, [trim($input['name']), $categoryId]);
    
    if ($duplicateName) {
        Response::validationError(['name' => 'A category with this name already exists']);
    }
    
    // Get current user
    $currentUser = $auth->getCurrentUser();
    
    // Prepare data for update
    $name = trim($input['name']);
    $description = trim($input['description'] ?? '');
    
    // Store old values for logging
    $oldValues = [
        'name' => $existingCategory['name'],
        'description' => $existingCategory['description']
    ];
    
    // Update category
    $sql = "UPDATE categories SET name = ?, description = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $database->execute($sql, [$name, $description, $categoryId]);
    
    // Log activity
    $newValues = [
        'name' => $name,
        'description' => $description
    ];
    
    $auth->logActivity(
        $currentUser['id'],
        'UPDATE',
        'categories',
        $categoryId,
        $oldValues,
        $newValues
    );
    
    // Get the updated category
    $updatedCategorySql = "SELECT id, name, description, created_at, updated_at FROM categories WHERE id = ?";
    $updatedCategory = $database->fetchOne($updatedCategorySql, [$categoryId]);
    
    // Format response
    $formattedCategory = [
        'id' => (int)$updatedCategory['id'],
        'name' => $updatedCategory['name'],
        'description' => $updatedCategory['description'],
        'created_at' => $updatedCategory['created_at'],
        'updated_at' => $updatedCategory['updated_at']
    ];
    
    Response::success('Category updated successfully', $formattedCategory);
    
} catch (Exception $e) {
    error_log("Category update error: " . $e->getMessage());
    Response::serverError('An error occurred while updating the category');
}
?>
