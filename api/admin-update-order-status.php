<?php
/**
 * Admin Update Order Status API
 * 
 * Updates the status of an order
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

// Get parameters from POST
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';
$note = isset($_POST['note']) ? $_POST['note'] : '';

// Validate parameters
if (!$order_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Order ID is required']);
    exit;
}

if (!in_array($status, ['pending', 'processing', 'completed', 'failed', 'refunded'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

try {
    // Start transaction
    db_begin_transaction();
    
    // Get order
    $order_query = db_query(
        "SELECT * FROM orders WHERE id = ?",
        [$order_id]
    );
    
    $order = db_fetch_one($order_query);
    
    if (!$order) {
        db_rollback();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }
    
    // Update order status
    db_update(
        'orders',
        [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ],
        ['id' => $order_id]
    );
    
    // Log the status change
    $log_message = "Order #{$order_id} status changed from '{$order['status']}' to '{$status}'";
    if ($note) {
        $log_message .= ": {$note}";
    }
    
    db_query(
        "INSERT INTO logs (type, message, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)",
        ['order_status', $log_message]
    );
    
    // If status is completed, update user's tier level
    if ($status === 'completed' && $order['tier_level'] > 0) {
        // Only update if new tier is higher than current tier
        db_query(
            "UPDATE users SET tier_level = GREATEST(tier_level, ?), updated_at = CURRENT_TIMESTAMP 
             WHERE id = ?",
            [$order['tier_level'], $order['user_id']]
        );
    }
    
    // Create activity record
    db_insert(
        'activities',
        [
            'user_id' => $order['user_id'],
            'type' => 'order_status',
            'description' => "Order status changed to " . ucfirst($status),
            'created_at' => date('Y-m-d H:i:s')
        ]
    );
    
    // Commit transaction
    db_commit();
    
    // Try to sync with GHL if needed
    $ghl_synced = false;
    
    if (!empty($order['ghl_order_id'])) {
        try {
            require_once __DIR__ . '/ghl_api.php';
            $ghl_api = ghl_api();
            
            // Update order status in GHL
            $ghl_api->update_order_status($order['ghl_order_id'], $status);
            $ghl_synced = true;
        } catch (Exception $e) {
            // Log error but continue
            error_log('Failed to sync order status with GHL: ' . $e->getMessage());
        }
    }
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Order status updated successfully',
        'ghl_synced' => $ghl_synced
    ]);
} catch (Exception $e) {
    // Rollback transaction
    db_rollback();
    
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}