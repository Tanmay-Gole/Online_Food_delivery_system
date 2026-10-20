<?php
/**
 * Application Configuration
 * Online Food Ordering & Delivery System - Version 1.0
 * Compliant with IEEE 830 SRS Specification
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Timezone (SRS Context: Manipal University Jaipur, India)
date_default_timezone_set('Asia/Kolkata');

// Database Configuration (Default XAMPP credentials)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'food_ordering_db');

// SQLite fallback file path
define('SQLITE_PATH', dirname(__DIR__) . '/database/food_ordering.sqlite');

// Application Constants
define('APP_NAME', 'FoodieExpress');
define('APP_TAGLINE', 'Delicious food delivered fast to your doorstep');
define('APP_VERSION', '1.0');
define('APP_AUTHOR', 'Tanmay Gole (2430030098) - Manipal University Jaipur');
define('CURRENCY_SYMBOL', '₹');
define('TAX_RATE', 0.05); // 5% GST
define('DEFAULT_DELIVERY_FEE', 40.00);

// Base URL calculation
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    
    // Determine path relative to project root
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    
    // Normalize if running inside a subfolder
    $root = rtrim($scriptDir, '/');
    // If the script is in a subdirectory (like /customer or /admin), walk up
    $subdirs = ['/customer', '/restaurant', '/delivery', '/admin', '/auth', '/api', '/database'];
    foreach ($subdirs as $sub) {
        if (str_ends_with($root, $sub)) {
            $root = substr($root, 0, -strlen($sub));
            break;
        }
    }
    
    define('BASE_URL', $protocol . $host . $root);
}

// Start Session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
