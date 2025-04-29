<?php
/**
 * Admin Update Order API
 * 
 * Updates an existing order
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
    // Get request body
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid request data']);
        exit;
    }
    
    // Validate required fields
    if (!isset($data['id']) || !isset($data['product_id']) || !isset($data['amount']) || !isset($data['status'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    // Check if order exists
    $check_query = db_query("SELECT id FROM orders WHERE id = ?", [$data['id']]);
    $order = db_fetch_one($check_query);
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }
    
    // Update order
    $query = "
        UPDATE orders
        SET product_id = ?,
            amount = ?,
            status = ?,
            order_date = ?,
            notes = ?,
            updated_at = NOW()
        WHERE id = ?
    ";
    
    db_query($query, [
        $data['product_id'],
        $data['amount'],
        $data['status'],
        $data['order_date'],
        $data['notes'] ?? '',
        $data['id']
    ]);
    
    // Get product details for log
    $product_query = db_query("SELECT name FROM products WHERE id = ?", [$data['product_id']]);
    $product = db_fetch_one($product_query);
    
    // Log update
    $log_message = "Order #{$data['id']} was updated by admin (ID: {$user['id']}, Name: {$user['first_name']} {$user['last_name']}).";
    $log_data = [
        'order_id' => $data['id'],
        'user_id' => $user['id'],
        'action' => 'update',
        'details' => json_encode([
            'product' => $product['name'] ?? 'Unknown',
            'amount' => $data['amount'],
            'status' => $data['status'],
            'order_date' => $data['order_date']
        ])
    ];
    
    db_query("
        INSERT INTO logs (user_id, action, reference_id, reference_type, message, data, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ", [
        $user['id'],
        'order.update',
        $data['id'],
        'order',
        $log_message,
        json_encode($log_data)
    ]);
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Order updated successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}