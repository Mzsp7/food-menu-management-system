<?php
/**
 * User Login API Endpoint
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
    // Get input data
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        Response::error('Username and password are required');
    }
    
    // Initialize database and auth
    global $database;
    $auth = new Auth($database);
    
    // Attempt login
    $result = $auth->login($username, $password);
    
    if ($result['success']) {
        // Set session
        session_start();
        $_SESSION['user_id'] = $result['user']['id'];
        $_SESSION['username'] = $result['user']['username'];
        $_SESSION['role'] = $result['user']['role'];
        
        // Log successful login
        $auth->logActivity($result['user']['id'], 'LOGIN', 'users', $result['user']['id'], null, null, $_SERVER['REMOTE_ADDR']);
        
        Response::success('Login successful', [
            'user' => [
                'id' => $result['user']['id'],
                'username' => $result['user']['username'],
                'email' => $result['user']['email'],
                'role' => $result['user']['role']
            ],
            'session_token' => $result['session_token']
        ]);
    } else {
        Response::error($result['message']);
    }
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    Response::error('An error occurred during login');
}
?>
