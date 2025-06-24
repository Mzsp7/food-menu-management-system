<?php
/**
 * User Logout API Endpoint
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
    Response::error('Method not allowed', 405);
}

try {
    session_start();
    
    // Initialize database and auth
    global $database;
    $auth = new Auth($database);
    
    // Log logout activity if user is logged in
    if (isset($_SESSION['user_id'])) {
        $auth->logActivity($_SESSION['user_id'], 'LOGOUT', 'users', $_SESSION['user_id'], null, null, $_SERVER['REMOTE_ADDR']);
    }
    
    // Clear session
    $auth->logout();
    
    Response::success('Logout successful');
    
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    Response::error('An error occurred during logout');
}
?>
