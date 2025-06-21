<?php
/**
 * Order Details API
 * 
 * Returns details for a specific order
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

try {
    // Get order ID
    $order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($order_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid order ID']);
        exit;
    }
    
    // Build query
    $query = "
       SELECT 
    o.id AS order_id,
    o.user_id, 
    o.product_id, 
    o.total_amount, 
    o.status, 
    o.order_date, 
    o.created_at, 
    o.updated_at,
    
    p.name AS product_name, 
    p.price AS product_price, 
    p.tier_level,
    
    -- Optional CASE fields
    CASE 
        WHEN p.tier_level = 1 THEN 'Basic'
        WHEN p.tier_level = 2 THEN 'Premium'
        WHEN p.tier_level = 3 THEN 'Elite'
        ELSE 'Unknown'
    END AS tier_name,
    
    CASE 
        WHEN o.status = 'completed' THEN 'success'
        WHEN o.status = 'pending' THEN 'warning'
        WHEN o.status = 'failed' THEN 'danger'
        ELSE 'secondary'
    END AS status_class,
    
    u.first_name, 
    u.last_name, 
    u.email, 
    u.is_admin,
    
    i.id AS item_id,
    i.name AS item_name,
    i.quantity AS item_quantity,
    i.price AS item_price
FROM orders o
LEFT JOIN order_items i ON o.id = i.order_id
LEFT JOIN products p ON o.product_id = p.id
LEFT JOIN users u ON o.user_id = u.id
WHERE o.id = ?";
    
    // Execute query
    $order_result = db_query($query, [$order_id]);
    $order = db_fetch_one($order_result);
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }
    
    // Check if user has access to this order
    if (!$user['is_admin'] && $order['user_id'] != $user['id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }
    
    // Return order details
    echo json_encode([
        'success' => true,
        'order' => $order
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}