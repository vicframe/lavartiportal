<?php
/**
 * Webhook handler for GHL and Pillars integrations
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/ghl_api.php';
require_once __DIR__ . '/pillars_api.php';

// Set content type to JSON
header('Content-Type: application/json');

// Verify request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get request body
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit;
}

// Get webhook source
$source = isset($_GET['source']) ? $_GET['source'] : 'unknown';

try {
    log_activity('Webhook received from: ' . $source, 'info');
    log_activity('Webhook data: ' . json_encode($data), 'debug');
    
    switch ($source) {
        case 'ghl':
            process_ghl_webhook($data);
            break;
        
        case 'pillars':
            process_pillars_webhook($data);
            break;
        
        default:
            throw new Exception('Unknown webhook source: ' . $source);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    log_activity('Webhook error: ' . $e->getMessage(), 'error');
    
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Process a GoHighLevel webhook
 *
 * @param array $data The webhook data
 * @return void
 */
function process_ghl_webhook($data) {
    // Get event type
    $event = isset($data['event']) ? $data['event'] : '';
    
    log_activity('Processing GHL webhook: ' . $event, 'info');
    
    switch ($event) {
        case 'contact.created':
            process_ghl_contact_created($data);
            break;
        
        case 'contact.updated':
            process_ghl_contact_updated($data);
            break;
        
        case 'order.created':
            process_ghl_order_created($data);
            break;
        
        case 'order.updated':
            process_ghl_order_updated($data);
            break;
        
        default:
            log_activity('Unsupported GHL event: ' . $event, 'warning');
    }
}

/**
 * Process a Pillars webhook
 *
 * @param array $data The webhook data
 * @return void
 */
function process_pillars_webhook($data) {
    // Get event type
    $event = isset($data['event']) ? $data['event'] : '';
    
    log_activity('Processing Pillars webhook: ' . $event, 'info');
    
    switch ($event) {
        case 'commission.created':
            process_pillars_commission_created($data);
            break;
        
        case 'commission.updated':
            process_pillars_commission_updated($data);
            break;
        
        case 'member.sponsorChanged':
            process_pillars_sponsor_changed($data);
            break;
        
        default:
            log_activity('Unsupported Pillars event: ' . $event, 'warning');
    }
}

/**
 * Process a GHL contact created event
 *
 * @param array $data The webhook data
 * @return void
 */
function process_ghl_contact_created($data) {
    if (!isset($data['contact'])) {
        throw new Exception('Contact data not provided');
    }
    
    $contact = $data['contact'];
    
    // Sync contact to local database
    $user_id = sync_user_from_ghl($contact);
    
    if (!$user_id) {
        throw new Exception('Failed to sync user from GHL');
    }
    
    log_activity('Contact created and synced: ' . $contact['email'], 'info');
    
    // Get user data
    $user_query = db_query("SELECT * FROM users WHERE id = ?", [$user_id]);
    $user = db_fetch_one($user_query);
    
    // Sync user to Pillars
    $sync_result = sync_user_to_pillars($user);
    
    if ($sync_result) {
        log_activity('User synced to Pillars: ' . $user['email'], 'info');
    } else {
        log_activity('Failed to sync user to Pillars: ' . $user['email'], 'warning');
    }
}

/**
 * Process a GHL contact updated event
 *
 * @param array $data The webhook data
 * @return void
 */
function process_ghl_contact_updated($data) {
    if (!isset($data['contact'])) {
        throw new Exception('Contact data not provided');
    }
    
    $contact = $data['contact'];
    
    // Sync contact to local database
    $user_id = sync_user_from_ghl($contact);
    
    if (!$user_id) {
        throw new Exception('Failed to sync user from GHL');
    }
    
    log_activity('Contact updated and synced: ' . $contact['email'], 'info');
    
    // Get user data
    $user_query = db_query("SELECT * FROM users WHERE id = ?", [$user_id]);
    $user = db_fetch_one($user_query);
    
    // Sync user to Pillars
    $sync_result = sync_user_to_pillars($user);
    
    if ($sync_result) {
        log_activity('User synced to Pillars: ' . $user['email'], 'info');
    } else {
        log_activity('Failed to sync user to Pillars: ' . $user['email'], 'warning');
    }
}

/**
 * Process a GHL order created event
 *
 * @param array $data The webhook data
 * @return void
 */
function process_ghl_order_created($data) {
    if (!isset($data['order'])) {
        throw new Exception('Order data not provided');
    }
    
    $order = $data['order'];
    
    // Sync order to local database
    $order_id = sync_order_from_ghl($order);
    
    if (!$order_id) {
        throw new Exception('Failed to sync order from GHL');
    }
    
    log_activity('Order created and synced: ' . $order['id'], 'info');
    
    // Get order data
    $order_query = db_query("SELECT * FROM orders WHERE id = ?", [$order_id]);
    $order_data = db_fetch_one($order_query);
    
    // Sync order to Pillars for commission calculation
    $sync_result = sync_order_to_pillars($order_data);
    
    if ($sync_result) {
        log_activity('Order synced to Pillars for commission: ' . $order_data['id'], 'info');
    } else {
        log_activity('Failed to sync order to Pillars: ' . $order_data['id'], 'warning');
    }
}

/**
 * Process a GHL order updated event
 *
 * @param array $data The webhook data
 * @return void
 */
function process_ghl_order_updated($data) {
    if (!isset($data['order'])) {
        throw new Exception('Order data not provided');
    }
    
    $order = $data['order'];
    
    // Sync order to local database
    $order_id = sync_order_from_ghl($order);
    
    if (!$order_id) {
        throw new Exception('Failed to sync order from GHL');
    }
    
    log_activity('Order updated and synced: ' . $order['id'], 'info');
    
    // Get order data
    $order_query = db_query("SELECT * FROM orders WHERE id = ?", [$order_id]);
    $order_data = db_fetch_one($order_query);
    
    // Sync order to Pillars for commission calculation
    $sync_result = sync_order_to_pillars($order_data);
    
    if ($sync_result) {
        log_activity('Order synced to Pillars for commission: ' . $order_data['id'], 'info');
    } else {
        log_activity('Failed to sync order to Pillars: ' . $order_data['id'], 'warning');
    }
}

/**
 * Process a Pillars commission created event
 *
 * @param array $data The webhook data
 * @return void
 */
function process_pillars_commission_created($data) {
    if (!isset($data['commission'])) {
        throw new Exception('Commission data not provided');
    }
    
    $commission = $data['commission'];
    
    // Check if we have a user with this Pillars member ID
    $user_query = db_query("SELECT * FROM users WHERE pillars_id = ?", [$commission['memberId']]);
    $user = db_fetch_one($user_query);
    
    if (!$user) {
        log_activity('No user found for Pillars member ID: ' . $commission['memberId'], 'warning');
        return;
    }
    
    // Check if we already have this commission
    $existing_query = db_query(
        "SELECT * FROM commissions WHERE pillars_id = ?",
        [$commission['id']]
    );
    $existing_commission = db_fetch_one($existing_query);
    
    if ($existing_commission) {
        // Update existing commission
        $update_data = [
            'status' => map_pillars_status_to_local($commission['status']),
            'amount' => $commission['amount'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        db_update('commissions', $update_data, ['id' => $existing_commission['id']]);
        
        log_activity('Updated commission from Pillars: ' . $commission['id'], 'info');
    } else {
        // Create new commission record
        $insert_data = [
            'user_id' => $user['id'],
            'pillars_id' => $commission['id'],
            'status' => map_pillars_status_to_local($commission['status']),
            'amount' => $commission['amount'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // If there's an external order ID, try to link it
        if (isset($commission['externalId'])) {
            $order_query = db_query("SELECT * FROM orders WHERE id = ?", [$commission['externalId']]);
            $order = db_fetch_one($order_query);
            
            if ($order) {
                $insert_data['order_id'] = $order['id'];
            }
        }
        
        db_insert('commissions', $insert_data);
        
        log_activity('Created commission from Pillars: ' . $commission['id'], 'info');
    }
}

/**
 * Process a Pillars commission updated event
 *
 * @param array $data The webhook data
 * @return void
 */
function process_pillars_commission_updated($data) {
    if (!isset($data['commission'])) {
        throw new Exception('Commission data not provided');
    }
    
    $commission = $data['commission'];
    
    // Check if we have this commission
    $commission_query = db_query("SELECT * FROM commissions WHERE pillars_id = ?", [$commission['id']]);
    $existing_commission = db_fetch_one($commission_query);
    
    if (!$existing_commission) {
        // Try to create it
        process_pillars_commission_created($data);
        return;
    }
    
    // Update the commission
    $update_data = [
        'status' => map_pillars_status_to_local($commission['status']),
        'amount' => $commission['amount'],
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    db_update('commissions', $update_data, ['id' => $existing_commission['id']]);
    
    log_activity('Updated commission from Pillars: ' . $commission['id'], 'info');
}

/**
 * Process a Pillars sponsor changed event
 *
 * @param array $data The webhook data
 * @return void
 */
function process_pillars_sponsor_changed($data) {
    if (!isset($data['member']) || !isset($data['sponsorId'])) {
        throw new Exception('Member or sponsor data not provided');
    }
    
    $member = $data['member'];
    $sponsor_id = $data['sponsorId'];
    
    // Find user with this Pillars member ID
    $user_query = db_query("SELECT * FROM users WHERE pillars_id = ?", [$member['id']]);
    $user = db_fetch_one($user_query);
    
    if (!$user) {
        log_activity('No user found for Pillars member ID: ' . $member['id'], 'warning');
        return;
    }
    
    // Find sponsor
    $sponsor_query = db_query("SELECT * FROM users WHERE pillars_id = ?", [$sponsor_id]);
    $sponsor = db_fetch_one($sponsor_query);
    
    if (!$sponsor) {
        log_activity('No user found for Pillars sponsor ID: ' . $sponsor_id, 'warning');
        return;
    }
    
    // Update user's sponsor
    db_update('users', ['sponsor_id' => $sponsor['id']], ['id' => $user['id']]);
    
    log_activity('Updated user sponsor: ' . $user['email'] . ' -> ' . $sponsor['email'], 'info');
}