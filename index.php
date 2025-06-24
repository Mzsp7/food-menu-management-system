<?php
/**
 * Entry point for Food Menu Management System
 * This file handles routing and serves the main application
 */

// Start session
session_start();

// Load configuration
require_once 'config/config.php';

// Set security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Check if this is an API request
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = dirname($scriptName);

// Remove base path from request URI
if ($basePath !== '/') {
    $requestUri = substr($requestUri, strlen($basePath));
}

// Handle API requests
if (strpos($requestUri, '/api/') === 0) {
    // Remove /api/ prefix
    $apiPath = substr($requestUri, 5);
    
    // Security check - prevent directory traversal
    if (strpos($apiPath, '..') !== false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    // Build file path
    $filePath = __DIR__ . '/api/' . $apiPath;
    
    // Check if file exists
    if (file_exists($filePath) && is_file($filePath)) {
        // Include the API endpoint
        include $filePath;
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'API endpoint not found']);
    }
    exit;
}

// For all other requests, serve the main application
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <meta name="description" content="Professional food menu management system with user authentication and role-based access control">
    <meta name="keywords" content="menu management, restaurant, food, admin panel">
    <meta name="author" content="Food Menu Management System">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    
    <!-- Open Graph meta tags -->
    <meta property="og:title" content="<?php echo APP_NAME; ?>">
    <meta property="og:description" content="Professional food menu management system">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo APP_URL; ?>">
    
    <!-- Security meta tags -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' cdnjs.cloudflare.com; font-src 'self' cdnjs.cloudflare.com; img-src 'self' data: https:;">
</head>
<body>
    <div id="app">
        <!-- Navigation -->
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <h2><i class="fas fa-utensils"></i> Menu Manager</h2>
                </div>
                <div class="nav-menu" id="nav-menu">
                    <a href="#dashboard" class="nav-link" data-page="dashboard">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="#menu" class="nav-link" data-page="menu">
                        <i class="fas fa-list"></i> Menu Items
                    </a>
                    <a href="#categories" class="nav-link" data-page="categories">
                        <i class="fas fa-tags"></i> Categories
                    </a>
                    <a href="#users" class="nav-link admin-only" data-page="users">
                        <i class="fas fa-users"></i> Users
                    </a>
                    <a href="#logout" class="nav-link" id="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
                <div class="nav-toggle" id="nav-toggle">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Login Page -->
            <div id="login-page" class="page active">
                <div class="login-container">
                    <div class="login-form">
                        <h2><i class="fas fa-utensils"></i> Menu Manager</h2>
                        <form id="login-form">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" required autocomplete="username">
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" required autocomplete="current-password">
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        </form>
                        <div class="login-footer">
                            <p>Don't have an account? <a href="#" id="show-register">Register here</a></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Register Page -->
            <div id="register-page" class="page">
                <div class="login-container">
                    <div class="login-form">
                        <h2>Create Account</h2>
                        <form id="register-form">
                            <div class="form-group">
                                <label for="reg-username">Username</label>
                                <input type="text" id="reg-username" name="username" required autocomplete="username">
                            </div>
                            <div class="form-group">
                                <label for="reg-email">Email</label>
                                <input type="email" id="reg-email" name="email" required autocomplete="email">
                            </div>
                            <div class="form-group">
                                <label for="reg-password">Password</label>
                                <input type="password" id="reg-password" name="password" required autocomplete="new-password">
                            </div>
                            <div class="form-group">
                                <label for="reg-confirm-password">Confirm Password</label>
                                <input type="password" id="reg-confirm-password" name="confirm_password" required autocomplete="new-password">
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Register
                            </button>
                        </form>
                        <div class="login-footer">
                            <p>Already have an account? <a href="#" id="show-login">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Page -->
            <div id="dashboard-page" class="page">
                <div class="page-header">
                    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                </div>
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="total-items">0</h3>
                            <p>Total Menu Items</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="total-categories">0</h3>
                            <p>Categories</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="total-users">0</h3>
                            <p>Users</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Menu Items Page -->
            <div id="menu-page" class="page">
                <div class="page-header">
                    <h1><i class="fas fa-list"></i> Menu Items</h1>
                    <button class="btn btn-primary" id="add-item-btn">
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                </div>
                
                <!-- Search and Filter Controls -->
                <div class="controls">
                    <div class="search-box">
                        <input type="text" id="search-input" placeholder="Search menu items...">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="filter-controls">
                        <select id="category-filter">
                            <option value="">All Categories</option>
                        </select>
                        <select id="sort-options">
                            <option value="name">Sort by Name</option>
                            <option value="price">Sort by Price</option>
                            <option value="category">Sort by Category</option>
                        </select>
                    </div>
                </div>

                <!-- Menu Items Grid -->
                <div id="menu-items-grid" class="items-grid">
                    <!-- Menu items will be loaded here dynamically -->
                </div>
            </div>

            <!-- Categories Page -->
            <div id="categories-page" class="page">
                <div class="page-header">
                    <h1><i class="fas fa-tags"></i> Categories</h1>
                    <button class="btn btn-primary" id="add-category-btn">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </div>
                <div id="categories-list" class="categories-container">
                    <!-- Categories will be loaded here dynamically -->
                </div>
            </div>

            <!-- Users Page (Admin Only) -->
            <div id="users-page" class="page">
                <div class="page-header">
                    <h1><i class="fas fa-users"></i> Users</h1>
                    <button class="btn btn-primary" id="add-user-btn">
                        <i class="fas fa-user-plus"></i> Add User
                    </button>
                </div>
                <div id="users-list" class="users-container">
                    <!-- Users will be loaded here dynamically -->
                </div>
            </div>
        </main>

        <!-- Modal for Add/Edit Item -->
        <div id="item-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="item-modal-title">Add Menu Item</h3>
                    <span class="close" id="close-item-modal">&times;</span>
                </div>
                <form id="item-form">
                    <input type="hidden" id="item-id" name="id">
                    <div class="form-group">
                        <label for="item-name">Name</label>
                        <input type="text" id="item-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="item-description">Description</label>
                        <textarea id="item-description" name="description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="item-price">Price ($)</label>
                        <input type="number" id="item-price" name="price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="item-category">Category</label>
                        <select id="item-category" name="category_id" required>
                            <option value="">Select Category</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="item-image">Image URL</label>
                        <input type="url" id="item-image" name="image_url">
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancel-item">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Item</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal for Add/Edit Category -->
        <div id="category-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="category-modal-title">Add Category</h3>
                    <span class="close" id="close-category-modal">&times;</span>
                </div>
                <form id="category-form">
                    <input type="hidden" id="category-id" name="id">
                    <div class="form-group">
                        <label for="category-name">Category Name</label>
                        <input type="text" id="category-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="category-description">Description</label>
                        <textarea id="category-description" name="description" rows="2"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancel-category">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Category</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div id="loading" class="loading">
            <div class="spinner"></div>
        </div>

        <!-- Toast Notifications -->
        <div id="toast-container" class="toast-container"></div>
    </div>

    <script src="assets/js/app.js"></script>
    
    <?php if (APP_ENV === 'development'): ?>
    <!-- Development mode indicators -->
    <script>
        console.log('Food Menu Management System - Development Mode');
        console.log('API Base URL:', window.location.origin);
    </script>
    <?php endif; ?>
</body>
</html>
