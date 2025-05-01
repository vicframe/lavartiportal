<?php
/**
 * Configuration file for local XAMPP environment
 */

// Debug mode
define('DEBUG', true);

// Application URL
$base_path = '/lavartiportal'; // Change this to match your subdirectory in XAMPP
define('APP_URL', 'http://localhost' . $base_path);

// Include paths
define('INCLUDE_PATH', __DIR__ . '/includes');
define('ASSETS_PATH', __DIR__ . '/assets');

// Include required files
require_once INCLUDE_PATH . '/database_mysql.php'; // Use MySQL database functions instead of PostgreSQL
require_once INCLUDE_PATH . '/functions.php';
require_once INCLUDE_PATH . '/auth.php';

// Error reporting
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ERROR | E_PARSE);
    ini_set('display_errors', 0);
}

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}