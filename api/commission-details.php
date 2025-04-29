<?php
/**
 * Commission Details API
 * 
 * Returns details for a specific commission
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
    // Get commission ID
    $commission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($commission_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid commission ID']);
        exit;
    }
    
    // Build query
    $query = "
        SELECT c.id, c.user_id, c.order_id, c.amount, c.status, c.type, c.commission_date, 
               c.created_at, c.updated_at, c.notes,
               u.first_name, u.last_name, u.email, u.is_admin
        FROM commissions c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.id = ?
    ";
    
    // Execute query
    $commission_result = db_query($query, [$commission_id]);
    $commission = db_fetch_one($commission_result);
    
    if (!$commission) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Commission not found']);
        exit;
    }
    
    // Check if user has access to this commission
    if (!$user['is_admin'] && $commission['user_id'] != $user['id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }
    
    // Return commission details
    echo json_encode([
        'success' => true,
        'commission' => $commission
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}