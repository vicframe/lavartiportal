<?php
/**
 * Admin Recent Users API
 * 
 * Returns recent users for the admin dashboard
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

// Set content type to JSON
header('Content-Type: application/json');

// Require admin login
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$user = get_current_logged_user();

if (!isset($user['is_admin']) || !$user['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

try {
    // Get recent users
    $users_query = db_query("
        SELECT id, email, first_name, last_name, tier_level, ghl_id, created_at
        FROM users
        ORDER BY created_at DESC
        LIMIT 10
    ");
    
    $users = db_fetch_all($users_query);
    
    // Return users
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}