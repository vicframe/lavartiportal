<?php
/**
 * Pillars Webhook Handler
 * 
 * This script handles incoming webhooks from Pillars
 * It processes events like commission status changes, affiliate activations, etc.
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
$log_file = __DIR__ . '/logs/pillars_webhook_' . date('Y-m-d') . '.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Pillars Webhook Received:\n" . $webhook_data . "\n\n", FILE_APPEND);

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
$verified = verify_pillars_webhook($headers, $webhook_data);
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
        'integration' => 'pillars',
        'action' => $event_type,
        'status' => 'success',
        'records_processed' => 0,
        'summary' => '',
        'details' => []
    ];

    switch ($event_type) {
        case 'affiliate.created':
            // Process new affiliate
            $result = process_pillars_affiliate($data);
            $sync_result['records_processed'] = 1;
            $sync_result['summary'] = 'Pillars affiliate created';
            $sync_result['details'] = $result;
            break;
            
        case 'affiliate.updated':
            // Process updated affiliate
            $result = process_pillars_affiliate($data, true);
            $sync_result['records_processed'] = 1;
            $sync_result['summary'] = 'Pillars affiliate updated';
            $sync_result['details'] = $result;
            break;
            
        case 'commission.created':
        case 'commission.updated':
        case 'commission.paid':
            // Process commission status change
            $result = process_pillars_commission($data);
            $sync_result['records_processed'] = 1;
            $sync_result['summary'] = 'Pillars commission ' . ($event_type === 'commission.created' ? 'created' : ($event_type === 'commission.paid' ? 'paid' : 'updated'));
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
        'integration' => 'pillars',
        'action' => $event_type,
        'status' => 'error',
        'records_processed' => 0,
        'summary' => 'Error processing Pillars webhook: ' . $e->getMessage(),
        'details' => ['error' => $e->getMessage()]
    ];
    log_sync_history($sync_result);
    
    // Return error response
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Verifies the Pillars webhook signature
 */
function verify_pillars_webhook($headers, $payload) {
    // In a production environment, you would verify the webhook signature
    // with the Pillars signature in the headers using the webhook secret
    
    // For now, we'll assume it's valid
    return true;
}

/**
 * Process a Pillars affiliate
 */
function process_pillars_affiliate($data, $is_update = false) {
    global $log_file;
    
    // Extract affiliate data
    $affiliate = $data['affiliate'] ?? null;
    
    if (!$affiliate) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: Missing affiliate data\n\n", FILE_APPEND);
        throw new Exception('Missing affiliate data');
    }
    
    // Extract required fields
    $pillars_id = $affiliate['id'] ?? null;
    $email = $affiliate['email'] ?? null;
    $first_name = $affiliate['firstName'] ?? '';
    $last_name = $affiliate['lastName'] ?? '';
    $referrer_id = $affiliate['referrerId'] ?? null;
    
    if (!$pillars_id || !$email) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: Missing required affiliate fields\n\n", FILE_APPEND);
        throw new Exception('Missing required affiliate fields (id or email)');
    }
    
    // Check if user already exists with this email
    $user_query = db_query("SELECT * FROM users WHERE email = ?", [$email]);
    $user = db_fetch_one($user_query);
    
    if ($user) {
        // User exists, update with Pillars data
        $query = "
            UPDATE users 
            SET 
                pillars_id = ?,
                first_name = ?,
                last_name = ?,
                updated_at = NOW()
            WHERE id = ?
        ";
        
        $update_params = [
            $pillars_id,
            $first_name,
            $last_name,
            $user['id']
        ];
        
        // If referrer_id exists, find and set sponsor
        if ($referrer_id) {
            // Look up the referrer in our database by pillars_id
            $referrer_query = db_query("SELECT id FROM users WHERE pillars_id = ?", [$referrer_id]);
            $referrer = db_fetch_one($referrer_query);
            
            if ($referrer) {
                // Update the query to include sponsor_id
                $query = "
                    UPDATE users 
                    SET 
                        pillars_id = ?,
                        first_name = ?,
                        last_name = ?,
                        sponsor_id = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ";
                
                $update_params = [
                    $pillars_id,
                    $first_name,
                    $last_name,
                    $referrer['id'],
                    $user['id']
                ];
            }
        }
        
        db_query($query, $update_params);
        
        return [
            'action' => 'updated',
            'user_id' => $user['id'],
            'pillars_id' => $pillars_id
        ];
    } else if (!$is_update) {
        // User doesn't exist, create new user
        $password = password_hash(generate_random_password(), PASSWORD_DEFAULT);
        
        $query = "
            INSERT INTO users (
                email, first_name, last_name, password, pillars_id, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, NOW(), NOW()
            ) RETURNING id
        ";
        
        $insert_params = [
            $email,
            $first_name,
            $last_name,
            $password,
            $pillars_id
        ];
        
        $result = db_query($query, $insert_params);
        $new_user = db_fetch_one($result);
        
        // If referrer_id exists, find and set sponsor
        if ($referrer_id && $new_user) {
            // Look up the referrer in our database by pillars_id
            $referrer_query = db_query("SELECT id FROM users WHERE pillars_id = ?", [$referrer_id]);
            $referrer = db_fetch_one($referrer_query);
            
            if ($referrer) {
                // Update the user to set sponsor_id
                db_query("UPDATE users SET sponsor_id = ? WHERE id = ?", [$referrer['id'], $new_user['id']]);
            }
        }
        
        return [
            'action' => 'created',
            'user_id' => $new_user['id'],
            'pillars_id' => $pillars_id
        ];
    }
    
    return [
        'action' => 'skipped',
        'reason' => 'User not found for update request'
    ];
}

/**
 * Process a Pillars commission update
 */
function process_pillars_commission($data) {
    global $log_file;
    
    // Extract commission data
    $commission_data = $data['commission'] ?? null;
    
    if (!$commission_data) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: Missing commission data\n\n", FILE_APPEND);
        throw new Exception('Missing commission data');
    }
    
    // Extract required fields
    $pillars_commission_id = $commission_data['id'] ?? null;
    $pillars_affiliate_id = $commission_data['affiliateId'] ?? null;
    $status = $commission_data['status'] ?? '';
    $amount = $commission_data['amount'] ?? 0;
    $type = $commission_data['type'] ?? 'direct';
    $reference_id = $commission_data['referenceId'] ?? null;
    
    if (!$pillars_commission_id || !$pillars_affiliate_id) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: Missing required commission fields\n\n", FILE_APPEND);
        throw new Exception('Missing required commission fields (id or affiliateId)');
    }
    
    // Find user by Pillars affiliate ID
    $user_query = db_query("SELECT * FROM users WHERE pillars_id = ?", [$pillars_affiliate_id]);
    $user = db_fetch_one($user_query);
    
    if (!$user) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error: User not found for Pillars affiliate ID: {$pillars_affiliate_id}\n\n", FILE_APPEND);
        throw new Exception("User not found for Pillars affiliate ID: {$pillars_affiliate_id}");
    }
    
    // Map Pillars status to our commission status
    $commission_status = map_pillars_status_to_commission_status($status);
    
    // Check if we already have this commission in our system
    $commission_query = db_query("
        SELECT c.*, o.ghl_order_id 
        FROM commissions c
        LEFT JOIN orders o ON c.order_id = o.id
        WHERE c.external_id = ?
    ", [$pillars_commission_id]);
    
    $commission = db_fetch_one($commission_query);
    
    if ($commission) {
        // Commission exists, update it
        $query = "
            UPDATE commissions 
            SET 
                amount = ?,
                status = ?,
                updated_at = NOW()
            WHERE id = ?
        ";
        
        db_query($query, [
            $amount,
            $commission_status,
            $commission['id']
        ]);
        
        return [
            'action' => 'updated',
            'commission_id' => $commission['id'],
            'pillars_commission_id' => $pillars_commission_id,
            'status' => $commission_status
        ];
    } else {
        // Try to find the order by reference_id
        $order_id = null;
        
        if ($reference_id) {
            $order_query = db_query("SELECT id FROM orders WHERE ghl_order_id = ?", [$reference_id]);
            $order = db_fetch_one($order_query);
            
            if ($order) {
                $order_id = $order['id'];
            }
        }
        
        // If no order found, find the most recent order for this user
        if (!$order_id) {
            $order_query = db_query("
                SELECT id FROM orders 
                WHERE user_id = ? AND status = 'completed'
                ORDER BY created_at DESC LIMIT 1
            ", [$user['id']]);
            
            $order = db_fetch_one($order_query);
            
            if ($order) {
                $order_id = $order['id'];
            }
        }
        
        if (!$order_id) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Warning: No matching order found for commission, creating without order reference\n\n", FILE_APPEND);
        }
        
        // Create new commission
        $query = "
            INSERT INTO commissions (
                user_id, order_id, amount, type, status, external_id, commission_date, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW()
            ) RETURNING id
        ";
        
        $result = db_query($query, [
            $user['id'],
            $order_id,
            $amount,
            $type,
            $commission_status,
            $pillars_commission_id
        ]);
        
        $new_commission = db_fetch_one($result);
        
        return [
            'action' => 'created',
            'commission_id' => $new_commission['id'],
            'pillars_commission_id' => $pillars_commission_id,
            'status' => $commission_status
        ];
    }
}

/**
 * Maps Pillars commission status to our commission status
 */
function map_pillars_status_to_commission_status($pillars_status) {
    $status_map = [
        'pending' => 'pending',
        'approved' => 'approved',
        'paid' => 'paid',
        'rejected' => 'declined'
    ];
    
    $pillars_status = strtolower($pillars_status);
    
    return isset($status_map[$pillars_status]) ? $status_map[$pillars_status] : 'pending';
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