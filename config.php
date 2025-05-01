<?php
/**
 * Application configuration
 */

// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent session hijacking
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} else if (time() - $_SESSION['CREATED'] > 1800) {
    // Session started more than 30 minutes ago
    session_regenerate_id(true);    // Change session ID for the current session
    $_SESSION['CREATED'] = time();  // Update creation time
}

// Set timezone
date_default_timezone_set('America/New_York');

// Define constants
define('APP_NAME', 'LaVarti Travel');
define('APP_VERSION', '1.0.0');

// Set APP_URL and BASE_PATH safely whether called from web or CLI
if (php_sapi_name() === 'cli') {
    define('APP_URL', 'http://localhost:5000');
    define('BASE_PATH', '');
} else {
    // Determine the base directory
    $script_name = dirname($_SERVER['SCRIPT_NAME']);
    $base_path = $script_name === '/' ? '' : $script_name;
    
    // If application is in a subdirectory, the base path will be something like '/lavartiportal'
    define('BASE_PATH', $base_path);
    
    // Set the full application URL
    define('APP_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
           '://' . $_SERVER['HTTP_HOST'] . BASE_PATH);
}

define('SESSION_LIFETIME', 86400); // 24 hours

// Define log directory
define('LOG_DIR', __DIR__ . '/logs');

// Create log directory if it doesn't exist
if (!file_exists(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'lavartiportal');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_PORT', getenv('DB_PORT') ?: 3306);

// API Keys (should be stored securely in environment variables)
define('GHL_API_KEY', getenv('GHL_API_KEY'));
define('PILLARS_API_KEY', getenv('PILLARS_API_KEY'));

// Include helper functions
require_once __DIR__ . '/includes/functions.php';

// Include database functions
require_once __DIR__ . '/includes/database.php';

/**
 * Get URI path
 *
 * @return string The URI path
 */
function get_uri_path() {
    $path = $_SERVER['REQUEST_URI'];
    
    // Remove query string
    $path = parse_url($path, PHP_URL_PATH);
    
    // Remove trailing slash
    $path = rtrim($path, '/');
    
    return $path;
}

/**
 * Is the current page active
 *
 * @param string $path The path to check
 * @return bool True if active, false otherwise
 */
function is_active($path) {
    $current_path = get_uri_path();
    
    return $current_path === $path;
}

/**
 * Get asset URL
 *
 * @param string $path The asset path
 * @return string The asset URL
 */
function asset_url($path) {
    return APP_URL . '/' . ltrim($path, '/');
}

/**
 * Get URL
 *
 * @param string $path The path
 * @return string The URL
 */
function url($path) {
    return APP_URL . '/' . ltrim($path, '/');
}

/**
 * Get URL relative to the application root
 * 
 * @param string $path The path
 * @return string The URL
 */
function relative_url($path) {
    return BASE_PATH . '/' . ltrim($path, '/');
}

/**
 * Get current URL with query string
 *
 * @return string The current URL
 */
function current_url() {
    // Get the request URI without the base path
    $request_uri = $_SERVER['REQUEST_URI'];
    if (BASE_PATH && strpos($request_uri, BASE_PATH) === 0) {
        $request_uri = substr($request_uri, strlen(BASE_PATH));
    }
    
    return APP_URL . $request_uri;
}

/**
 * Redirect to a URL
 *
 * @param string $path The path to redirect to
 * @return void
 */
function redirect($path) {
    $url = url($path);
    header("Location: {$url}");
    exit;
}

/**
 * Get query string parameter
 *
 * @param string $name The parameter name
 * @param mixed $default The default value
 * @return mixed The parameter value
 */
function get_query_param($name, $default = null) {
    return isset($_GET[$name]) ? $_GET[$name] : $default;
}

/**
 * Output JSON response
 *
 * @param array $data The response data
 * @param int $status_code The HTTP status code
 * @return void
 */
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Handle AJAX request
 *
 * @param callable $callback The callback function
 * @return void
 */
function ajax_request($callback) {
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        json_response(['success' => false, 'error' => 'Invalid request'], 400);
    }
    
    try {
        $result = $callback();
        
        if (is_array($result) && isset($result['success'])) {
            json_response($result);
        } else {
            json_response(['success' => true, 'data' => $result]);
        }
    } catch (Exception $e) {
        json_response(['success' => false, 'error' => $e->getMessage()], 500);
    }
}