<?php
/**
 * Webhook Handler
 * 
 * Processes incoming webhooks from GHL, Pillars, and RSI
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/ghl_api.php';
require_once __DIR__ . '/pillars_api.php';
require_once __DIR__ . '/rsi_api.php';

// Validate webhook request
function validate_webhook_request() {
    $headers = getallheaders();
    $signature = isset($headers['X-Webhook-Signature']) ? $headers['X-Webhook-Signature'] : '';
    $source = isset($_GET['source']) ? $_GET['source'] : '';
    
    if (empty($source)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing source parameter']);
        exit;
    }
    
    // Get the raw payload
    $payload = file_get_contents('php://input');
    
    if (empty($payload)) {
        http_response_code(400);
        echo json_encode(['error' => 'Empty payload']);
        exit;
    }
    
    // Verify signature based on source
    $is_valid = false;
    
    switch ($source) {
        case 'ghl':
            $is_valid = ghl_verify_webhook($payload, $signature);
            break;
            
        case 'pillars':
            $is_valid = pillars_verify_webhook($payload, $signature);
            break;
            
        case 'rsi':
            $is_valid = rsi_verify_webhook($payload, $signature);
            break;
            
        default:
            $is_valid = false;
    }
    
    if (!$is_valid && !DEBUG_MODE) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid webhook signature']);
        exit;
    }
    
    return [
        'source' => $source,
        'payload' => json_decode($payload, true)
    ];
}

// Process GHL webhooks
function process_ghl_webhook($payload) {
    $event_type = isset($payload['event']) ? $payload['event'] : '';
    $log_id = log_webhook('ghl', $event_type, $payload);
    
    try {
        switch ($event_type) {
            case 'contact.created':
                handle_ghl_contact_created($payload['contact'], $log_id);
                break;
                
            case 'contact.updated':
                handle_ghl_contact_updated($payload['contact'], $log_id);
                break;
                
            case 'order.created':
                handle_ghl_order_created($payload['order'], $log_id);
                break;
                
            case 'order.updated':
                handle_ghl_order_updated($payload['order'], $log_id);
                break;
                
            default:
                update_webhook_log($log_id, 'completed', 'Unsupported event type');
                return [
                    'success' => true,
                    'message' => 'Unsupported event type'
                ];
        }
        
        update_webhook_log($log_id, 'completed');
        
        return [
            'success' => true,
            'message' => 'Webhook processed successfully'
        ];
        
    } catch (Exception $e) {
        update_webhook_log($log_id, 'failed', $e->getMessage());
        
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Process Pillars webhooks
function process_pillars_webhook($payload) {
    $event_type = isset($payload['event']) ? $payload['event'] : '';
    $log_id = log_webhook('pillars', $event_type, $payload);
    
    try {
        switch ($event_type) {
            case 'user.created':
                handle_pillars_user_created($payload['user'], $log_id);
                break;
                
            case 'user.updated':
                handle_pillars_user_updated($payload['user'], $log_id);
                break;
                
            case 'order.created':
                handle_pillars_order_created($payload['order'], $log_id);
                break;
                
            case 'order.updated':
                handle_pillars_order_updated($payload['order'], $log_id);
                break;
                
            case 'commission.created':
                handle_pillars_commission_created($payload['commission'], $log_id);
                break;
                
            case 'commission.updated':
                handle_pillars_commission_updated($payload['commission'], $log_id);
                break;
                
            default:
                update_webhook_log($log_id, 'completed', 'Unsupported event type');
                return [
                    'success' => true,
                    'message' => 'Unsupported event type'
                ];
        }
        
        update_webhook_log($log_id, 'completed');
        
        return [
            'success' => true,
            'message' => 'Webhook processed successfully'
        ];
        
    } catch (Exception $e) {
        update_webhook_log($log_id, 'failed', $e->getMessage());
        
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Process RSI webhooks
function process_rsi_webhook($payload) {
    $event_type = isset($payload['event']) ? $payload['event'] : '';
    $log_id = log_webhook('rsi', $event_type, $payload);
    
    try {
        switch ($event_type) {
            case 'travel_dollars.processed':
                handle_rsi_travel_dollars_processed($payload['travel_dollars'], $log_id);
                break;
                
            case 'booking.created':
                handle_rsi_booking_created($payload['booking'], $log_id);
                break;
                
            case 'booking.updated':
                handle_rsi_booking_updated($payload['booking'], $log_id);
                break;
                
            default:
                update_webhook_log($log_id, 'completed', 'Unsupported event type');
                return [
                    'success' => true,
                    'message' => 'Unsupported event type'
                ];
        }
        
        update_webhook_log($log_id, 'completed');
        
        return [
            'success' => true,
            'message' => 'Webhook processed successfully'
        ];
        
    } catch (Exception $e) {
        update_webhook_log($log_id, 'failed', $e->getMessage());
        
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Handler for GHL contact created webhook
function handle_ghl_contact_created($contact, $log_id) {
    // Check if contact already exists in our database
    $existing_user = get_user_by_ghl_id($contact['id']);
    
    if ($existing_user) {
        log_event("GHL contact already exists: " . $contact['id'], 'warning');
        return;
    }
    
    // Check if contact exists in Pillars
    $pillars_user = pillars_get_user_by_email($contact['email']);
    
    if ($pillars_user) {
        // Link existing Pillars user to GHL contact
        $user_id = create_user([
            'ghl_id' => $contact['id'],
            'pillars_id' => $pillars_user['id'],
            'email' => $contact['email'],
            'first_name' => $contact['firstName'] ?? '',
            'last_name' => $contact['lastName'] ?? '',
            'phone' => $contact['phone'] ?? '',
            'status' => 'active',
            'sponsor_id' => $pillars_user['sponsor_id'] ?? null,
            'replicated_site' => $pillars_user['replicated_site'] ?? null,
            'tier_id' => $pillars_user['tier_id'] ?? 0
        ]);
        
        log_sync('user', $user_id, 'ghl', 'local', 'success', 'Linked existing Pillars user to GHL contact');
    } else {
        // Create new user in our database
        $user_id = create_user([
            'ghl_id' => $contact['id'],
            'email' => $contact['email'],
            'first_name' => $contact['firstName'] ?? '',
            'last_name' => $contact['lastName'] ?? '',
            'phone' => $contact['phone'] ?? '',
            'status' => 'active'
        ]);
        
        // Create user in Pillars
        $pillars_data = [
            'email' => $contact['email'],
            'first_name' => $contact['firstName'] ?? '',
            'last_name' => $contact['lastName'] ?? '',
            'phone' => $contact['phone'] ?? '',
            'external_id' => $contact['id']
        ];
        
        $pillars_user = pillars_create_user($pillars_data);
        
        if ($pillars_user) {
            // Update user with Pillars ID
            update_user($user_id, [
                'pillars_id' => $pillars_user['id']
            ]);
            
            log_sync('user', $user_id, 'ghl', 'pillars', 'success', 'Created new user in Pillars');
        } else {
            log_sync('user', $user_id, 'ghl', 'pillars', 'failed', 'Failed to create user in Pillars');
        }
    }
}

// Handler for GHL contact updated webhook
function handle_ghl_contact_updated($contact, $log_id) {
    // Check if contact exists in our database
    $existing_user = get_user_by_ghl_id($contact['id']);
    
    if (!$existing_user) {
        // Handle as if it's a new contact
        handle_ghl_contact_created($contact, $log_id);
        return;
    }
    
    // Update the user in our database
    update_user($existing_user['id'], [
        'email' => $contact['email'],
        'first_name' => $contact['firstName'] ?? '',
        'last_name' => $contact['lastName'] ?? '',
        'phone' => $contact['phone'] ?? ''
    ]);
    
    // Update user in Pillars if linked
    if ($existing_user['pillars_id']) {
        $pillars_data = [
            'email' => $contact['email'],
            'first_name' => $contact['firstName'] ?? '',
            'last_name' => $contact['lastName'] ?? '',
            'phone' => $contact['phone'] ?? ''
        ];
        
        $pillars_result = pillars_update_user($existing_user['pillars_id'], $pillars_data);
        
        if ($pillars_result) {
            log_sync('user', $existing_user['id'], 'ghl', 'pillars', 'success', 'Updated user in Pillars');
        } else {
            log_sync('user', $existing_user['id'], 'ghl', 'pillars', 'failed', 'Failed to update user in Pillars');
        }
    }
}

// Handler for GHL order created webhook
function handle_ghl_order_created($order, $log_id) {
    // Check if order already exists in our database
    $existing_order = get_order_by_ghl_id($order['id']);
    
    if ($existing_order) {
        log_event("GHL order already exists: " . $order['id'], 'warning');
        return;
    }
    
    // Get the user
    $user = get_user_by_ghl_id($order['contactId']);
    
    if (!$user) {
        log_event("Cannot find user for GHL order: " . $order['id'], 'error');
        throw new Exception("User not found for order");
    }
    
    // Extract product ID and amount
    $product_id = $order['productId'] ?? null;
    $amount = $order['amount'] ?? 0;
    
    if (!$product_id) {
        log_event("Product ID is missing for GHL order: " . $order['id'], 'error');
        throw new Exception("Product ID missing");
    }
    
    // Get our internal product ID
    $product = db_query(
        "SELECT id FROM products WHERE ghl_id = ? LIMIT 1", 
        [$product_id]
    );
    
    $product = db_fetch_one($product);
    
    if (!$product) {
        log_event("Cannot find product for GHL order: " . $order['id'], 'error');
        throw new Exception("Product not found");
    }
    
    // Create the order in our database
    $order_id = create_order([
        'ghl_id' => $order['id'],
        'user_id' => $user['id'],
        'product_id' => $product['id'],
        'amount' => $amount,
        'status' => 'pending',
        'order_date' => date('Y-m-d H:i:s', strtotime($order['createdAt'])),
        'sync_status' => 'pending'
    ]);
    
    // Create order in Pillars
    if ($user['pillars_id']) {
        $pillars_data = [
            'user_id' => $user['pillars_id'],
            'product_id' => $product_id,
            'amount' => $amount,
            'external_id' => $order['id'],
            'status' => 'pending',
            'order_date' => date('Y-m-d H:i:s', strtotime($order['createdAt']))
        ];
        
        $pillars_order = pillars_create_order($pillars_data);
        
        if ($pillars_order) {
            // Update our order with Pillars ID
            update_order($order_id, [
                'pillars_id' => $pillars_order['id'],
                'sync_status' => 'synced'
            ]);
            
            log_sync('order', $order_id, 'ghl', 'pillars', 'success', 'Created order in Pillars');
        } else {
            update_order($order_id, [
                'sync_status' => 'failed',
                'error_message' => 'Failed to create order in Pillars'
            ]);
            
            log_sync('order', $order_id, 'ghl', 'pillars', 'failed', 'Failed to create order in Pillars');
        }
    }
    
    // Update user tier based on product purchased
    $product_details = db_query(
        "SELECT tier_level FROM products WHERE id = ? LIMIT 1", 
        [$product['id']]
    );
    
    $product_details = db_fetch_one($product_details);
    
    if ($product_details && $product_details['tier_level'] > $user['tier_id']) {
        update_user($user['id'], [
            'tier_id' => $product_details['tier_level']
        ]);
        
        // Update tier in Pillars if linked
        if ($user['pillars_id']) {
            pillars_update_user($user['pillars_id'], [
                'tier_id' => $product_details['tier_level']
            ]);
        }
    }
}

// Handler for GHL order updated webhook
function handle_ghl_order_updated($order, $log_id) {
    // Check if order exists in our database
    $existing_order = get_order_by_ghl_id($order['id']);
    
    if (!$existing_order) {
        // Handle as if it's a new order
        handle_ghl_order_created($order, $log_id);
        return;
    }
    
    // Map GHL order status to our status
    $status_mapping = [
        'COMPLETED' => 'completed',
        'PAID' => 'completed',
        'PENDING' => 'pending',
        'PROCESSING' => 'processing',
        'FAILED' => 'failed',
        'REFUNDED' => 'refunded',
        'CANCELLED' => 'failed'
    ];
    
    $order_status = $status_mapping[$order['status']] ?? 'pending';
    
    // Update the order in our database
    update_order($existing_order['id'], [
        'status' => $order_status,
        'amount' => $order['amount'] ?? $existing_order['amount']
    ]);
    
    // Update order in Pillars if linked
    if ($existing_order['pillars_id']) {
        $pillars_data = [
            'status' => $order_status,
            'amount' => $order['amount'] ?? $existing_order['amount']
        ];
        
        $pillars_result = pillars_update_order($existing_order['pillars_id'], $pillars_data);
        
        if ($pillars_result) {
            log_sync('order', $existing_order['id'], 'ghl', 'pillars', 'success', 'Updated order in Pillars');
        } else {
            log_sync('order', $existing_order['id'], 'ghl', 'pillars', 'failed', 'Failed to update order in Pillars');
        }
    }
    
    // If order is completed, process commissions
    if ($order_status === 'completed' && $existing_order['status'] !== 'completed') {
        // Get the user
        $user_result = db_query(
            "SELECT u.*, p.tier_level, p.price 
            FROM users u 
            JOIN orders o ON u.id = o.user_id 
            JOIN products p ON o.product_id = p.id
            WHERE o.id = ? LIMIT 1", 
            [$existing_order['id']]
        );
        
        $user = db_fetch_one($user_result);
        
        if ($user && $user['pillars_id']) {
            // Trigger commission processing in Pillars
            $pillars_data = [
                'order_id' => $existing_order['pillars_id'],
                'user_id' => $user['pillars_id'],
                'tier_level' => $user['tier_level'],
                'amount' => $user['price']
            ];
            
            $commission_result = pillars_process_commission($pillars_data);
            
            if ($commission_result) {
                log_event("Triggered commission processing for order: " . $existing_order['id'], 'info');
            } else {
                log_event("Failed to trigger commission processing for order: " . $existing_order['id'], 'error');
            }
        }
    }
}

// Handler for Pillars user created webhook
function handle_pillars_user_created($user, $log_id) {
    // Check if user already exists in our database by Pillars ID
    $existing_user = db_query(
        "SELECT * FROM users WHERE pillars_id = ? LIMIT 1", 
        [$user['id']]
    );
    
    $existing_user = db_fetch_one($existing_user);
    
    if ($existing_user) {
        log_event("Pillars user already exists: " . $user['id'], 'warning');
        return;
    }
    
    // Check if user exists by email
    $existing_user = get_user_by_email($user['email']);
    
    if ($existing_user) {
        // Link existing user to Pillars user
        update_user($existing_user['id'], [
            'pillars_id' => $user['id'],
            'sponsor_id' => $user['sponsor_id'] ?? null,
            'replicated_site' => $user['replicated_site'] ?? null,
            'tier_id' => $user['tier_id'] ?? $existing_user['tier_id']
        ]);
        
        log_sync('user', $existing_user['id'], 'pillars', 'local', 'success', 'Linked existing user to Pillars user');
        
        // If user has GHL ID, update Pillars with external_id
        if ($existing_user['ghl_id']) {
            pillars_update_user($user['id'], [
                'external_id' => $existing_user['ghl_id']
            ]);
        }
    } else {
        // Create new user in our database
        $user_id = create_user([
            'pillars_id' => $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'] ?? '',
            'last_name' => $user['last_name'] ?? '',
            'phone' => $user['phone'] ?? '',
            'status' => 'active',
            'sponsor_id' => $user['sponsor_id'] ?? null,
            'replicated_site' => $user['replicated_site'] ?? null,
            'tier_id' => $user['tier_id'] ?? 0
        ]);
        
        // Create contact in GHL
        $ghl_data = [
            'email' => $user['email'],
            'firstName' => $user['first_name'] ?? '',
            'lastName' => $user['last_name'] ?? '',
            'phone' => $user['phone'] ?? ''
        ];
        
        $ghl_contact = ghl_create_contact($ghl_data);
        
        if ($ghl_contact) {
            // Update user with GHL ID
            update_user($user_id, [
                'ghl_id' => $ghl_contact['id']
            ]);
            
            // Update Pillars with GHL ID
            pillars_update_user($user['id'], [
                'external_id' => $ghl_contact['id']
            ]);
            
            log_sync('user', $user_id, 'pillars', 'ghl', 'success', 'Created new contact in GHL');
        } else {
            log_sync('user', $user_id, 'pillars', 'ghl', 'failed', 'Failed to create contact in GHL');
        }
    }
}

// Handler for Pillars user updated webhook
function handle_pillars_user_updated($user, $log_id) {
    // Check if user exists in our database
    $existing_user = db_query(
        "SELECT * FROM users WHERE pillars_id = ? LIMIT 1", 
        [$user['id']]
    );
    
    $existing_user = db_fetch_one($existing_user);
    
    if (!$existing_user) {
        // Handle as if it's a new user
        handle_pillars_user_created($user, $log_id);
        return;
    }
    
    // Update the user in our database
    update_user($existing_user['id'], [
        'email' => $user['email'],
        'first_name' => $user['first_name'] ?? '',
        'last_name' => $user['last_name'] ?? '',
        'phone' => $user['phone'] ?? '',
        'sponsor_id' => $user['sponsor_id'] ?? $existing_user['sponsor_id'],
        'replicated_site' => $user['replicated_site'] ?? $existing_user['replicated_site'],
        'tier_id' => $user['tier_id'] ?? $existing_user['tier_id']
    ]);
    
    // Update contact in GHL if linked
    if ($existing_user['ghl_id']) {
        $ghl_data = [
            'email' => $user['email'],
            'firstName' => $user['first_name'] ?? '',
            'lastName' => $user['last_name'] ?? '',
            'phone' => $user['phone'] ?? ''
        ];
        
        $ghl_result = ghl_update_contact($existing_user['ghl_id'], $ghl_data);
        
        if ($ghl_result) {
            log_sync('user', $existing_user['id'], 'pillars', 'ghl', 'success', 'Updated contact in GHL');
        } else {
            log_sync('user', $existing_user['id'], 'pillars', 'ghl', 'failed', 'Failed to update contact in GHL');
        }
    }
}

// Handler for Pillars order created webhook
function handle_pillars_order_created($order, $log_id) {
    // Check if we already have this order by Pillars ID
    $existing_order = db_query(
        "SELECT * FROM orders WHERE pillars_id = ? LIMIT 1", 
        [$order['id']]
    );
    
    $existing_order = db_fetch_one($existing_order);
    
    if ($existing_order) {
        log_event("Pillars order already exists: " . $order['id'], 'warning');
        return;
    }
    
    // Check if we have this order by external_id (GHL ID)
    if (!empty($order['external_id'])) {
        $existing_order = get_order_by_ghl_id($order['external_id']);
        
        if ($existing_order) {
            // Link existing order to Pillars order
            update_order($existing_order['id'], [
                'pillars_id' => $order['id'],
                'sync_status' => 'synced'
            ]);
            
            log_sync('order', $existing_order['id'], 'pillars', 'local', 'success', 'Linked existing order to Pillars order');
            return;
        }
    }
    
    // Get the user by Pillars ID
    $user = db_query(
        "SELECT * FROM users WHERE pillars_id = ? LIMIT 1", 
        [$order['user_id']]
    );
    
    $user = db_fetch_one($user);
    
    if (!$user) {
        log_event("Cannot find user for Pillars order: " . $order['id'], 'error');
        throw new Exception("User not found for order");
    }
    
    // Get the product
    $product = db_query(
        "SELECT * FROM products WHERE ghl_id = ? LIMIT 1", 
        [$order['product_id']]
    );
    
    $product = db_fetch_one($product);
    
    if (!$product) {
        log_event("Cannot find product for Pillars order: " . $order['id'], 'error');
        throw new Exception("Product not found");
    }
    
    // Create the order in our database
    $order_id = create_order([
        'pillars_id' => $order['id'],
        'ghl_id' => $order['external_id'] ?? null,
        'user_id' => $user['id'],
        'product_id' => $product['id'],
        'amount' => $order['amount'],
        'status' => $order['status'],
        'order_date' => $order['order_date'],
        'sync_status' => 'synced'
    ]);
    
    // Create order in GHL if user has GHL ID and order doesn't have external_id
    if ($user['ghl_id'] && empty($order['external_id'])) {
        $ghl_data = [
            'contactId' => $user['ghl_id'],
            'productId' => $order['product_id'],
            'amount' => $order['amount'],
            'status' => strtoupper($order['status'])
        ];
        
        $ghl_order = ghl_create_order($ghl_data);
        
        if ($ghl_order) {
            // Update our order with GHL ID
            update_order($order_id, [
                'ghl_id' => $ghl_order['id']
            ]);
            
            // Update Pillars order with external_id
            pillars_update_order($order['id'], [
                'external_id' => $ghl_order['id']
            ]);
            
            log_sync('order', $order_id, 'pillars', 'ghl', 'success', 'Created order in GHL');
        } else {
            log_sync('order', $order_id, 'pillars', 'ghl', 'failed', 'Failed to create order in GHL');
        }
    }
    
    // Update user tier based on product purchased
    if ($product['tier_level'] > $user['tier_id'] && $order['status'] === 'completed') {
        update_user($user['id'], [
            'tier_id' => $product['tier_level']
        ]);
        
        // Update tier in GHL if linked
        if ($user['ghl_id']) {
            // Add tier tag to contact
            ghl_add_tag_to_contact($user['ghl_id'], 'Tier ' . $product['tier_level']);
        }
    }
}

// Handler for Pillars order updated webhook
function handle_pillars_order_updated($order, $log_id) {
    // Check if order exists in our database
    $existing_order = db_query(
        "SELECT * FROM orders WHERE pillars_id = ? LIMIT 1", 
        [$order['id']]
    );
    
    $existing_order = db_fetch_one($existing_order);
    
    if (!$existing_order) {
        // Handle as if it's a new order
        handle_pillars_order_created($order, $log_id);
        return;
    }
    
    // Update the order in our database
    update_order($existing_order['id'], [
        'status' => $order['status'],
        'amount' => $order['amount']
    ]);
    
    // Update order in GHL if linked
    if ($existing_order['ghl_id']) {
        $ghl_data = [
            'status' => strtoupper($order['status']),
            'amount' => $order['amount']
        ];
        
        $ghl_result = ghl_update_order($existing_order['ghl_id'], $ghl_data);
        
        if ($ghl_result) {
            log_sync('order', $existing_order['id'], 'pillars', 'ghl', 'success', 'Updated order in GHL');
        } else {
            log_sync('order', $existing_order['id'], 'pillars', 'ghl', 'failed', 'Failed to update order in GHL');
        }
    }
    
    // If order status changed to completed, update user tier if needed
    if ($order['status'] === 'completed' && $existing_order['status'] !== 'completed') {
        // Get the product and user
        $result = db_query(
            "SELECT u.id, u.tier_id, u.ghl_id, p.tier_level 
            FROM users u 
            JOIN orders o ON u.id = o.user_id 
            JOIN products p ON o.product_id = p.id
            WHERE o.id = ? LIMIT 1", 
            [$existing_order['id']]
        );
        
        $data = db_fetch_one($result);
        
        if ($data && $data['tier_level'] > $data['tier_id']) {
            update_user($data['id'], [
                'tier_id' => $data['tier_level']
            ]);
            
            // Update tier tag in GHL if linked
            if ($data['ghl_id']) {
                ghl_add_tag_to_contact($data['ghl_id'], 'Tier ' . $data['tier_level']);
            }
        }
    }
}

// Handler for Pillars commission created webhook
function handle_pillars_commission_created($commission, $log_id) {
    // Check if we have the user in our database
    $user = db_query(
        "SELECT * FROM users WHERE pillars_id = ? LIMIT 1", 
        [$commission['user_id']]
    );
    
    $user = db_fetch_one($user);
    
    if (!$user) {
        log_event("Cannot find user for Pillars commission: " . $commission['id'], 'error');
        throw new Exception("User not found for commission");
    }
    
    // Check if we have the order in our database
    $order = db_query(
        "SELECT * FROM orders WHERE pillars_id = ? LIMIT 1", 
        [$commission['order_id']]
    );
    
    $order = db_fetch_one($order);
    
    if (!$order) {
        log_event("Cannot find order for Pillars commission: " . $commission['id'], 'error');
        throw new Exception("Order not found for commission");
    }
    
    // Create the commission record in our database
    db_insert('commissions', [
        'user_id' => $user['id'],
        'order_id' => $order['id'],
        'amount' => $commission['amount'],
        'commission_type' => $commission['type'],
        'status' => $commission['status']
    ]);
    
    // If user has GHL ID, add a tag for the commission
    if ($user['ghl_id']) {
        $amount_formatted = format_currency($commission['amount']);
        ghl_add_tag_to_contact($user['ghl_id'], "Commission: $amount_formatted");
    }
    
    // If this is travel commission, process with RSI
    if ($commission['is_travel'] ?? false) {
        rsi_process_travel_dollars([
            'user_id' => $user['pillars_id'],
            'amount' => $commission['amount'],
            'source' => 'commission',
            'reference_id' => $commission['id']
        ]);
    }
}

// Handler for Pillars commission updated webhook
function handle_pillars_commission_updated($commission, $log_id) {
    // Check if we have the user in our database
    $user = db_query(
        "SELECT * FROM users WHERE pillars_id = ? LIMIT 1", 
        [$commission['user_id']]
    );
    
    $user = db_fetch_one($user);
    
    if (!$user) {
        log_event("Cannot find user for Pillars commission: " . $commission['id'], 'error');
        throw new Exception("User not found for commission");
    }
    
    // Check if we have the order in our database
    $order = db_query(
        "SELECT * FROM orders WHERE pillars_id = ? LIMIT 1", 
        [$commission['order_id']]
    );
    
    $order = db_fetch_one($order);
    
    if (!$order) {
        log_event("Cannot find order for Pillars commission: " . $commission['id'], 'error');
        throw new Exception("Order not found for commission");
    }
    
    // Update the commission record in our database
    db_query(
        "UPDATE commissions SET amount = ?, status = ? WHERE user_id = ? AND order_id = ?",
        [$commission['amount'], $commission['status'], $user['id'], $order['id']]
    );
    
    // If status changed to paid and this is travel commission, process with RSI
    if ($commission['status'] === 'paid' && ($commission['is_travel'] ?? false)) {
        rsi_process_travel_dollars([
            'user_id' => $user['pillars_id'],
            'amount' => $commission['amount'],
            'source' => 'commission',
            'reference_id' => $commission['id']
        ]);
    }
}

// Handler for RSI travel dollars processed webhook
function handle_rsi_travel_dollars_processed($travel_dollars, $log_id) {
    // Get the user by Pillars ID (RSI uses Pillars ID)
    $user = db_query(
        "SELECT * FROM users WHERE pillars_id = ? LIMIT 1", 
        [$travel_dollars['user_id']]
    );
    
    $user = db_fetch_one($user);
    
    if (!$user) {
        log_event("Cannot find user for RSI travel dollars: " . $travel_dollars['id'], 'error');
        throw new Exception("User not found for travel dollars");
    }
    
    // If user has GHL ID, add tag for travel dollars
    if ($user['ghl_id']) {
        $amount_formatted = format_currency($travel_dollars['amount']);
        ghl_add_tag_to_contact($user['ghl_id'], "Travel Dollars: $amount_formatted");
    }
    
    // Log the event
    log_event("Travel dollars processed for user: {$user['email']}, amount: {$travel_dollars['amount']}", 'info');
}

// Handler for RSI booking created webhook
function handle_rsi_booking_created($booking, $log_id) {
    // Get the user by Pillars ID (RSI uses Pillars ID)
    $user = db_query(
        "SELECT * FROM users WHERE pillars_id = ? LIMIT 1", 
        [$booking['user_id']]
    );
    
    $user = db_fetch_one($user);
    
    if (!$user) {
        log_event("Cannot find user for RSI booking: " . $booking['id'], 'error');
        throw new Exception("User not found for booking");
    }
    
    // If user has GHL ID, add tag for booking
    if ($user['ghl_id']) {
        ghl_add_tag_to_contact($user['ghl_id'], "Travel Booking: {$booking['destination']}");
    }
    
    // Log the event
    log_event("Travel booking created for user: {$user['email']}, destination: {$booking['destination']}", 'info');
}

// Handler for RSI booking updated webhook
function handle_rsi_booking_updated($booking, $log_id) {
    // Get the user by Pillars ID (RSI uses Pillars ID)
    $user = db_query(
        "SELECT * FROM users WHERE pillars_id = ? LIMIT 1", 
        [$booking['user_id']]
    );
    
    $user = db_fetch_one($user);
    
    if (!$user) {
        log_event("Cannot find user for RSI booking: " . $booking['id'], 'error');
        throw new Exception("User not found for booking");
    }
    
    // If booking is confirmed and user has GHL ID, update tag
    if ($booking['status'] === 'confirmed' && $user['ghl_id']) {
        ghl_add_tag_to_contact($user['ghl_id'], "Travel Confirmed: {$booking['destination']}");
    }
    
    // Log the event
    log_event("Travel booking updated for user: {$user['email']}, status: {$booking['status']}", 'info');
}

// Process the webhook
$webhook = validate_webhook_request();

switch ($webhook['source']) {
    case 'ghl':
        $result = process_ghl_webhook($webhook['payload']);
        break;
        
    case 'pillars':
        $result = process_pillars_webhook($webhook['payload']);
        break;
        
    case 'rsi':
        $result = process_rsi_webhook($webhook['payload']);
        break;
        
    default:
        $result = [
            'success' => false,
            'message' => 'Unsupported webhook source'
        ];
}

header('Content-Type: application/json');
echo json_encode($result);
?>
