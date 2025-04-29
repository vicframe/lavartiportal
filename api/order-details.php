<?php
/**
 * Order Details API
 * 
 * Returns detailed information about a specific order
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

// Get order ID from request
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$order_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Order ID is required']);
    exit;
}

try {
    // Get order details
    $order_query = db_query(
        "SELECT * FROM orders WHERE id = ?",
        [$order_id]
    );
    
    $order = db_fetch_one($order_query);
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }
    
    // Check if user has access to this order
    // Regular users can only see their own orders, admins can see all orders
    $is_admin = isset($user['is_admin']) && $user['is_admin'];
    
    if (!$is_admin && $order['user_id'] != $user['id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You do not have permission to view this order']);
        exit;
    }
    
    // If admin is viewing another user's order, get user information
    if ($is_admin && $order['user_id'] != $user['id']) {
        $order_user_query = db_query(
            "SELECT id, email, first_name, last_name FROM users WHERE id = ?",
            [$order['user_id']]
        );
        
        $order_user = db_fetch_one($order_user_query);
        
        if ($order_user) {
            $order['user'] = $order_user;
        }
    }
    
    // Get GHL order details if available
    if (!empty($order['ghl_order_id'])) {
        try {
            require_once __DIR__ . '/ghl_api.php';
            $ghl_api = ghl_api();
            $ghl_order = $ghl_api->get_order($order['ghl_order_id']);
            
            if ($ghl_order) {
                $order['ghl_order'] = $ghl_order;
            }
        } catch (Exception $e) {
            // Log error but continue
            error_log('Failed to get GHL order details: ' . $e->getMessage());
        }
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