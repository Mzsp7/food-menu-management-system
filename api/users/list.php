<?php
/**
 * List Users API Endpoint (Admin Only)
 */

require_once '../../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/response.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::methodNotAllowed();
}

try {
    session_start();
    
    // Initialize database and auth
    global $database;
    $auth = new Auth($database);
    
    // Require admin role
    $auth->requireRole('admin');
    
    // Get query parameters
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
    $search = trim($_GET['search'] ?? '');
    $role = $_GET['role'] ?? '';
    
    // Build query
    $whereConditions = ['is_active = 1'];
    $params = [];
    
    // Add search condition
    if (!empty($search)) {
        $whereConditions[] = '(username LIKE ? OR email LIKE ?)';
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Add role filter
    if (!empty($role) && in_array($role, ['admin', 'manager', 'staff'])) {
        $whereConditions[] = 'role = ?';
        $params[] = $role;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM users WHERE {$whereClause}";
    $totalResult = $database->fetchOne($countSql, $params);
    $total = $totalResult['total'];
    
    // Get users with pagination
    $offset = ($page - 1) * $perPage;
    
    $sql = "
        SELECT 
            id,
            username,
            email,
            role,
            created_at,
            updated_at,
            last_login
        FROM users
        WHERE {$whereClause}
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $perPage;
    $params[] = $offset;
    
    $users = $database->fetchAll($sql, $params);
    
    // Format the response
    $formattedUsers = array_map(function($user) {
        return [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'created_at' => $user['created_at'],
            'updated_at' => $user['updated_at'],
            'last_login' => $user['last_login']
        ];
    }, $users);
    
    // Return paginated response
    Response::paginated($formattedUsers, $total, $page, $perPage, 'Users retrieved successfully');
    
} catch (Exception $e) {
    error_log("Users list error: " . $e->getMessage());
    Response::serverError('An error occurred while retrieving users');
}
?>
