<?php
/**
 * Image Upload API Endpoint
 */

require_once '../../config/config.php';
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
    
    // Check if file was uploaded
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        Response::error('No file uploaded or upload error occurred');
    }
    
    $file = $_FILES['image'];
    
    // Validate file size
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        Response::error('File size exceeds maximum allowed size of ' . formatFileSize(UPLOAD_MAX_SIZE));
    }
    
    // Validate file type
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, UPLOAD_ALLOWED_TYPES)) {
        Response::error('File type not allowed. Allowed types: ' . implode(', ', UPLOAD_ALLOWED_TYPES));
    }
    
    // Validate image
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        Response::error('File is not a valid image');
    }
    
    // Generate unique filename
    $filename = uniqid('food_', true) . '.' . $fileExtension;
    $uploadPath = UPLOAD_PATH . $filename;
    
    // Create upload directory if it doesn't exist
    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
    }
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        Response::error('Failed to save uploaded file');
    }
    
    // Get current user
    $currentUser = $auth->getCurrentUser();
    
    // Log upload activity
    $auth->logActivity(
        $currentUser['id'],
        'UPLOAD',
        'images',
        null,
        null,
        [
            'filename' => $filename,
            'original_name' => $file['name'],
            'size' => $file['size'],
            'type' => $file['type']
        ]
    );
    
    // Return success response with file URL
    $fileUrl = APP_URL . '/' . $uploadPath;
    
    Response::success('Image uploaded successfully', [
        'filename' => $filename,
        'url' => $fileUrl,
        'size' => $file['size'],
        'type' => $file['type'],
        'dimensions' => [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Image upload error: " . $e->getMessage());
    Response::serverError('An error occurred while uploading the image');
}
?>
