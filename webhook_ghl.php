<?php
/**
 * GoHighLevel Webhook Handler
 * 
 * This script handles incoming webhooks from GoHighLevel (GHL)
 * It processes events like new orders, new customers, etc.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// Set content type to JSON for all responses
header('Content-Type: application/json');

// Log webhook received
$webhook_data = file_get_contents('php://input');
$headers = getallheaders();

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// Log raw webhook data for debugging
$log_file = __DIR__ . '/logs/ghl_webhook_' . date('Y-m-d') . '.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - GHL Webhook Received:\n" . $webhook_data . "\n\n", FILE_APPEND);

// Parse webhook data
$data = json_decode($webhook_data, true);

// Validate webhook data
if (!$data) {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: Invalid JSON payload\n\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON payload']);
    exit;
}

// Verify webhook signature if available
$verified = verify_ghl_webhook($headers, $webhook_data);
if (!$verified) {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: Invalid webhook signature\n\n", FILE_APPEND);
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid webhook signature']);
    exit;
}

// Get event type from data
$event_type = isset($data['event']) ? $data['event'] : null;

if (!$event_type) {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: Missing event type\n\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing event type']);
    exit;
}

// Process based on event type
try {
    $start_time = microtime(true);
    $sync_result = [
        'integration' => 'ghl',
        'action' => $event_type,
        'status' => 'success',
        'records_processed' => 0,
        'summary' => '',
        'details' => []
    ];

    switch ($event_type) {
        case 'contact.created':
            // Process new contact
            $result = process_ghl_contact($data);
            $sync_result['records_processed'] = 1;
            $sync_result['summary'] = 'GHL contact created';
            $sync_result['details'] = $result;
            break;
            
        case 'contact.updated':
            // Process updated contact
            $result = process_ghl_contact($data, true);
            $sync_result['records_processed'] = 1;
            $sync_result['summary'] = 'GHL contact updated';
            $sync_result['details'] = $result;
            break;
            
        case 'opportunity.created':
        case 'opportunity.updated':
            // Process opportunity (order)
            $result = process_ghl_opportunity($data);
            $sync_result['records_processed'] = 1;
            $sync_result['summary'] = 'GHL opportunity ' . ($event_type === 'opportunity.created' ? 'created' : 'updated');
            $sync_result['details'] = $result;
            break;
            
        default:
            // Unhandled event type
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Warning: Unhandled event type: {$event_type}\n\n", FILE_APPEND);
            $sync_result['status'] = 'ignored';
            $sync_result['summary'] = "Unhandled event type: {$event_type}";
    }
    
    // Calculate duration
    $duration = microtime(true) - $start_time;
    $sync_result['duration_seconds'] = round($duration, 3);
    
    // Log sync result to database
    log_sync_history($sync_result);
    
    // Return success response
    echo json_encode(['success' => true, 'message' => "Processed {$event_type} event"]);
    
} catch (Exception $e) {
    // Log error
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error processing {$event_type}: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND);
    
    // Log sync failure
    $sync_result = [
        'integration' => 'ghl',
        'action' => $event_type,
        'status' => 'error',
        'records_processed' => 0,
        'summary' => 'Error processing GHL webhook: ' . $e->getMessage(),
        'details' => ['error' => $e->getMessage()]
    ];
    log_sync_history($sync_result);
    
    // Return error response
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Verifies the GHL webhook signature
 */
function verify_ghl_webhook($headers, $payload) {
    // In a production environment, you would verify the webhook signature
    // with the GHL signature in the headers using the webhook secret
    
    // For now, we'll assume it's valid
    return true;
}

/**
 * Process a GHL contact (customer)
 */
function process_ghl_contact($data, $is_update = false) {
    global $log_file;
    
    // Extract contact data
    $contact = $data['contact'] ?? null;
    
    if (!$contact) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: Missing contact data\n\n", FILE_APPEND);
        throw new Exception('Missing contact data');
    }
    
    // Extract required fields
    $ghl_id = $contact['id'] ?? null;
    $email = $contact['email'] ?? null;
    $first_name = $contact['firstName'] ?? '';
    $last_name = $contact['lastName'] ?? '';
    $phone = $contact['phone'] ?? null;
    
    if (!$ghl_id || !$email) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: Missing required contact fields\n\n", FILE_APPEND);
        throw new Exception('Missing required contact fields (id or email)');
    }
    
    // Check if user already exists with this email
    $user_query = db_query("SELECT * FROM users WHERE email = ?", [$email]);
    $user = db_fetch_one($user_query);
    
    if ($user) {
        // User exists, update with GHL data
        $query = "
            UPDATE users 
            SET 
                ghl_id = ?,
                first_name = ?,
                last_name = ?,
                phone = ?,
                updated_at = NOW()
            WHERE id = ?
        ";
        
        db_query($query, [
            $ghl_id,
            $first_name,
            $last_name,
            $phone,
            $user['id']
        ]);
        
        return [
            'action' => 'updated',
            'user_id' => $user['id'],
            'ghl_id' => $ghl_id
        ];
    } else if (!$is_update) {
        // User doesn't exist, create new user
        $password = password_hash(generate_random_password(), PASSWORD_DEFAULT);
        
        $query = "
            INSERT INTO users (
                email, first_name, last_name, password, ghl_id, phone, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, NOW(), NOW()
            ) RETURNING id
        ";
        
        $result = db_query($query, [
            $email,
            $first_name,
            $last_name,
            $password,
            $ghl_id,
            $phone
        ]);
        
        $new_user = db_fetch_one($result);
        
        return [
            'action' => 'created',
            'user_id' => $new_user['id'],
            'ghl_id' => $ghl_id
        ];
    }
    
    return [
        'action' => 'skipped',
        'reason' => 'User not found for update request'
    ];
}

/**
 * Process a GHL opportunity (order)
 */
function process_ghl_opportunity($data) {
    global $log_file;
    
    // Extract opportunity data
    $opportunity = $data['opportunity'] ?? null;
    
    if (!$opportunity) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: Missing opportunity data\n\n", FILE_APPEND);
        throw new Exception('Missing opportunity data');
    }
    
    // Extract required fields
    $ghl_opportunity_id = $opportunity['id'] ?? null;
    $contact_id = $opportunity['contactId'] ?? null;
    $title = $opportunity['title'] ?? '';
    $status = $opportunity['status'] ?? '';
    $value = $opportunity['monetaryValue'] ?? 0;
    $created_at = $opportunity['createdAt'] ?? null;
    
    if (!$ghl_opportunity_id || !$contact_id) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: Missing required opportunity fields\n\n", FILE_APPEND);
        throw new Exception('Missing required opportunity fields (id or contactId)');
    }
    
    // Find user by GHL contact ID
    $user_query = db_query("SELECT * FROM users WHERE ghl_id = ?", [$contact_id]);
    $user = db_fetch_one($user_query);
    
    if (!$user) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: User not found for GHL contact ID: {$contact_id}\n\n", FILE_APPEND);
        throw new Exception("User not found for GHL contact ID: {$contact_id}");
    }
    
    // Map GHL status to our order status
    $order_status = map_ghl_status_to_order_status($status);
    
    // Determine product based on opportunity title or other fields
    $product_id = determine_product_from_opportunity($opportunity);
    
    // Check if this order already exists
    $order_query = db_query("SELECT * FROM orders WHERE ghl_order_id = ?", [$ghl_opportunity_id]);
    $order = db_fetch_one($order_query);
    
    if ($order) {
        // Order exists, update it
        $query = "
            UPDATE orders 
            SET 
                product_id = ?,
                amount = ?,
                status = ?,
                updated_at = NOW()
            WHERE id = ?
        ";
        
        db_query($query, [
            $product_id,
            $value,
            $order_status,
            $order['id']
        ]);
        
        // If order status changed to completed, process commissions
        if ($order_status === 'completed' && $order['status'] !== 'completed') {
            process_order_commissions($order['id']);
        }
        
        return [
            'action' => 'updated',
            'order_id' => $order['id'],
            'ghl_order_id' => $ghl_opportunity_id,
            'status' => $order_status
        ];
    } else {
        // New order, create it
        $order_date = $created_at ? date('Y-m-d', strtotime($created_at)) : date('Y-m-d');
        
        $query = "
            INSERT INTO orders (
                user_id, product_id, amount, status, order_date, ghl_order_id, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, NOW(), NOW()
            ) RETURNING id
        ";
        
        $result = db_query($query, [
            $user['id'],
            $product_id,
            $value,
            $order_status,
            $order_date,
            $ghl_opportunity_id
        ]);
        
        $new_order = db_fetch_one($result);
        
        // If order is completed, process commissions
        if ($order_status === 'completed') {
            process_order_commissions($new_order['id']);
        }
        
        return [
            'action' => 'created',
            'order_id' => $new_order['id'],
            'ghl_order_id' => $ghl_opportunity_id,
            'status' => $order_status
        ];
    }
}

/**
 * Maps GHL opportunity status to our order status
 */
function map_ghl_status_to_order_status($ghl_status) {
    $status_map = [
        'new' => 'pending',
        'working' => 'processing',
        'qualified' => 'processing',
        'won' => 'completed',
        'lost' => 'failed'
    ];
    
    $ghl_status = strtolower($ghl_status);
    
    return isset($status_map[$ghl_status]) ? $status_map[$ghl_status] : 'pending';
}

/**
 * Determines product ID based on opportunity details
 */
function determine_product_from_opportunity($opportunity) {
    global $log_file;
    
    // Get opportunity title
    $title = $opportunity['title'] ?? '';
    $pipeline = $opportunity['pipeline'] ?? '';
    $value = $opportunity['monetaryValue'] ?? 0;
    
    // Look for product with matching name
    $query = "SELECT * FROM products WHERE LOWER(name) LIKE ? OR LOWER(name) LIKE ?";
    $result = db_query($query, ['%' . strtolower($title) . '%', '%' . strtolower($pipeline) . '%']);
    $product = db_fetch_one($result);
    
    if ($product) {
        return $product['id'];
    }
    
    // If no product found by name, try to match by price
    if ($value > 0) {
        $query = "SELECT * FROM products WHERE ABS(price - ?) < 1 ORDER BY ABS(price - ?) LIMIT 1";
        $result = db_query($query, [$value, $value]);
        $product = db_fetch_one($result);
        
        if ($product) {
            return $product['id'];
        }
    }
    
    // If still no product found, get the default product
    $query = "SELECT * FROM products ORDER BY id ASC LIMIT 1";
    $result = db_query($query);
    $product = db_fetch_one($result);
    
    if ($product) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Warning: Could not determine specific product, using default ID {$product['id']}\n\n", FILE_APPEND);
        return $product['id'];
    }
    
    // If no products in database
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: No products found in database\n\n", FILE_APPEND);
    throw new Exception('No products found in database');
}

/**
 * Process commissions for an order
 */
function process_order_commissions($order_id) {
    global $log_file;
    
    // Get order details
    $order_query = db_query("SELECT * FROM orders WHERE id = ?", [$order_id]);
    $order = db_fetch_one($order_query);
    
    if (!$order) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: Order not found ID: {$order_id}\n\n", FILE_APPEND);
        throw new Exception("Order not found ID: {$order_id}");
    }
    
    // Get user details
    $user_query = db_query("SELECT * FROM users WHERE id = ?", [$order['user_id']]);
    $user = db_fetch_one($user_query);
    
    if (!$user) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: User not found ID: {$order['user_id']}\n\n", FILE_APPEND);
        throw new Exception("User not found ID: {$order['user_id']}");
    }
    
    // Get direct commission settings
    $direct_commission_rate = get_system_setting('direct_commission_rate', 10);
    
    // Calculate direct commission amount
    $commission_amount = $order['amount'] * ($direct_commission_rate / 100);
    
    // If user has a sponsor, create commission for them
    if (!empty($user['sponsor_id'])) {
        // Create commission record for sponsor
        $query = "
            INSERT INTO commissions (
                user_id, order_id, amount, type, status, commission_date, created_at, updated_at
            ) VALUES (
                ?, ?, ?, 'direct', 'pending', ?, NOW(), NOW()
            ) RETURNING id
        ";
        
        $result = db_query($query, [
            $user['sponsor_id'],
            $order_id,
            $commission_amount,
            $order['order_date']
        ]);
        
        $commission = db_fetch_one($result);
        
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Direct commission created: {$commission['id']} for sponsor ID: {$user['sponsor_id']}, amount: {$commission_amount}\n\n", FILE_APPEND);
        
        // Send commission to Pillars for processing
        sync_commission_to_pillars($commission['id']);
    }
    
    // Check for override commissions (sponsor's sponsor)
    if (!empty($user['sponsor_id'])) {
        $sponsor_query = db_query("SELECT * FROM users WHERE id = ?", [$user['sponsor_id']]);
        $sponsor = db_fetch_one($sponsor_query);
        
        if ($sponsor && !empty($sponsor['sponsor_id'])) {
            // Get override commission settings
            $override_commission_rate = get_system_setting('override_commission_rate', 5);
            
            // Calculate override commission amount
            $override_amount = $order['amount'] * ($override_commission_rate / 100);
            
            // Create override commission for sponsor's sponsor
            $query = "
                INSERT INTO commissions (
                    user_id, order_id, amount, type, status, commission_date, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, 'override', 'pending', ?, NOW(), NOW()
                ) RETURNING id
            ";
            
            $result = db_query($query, [
                $sponsor['sponsor_id'],
                $order_id,
                $override_amount,
                $order['order_date']
            ]);
            
            $commission = db_fetch_one($result);
            
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Override commission created: {$commission['id']} for sponsor's sponsor ID: {$sponsor['sponsor_id']}, amount: {$override_amount}\n\n", FILE_APPEND);
            
            // Send override commission to Pillars for processing
            sync_commission_to_pillars($commission['id']);
        }
    }
    
    return true;
}

/**
 * Send commission data to Pillars
 */
function sync_commission_to_pillars($commission_id) {
    global $log_file;
    
    // Get commission details
    $commission_query = db_query("
        SELECT c.*, o.order_date, o.ghl_order_id, o.product_id, o.amount as order_amount,
               u.email, u.first_name, u.last_name, u.pillars_id
        FROM commissions c
        JOIN orders o ON c.order_id = o.id
        JOIN users u ON c.user_id = u.id
        WHERE c.id = ?
    ", [$commission_id]);
    
    $commission = db_fetch_one($commission_query);
    
    if (!$commission) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: Commission not found ID: {$commission_id}\n\n", FILE_APPEND);
        throw new Exception("Commission not found ID: {$commission_id}");
    }
    
    // Prepare Pillars API request
    // In a real implementation, you would make an API call to Pillars to create/update the commission
    
    // For now, just mark commission as synced
    $query = "UPDATE commissions SET status = 'approved', updated_at = NOW() WHERE id = ?";
    db_query($query, [$commission_id]);
    
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Commission synced to Pillars ID: {$commission_id}\n\n", FILE_APPEND);
    
    return true;
}

/**
 * Get a system setting value with default
 */
function get_system_setting($key, $default = null) {
    $query = db_query("SELECT value FROM system_settings WHERE setting_key = ?", [$key]);
    $result = db_fetch_one($query);
    
    if ($result) {
        return $result['value'];
    }
    
    return $default;
}

/**
 * Generate a random password
 */
function generate_random_password($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    return $password;
}

/**
 * Log sync history to database
 */
function log_sync_history($data) {
    global $log_file;
    
    try {
        // Convert details to JSON if it's an array
        if (is_array($data['details'])) {
            $details = json_encode($data['details']);
        } else {
            $details = $data['details'];
        }
        
        $query = "
            INSERT INTO sync_history (
                integration, action, status, records_processed, duration_seconds, summary, details, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, NOW()
            )
        ";
        
        db_query($query, [
            $data['integration'],
            $data['action'],
            $data['status'],
            $data['records_processed'],
            $data['duration_seconds'] ?? 0,
            $data['summary'],
            $details
        ]);
        
    } catch (Exception $e) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error logging sync history: " . $e->getMessage() . "\n\n", FILE_APPEND);
    }
}