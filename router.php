<?php
/**
 * Custom router for PHP's built-in web server
 * This simulates .htaccess functionality since PHP's server doesn't use .htaccess
 */

// Include core configuration
require_once __DIR__ . '/config.php';

// Use the BASE_PATH constant from config.php
$base_path = BASE_PATH;

// Get the requested URI and remove base path if present
$uri = $_SERVER['REQUEST_URI'];
if (!empty($base_path) && strpos($uri, $base_path) === 0) {
    $uri = substr($uri, strlen($base_path));
}

// Parse the URI to get the path without query string
$parsed_uri = parse_url($uri);
$path = $parsed_uri['path'] ?? '';

// If the file exists, serve it directly
if (is_file(__DIR__ . $path)) {
    // Block access to sensitive files
    if (preg_match('/^\/includes\/.*\.php$/', $path) || preg_match('/^\/?\.ht/', $path)) {
        header('HTTP/1.1 403 Forbidden');
        echo '403 Forbidden';
        exit;
    }
    
    // Determine the file's MIME type
    $extension = pathinfo($path, PATHINFO_EXTENSION);
    switch ($extension) {
        case 'css':
            header('Content-Type: text/css');
            break;
        case 'js':
            header('Content-Type: application/javascript');
            break;
        case 'json':
            header('Content-Type: application/json');
            break;
        case 'svg':
            header('Content-Type: image/svg+xml');
            break;
        case 'jpg':
        case 'jpeg':
            header('Content-Type: image/jpeg');
            break;
        case 'png':
            header('Content-Type: image/png');
            break;
        case 'gif':
            header('Content-Type: image/gif');
            break;
    }
    
    // Set environment variables
    $_SERVER['BASE_PATH'] = $base_path;
    
    readfile(__DIR__ . $path);
    return true;
}

// Handle clean URLs for common routes
$routes = [
    '/dashboard' => '/dashboard/index.php',
    '/admin' => '/admin/index.php',
    '/profile' => '/profile.php',
    '/login' => '/login.php',
    '/logout' => '/logout.php'
];

// Add trailing slash to URL if missing (except files)
if (!preg_match('/\..+$/', $path) && substr($path, -1) !== '/') {
    header('Location: ' . $path . '/');
    exit;
}

// Check if path matches any route
foreach ($routes as $route => $file) {
    if ($path === $route || $path === $route . '/') {
        // Set environment variables
        $_SERVER['BASE_PATH'] = $base_path;
        
        require __DIR__ . $file;
        return true;
    }
}

// Default to index.php if no other route matched
if ($path === '/' || $path === '') {
    require __DIR__ . '/index.php';
    return true;
}

// Return false to let the server handle the request as it would normally
return false;