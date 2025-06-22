<?php
/**
 * Admin Recent Orders API
 * 
 * Admin Recent Orders API (Based on order_items only, no product_id)
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
    $query = "
        SELECT 
            o.*,
            u.first_name,
            u.last_name,
            CONCAT(u.first_name, ' ', u.last_name) AS user_name,
            oi.name AS product_name,
            oi.price AS product_price
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN (
            SELECT oi1.*
            FROM order_items oi1
            INNER JOIN (
                SELECT order_id, MIN(id) AS min_id
                FROM order_items
                GROUP BY order_id
            ) oi2 ON oi1.id = oi2.min_id
        ) oi ON oi.order_id = o.id
        ORDER BY o.created_at DESC
        LIMIT 50
    ";

    $result = db_query($query);
    $orders = db_fetch_all($result);

    echo json_encode([
        'success' => true,
        'orders' => $orders
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
