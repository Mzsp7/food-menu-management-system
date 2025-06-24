<?php
/**
 * Setup Script for Food Menu Management System
 * Run this script to initialize the database and create the admin user
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Check if setup has already been run
if (file_exists('.setup_complete')) {
    die("Setup has already been completed. Delete .setup_complete file to run setup again.\n");
}

echo "Food Menu Management System - Setup\n";
echo "===================================\n\n";

try {
    global $database;
    
    // Test database connection
    echo "Testing database connection...\n";
    $connection = $database->getConnection();
    echo "✓ Database connection successful\n\n";
    
    // Check if database exists and has tables
    echo "Checking database structure...\n";
    
    $tables = ['users', 'categories', 'menu_items', 'user_sessions', 'activity_log'];
    $missingTables = [];
    
    foreach ($tables as $table) {
        $sql = "SHOW TABLES LIKE ?";
        $result = $database->fetchOne($sql, [$table]);
        
        if (!$result) {
            $missingTables[] = $table;
        } else {
            echo "✓ Table '{$table}' exists\n";
        }
    }
    
    if (!empty($missingTables)) {
        echo "\n⚠ Missing tables: " . implode(', ', $missingTables) . "\n";
        echo "Please run the database schema script first:\n";
        echo "mysql -u [username] -p [database_name] < database/schema.sql\n\n";
        exit(1);
    }
    
    echo "✓ All required tables exist\n\n";
    
    // Check if admin user exists
    echo "Checking for admin user...\n";
    $adminSql = "SELECT id FROM users WHERE role = 'admin' LIMIT 1";
    $adminUser = $database->fetchOne($adminSql);
    
    if ($adminUser) {
        echo "✓ Admin user already exists\n";
    } else {
        echo "Creating default admin user...\n";
        
        $username = 'admin';
        $email = 'admin@menumanager.com';
        $password = 'admin123';
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $insertSql = "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'admin')";
        $database->execute($insertSql, [$username, $email, $passwordHash]);
        
        echo "✓ Admin user created\n";
        echo "  Username: {$username}\n";
        echo "  Email: {$email}\n";
        echo "  Password: {$password}\n";
        echo "  ⚠ Please change the password after first login!\n";
    }
    
    // Check if default categories exist
    echo "\nChecking for default categories...\n";
    $categorySql = "SELECT COUNT(*) as count FROM categories WHERE is_active = 1";
    $categoryCount = $database->fetchOne($categorySql);
    
    if ($categoryCount['count'] == 0) {
        echo "Creating default categories...\n";
        
        $defaultCategories = [
            ['Appetizers', 'Starters and small plates'],
            ['Main Courses', 'Primary dishes and entrees'],
            ['Desserts', 'Sweet treats and desserts'],
            ['Beverages', 'Drinks and refreshments'],
            ['Salads', 'Fresh salads and healthy options'],
            ['Soups', 'Hot and cold soups']
        ];
        
        $insertCategorySql = "INSERT INTO categories (name, description) VALUES (?, ?)";
        
        foreach ($defaultCategories as $category) {
            $database->execute($insertCategorySql, $category);
            echo "✓ Created category: {$category[0]}\n";
        }
    } else {
        echo "✓ Categories already exist ({$categoryCount['count']} found)\n";
    }
    
    // Create necessary directories
    echo "\nCreating directories...\n";
    $directories = [
        'logs',
        'backups',
        'uploads',
        'cache'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "✓ Created directory: {$dir}\n";
        } else {
            echo "✓ Directory exists: {$dir}\n";
        }
    }
    
    // Create .htaccess for security
    echo "\nCreating security files...\n";
    
    $htaccessContent = "# Security headers\n";
    $htaccessContent .= "Header always set X-Content-Type-Options nosniff\n";
    $htaccessContent .= "Header always set X-Frame-Options DENY\n";
    $htaccessContent .= "Header always set X-XSS-Protection \"1; mode=block\"\n\n";
    $htaccessContent .= "# Prevent access to sensitive files\n";
    $htaccessContent .= "<Files \".env\">\n";
    $htaccessContent .= "    Order allow,deny\n";
    $htaccessContent .= "    Deny from all\n";
    $htaccessContent .= "</Files>\n\n";
    $htaccessContent .= "<Files \"*.log\">\n";
    $htaccessContent .= "    Order allow,deny\n";
    $htaccessContent .= "    Deny from all\n";
    $htaccessContent .= "</Files>\n";
    
    file_put_contents('.htaccess', $htaccessContent);
    echo "✓ Created .htaccess file\n";
    
    // Create .env file if it doesn't exist
    if (!file_exists('.env')) {
        copy('.env.example', '.env');
        echo "✓ Created .env file from example\n";
        echo "  ⚠ Please update .env with your actual configuration\n";
    } else {
        echo "✓ .env file already exists\n";
    }
    
    // Test API endpoints
    echo "\nTesting API endpoints...\n";
    
    // Start session for testing
    session_start();
    
    // Test basic endpoints (this is a simplified test)
    $testEndpoints = [
        'api/auth/check.php',
        'api/dashboard/stats.php',
        'api/categories/list.php',
        'api/menu/list.php'
    ];
    
    foreach ($testEndpoints as $endpoint) {
        if (file_exists($endpoint)) {
            echo "✓ Endpoint exists: {$endpoint}\n";
        } else {
            echo "✗ Missing endpoint: {$endpoint}\n";
        }
    }
    
    // Mark setup as complete
    file_put_contents('.setup_complete', date('Y-m-d H:i:s'));
    
    echo "\n🎉 Setup completed successfully!\n\n";
    echo "Next steps:\n";
    echo "1. Update your .env file with the correct database credentials\n";
    echo "2. Configure your web server to point to this directory\n";
    echo "3. Access the application in your web browser\n";
    echo "4. Login with the admin credentials shown above\n";
    echo "5. Change the default admin password\n\n";
    echo "For development, you can use PHP's built-in server:\n";
    echo "php -S localhost:8000\n\n";
    
} catch (Exception $e) {
    echo "✗ Setup failed: " . $e->getMessage() . "\n";
    echo "Please check your database configuration and try again.\n";
    exit(1);
}
?>
