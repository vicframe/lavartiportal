<?php
/**
 * Pillars API Integration
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/database.php';

/**
 * Pillars API Class
 */
class Pillars_API {
    private $api_key;
    private $api_url = 'https://api.pillarsrsi.com/v1/';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = PILLARS_API_KEY;
        
        if (empty($this->api_key)) {
            throw new Exception('Pillars API key is not configured.');
        }
    }
    
    /**
     * Make a request to the Pillars API
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
     * Get a member by ID
     *
     * @param string $member_id The member ID
     * @return array The member data
     */
    public function get_member($member_id) {
        return $this->make_request('members/' . $member_id);
    }
    
    /**
     * Get a member by email
     *
     * @param string $email The email address
     * @return array The member data
     */
    public function get_member_by_email($email) {
        return $this->make_request('members/lookup?email=' . urlencode($email));
    }
    
    /**
     * Create a new member
     *
     * @param array $member_data The member data
     * @return array The created member
     */
    public function create_member($member_data) {
        return $this->make_request('members', 'POST', $member_data);
    }
    
    /**
     * Update a member
     *
     * @param string $member_id The member ID
     * @param array $member_data The member data
     * @return array The updated member
     */
    public function update_member($member_id, $member_data) {
        return $this->make_request('members/' . $member_id, 'PUT', $member_data);
    }
    
    /**
     * Get commissions for a member
     *
     * @param string $member_id The member ID
     * @return array The commissions data
     */
    public function get_member_commissions($member_id) {
        return $this->make_request('members/' . $member_id . '/commissions');
    }
    
    /**
     * Get a specific commission
     *
     * @param string $commission_id The commission ID
     * @return array The commission data
     */
    public function get_commission($commission_id) {
        return $this->make_request('commissions/' . $commission_id);
    }
    
    /**
     * Create a new commission
     *
     * @param array $commission_data The commission data
     * @return array The created commission
     */
    public function create_commission($commission_data) {
        return $this->make_request('commissions', 'POST', $commission_data);
    }
    
    /**
     * Update a commission
     *
     * @param string $commission_id The commission ID
     * @param array $commission_data The commission data
     * @return array The updated commission
     */
    public function update_commission($commission_id, $commission_data) {
        return $this->make_request('commissions/' . $commission_id, 'PUT', $commission_data);
    }
    
    /**
     * Get team members
     *
     * @param string $member_id The member ID
     * @return array The team members data
     */
    public function get_team_members($member_id) {
        return $this->make_request('members/' . $member_id . '/team');
    }
    
    /**
     * Get member's sponsor
     *
     * @param string $member_id The member ID
     * @return array The sponsor data
     */
    public function get_member_sponsor($member_id) {
        return $this->make_request('members/' . $member_id . '/sponsor');
    }
}

/**
 * Create Pillars API instance
 *
 * @return Pillars_API The Pillars API instance
 */
function pillars_api() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new Pillars_API();
    }
    
    return $instance;
}

/**
 * Sync a user account to Pillars
 *
 * @param array $user The user data
 * @return bool Success status
 */
function sync_user_to_pillars($user) {
    try {
        $pillars_api = pillars_api();
        
        // Check if member already exists in Pillars
        try {
            $member = $pillars_api->get_member_by_email($user['email']);
            
            if (isset($member['id'])) {
                // Update existing member
                $update_data = [
                    'firstName' => $user['first_name'],
                    'lastName' => $user['last_name'],
                    'phone' => $user['phone'] ?? '',
                    'externalId' => $user['id']
                ];
                
                // If user has a sponsor, set it
                if (!empty($user['sponsor_id'])) {
                    // Get sponsor's Pillars ID
                    $sponsor_query = db_query("SELECT pillars_id FROM users WHERE id = ?", [$user['sponsor_id']]);
                    $sponsor = db_fetch_one($sponsor_query);
                    
                    if ($sponsor && !empty($sponsor['pillars_id'])) {
                        $update_data['sponsorId'] = $sponsor['pillars_id'];
                    }
                }
                
                $result = $pillars_api->update_member($member['id'], $update_data);
                
                if (isset($result['id'])) {
                    // Update local user with Pillars ID
                    db_update('users', ['pillars_id' => $result['id']], ['id' => $user['id']]);
                    
                    log_activity("Updated member in Pillars: {$user['email']}", 'info');
                    return true;
                } else {
                    throw new Exception("Failed to update member in Pillars: {$user['email']}");
                }
            } else {
                // Create new member
                $member_data = [
                    'email' => $user['email'],
                    'firstName' => $user['first_name'],
                    'lastName' => $user['last_name'],
                    'phone' => $user['phone'] ?? '',
                    'externalId' => $user['id']
                ];
                
                // If user has a sponsor, set it
                if (!empty($user['sponsor_id'])) {
                    // Get sponsor's Pillars ID
                    $sponsor_query = db_query("SELECT pillars_id FROM users WHERE id = ?", [$user['sponsor_id']]);
                    $sponsor = db_fetch_one($sponsor_query);
                    
                    if ($sponsor && !empty($sponsor['pillars_id'])) {
                        $member_data['sponsorId'] = $sponsor['pillars_id'];
                    }
                }
                
                $result = $pillars_api->create_member($member_data);
                
                if (isset($result['id'])) {
                    // Update local user with Pillars ID
                    db_update('users', ['pillars_id' => $result['id']], ['id' => $user['id']]);
                    
                    log_activity("Created member in Pillars: {$user['email']}", 'info');
                    return true;
                } else {
                    throw new Exception("Failed to create member in Pillars: {$user['email']}");
                }
            }
        } catch (Exception $e) {
            error_log('Error syncing user to Pillars: ' . $e->getMessage());
            log_activity('Error syncing user to Pillars: ' . $e->getMessage(), 'error');
            return false;
        }
    } catch (Exception $e) {
        error_log('Error initializing Pillars API: ' . $e->getMessage());
        log_activity('Error initializing Pillars API: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Sync an order to Pillars for commission calculation
 *
 * @param array $order The order data
 * @return bool Success status
 */
function sync_order_to_pillars($order) {
    try {
        // Get user data
        $user_query = db_query("SELECT * FROM users WHERE id = ?", [$order['user_id']]);
        $user = db_fetch_one($user_query);
        
        if (!$user) {
            throw new Exception("User not found for order: {$order['id']}");
        }
        
        // Make sure user is synchronized with Pillars
        if (empty($user['pillars_id'])) {
            $sync_result = sync_user_to_pillars($user);
            
            if (!$sync_result) {
                throw new Exception("Failed to sync user to Pillars: {$user['email']}");
            }
            
            // Refresh user data
            $user_query = db_query("SELECT * FROM users WHERE id = ?", [$order['user_id']]);
            $user = db_fetch_one($user_query);
        }
        
        // Get order items
        $items_query = db_query("SELECT * FROM order_items WHERE order_id = ?", [$order['id']]);
        $items = db_fetch_all($items_query);
        
        // Create commission data for Pillars
        $pillars_api = pillars_api();
        
        $commission_data = [
            'memberId' => $user['pillars_id'],
            'externalId' => $order['id'],
            'amount' => $order['total'],
            'status' => map_order_status_to_pillars($order['status']),
            'source' => 'GHL',
            'sourceId' => $order['ghl_order_id'],
            'items' => []
        ];
        
        // Add order items
        foreach ($items as $item) {
            $commission_data['items'][] = [
                'name' => $item['name'],
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'externalId' => $item['id']
            ];
        }
        
        // Check if commission already exists in Pillars
        $existing_commission_query = db_query(
            "SELECT * FROM commissions WHERE order_id = ? AND user_id = ?",
            [$order['id'], $user['id']]
        );
        $existing_commission = db_fetch_one($existing_commission_query);
        
        if ($existing_commission && !empty($existing_commission['pillars_id'])) {
            // Update existing commission
            $result = $pillars_api->update_commission($existing_commission['pillars_id'], $commission_data);
            
            if (isset($result['id'])) {
                // Update local commission record
                $update_data = [
                    'status' => map_pillars_status_to_local($result['status']),
                    'amount' => $result['amount'],
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                db_update('commissions', $update_data, ['id' => $existing_commission['id']]);
                
                log_activity("Updated commission in Pillars for order: {$order['id']}", 'info');
                return true;
            } else {
                throw new Exception("Failed to update commission in Pillars for order: {$order['id']}");
            }
        } else {
            // Create new commission
            $result = $pillars_api->create_commission($commission_data);
            
            if (isset($result['id'])) {
                // Create local commission record
                $insert_data = [
                    'user_id' => $user['id'],
                    'order_id' => $order['id'],
                    'pillars_id' => $result['id'],
                    'status' => map_pillars_status_to_local($result['status']),
                    'amount' => $result['amount'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                db_insert('commissions', $insert_data);
                
                log_activity("Created commission in Pillars for order: {$order['id']}", 'info');
                return true;
            } else {
                throw new Exception("Failed to create commission in Pillars for order: {$order['id']}");
            }
        }
    } catch (Exception $e) {
        error_log('Error syncing order to Pillars: ' . $e->getMessage());
        log_activity('Error syncing order to Pillars: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Map order status to Pillars status
 *
 * @param string $status The order status
 * @return string The Pillars status
 */
function map_order_status_to_pillars($status) {
    switch (strtolower($status)) {
        case 'completed':
        case 'paid':
        case 'shipped':
            return 'approved';
        case 'pending':
        case 'processing':
            return 'pending';
        case 'cancelled':
        case 'refunded':
            return 'cancelled';
        default:
            return 'pending';
    }
}

/**
 * Map Pillars status to local status
 *
 * @param string $status The Pillars status
 * @return string The local status
 */
function map_pillars_status_to_local($status) {
    switch (strtolower($status)) {
        case 'approved':
        case 'paid':
            return 'approved';
        case 'pending':
            return 'pending';
        case 'cancelled':
            return 'cancelled';
        default:
            return 'pending';
    }
}

/**
 * Get team members from Pillars
 *
 * @param int $user_id The user ID
 * @return array The team members
 */
function get_team_members_from_pillars($user_id) {
    try {
        // Get user data
        $user_query = db_query("SELECT * FROM users WHERE id = ?", [$user_id]);
        $user = db_fetch_one($user_query);
        
        if (!$user || empty($user['pillars_id'])) {
            return [];
        }
        
        $pillars_api = pillars_api();
        $team_members = $pillars_api->get_team_members($user['pillars_id']);
        
        return $team_members;
    } catch (Exception $e) {
        error_log('Error getting team members from Pillars: ' . $e->getMessage());
        log_activity('Error getting team members from Pillars: ' . $e->getMessage(), 'error');
        return [];
    }
}

/**
 * Get commissions from Pillars
 *
 * @param int $user_id The user ID
 * @return array The commissions
 */
function get_commissions_from_pillars($user_id) {
    try {
        // Get user data
        $user_query = db_query("SELECT * FROM users WHERE id = ?", [$user_id]);
        $user = db_fetch_one($user_query);
        
        if (!$user || empty($user['pillars_id'])) {
            return [];
        }
        
        $pillars_api = pillars_api();
        $commissions = $pillars_api->get_member_commissions($user['pillars_id']);
        
        return $commissions;
    } catch (Exception $e) {
        error_log('Error getting commissions from Pillars: ' . $e->getMessage());
        log_activity('Error getting commissions from Pillars: ' . $e->getMessage(), 'error');
        return [];
    }
}