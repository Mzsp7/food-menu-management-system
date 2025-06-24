<?php
/**
 * API Testing Script for Food Menu Management System
 * This script tests the basic functionality of the API endpoints
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'api/includes/auth.php';
require_once 'api/includes/response.php';

class APITester {
    private $baseUrl;
    private $sessionToken;
    private $testResults = [];
    
    public function __construct($baseUrl = 'http://localhost:8000') {
        $this->baseUrl = rtrim($baseUrl, '/');
    }
    
    public function runTests() {
        echo "Food Menu Management System - API Tests\n";
        echo "======================================\n\n";
        
        // Test database connection
        $this->testDatabaseConnection();
        
        // Test authentication endpoints
        $this->testAuthEndpoints();
        
        // Test menu endpoints
        $this->testMenuEndpoints();
        
        // Test category endpoints
        $this->testCategoryEndpoints();
        
        // Test dashboard endpoints
        $this->testDashboardEndpoints();
        
        // Display results
        $this->displayResults();
    }
    
    private function testDatabaseConnection() {
        echo "Testing Database Connection...\n";
        
        try {
            global $database;
            $connection = $database->getConnection();
            
            // Test basic query
            $result = $database->fetchOne("SELECT 1 as test");
            
            if ($result && $result['test'] == 1) {
                $this->addResult('Database Connection', true, 'Connection successful');
            } else {
                $this->addResult('Database Connection', false, 'Query test failed');
            }
        } catch (Exception $e) {
            $this->addResult('Database Connection', false, $e->getMessage());
        }
        
        echo "\n";
    }
    
    private function testAuthEndpoints() {
        echo "Testing Authentication Endpoints...\n";
        
        // Test login with default admin credentials
        $loginData = [
            'username' => 'admin',
            'password' => 'admin123'
        ];
        
        $response = $this->makeRequest('POST', '/api/auth/login.php', $loginData);
        
        if ($response && isset($response['success']) && $response['success']) {
            $this->addResult('Admin Login', true, 'Login successful');
            $this->sessionToken = $response['session_token'] ?? null;
            
            // Set session for subsequent tests
            if (isset($response['user'])) {
                session_start();
                $_SESSION['user_id'] = $response['user']['id'];
                $_SESSION['username'] = $response['user']['username'];
                $_SESSION['role'] = $response['user']['role'];
            }
        } else {
            $this->addResult('Admin Login', false, $response['message'] ?? 'Login failed');
        }
        
        // Test auth check
        $authCheck = $this->makeRequest('GET', '/api/auth/check.php');
        
        if ($authCheck && isset($authCheck['success']) && $authCheck['success']) {
            $this->addResult('Auth Check', true, 'Authentication verified');
        } else {
            $this->addResult('Auth Check', false, $authCheck['message'] ?? 'Auth check failed');
        }
        
        echo "\n";
    }
    
    private function testMenuEndpoints() {
        echo "Testing Menu Endpoints...\n";
        
        // Test menu list
        $menuList = $this->makeRequest('GET', '/api/menu/list.php');
        
        if ($menuList && isset($menuList['success']) && $menuList['success']) {
            $this->addResult('Menu List', true, 'Menu items retrieved');
        } else {
            $this->addResult('Menu List', false, $menuList['message'] ?? 'Failed to get menu items');
        }
        
        // Test menu creation
        $newItem = [
            'name' => 'Test Item',
            'description' => 'Test description',
            'price' => 9.99,
            'category_id' => 1
        ];
        
        $createResponse = $this->makeRequest('POST', '/api/menu/create.php', $newItem);
        
        if ($createResponse && isset($createResponse['success']) && $createResponse['success']) {
            $this->addResult('Menu Create', true, 'Menu item created');
            $testItemId = $createResponse['data']['id'] ?? null;
            
            // Test menu update if item was created
            if ($testItemId) {
                $updateData = [
                    'id' => $testItemId,
                    'name' => 'Updated Test Item',
                    'description' => 'Updated description',
                    'price' => 12.99,
                    'category_id' => 1
                ];
                
                $updateResponse = $this->makeRequest('POST', '/api/menu/update.php', $updateData);
                
                if ($updateResponse && isset($updateResponse['success']) && $updateResponse['success']) {
                    $this->addResult('Menu Update', true, 'Menu item updated');
                } else {
                    $this->addResult('Menu Update', false, $updateResponse['message'] ?? 'Update failed');
                }
                
                // Test menu deletion
                $deleteData = ['id' => $testItemId];
                $deleteResponse = $this->makeRequest('POST', '/api/menu/delete.php', $deleteData);
                
                if ($deleteResponse && isset($deleteResponse['success']) && $deleteResponse['success']) {
                    $this->addResult('Menu Delete', true, 'Menu item deleted');
                } else {
                    $this->addResult('Menu Delete', false, $deleteResponse['message'] ?? 'Delete failed');
                }
            }
        } else {
            $this->addResult('Menu Create', false, $createResponse['message'] ?? 'Failed to create menu item');
        }
        
        echo "\n";
    }
    
    private function testCategoryEndpoints() {
        echo "Testing Category Endpoints...\n";
        
        // Test category list
        $categoryList = $this->makeRequest('GET', '/api/categories/list.php');
        
        if ($categoryList && isset($categoryList['success']) && $categoryList['success']) {
            $this->addResult('Category List', true, 'Categories retrieved');
        } else {
            $this->addResult('Category List', false, $categoryList['message'] ?? 'Failed to get categories');
        }
        
        echo "\n";
    }
    
    private function testDashboardEndpoints() {
        echo "Testing Dashboard Endpoints...\n";
        
        // Test dashboard stats
        $dashboardStats = $this->makeRequest('GET', '/api/dashboard/stats.php');
        
        if ($dashboardStats && isset($dashboardStats['success']) && $dashboardStats['success']) {
            $this->addResult('Dashboard Stats', true, 'Statistics retrieved');
        } else {
            $this->addResult('Dashboard Stats', false, $dashboardStats['message'] ?? 'Failed to get stats');
        }
        
        echo "\n";
    }
    
    private function makeRequest($method, $endpoint, $data = null) {
        // For this simple test, we'll simulate the requests by including the files directly
        // In a real scenario, you'd use cURL or similar to make HTTP requests
        
        try {
            // Set up the request environment
            $_SERVER['REQUEST_METHOD'] = $method;
            $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
            $_SERVER['HTTP_USER_AGENT'] = 'API Tester';
            
            if ($data && $method === 'POST') {
                $_POST = $data;
            } elseif ($data && $method === 'GET') {
                $_GET = $data;
            }
            
            // Capture output
            ob_start();
            
            // Include the endpoint file
            $filePath = ltrim($endpoint, '/');
            if (file_exists($filePath)) {
                include $filePath;
            } else {
                return ['success' => false, 'message' => 'Endpoint not found'];
            }
            
            $output = ob_get_clean();
            
            // Parse JSON response
            $response = json_decode($output, true);
            
            return $response;
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    private function addResult($test, $success, $message) {
        $this->testResults[] = [
            'test' => $test,
            'success' => $success,
            'message' => $message
        ];
        
        $status = $success ? '✓' : '✗';
        echo "{$status} {$test}: {$message}\n";
    }
    
    private function displayResults() {
        echo "\nTest Results Summary\n";
        echo "===================\n";
        
        $passed = 0;
        $failed = 0;
        
        foreach ($this->testResults as $result) {
            if ($result['success']) {
                $passed++;
            } else {
                $failed++;
            }
        }
        
        $total = $passed + $failed;
        
        echo "Total Tests: {$total}\n";
        echo "Passed: {$passed}\n";
        echo "Failed: {$failed}\n";
        
        if ($failed > 0) {
            echo "\nFailed Tests:\n";
            foreach ($this->testResults as $result) {
                if (!$result['success']) {
                    echo "- {$result['test']}: {$result['message']}\n";
                }
            }
        }
        
        echo "\n";
        
        if ($failed === 0) {
            echo "🎉 All tests passed!\n";
        } else {
            echo "⚠ Some tests failed. Please check the configuration and try again.\n";
        }
    }
}

// Run the tests
if (php_sapi_name() === 'cli') {
    $tester = new APITester();
    $tester->runTests();
} else {
    echo "This script should be run from the command line.\n";
    echo "Usage: php test_api.php\n";
}
?>
