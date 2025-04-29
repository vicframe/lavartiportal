<?php
/**
 * Admin Current User API
 * 
 * Returns information about the currently logged in admin user
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

// Set content type to JSON
header('Content-Type: application/json');

// Require login
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$user = get_current_logged_user();

// Check if user is admin
if (!isset($user['is_admin']) || !$user['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

try {
    echo json_encode([
        'success' => true,
        'id' => $user['id'],
        'email' => $user['email'],
        'name' => $user['first_name'] . ' ' . $user['last_name']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}