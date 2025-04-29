<?php
/**
 * Admin User Details API
 * 
 * Returns details for a specific user
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
    // Get user ID
    $user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($user_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
        exit;
    }
    
    // Get user details
    $user_query = db_query("
        SELECT id, email, first_name, last_name, tier_id, ghl_id, is_admin, phone, 
               created_at, updated_at, sponsor_id, replicated_site, pillars_id
        FROM users
        WHERE id = ?
    ", [$user_id]);
    
    $user_data = db_fetch_one($user_query);
    
    if (!$user_data) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
    
    // Return user details
    echo json_encode([
        'success' => true,
        'user' => $user_data
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}