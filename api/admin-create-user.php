<?php
/**
 * Admin Create User API
 * 
 * Creates a new user account from the admin panel
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
    if (!isset($data['email']) || !isset($data['first_name']) || !isset($data['last_name']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    // Check if email already exists
    $check_query = db_query("SELECT id FROM users WHERE email = ?", [$data['email']]);
    $existing_user = db_fetch_one($check_query);
    
    if ($existing_user) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email already in use']);
        exit;
    }
    
    // Hash password
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Create user
    $query = "
        INSERT INTO users (
            email, first_name, last_name, password, tier_id, is_admin, phone, 
            created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
        ) RETURNING id
    ";
    
    $params = [
        $data['email'],
        $data['first_name'],
        $data['last_name'],
        $hashed_password,
        $data['tier_id'] ?? 0,
        isset($data['is_admin']) && $data['is_admin'] ? 1 : 0,
        $data['phone'] ?? null
    ];
    
    $result = db_query($query, $params);
    $new_user = db_fetch_one($result);
    
    if (!$new_user) {
        throw new Exception('Failed to create user');
    }
    
    // Log user creation
    $log_message = "User #{$new_user['id']} was created by admin (ID: {$user['id']}, Name: {$user['first_name']} {$user['last_name']}).";
    $log_data = [
        'user_id' => $new_user['id'],
        'admin_id' => $user['id'],
        'action' => 'create',
        'details' => json_encode([
            'email' => $data['email'],
            'name' => $data['first_name'] . ' ' . $data['last_name'],
            'tier_id' => $data['tier_id'] ?? 0,
            'is_admin' => isset($data['is_admin']) && $data['is_admin'] ? 1 : 0
        ])
    ];
    
    db_query("
        INSERT INTO logs (user_id, action, reference_id, reference_type, message, data, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ", [
        $user['id'],
        'user.create',
        $new_user['id'],
        'user',
        $log_message,
        json_encode($log_data)
    ]);
    
    // Return success with new user ID
    echo json_encode([
        'success' => true,
        'message' => 'User created successfully',
        'user_id' => $new_user['id']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}