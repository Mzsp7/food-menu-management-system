<?php
/**
 * Delete Category API Endpoint
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
    
    // Require authentication and manager role
    $auth->requireRole('manager');
    
    // Get input data
    $input = Request::all();
    
    // Validate input
    $validator = new Validator($input);
    $validator
        ->required('id', 'Category ID is required')
        ->numeric('id', 'Category ID must be a valid number');
    
    $validator->validate();
    
    $categoryId = (int)$input['id'];
    
    // Check if category exists
    $existingCategorySql = "SELECT * FROM categories WHERE id = ? AND is_active = 1";
    $existingCategory = $database->fetchOne($existingCategorySql, [$categoryId]);
    
    if (!$existingCategory) {
        Response::notFound('Category not found');
    }
    
    // Check if category has menu items
    $itemCountSql = "SELECT COUNT(*) as count FROM menu_items WHERE category_id = ? AND is_available = 1";
    $itemCount = $database->fetchOne($itemCountSql, [$categoryId]);
    
    if ($itemCount['count'] > 0) {
        Response::validationError([
            'category' => "Cannot delete category. It has {$itemCount['count']} menu items associated with it. Please reassign or delete the menu items first."
        ]);
    }
    
    // Get current user
    $currentUser = $auth->getCurrentUser();
    
    // Store category data for logging before deletion
    $categoryData = [
        'name' => $existingCategory['name'],
        'description' => $existingCategory['description']
    ];
    
    // Soft delete - set is_active to false
    $sql = "UPDATE categories SET is_active = 0, updated_at = NOW() WHERE id = ?";
    $stmt = $database->execute($sql, [$categoryId]);
    
    if ($database->rowCount($stmt) === 0) {
        Response::serverError('Failed to delete category');
    }
    
    // Log activity
    $auth->logActivity(
        $currentUser['id'],
        'DELETE',
        'categories',
        $categoryId,
        $categoryData,
        null
    );
    
    Response::success('Category deleted successfully');
    
} catch (Exception $e) {
    error_log("Category delete error: " . $e->getMessage());
    Response::serverError('An error occurred while deleting the category');
}
?>
