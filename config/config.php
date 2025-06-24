<?php
/**
 * Application Configuration
 */

// Load environment variables from .env file if it exists
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

// Application settings
define('APP_NAME', $_ENV['APP_NAME'] ?? 'Food Menu Management System');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost');

// Database settings
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'food_menu_system');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// Security settings
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'your-secret-key-here-change-this-in-production');
define('SESSION_LIFETIME', (int)($_ENV['SESSION_LIFETIME'] ?? 3600));
define('PASSWORD_MIN_LENGTH', (int)($_ENV['PASSWORD_MIN_LENGTH'] ?? 6));

// File upload settings
define('UPLOAD_MAX_SIZE', (int)($_ENV['UPLOAD_MAX_SIZE'] ?? 5242880)); // 5MB
define('UPLOAD_ALLOWED_TYPES', explode(',', $_ENV['UPLOAD_ALLOWED_TYPES'] ?? 'jpg,jpeg,png,gif,webp'));
define('UPLOAD_PATH', $_ENV['UPLOAD_PATH'] ?? 'uploads/');

// Email settings
define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com');
define('MAIL_PORT', (int)($_ENV['MAIL_PORT'] ?? 587));
define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? '');
define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
define('MAIL_ENCRYPTION', $_ENV['MAIL_ENCRYPTION'] ?? 'tls');
define('MAIL_FROM_ADDRESS', $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@menumanager.com');
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'Menu Manager');

// API settings
define('API_RATE_LIMIT', (int)($_ENV['API_RATE_LIMIT'] ?? 100));
define('API_RATE_LIMIT_WINDOW', (int)($_ENV['API_RATE_LIMIT_WINDOW'] ?? 3600));

// Logging settings
define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'info');
define('LOG_FILE', $_ENV['LOG_FILE'] ?? 'logs/app.log');
define('LOG_MAX_SIZE', (int)($_ENV['LOG_MAX_SIZE'] ?? 10485760)); // 10MB

// Cache settings
define('CACHE_DRIVER', $_ENV['CACHE_DRIVER'] ?? 'file');
define('CACHE_TTL', (int)($_ENV['CACHE_TTL'] ?? 3600));

// Backup settings
define('BACKUP_PATH', $_ENV['BACKUP_PATH'] ?? 'backups/');
define('BACKUP_RETENTION_DAYS', (int)($_ENV['BACKUP_RETENTION_DAYS'] ?? 30));

// Set error reporting based on environment
if (APP_ENV === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../' . LOG_FILE);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Set timezone to Indian Standard Time
date_default_timezone_set('Asia/Kolkata');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

// Create necessary directories
$directories = [
    __DIR__ . '/../logs',
    __DIR__ . '/../' . BACKUP_PATH,
    __DIR__ . '/../' . UPLOAD_PATH
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

/**
 * Application helper functions
 */

/**
 * Get configuration value
 */
function config($key, $default = null) {
    return $_ENV[$key] ?? $default;
}

/**
 * Check if application is in debug mode
 */
function isDebug() {
    return APP_DEBUG;
}

/**
 * Check if application is in production
 */
function isProduction() {
    return APP_ENV === 'production';
}

/**
 * Log message to file
 */
function logMessage($level, $message, $context = []) {
    $logLevels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];
    $currentLevel = $logLevels[LOG_LEVEL] ?? 1;
    $messageLevel = $logLevels[$level] ?? 1;
    
    if ($messageLevel < $currentLevel) {
        return;
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
    $logEntry = "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;
    
    $logFile = __DIR__ . '/../' . LOG_FILE;
    
    // Rotate log if it's too large
    if (file_exists($logFile) && filesize($logFile) > LOG_MAX_SIZE) {
        rename($logFile, $logFile . '.' . date('Y-m-d-H-i-s'));
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Generate secure random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Sanitize filename for uploads
 */
function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    return substr($filename, 0, 255);
}

/**
 * Check if file type is allowed for upload
 */
function isAllowedFileType($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, UPLOAD_ALLOWED_TYPES);
}

/**
 * Format price in Indian Rupees
 */
function formatPrice($amount) {
    return '₹' . number_format($amount, 2);
}

/**
 * Format price for display (without decimals if whole number)
 */
function formatPriceDisplay($amount) {
    if ($amount == floor($amount)) {
        return '₹' . number_format($amount, 0);
    }
    return '₹' . number_format($amount, 2);
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Validate email address
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateRandomString();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Rate limiting check
 */
function checkRateLimit($identifier, $limit = null, $window = null) {
    $limit = $limit ?? API_RATE_LIMIT;
    $window = $window ?? API_RATE_LIMIT_WINDOW;
    
    $cacheFile = __DIR__ . '/../cache/rate_limit_' . md5($identifier);
    $now = time();
    
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        
        // Clean old entries
        $data = array_filter($data, function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });
        
        if (count($data) >= $limit) {
            return false;
        }
    } else {
        $data = [];
    }
    
    $data[] = $now;
    
    // Create cache directory if it doesn't exist
    $cacheDir = dirname($cacheFile);
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    file_put_contents($cacheFile, json_encode($data));
    
    return true;
}
?>
