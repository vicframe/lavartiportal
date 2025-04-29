<?php
/**
 * Admin Users API
 * 
 * Returns all users with pagination for the admin panel
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
    // Get pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    // Ensure valid values
    $page = max(1, $page);
    $limit = max(1, min(100, $limit));
    
    // Calculate offset
    $offset = ($page - 1) * $limit;
    
    // Get total users count
    $total_query = db_query("SELECT COUNT(*) as count FROM users");
    $total_result = db_fetch_one($total_query);
    $total_users = $total_result['count'];
    
    // Calculate total pages
    $total_pages = ceil($total_users / $limit);
    
    // Get users with pagination
    $users_query = db_query("
        SELECT id, email, first_name, last_name, tier_id, ghl_id, is_admin, phone, 
               created_at, updated_at, sponsor_id, replicated_site, pillars_id
        FROM users
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ", [$limit, $offset]);
    
    $users = db_fetch_all($users_query);
    
    // Return users with pagination info
    echo json_encode([
        'success' => true,
        'users' => $users,
        'page' => $page,
        'limit' => $limit,
        'totalUsers' => $total_users,
        'totalPages' => $total_pages
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}