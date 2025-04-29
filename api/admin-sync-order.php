<?php
/**
 * Admin Sync Order API
 * 
 * Syncs an order with GHL
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/ghl_api.php';

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

// Get order ID from request
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$order_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Order ID is required']);
    exit;
}

try {
    // Get order
    $order_query = db_query(
        "SELECT o.*, u.email, u.first_name, u.last_name, u.ghl_id
         FROM orders o
         JOIN users u ON o.user_id = u.id
         WHERE o.id = ?",
        [$order_id]
    );
    
    $order = db_fetch_one($order_query);
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }
    
    // Initialize GHL API
    $ghl_api = ghl_api();
    
    // If order has GHL order ID, fetch it from GHL
    if (!empty($order['ghl_order_id'])) {
        $ghl_order = $ghl_api->get_order($order['ghl_order_id']);
        
        if (!$ghl_order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Order not found in GHL']);
            exit;
        }
        
        // Update order with latest GHL data
        db_update(
            'orders',
            [
                'status' => strtolower($ghl_order['status']),
                'amount' => $ghl_order['amount'],
                'updated_at' => date('Y-m-d H:i:s')
            ],
            ['id' => $order_id]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Order synchronized with GHL successfully',
            'ghl_order' => $ghl_order
        ]);
    } else {
        // If order doesn't have GHL order ID, try to create a new one in GHL
        
        // First, make sure we have a GHL contact
        $ghl_contact_id = $order['ghl_id'];
        
        if (!$ghl_contact_id) {
            // Try to find contact by email
            $contacts = $ghl_api->get_contacts(['query' => $order['email']]);
            
            if (isset($contacts['contacts']) && !empty($contacts['contacts'])) {
                $ghl_contact = $contacts['contacts'][0];
                $ghl_contact_id = $ghl_contact['id'];
                
                // Update user with GHL ID
                db_update(
                    'users',
                    [
                        'ghl_id' => $ghl_contact_id,
                        'updated_at' => date('Y-m-d H:i:s')
                    ],
                    ['id' => $order['user_id']]
                );
            } else {
                // Create new contact in GHL
                $contact_data = [
                    'email' => $order['email'],
                    'firstName' => $order['first_name'],
                    'lastName' => $order['last_name']
                ];
                
                $new_contact = $ghl_api->create_contact($contact_data);
                $ghl_contact_id = $new_contact['id'];
                
                // Update user with GHL ID
                db_update(
                    'users',
                    [
                        'ghl_id' => $ghl_contact_id,
                        'updated_at' => date('Y-m-d H:i:s')
                    ],
                    ['id' => $order['user_id']]
                );
            }
        }
        
        // Create order in GHL
        $order_data = [
            'contactId' => $ghl_contact_id,
            'locationId' => getenv('GHL_LOCATION_ID'),
            'amount' => $order['amount'],
            'status' => $order['status'],
            'source' => 'LaVarti Portal',
            'items' => [
                [
                    'name' => $order['product_name'],
                    'price' => $order['amount'],
                    'quantity' => 1
                ]
            ]
        ];
        
        $new_ghl_order = $ghl_api->create_order($order_data);
        
        // Update order with GHL order ID
        db_update(
            'orders',
            [
                'ghl_order_id' => $new_ghl_order['id'],
                'updated_at' => date('Y-m-d H:i:s')
            ],
            ['id' => $order_id]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'New order created in GHL successfully',
            'ghl_order' => $new_ghl_order
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}