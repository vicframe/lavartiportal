<?php
/**
 * Helper functions for the application
 */

// Sanitize user input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Generate a random string
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// Log an event to the system log
function log_event($message, $level = 'info') {
    $date = date('Y-m-d H:i:s');
    $log_message = "[$date] [$level] $message" . PHP_EOL;
    
    $log_file = __DIR__ . '/../logs/system.log';
    
    // Create logs directory if it doesn't exist
    if (!is_dir(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// Log synchronization activity
function log_sync($entity_type, $entity_id, $source_system, $target_system, $status, $message) {
    return db_insert('sync_logs', [
        'entity_type' => $entity_type,
        'entity_id' => $entity_id,
        'source_system' => $source_system,
        'target_system' => $target_system,
        'status' => $status,
        'message' => $message
    ]);
}

// Log webhook activity
function log_webhook($source, $event_type, $payload) {
    return db_insert('webhook_logs', [
        'source' => $source,
        'event_type' => $event_type,
        'payload' => json_encode($payload),
        'status' => 'received'
    ]);
}

// Update webhook log status
function update_webhook_log($log_id, $status, $error_message = null) {
    $data = [
        'status' => $status,
        'processed' => ($status === 'completed'),
        'processed_at' => date('Y-m-d H:i:s')
    ];
    
    if ($error_message) {
        $data['error_message'] = $error_message;
    }
    
    return db_update('webhook_logs', $data, 'id = ?', [$log_id]);
}

// Get user by GHL ID
function get_user_by_ghl_id($ghl_id) {
    $result = db_query("SELECT * FROM users WHERE ghl_id = ? LIMIT 1", [$ghl_id]);
    return db_fetch_one($result);
}

// Get user by email
function get_user_by_email($email) {
    $result = db_query("SELECT * FROM users WHERE email = ? LIMIT 1", [$email]);
    return db_fetch_one($result);
}

// Get user by ID
function get_user_by_id($id) {
    $result = db_query("SELECT * FROM users WHERE id = ? LIMIT 1", [$id]);
    return db_fetch_one($result);
}

// Get product by tier level
function get_product_by_tier($tier_level) {
    $result = db_query("SELECT * FROM users WHERE tier_level = ? AND status = 'active' LIMIT 1", [$tier_level]);
    return db_fetch_one($result);
}

// Create a new user
function create_user($data) {
    return db_insert('users', $data);
}

// Update user data
function update_user($user_id, $data) {
    return db_update('users', $data, 'id = ?', [$user_id]);
}

// Create a new order
function create_order($data) {
    return db_insert('orders', $data);
}

// Update order data
function update_order($order_id, $data) {
    return db_update('orders', $data, 'id = ?', [$order_id]);
}

// Get order by GHL ID
function get_order_by_ghl_id($ghl_id) {
    $result = db_query("SELECT * FROM orders WHERE ghl_id = ? LIMIT 1", [$ghl_id]);
    return db_fetch_one($result);
}

// Get user's orders
function get_user_orders($user_id) {
    $result = db_query("
        SELECT o.*, p.name as product_name, p.tier_level 
        FROM orders o
        JOIN products p ON o.product_id = p.id
        WHERE o.user_id = ?
        ORDER BY o.order_date DESC
    ", [$user_id]);
    
    return db_fetch_all($result);
}

// Get user's commission records
function get_user_commissions($user_id) {
    $result = db_query("
        SELECT c.*, u.first_name, u.last_name, p.name as product_name
        FROM commissions c
        JOIN orders o ON c.order_id = o.id
        JOIN users u ON o.user_id = u.id
        JOIN products p ON o.product_id = p.id
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
    ", [$user_id]);
    
    return db_fetch_all($result);
}

// Get all available products
function get_all_products() {
    $result = db_query("SELECT * FROM products WHERE status = 'active' ORDER BY price ASC");
    return db_fetch_all($result);
}

// Generate replicated site URL
function generate_replicated_site($user_id, $first_name, $last_name) {
    $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $first_name . $last_name));
    $site = $base;
    $counter = 1;
    
    // Check if the site already exists
    while (db_record_exists('users', 'replicated_site = ?', [$site]) && $counter < 100) {
        $site = $base . $counter;
        $counter++;
    }
    
    // If we couldn't find a unique name, use user ID
    if ($counter >= 100) {
        $site = 'user' . $user_id;
    }
    
    return $site;
}

// Format currency
function format_currency($amount) {
    return '$' . number_format($amount, 2);
}

// Get user tier name
function get_tier_name($tier_id) {
    switch ($tier_id) {
        case 1:
            return 'Basic';
        case 2:
            return 'Premium';
        case 3:
            return 'Elite';
        default:
            return 'None';
    }
}

// Calculate the commission amount based on product and tier
function calculate_commission($product_id, $amount, $user_tier, $commission_type = 'direct') {
    // This is a simplified calculation - would be replaced with actual business logic
    switch ($commission_type) {
        case 'direct':
            // Direct commission is 10% for basic, 15% for premium, 20% for elite
            $percentages = [1 => 0.10, 2 => 0.15, 3 => 0.20];
            return $amount * ($percentages[$user_tier] ?? 0);
            
        case 'override':
            // Override commission is 5% for all tiers
            return $amount * 0.05;
            
        case 'bonus':
            // Bonus is flat $10 for basic, $20 for premium, $50 for elite
            $bonuses = [1 => 10, 2 => 20, 3 => 50];
            return $bonuses[$user_tier] ?? 0;
            
        default:
            return 0;
    }
}

// Send notification email
function send_notification_email($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: LaVarti Systems <noreply@lavartiportal.com>' . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Generate an affiliate tracking link
function generate_affiliate_link($user_id, $replicated_site) {
    return APP_URL . '/?ref=' . $replicated_site;
}

// Check if user has access to specific tier content
function user_has_tier_access($user_tier, $content_tier) {
    return $user_tier >= $content_tier;
}

// Get error message based on error code
function get_error_message($error_code) {
    $error_messages = [
        'invalid_credentials' => 'Invalid username or password.',
        'account_inactive' => 'Your account is inactive. Please contact support.',
        'invalid_token' => 'Your session has expired. Please log in again.',
        'payment_failed' => 'Payment processing failed. Please try again or use a different payment method.',
        'sync_failed' => 'System synchronization failed. Please try again later.',
        'api_error' => 'API connection error. Please try again later.',
        'permission_denied' => 'You do not have permission to access this resource.',
        'invalid_input' => 'Invalid input provided. Please check your data and try again.',
        'server_error' => 'Server error occurred. Please try again later.'
    ];
    
    return $error_messages[$error_code] ?? 'An unknown error occurred.';
}

// Get a formatted date
function format_date($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

// Get the user's tier based on their latest order
function get_user_current_tier($user_id) {
    $result = db_query("
        SELECT p.tier_level
        FROM orders o
        JOIN products p ON o.product_id = p.id
        WHERE o.user_id = ? AND o.status = 'completed'
        ORDER BY o.order_date DESC
        LIMIT 1
    ", [$user_id]);
    
    $row = db_fetch_one($result);
    
    return $row ? $row['tier_level'] : 0;
}
?>
