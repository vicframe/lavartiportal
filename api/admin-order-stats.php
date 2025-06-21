<?php
/**
 * Admin Order Stats API
 * 
 * Returns order statistics for the admin panel
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
    // Get total orders count
    $total_query = db_query("SELECT COUNT(*) as count FROM orders");
    $total_result = db_fetch_one($total_query);
    $total_orders = $total_result['count'];
    
    // Get total revenue
    $revenue_query = db_query("SELECT SUM(total_amount) as total FROM orders");
    $revenue_result = db_fetch_one($revenue_query);
    $total_revenue = $revenue_result['total'] ?: 0;
    
    // Get completed orders count
    $completed_query = db_query("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'");
    $completed_result = db_fetch_one($completed_query);
    $completed_orders = $completed_result['count'];
    
    // Get pending orders count
    $pending_query = db_query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
    $pending_result = db_fetch_one($pending_query);
    $pending_orders = $pending_result['count'];
    
    // Return stats
    echo json_encode([
        'success' => true,
        'stats' => [
            'totalOrders' => $total_orders,
            'totalRevenue' => $total_revenue,
            'completedOrders' => $completed_orders,
            'pendingOrders' => $pending_orders
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}