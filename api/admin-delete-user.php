<?php
/**
 * Admin Delete User API
 * 
 * Deletes an existing user from the admin panel
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
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing user ID']);
        exit;
    }
    
    // Check if trying to delete self
    if ($data['id'] == $user['id']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Cannot delete your own account']);
        exit;
    }
    
    // Check if user exists
    $check_query = db_query("SELECT id, email, first_name, last_name FROM users WHERE id = ?", [$data['id']]);
    $existing_user = db_fetch_one($check_query);
    
    if (!$existing_user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
    
    // Begin transaction
    db_query("BEGIN");
    
    try {
        // Log user deletion before actually deleting the user
        $log_message = "User #{$data['id']} ({$existing_user['email']}, {$existing_user['first_name']} {$existing_user['last_name']}) was deleted by admin (ID: {$user['id']}, Name: {$user['first_name']} {$user['last_name']}).";
        
        db_query("
            INSERT INTO logs (user_id, action, reference_id, reference_type, message, data, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ", [
            $user['id'],
            'user.delete',
            $data['id'],
            'user',
            $log_message,
            json_encode(['deleted_user' => $existing_user])
        ]);
        
        // Delete related records first
        
        // Delete user's orders
        db_query("DELETE FROM orders WHERE user_id = ?", [$data['id']]);
        
        // Delete user's commissions
        db_query("DELETE FROM commissions WHERE user_id = ?", [$data['id']]);
        
        // Delete user's activity
        db_query("DELETE FROM activities WHERE user_id = ?", [$data['id']]);
        
        // Finally, delete the user
        db_query("DELETE FROM users WHERE id = ?", [$data['id']]);
        
        // Commit transaction
        db_query("COMMIT");
        
        // Return success
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    } catch (Exception $e) {
        // Rollback transaction on error
        db_query("ROLLBACK");
        throw $e;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}