<?php
/**
 * Authentication Class for Food Menu Management System
 */

class Auth {
    private $db;
    
    public function __construct(Database $database) {
        $this->db = $database;
    }
    
    /**
     * User login
     */
    public function login($username, $password) {
        try {
            // Get user by username or email
            $sql = "SELECT id, username, email, password_hash, role, is_active 
                    FROM users 
                    WHERE (username = ? OR email = ?) AND is_active = 1";
            
            $user = $this->db->fetchOne($sql, [$username, $username]);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            // Create session token
            $sessionToken = $this->createSession($user['id']);
            
            return [
                'success' => true,
                'user' => $user,
                'session_token' => $sessionToken
            ];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed'];
        }
    }
    
    /**
     * User registration
     */
    public function register($username, $email, $password, $role = 'staff') {
        try {
            // Check if username already exists
            $sql = "SELECT id FROM users WHERE username = ?";
            $existingUser = $this->db->fetchOne($sql, [$username]);
            
            if ($existingUser) {
                return ['success' => false, 'message' => 'Username already exists'];
            }
            
            // Check if email already exists
            $sql = "SELECT id FROM users WHERE email = ?";
            $existingEmail = $this->db->fetchOne($sql, [$email]);
            
            if ($existingEmail) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            // Hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $sql = "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->execute($sql, [$username, $email, $passwordHash, $role]);
            
            $userId = $this->db->lastInsertId();
            
            return [
                'success' => true,
                'user_id' => $userId
            ];
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }
    
    /**
     * User logout
     */
    public function logout() {
        // Clear session token if exists
        if (isset($_SESSION['session_token'])) {
            $this->deleteSession($_SESSION['session_token']);
        }
        
        // Destroy session
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get current user
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        $sql = "SELECT id, username, email, role, created_at, last_login 
                FROM users 
                WHERE id = ? AND is_active = 1";
        
        return $this->db->fetchOne($sql, [$_SESSION['user_id']]);
    }
    
    /**
     * Check if user has required role
     */
    public function hasRole($requiredRole) {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }
        
        $roleHierarchy = ['staff' => 1, 'manager' => 2, 'admin' => 3];
        $userLevel = $roleHierarchy[$user['role']] ?? 0;
        $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;
        
        return $userLevel >= $requiredLevel;
    }
    
    /**
     * Require authentication
     */
    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            exit();
        }
    }
    
    /**
     * Require specific role
     */
    public function requireRole($role) {
        $this->requireAuth();
        
        if (!$this->hasRole($role)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
            exit();
        }
    }
    
    /**
     * Create session token
     */
    private function createSession($userId) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        $sql = "INSERT INTO user_sessions (user_id, session_token, expires_at, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)";
        
        $this->db->execute($sql, [
            $userId,
            $token,
            $expiresAt,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        $_SESSION['session_token'] = $token;
        
        return $token;
    }
    
    /**
     * Delete session
     */
    private function deleteSession($token) {
        $sql = "DELETE FROM user_sessions WHERE session_token = ?";
        $this->db->execute($sql, [$token]);
    }
    
    /**
     * Update last login timestamp
     */
    private function updateLastLogin($userId) {
        $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $this->db->execute($sql, [$userId]);
    }
    
    /**
     * Clean expired sessions
     */
    public function cleanExpiredSessions() {
        $sql = "DELETE FROM user_sessions WHERE expires_at < NOW()";
        $this->db->execute($sql);
    }
    
    /**
     * Log user activity
     */
    public function logActivity($userId, $action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null, $ipAddress = null) {
        try {
            $sql = "INSERT INTO activity_log (user_id, action, table_name, record_id, old_values, new_values, ip_address) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $this->db->execute($sql, [
                $userId,
                $action,
                $tableName,
                $recordId,
                $oldValues ? json_encode($oldValues) : null,
                $newValues ? json_encode($newValues) : null,
                $ipAddress ?? $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("Activity logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current password hash
            $sql = "SELECT password_hash FROM users WHERE id = ?";
            $user = $this->db->fetchOne($sql, [$userId]);
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Hash new password
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $sql = "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?";
            $this->db->execute($sql, [$newPasswordHash, $userId]);
            
            // Log activity
            $this->logActivity($userId, 'PASSWORD_CHANGE', 'users', $userId);
            
            return ['success' => true, 'message' => 'Password changed successfully'];
            
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Password change failed'];
        }
    }
    
    /**
     * Get user activity log
     */
    public function getUserActivity($userId, $limit = 50) {
        $sql = "SELECT action, table_name, record_id, created_at, ip_address 
                FROM activity_log 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$userId, $limit]);
    }
}
?>
