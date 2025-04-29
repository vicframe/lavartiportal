<?php
/**
 * Admin Update User API
 * 
 * Updates an existing user from the admin panel
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
    if (!isset($data['id']) || !isset($data['email']) || !isset($data['first_name']) || !isset($data['last_name'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    // Check if user exists
    $check_query = db_query("SELECT id FROM users WHERE id = ?", [$data['id']]);
    $existing_user = db_fetch_one($check_query);
    
    if (!$existing_user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
    
    // Check if email is already used by another user
    $email_query = db_query("SELECT id FROM users WHERE email = ? AND id != ?", [$data['email'], $data['id']]);
    $email_check = db_fetch_one($email_query);
    
    if ($email_check) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email is already in use by another user']);
        exit;
    }
    
    // Start building update query
    $update_fields = [
        "email = ?",
        "first_name = ?",
        "last_name = ?",
        "tier_id = ?",
        "is_admin = ?",
        "phone = ?",
        "updated_at = NOW()"
    ];
    
    $params = [
        $data['email'],
        $data['first_name'],
        $data['last_name'],
        $data['tier_id'] ?? 0,
        isset($data['is_admin']) && $data['is_admin'] ? 1 : 0,
        $data['phone'] ?? null
    ];
    
    // Handle password update if provided
    if (isset($data['password']) && !empty($data['password'])) {
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        $update_fields[] = "password = ?";
        $params[] = $hashed_password;
    }
    
    // Add user ID to params
    $params[] = $data['id'];
    
    // Update user
    $query = "
        UPDATE users
        SET " . implode(", ", $update_fields) . "
        WHERE id = ?
    ";
    
    db_query($query, $params);
    
    // Log user update
    $log_message = "User #{$data['id']} was updated by admin (ID: {$user['id']}, Name: {$user['first_name']} {$user['last_name']}).";
    $log_details = [
        'user_id' => $data['id'],
        'admin_id' => $user['id'],
        'action' => 'update',
        'details' => json_encode([
            'email' => $data['email'],
            'name' => $data['first_name'] . ' ' . $data['last_name'],
            'tier_id' => $data['tier_id'] ?? 0,
            'is_admin' => isset($data['is_admin']) && $data['is_admin'] ? 1 : 0,
            'password_changed' => isset($data['password']) && !empty($data['password'])
        ])
    ];
    
    db_query("
        INSERT INTO logs (user_id, action, reference_id, reference_type, message, data, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ", [
        $user['id'],
        'user.update',
        $data['id'],
        'user',
        $log_message,
        json_encode($log_details)
    ]);
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'User updated successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}