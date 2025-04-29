<?php
/**
 * Admin Dashboard Stats API
 * 
 * Returns statistics for the admin dashboard
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
    // Get total users
    $users_query = db_query("SELECT COUNT(*) as count FROM users");
    $users_result = db_fetch_one($users_query);
    $total_users = $users_result['count'];
    
    // Get total orders
    $orders_query = db_query("SELECT COUNT(*) as count FROM orders");
    $orders_result = db_fetch_one($orders_query);
    $total_orders = $orders_result['count'];
    
    // Get total revenue
    $revenue_query = db_query("SELECT COALESCE(SUM(amount), 0) as total FROM orders");
    $revenue_result = db_fetch_one($revenue_query);
    $total_revenue = $revenue_result['total'];
    
    // Get total commissions
    $commissions_query = db_query("SELECT COALESCE(SUM(amount), 0) as total FROM commissions");
    $commissions_result = db_fetch_one($commissions_query);
    $total_commissions = $commissions_result['total'];
    
    // Return stats
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_users' => $total_users,
            'total_orders' => $total_orders,
            'total_revenue' => $total_revenue,
            'total_commissions' => $total_commissions
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}