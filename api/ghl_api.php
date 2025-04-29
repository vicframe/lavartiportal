<?php
/**
 * GoHighLevel API Integration
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/database.php';

/**
 * GHL API Class
 */
class GHL_API {
    private $api_key;
    private $api_url = 'https://rest.gohighlevel.com/v1/';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = GHL_API_KEY;
        
        if (empty($this->api_key)) {
            throw new Exception('GoHighLevel API key is not configured.');
        }
    }
    
    /**
     * Make a request to the GHL API
     *
     * @param string $endpoint The API endpoint
     * @param string $method The HTTP method (GET, POST, PUT, DELETE)
     * @param array $data The data to send
     * @return array The API response
     */
    private function make_request($endpoint, $method = 'GET', $data = []) {
        $url = $this->api_url . $endpoint;
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json'
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('cURL error: ' . $error);
        }
        
        curl_close($ch);
        
        $response_data = json_decode($response, true);
        
        if ($http_status >= 400) {
            $error_message = isset($response_data['message']) ? $response_data['message'] : 'Unknown API error';
            throw new Exception('API error: ' . $error_message);
        }
        
        return $response_data;
    }
    
    /**
     * Get a contact by ID
     *
     * @param string $contact_id The contact ID
     * @return array The contact data
     */
    public function get_contact($contact_id) {
        return $this->make_request('contacts/' . $contact_id);
    }
    
    /**
     * Get contacts by email
     *
     * @param string $email The email address
     * @return array The contacts data
     */
    public function get_contacts_by_email($email) {
        return $this->make_request('contacts/lookup?email=' . urlencode($email));
    }
    
    /**
     * Create a new contact
     *
     * @param array $contact_data The contact data
     * @return array The created contact
     */
    public function create_contact($contact_data) {
        return $this->make_request('contacts', 'POST', $contact_data);
    }
    
    /**
     * Update a contact
     *
     * @param string $contact_id The contact ID
     * @param array $contact_data The contact data
     * @return array The updated contact
     */
    public function update_contact($contact_id, $contact_data) {
        return $this->make_request('contacts/' . $contact_id, 'PUT', $contact_data);
    }
    
    /**
     * Get orders for a contact
     *
     * @param string $contact_id The contact ID
     * @return array The orders data
     */
    public function get_contact_orders($contact_id) {
        return $this->make_request('contacts/' . $contact_id . '/orders');
    }
    
    /**
     * Get a specific order
     *
     * @param string $order_id The order ID
     * @return array The order data
     */
    public function get_order($order_id) {
        return $this->make_request('orders/' . $order_id);
    }
    
    /**
     * Create an opportunity
     *
     * @param array $opportunity_data The opportunity data
     * @return array The created opportunity
     */
    public function create_opportunity($opportunity_data) {
        return $this->make_request('opportunities', 'POST', $opportunity_data);
    }
    
    /**
     * Add a note to a contact
     *
     * @param string $contact_id The contact ID
     * @param string $note_text The note text
     * @return array The created note
     */
    public function add_contact_note($contact_id, $note_text) {
        $note_data = [
            'body' => $note_text
        ];
        
        return $this->make_request('contacts/' . $contact_id . '/notes', 'POST', $note_data);
    }
    
    /**
     * Add a tag to a contact
     *
     * @param string $contact_id The contact ID
     * @param string $tag The tag to add
     * @return array The API response
     */
    public function add_contact_tag($contact_id, $tag) {
        $tag_data = [
            'tags' => [$tag]
        ];
        
        return $this->make_request('contacts/' . $contact_id . '/tags', 'POST', $tag_data);
    }
    
    /**
     * Remove a tag from a contact
     *
     * @param string $contact_id The contact ID
     * @param string $tag The tag to remove
     * @return array The API response
     */
    public function remove_contact_tag($contact_id, $tag) {
        $tag_data = [
            'tags' => [$tag]
        ];
        
        return $this->make_request('contacts/' . $contact_id . '/tags/remove', 'POST', $tag_data);
    }
}

/**
 * Create GHL API instance
 *
 * @return GHL_API The GHL API instance
 */
function ghl_api() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new GHL_API();
    }
    
    return $instance;
}

/**
 * Sync a user account from GHL to the local database
 *
 * @param array $ghl_user The GHL user data
 * @return int|false The user ID or false on failure
 */
function sync_user_from_ghl($ghl_user) {
    try {
        // Check if user already exists by email
        $email = $ghl_user['email'];
        
        $query = db_query("SELECT * FROM users WHERE email = ?", [$email]);
        $existing_user = db_fetch_one($query);
        
        if ($existing_user) {
            // Update existing user
            $update_data = [
                'first_name' => $ghl_user['firstName'] ?? $existing_user['first_name'],
                'last_name' => $ghl_user['lastName'] ?? $existing_user['last_name'],
                'phone' => $ghl_user['phone'] ?? $existing_user['phone'],
                'ghl_id' => $ghl_user['id'] ?? $existing_user['ghl_id'],
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            db_update('users', $update_data, ['id' => $existing_user['id']]);
            
            log_activity("Updated user from GHL: {$email}", 'info');
            
            return $existing_user['id'];
        } else {
            // Create new user
            $insert_data = [
                'email' => $email,
                'first_name' => $ghl_user['firstName'] ?? '',
                'last_name' => $ghl_user['lastName'] ?? '',
                'phone' => $ghl_user['phone'] ?? '',
                'ghl_id' => $ghl_user['id'] ?? null,
                'tier_id' => 0, // Default tier
                'password' => password_hash(generate_random_string(12), PASSWORD_DEFAULT), // Random password
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $user_id = db_insert('users', $insert_data);
            
            if ($user_id) {
                log_activity("Created user from GHL: {$email}", 'info');
                return $user_id;
            } else {
                throw new Exception("Failed to insert user: {$email}");
            }
        }
    } catch (Exception $e) {
        error_log('Error syncing user from GHL: ' . $e->getMessage());
        log_activity('Error syncing user from GHL: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Sync an order from GHL to the local database
 *
 * @param array $ghl_order The GHL order data
 * @return int|false The order ID or false on failure
 */
function sync_order_from_ghl($ghl_order) {
    try {
        // Check if order already exists by GHL order ID
        $ghl_order_id = $ghl_order['id'];
        
        $query = db_query("SELECT * FROM orders WHERE ghl_order_id = ?", [$ghl_order_id]);
        $existing_order = db_fetch_one($query);
        
        if ($existing_order) {
            // Update existing order
            $update_data = [
                'status' => $ghl_order['status'] ?? $existing_order['status'],
                'total' => $ghl_order['total'] ?? $existing_order['total'],
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            db_update('orders', $update_data, ['id' => $existing_order['id']]);
            
            log_activity("Updated order from GHL: {$ghl_order_id}", 'info');
            
            return $existing_order['id'];
        } else {
            // Get user by GHL contact ID
            $ghl_contact_id = $ghl_order['contactId'];
            
            $user_query = db_query("SELECT * FROM users WHERE ghl_id = ?", [$ghl_contact_id]);
            $user = db_fetch_one($user_query);
            
            if (!$user) {
                // Try to get contact from GHL
                try {
                    $ghl_api = ghl_api();
                    $contact = $ghl_api->get_contact($ghl_contact_id);
                    
                    if ($contact) {
                        $user_id = sync_user_from_ghl($contact);
                        
                        if (!$user_id) {
                            throw new Exception("Failed to sync user for GHL contact: {$ghl_contact_id}");
                        }
                    } else {
                        throw new Exception("Contact not found in GHL: {$ghl_contact_id}");
                    }
                } catch (Exception $e) {
                    error_log('Error getting contact from GHL: ' . $e->getMessage());
                    log_activity('Error getting contact from GHL: ' . $e->getMessage(), 'error');
                    return false;
                }
            } else {
                $user_id = $user['id'];
            }
            
            // Create new order
            $insert_data = [
                'user_id' => $user_id,
                'ghl_order_id' => $ghl_order_id,
                'ghl_contact_id' => $ghl_contact_id,
                'status' => $ghl_order['status'] ?? 'pending',
                'total' => $ghl_order['total'] ?? 0,
                'created_at' => date('Y-m-d H:i:s', strtotime($ghl_order['createdAt'] ?? 'now')),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $order_id = db_insert('orders', $insert_data);
            
            if ($order_id) {
                // Process order items
                if (isset($ghl_order['items']) && is_array($ghl_order['items'])) {
                    foreach ($ghl_order['items'] as $item) {
                        $item_data = [
                            'order_id' => $order_id,
                            'product_id' => $item['productId'] ?? null,
                            'name' => $item['name'] ?? 'Unknown Product',
                            'price' => $item['price'] ?? 0,
                            'quantity' => $item['quantity'] ?? 1,
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        
                        db_insert('order_items', $item_data);
                    }
                }
                
                log_activity("Created order from GHL: {$ghl_order_id}", 'info');
                
                // Update user tier based on subscription product
                update_user_tier_from_order($user_id, $ghl_order);
                
                return $order_id;
            } else {
                throw new Exception("Failed to insert order: {$ghl_order_id}");
            }
        }
    } catch (Exception $e) {
        error_log('Error syncing order from GHL: ' . $e->getMessage());
        log_activity('Error syncing order from GHL: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Update user tier based on order
 *
 * @param int $user_id The user ID
 * @param array $ghl_order The GHL order data
 * @return bool Success status
 */
function update_user_tier_from_order($user_id, $ghl_order) {
    try {
        // Check if this is a subscription product
        if (isset($ghl_order['items']) && is_array($ghl_order['items'])) {
            foreach ($ghl_order['items'] as $item) {
                $product_name = strtolower($item['name'] ?? '');
                
                // Determine tier based on product name
                $tier_id = 0;
                
                if (strpos($product_name, 'basic') !== false) {
                    $tier_id = 1;
                } elseif (strpos($product_name, 'premium') !== false) {
                    $tier_id = 2;
                } elseif (strpos($product_name, 'elite') !== false) {
                    $tier_id = 3;
                }
                
                if ($tier_id > 0) {
                    // Update user tier
                    db_update('users', ['tier_id' => $tier_id], ['id' => $user_id]);
                    
                    log_activity("Updated user {$user_id} to tier {$tier_id} based on order", 'info');
                    return true;
                }
            }
        }
        
        return false;
    } catch (Exception $e) {
        error_log('Error updating user tier: ' . $e->getMessage());
        log_activity('Error updating user tier: ' . $e->getMessage(), 'error');
        return false;
    }
}