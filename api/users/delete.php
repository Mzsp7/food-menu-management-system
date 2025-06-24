<?php
/**
 * Delete User API Endpoint (Admin Only)
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
    
    // Require admin role
    $auth->requireRole('admin');
    
    // Get input data
    $input = Request::all();
    
    // Validate input
    $validator = new Validator($input);
    $validator
        ->required('id', 'User ID is required')
        ->numeric('id', 'User ID must be a valid number');
    
    $validator->validate();
    
    $userId = (int)$input['id'];
    $currentUser = $auth->getCurrentUser();
    
    // Prevent self-deletion
    if ($userId === $currentUser['id']) {
        Response::validationError(['user' => 'You cannot delete your own account']);
    }
    
    // Check if user exists
    $existingUserSql = "SELECT * FROM users WHERE id = ? AND is_active = 1";
    $existingUser = $database->fetchOne($existingUserSql, [$userId]);
    
    if (!$existingUser) {
        Response::notFound('User not found');
    }
    
    // Store user data for logging before deletion
    $userData = [
        'username' => $existingUser['username'],
        'email' => $existingUser['email'],
        'role' => $existingUser['role']
    ];
    
    // Soft delete - set is_active to false
    $sql = "UPDATE users SET is_active = 0, updated_at = NOW() WHERE id = ?";
    $stmt = $database->execute($sql, [$userId]);
    
    if ($database->rowCount($stmt) === 0) {
        Response::serverError('Failed to delete user');
    }
    
    // Also deactivate user sessions
    $sessionSql = "DELETE FROM user_sessions WHERE user_id = ?";
    $database->execute($sessionSql, [$userId]);
    
    // Log activity
    $auth->logActivity(
        $currentUser['id'],
        'DELETE',
        'users',
        $userId,
        $userData,
        null
    );
    
    Response::success('User deleted successfully');
    
} catch (Exception $e) {
    error_log("User delete error: " . $e->getMessage());
    Response::serverError('An error occurred while deleting the user');
}
?>
