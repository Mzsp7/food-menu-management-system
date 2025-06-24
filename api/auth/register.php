<?php
/**
 * User Registration API Endpoint
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
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        Response::error('All fields are required');
    }
    
    if ($password !== $confirmPassword) {
        Response::error('Passwords do not match');
    }
    
    if (strlen($password) < 6) {
        Response::error('Password must be at least 6 characters long');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Response::error('Invalid email format');
    }
    
    // Initialize database and auth
    global $database;
    $auth = new Auth($database);
    
    // Attempt registration
    $result = $auth->register($username, $email, $password);
    
    if ($result['success']) {
        Response::success('Registration successful', [
            'user_id' => $result['user_id']
        ]);
    } else {
        Response::error($result['message']);
    }
    
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    Response::error('An error occurred during registration');
}
?>
