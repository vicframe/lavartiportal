<?php
// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'lavarti_system');

// GHL API Configuration
define('GHL_API_KEY', getenv('GHL_API_KEY') ?: '');
define('GHL_LOCATION_ID', getenv('GHL_LOCATION_ID') ?: '');
define('GHL_API_URL', getenv('GHL_API_URL') ?: 'https://rest.gohighlevel.com/v1/');

// Pillars API Configuration
define('PILLARS_API_KEY', getenv('PILLARS_API_KEY') ?: '');
define('PILLARS_API_URL', getenv('PILLARS_API_URL') ?: 'https://api.pillars.com/v1/');

// RSI API Configuration
define('RSI_API_KEY', getenv('RSI_API_KEY') ?: '');
define('RSI_API_URL', getenv('RSI_API_URL') ?: 'https://api.rsi.com/v1/');

// Application Configuration
define('APP_NAME', 'LaVarti Systems Portal');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:5000');
define('DEBUG_MODE', getenv('DEBUG_MODE') ?: true);

// Session configuration
define('SESSION_LIFETIME', 86400); // 24 hours

// Product tiers
define('PRODUCT_TIER_BASIC', 25);
define('PRODUCT_TIER_PREMIUM', 65);
define('PRODUCT_TIER_ELITE', 500);

// Error and exception handling
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING);
}

// Define timezone
date_default_timezone_set('UTC');

// Webhook security
define('WEBHOOK_SECRET', getenv('WEBHOOK_SECRET') ?: '');

// Set error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $error = date("Y-m-d H:i:s") . " [ERROR] [$errno] $errstr in $errfile on line $errline\n";
    error_log($error, 3, __DIR__ . "/logs/error.log");
    
    if (DEBUG_MODE) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<strong>Error:</strong> $errstr in $errfile on line $errline";
        echo "</div>";
    }
    
    return true;
}
set_error_handler("customErrorHandler");

// Make sure logs directory exists
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}
?>
