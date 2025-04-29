<?php
/**
 * Webhook Handler
 * 
 * Handles incoming webhooks from GHL and Pillars
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/database.php';

// Get the request body
$request_body = file_get_contents('php://input');
$event_data = json_decode($request_body, true);
$source = isset($_GET['source']) ? $_GET['source'] : '';

// Log incoming webhook
log_activity('Webhook received from ' . $source . ': ' . substr($request_body, 0, 500), 'info');

// Validate webhook source
if (!in_array($source, ['ghl', 'pillars'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid webhook source']);
    exit;
}

// Verify webhook signature if needed
// ...

// Process webhook based on source
switch ($source) {
    case 'ghl':
        process_ghl_webhook($event_data);
        break;
    
    case 'pillars':
        process_pillars_webhook($event_data);
        break;
}

// Return success response
http_response_code(200);
echo json_encode(['success' => true]);
exit;

/**
 * Process GHL webhook
 *
 * @param array $event_data The webhook event data
 * @return void
 */
function process_ghl_webhook($event_data) {
    if (!isset($event_data['event'])) {
        log_activity('Invalid GHL webhook: event not specified', 'error');
        return;
    }

    $event = $event_data['event'];
    
    switch ($event) {
        case 'contact.created':
        case 'contact.updated':
            process_ghl_contact_event($event_data);
            break;
        
        case 'order.created':
        case 'order.updated':
            process_ghl_order_event($event_data);
            break;
        
        default:
            log_activity('Unhandled GHL webhook event: ' . $event, 'warning');
            break;
    }
}

/**
 * Process Pillars webhook
 *
 * @param array $event_data The webhook event data
 * @return void
 */
function process_pillars_webhook($event_data) {
    if (!isset($event_data['event'])) {
        log_activity('Invalid Pillars webhook: event not specified', 'error');
        return;
    }

    $event = $event_data['event'];
    
    switch ($event) {
        case 'commission.created':
        case 'commission.updated':
            process_pillars_commission_event($event_data);
            break;
        
        case 'member.sponsorChanged':
            process_pillars_sponsor_event($event_data);
            break;
        
        default:
            log_activity('Unhandled Pillars webhook event: ' . $event, 'warning');
            break;
    }
}

/**
 * Process GHL contact event
 *
 * @param array $event_data The webhook event data
 * @return void
 */
function process_ghl_contact_event($event_data) {
    if (!isset($event_data['contact'])) {
        log_activity('Invalid GHL contact event: contact not specified', 'error');
        return;
    }

    $contact = $event_data['contact'];
    $ghl_id = isset($contact['id']) ? $contact['id'] : null;
    
    if (!$ghl_id) {
        log_activity('Invalid GHL contact: ID not specified', 'error');
        return;
    }
    
    // Check if user exists with this GHL ID
    $user_query = db_query(
        "SELECT * FROM users WHERE ghl_id = ?",
        [$ghl_id]
    );
    $user = db_fetch_one($user_query);
    
    // Extract user data
    $email = isset($contact['email']) ? $contact['email'] : null;
    $phone = isset($contact['phone']) ? $contact['phone'] : null;
    $first_name = isset($contact['firstName']) ? $contact['firstName'] : null;
    $last_name = isset($contact['lastName']) ? $contact['lastName'] : null;
    
    if ($user) {
        // Update existing user
        db_update(
            'users',
            [
                'email' => $email,
                'phone' => $phone,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            ['id' => $user['id']]
        );
        
        log_activity("Updated user from GHL: {$email}", 'info');
    } else if ($email) {
        // Check if user exists with this email
        $user_query = db_query(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );
        $user = db_fetch_one($user_query);
        
        if ($user) {
            // Update user with GHL ID
            db_update(
                'users',
                [
                    'ghl_id' => $ghl_id,
                    'phone' => $phone,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                ['id' => $user['id']]
            );
            
            log_activity("Linked existing user to GHL: {$email}", 'info');
        } else {
            // Create new user
            $password = bin2hex(random_bytes(8)); // Random password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $user_id = db_insert(
                'users',
                [
                    'email' => $email,
                    'password' => $password_hash,
                    'phone' => $phone,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'ghl_id' => $ghl_id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
            
            if ($user_id) {
                log_activity("Created user from GHL: {$email}", 'info');
                
                // TODO: Send welcome email with password reset link
            } else {
                log_activity("Failed to create user from GHL: {$email}", 'error');
            }
        }
    } else {
        log_activity('GHL contact missing email', 'warning');
    }
}

/**
 * Process GHL order event
 *
 * @param array $event_data The webhook event data
 * @return void
 */
function process_ghl_order_event($event_data) {
    if (!isset($event_data['order'])) {
        log_activity('Invalid GHL order event: order not specified', 'error');
        return;
    }

    $order = $event_data['order'];
    $order_id = isset($order['id']) ? $order['id'] : null;
    
    if (!$order_id) {
        log_activity('Invalid GHL order: ID not specified', 'error');
        return;
    }
    
    // Check if order exists
    $order_query = db_query(
        "SELECT * FROM orders WHERE ghl_order_id = ?",
        [$order_id]
    );
    $existing_order = db_fetch_one($order_query);
    
    // Extract order data
    $ghl_contact_id = isset($order['contactId']) ? $order['contactId'] : null;
    $amount = isset($order['amount']) ? $order['amount'] : 0;
    $status = isset($order['status']) ? $order['status'] : 'pending';
    $items = isset($order['items']) ? $order['items'] : [];
    $product_name = '';
    $tier_level = 0;
    
    // Determine product name and tier level from items
    if (!empty($items)) {
        $item = $items[0]; // Use first item for now
        $product_name = isset($item['name']) ? $item['name'] : '';
        
        // Set tier level based on product name or ID
        if (stripos($product_name, 'basic') !== false) {
            $tier_level = 1;
        } elseif (stripos($product_name, 'premium') !== false) {
            $tier_level = 2;
        } elseif (stripos($product_name, 'elite') !== false) {
            $tier_level = 3;
        }
    }
    
    // Find user by GHL contact ID
    $user_query = db_query(
        "SELECT * FROM users WHERE ghl_id = ?",
        [$ghl_contact_id]
    );
    $user = db_fetch_one($user_query);
    
    if (!$user) {
        log_activity("No user found for GHL contact ID: {$ghl_contact_id}", 'warning');
        return;
    }
    
    $user_id = $user['id'];
    $order_date = isset($order['date']) ? $order['date'] : date('Y-m-d H:i:s');
    
    if ($existing_order) {
        // Update existing order
        db_update(
            'orders',
            [
                'status' => $status,
                'amount' => $amount,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            ['id' => $existing_order['id']]
        );
        
        // Update user's membership tier if order is completed
        if ($status === 'completed' && $tier_level > 0) {
            db_update(
                'users',
                [
                    'tier_level' => $tier_level,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                ['id' => $user_id]
            );
        }
        
        log_activity("Updated order from GHL: {$order_id}", 'info');
    } else {
        // Create new order
        $order_id = db_insert(
            'orders',
            [
                'user_id' => $user_id,
                'ghl_order_id' => $order_id,
                'product_name' => $product_name,
                'amount' => $amount,
                'status' => $status,
                'tier_level' => $tier_level,
                'order_date' => $order_date,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );
        
        if ($order_id) {
            log_activity("Created order from GHL: {$product_name} for user ID {$user_id}", 'info');
            
            // Update user's membership tier if order is completed
            if ($status === 'completed' && $tier_level > 0) {
                db_update(
                    'users',
                    [
                        'tier_level' => $tier_level,
                        'updated_at' => date('Y-m-d H:i:s')
                    ],
                    ['id' => $user_id]
                );
            }
            
            // Create activity record
            db_insert(
                'activities',
                [
                    'user_id' => $user_id,
                    'type' => 'order',
                    'description' => "Purchased {$product_name}",
                    'amount' => $amount,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            );
        } else {
            log_activity("Failed to create order from GHL for user ID {$user_id}", 'error');
        }
    }
}

/**
 * Process Pillars commission event
 *
 * @param array $event_data The webhook event data
 * @return void
 */
function process_pillars_commission_event($event_data) {
    if (!isset($event_data['commission'])) {
        log_activity('Invalid Pillars commission event: commission not specified', 'error');
        return;
    }

    $commission = $event_data['commission'];
    $commission_id = isset($commission['id']) ? $commission['id'] : null;
    
    if (!$commission_id) {
        log_activity('Invalid Pillars commission: ID not specified', 'error');
        return;
    }
    
    // Check if commission exists
    $commission_query = db_query(
        "SELECT * FROM commissions WHERE pillars_commission_id = ?",
        [$commission_id]
    );
    $existing_commission = db_fetch_one($commission_query);
    
    // Extract commission data
    $pillars_member_id = isset($commission['memberId']) ? $commission['memberId'] : null;
    $amount = isset($commission['amount']) ? $commission['amount'] : 0;
    $status = isset($commission['status']) ? $commission['status'] : 'pending';
    $source = isset($commission['source']) ? $commission['source'] : '';
    $source_id = isset($commission['sourceId']) ? $commission['sourceId'] : '';
    
    // Find user by Pillars member ID
    $user_query = db_query(
        "SELECT * FROM users WHERE pillars_id = ?",
        [$pillars_member_id]
    );
    $user = db_fetch_one($user_query);
    
    if (!$user) {
        log_activity("No user found for Pillars member ID: {$pillars_member_id}", 'warning');
        return;
    }
    
    $user_id = $user['id'];
    
    if ($existing_commission) {
        // Update existing commission
        db_update(
            'commissions',
            [
                'status' => $status,
                'amount' => $amount,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            ['id' => $existing_commission['id']]
        );
        
        log_activity("Updated commission from Pillars: {$commission_id}", 'info');
    } else {
        // Create new commission
        $commission_id = db_insert(
            'commissions',
            [
                'user_id' => $user_id,
                'pillars_commission_id' => $commission_id,
                'amount' => $amount,
                'status' => $status,
                'source' => $source,
                'source_id' => $source_id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );
        
        if ($commission_id) {
            log_activity("Created commission from Pillars: {$amount} for user ID {$user_id}", 'info');
            
            // Create activity record
            db_insert(
                'activities',
                [
                    'user_id' => $user_id,
                    'type' => 'commission',
                    'description' => "Earned commission",
                    'amount' => $amount,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            );
        } else {
            log_activity("Failed to create commission from Pillars for user ID {$user_id}", 'error');
        }
    }
}

/**
 * Process Pillars sponsor event
 *
 * @param array $event_data The webhook event data
 * @return void
 */
function process_pillars_sponsor_event($event_data) {
    if (!isset($event_data['member'])) {
        log_activity('Invalid Pillars sponsor event: member not specified', 'error');
        return;
    }

    $member = $event_data['member'];
    $member_id = isset($member['id']) ? $member['id'] : null;
    $sponsor_id = isset($member['sponsorId']) ? $member['sponsorId'] : null;
    
    if (!$member_id) {
        log_activity('Invalid Pillars sponsor event: member ID not specified', 'error');
        return;
    }
    
    // Find user by Pillars member ID
    $user_query = db_query(
        "SELECT * FROM users WHERE pillars_id = ?",
        [$member_id]
    );
    $user = db_fetch_one($user_query);
    
    if (!$user) {
        log_activity("No user found for Pillars member ID: {$member_id}", 'warning');
        return;
    }
    
    $user_id = $user['id'];
    
    // Find sponsor user
    $sponsor_user_id = null;
    
    if ($sponsor_id) {
        $sponsor_query = db_query(
            "SELECT * FROM users WHERE pillars_id = ?",
            [$sponsor_id]
        );
        $sponsor = db_fetch_one($sponsor_query);
        
        if ($sponsor) {
            $sponsor_user_id = $sponsor['id'];
        }
    }
    
    // Update user with new sponsor
    db_update(
        'users',
        [
            'sponsor_id' => $sponsor_user_id,
            'updated_at' => date('Y-m-d H:i:s')
        ],
        ['id' => $user_id]
    );
    
    log_activity("Updated sponsor for user ID {$user_id}: sponsor ID {$sponsor_user_id}", 'info');
}