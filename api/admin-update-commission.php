<?php
/**
 * Admin Update Commission API
 * 
 * Updates an existing commission
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
    if (!isset($data['id']) || !isset($data['type']) || !isset($data['amount']) || !isset($data['status'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    // Check if commission exists
    $check_query = db_query("SELECT id FROM commissions WHERE id = ?", [$data['id']]);
    $commission = db_fetch_one($check_query);
    
    if (!$commission) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Commission not found']);
        exit;
    }
    
    // Update commission
    $query = "
        UPDATE commissions
        SET type = ?,
            amount = ?,
            status = ?,
            commission_date = ?,
            notes = ?,
            updated_at = NOW()
        WHERE id = ?
    ";
    
    db_query($query, [
        $data['type'],
        $data['amount'],
        $data['status'],
        $data['commission_date'],
        $data['notes'] ?? '',
        $data['id']
    ]);
    
    // Log update
    $log_message = "Commission #{$data['id']} was updated by admin (ID: {$user['id']}, Name: {$user['first_name']} {$user['last_name']}).";
    $log_data = [
        'commission_id' => $data['id'],
        'user_id' => $user['id'],
        'action' => 'update',
        'details' => json_encode([
            'type' => $data['type'],
            'amount' => $data['amount'],
            'status' => $data['status'],
            'commission_date' => $data['commission_date']
        ])
    ];
    
    db_query("
        INSERT INTO logs (user_id, action, reference_id, reference_type, message, data, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ", [
        $user['id'],
        'commission.update',
        $data['id'],
        'commission',
        $log_message,
        json_encode($log_data)
    ]);
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Commission updated successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}