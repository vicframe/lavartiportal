<?php
/**
 * Pillars API Integration
 */

// Get Pillars API client
function pillars_get_client() {
    return new PillarsApiClient();
}

// Pillars API Client Class
class PillarsApiClient {
    private $api_key;
    private $base_url;
    
    public function __construct() {
        $this->api_key = PILLARS_API_KEY;
        $this->base_url = PILLARS_API_URL;
    }
    
    // Execute a GET request
    public function get($endpoint, $params = []) {
        return $this->request('GET', $endpoint, $params);
    }
    
    // Execute a POST request
    public function post($endpoint, $data = []) {
        return $this->request('POST', $endpoint, [], $data);
    }
    
    // Execute a PUT request
    public function put($endpoint, $data = []) {
        return $this->request('PUT', $endpoint, [], $data);
    }
    
    // Execute a DELETE request
    public function delete($endpoint, $params = []) {
        return $this->request('DELETE', $endpoint, $params);
    }
    
    // Execute an API request
    private function request($method, $endpoint, $params = [], $data = null) {
        $url = $this->base_url . $endpoint;
        
        // Add query parameters
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Set headers
        $headers = [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Set request method and data
        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
                
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
                
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
                
            default:
                if ($data !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        $curl_error = null;
        if (curl_errno($ch)) {
            $curl_error = curl_error($ch);
        }
        
        curl_close($ch);
        
        if ($curl_error) {
            throw new Exception("Pillars API request failed: $curl_error");
        }
        
        $result = json_decode($response, true);
        
        if ($http_code >= 400) {
            $error_message = isset($result['message']) ? $result['message'] : 'Unknown error';
            throw new Exception("Pillars API error ($http_code): $error_message");
        }
        
        return $result;
    }
}

// Authenticate with Pillars by user credentials
function pillars_authenticate($email, $password) {
    try {
        $client = pillars_get_client();
        
        $response = $client->post('auth/login', [
            'email' => $email,
            'password' => $password
        ]);
        
        if (isset($response['token'])) {
            return $response;
        }
        
        return null;
        
    } catch (Exception $e) {
        log_event("Pillars authentication error: " . $e->getMessage(), 'error');
        return null;
    }
}

// Authenticate with Pillars by user ID
function pillars_authenticate_by_id($user_id) {
    try {
        $client = pillars_get_client();
        
        $response = $client->post('auth/login-by-id', [
            'user_id' => $user_id
        ]);
        
        if (isset($response['token'])) {
            return $response;
        }
        
        return null;
        
    } catch (Exception $e) {
        log_event("Pillars authentication by ID error: " . $e->getMessage(), 'error');
        return null;
    }
}

// Get user by ID
function pillars_get_user_by_id($user_id) {
    try {
        $client = pillars_get_client();
        
        $response = $client->get("users/$user_id");
        
        if (isset($response['user'])) {
            return $response['user'];
        }
        
        return null;
        
    } catch (Exception $e) {
        log_event("Pillars get user error: " . $e->getMessage(), 'error');
        return null;
    }
}

// Get user by email
function pillars_get_user_by_email($email) {
    try {
        $client = pillars_get_client();
        
        $response = $client->get('users/lookup', [
            'email' => $email
        ]);
        
        if (isset($response['user'])) {
            return $response['user'];
        }
        
        return null;
        
    } catch (Exception $e) {
        log_event("Pillars get user error: " . $e->getMessage(), 'error');
        return null;
    }
}

// Create a new user
function pillars_create_user($data) {
    try {
        $client = pillars_get_client();
        
        $response = $client->post('users', $data);
        
        if (isset($response['user'])) {
            return $response['user'];
        }
        
        return null;
        
    } catch (Exception $e) {
        log_event("Pillars create user error: " . $e->getMessage(), 'error');
        return null;
    }
}

// Update a user
function pillars_update_user($user_id, $data) {
    try {
        $client = pillars_get_client();
        
        $response = $client->put("users/$user_id", $data);
        
        if (isset($response['user'])) {
            return $response['user'];
        }
        
        return null;
        
    } catch (Exception $e) {
        log_event("Pillars update user error: " . $e->getMessage(), 'error');
        return null;
    }
}

// Create a new order
function pillars_create_order($data) {
    try {
        $client = pillars_get_client();
        
        $response = $client->post('orders', $data);
        
        if (isset($response['order'])) {
            return $response['order'];
        }
        
        return null;
        
    } catch (Exception $e) {
        log_event("Pillars create order error: " . $e->getMessage(), 'error');
        return null;
    }
}

// Get order by ID
function pillars_get_order_by_id($order_id) {
    try {
        $client = pillars_get_client();
        
        $response = $client->get("orders/$order_id");
        
        if (isset($response['order'])) {
            return $response['order'];
        }
        
        return null;
        
    } catch (Exception $e) {
        log_event("Pillars get order error: " . $e->getMessage(), 'error');
        return null;
    }
}

// Get user orders
function pillars_get_user_orders($user_id) {
    try {
        $client = pillars_get_client();
        
        $response = $client->get("users/$user_id/orders");
        
        if (isset($response['orders'])) {
            return $response['orders'];
        }
        
        return [];
        
    } catch (Exception $e) {
        log_event("Pillars get user orders error: " . $e->getMessage(), 'error');
        return [];
    }
}

// Get user commissions
function pillars_get_user_commissions($user_id) {
    try {
        $client = pillars_get_client();
        
        $response = $client->get("users/$user_id/commissions");
        
        if (isset($response['commissions'])) {
            return $response['commissions'];
        }
        
        return [];
        
    } catch (Exception $e) {
        log_event("Pillars get user commissions error: " . $e->getMessage(), 'error');
        return [];
    }
}

// Get user affiliates (downline)
function pillars_get_user_affiliates($user_id) {
    try {
        $client = pillars_get_client();
        
        $response = $client->get("users/$user_id/affiliates");
        
        if (isset($response['affiliates'])) {
            return $response['affiliates'];
        }
        
        return [];
        
    } catch (Exception $e) {
        log_event("Pillars get user affiliates error: " . $e->getMessage(), 'error');
        return [];
    }
}

// Generate affiliate link
function pillars_generate_affiliate_link($user_id) {
    try {
        $client = pillars_get_client();
        
        $response = $client->get("users/$user_id/affiliate-link");
        
        if (isset($response['link'])) {
            return $response['link'];
        }
        
        return null;
        
    } catch (Exception $e) {
        log_event("Pillars generate affiliate link error: " . $e->getMessage(), 'error');
        return null;
    }
}

// Process commission
function pillars_process_commission($data) {
    try {
        $client = pillars_get_client();
        
        $response = $client->post('commissions/process', $data);
        
        return $response !== null;
        
    } catch (Exception $e) {
        log_event("Pillars process commission error: " . $e->getMessage(), 'error');
        return false;
    }
}

// Get commission by ID
function pillars_get_commission_by_id($commission_id) {
    try {
        $client = pillars_get_client();
        
        $response = $client->get("commissions/$commission_id");
        
        if (isset($response['commission'])) {
            return $response['commission'];
        }
        
        return null;
        
    } catch (Exception $e) {
        log_event("Pillars get commission error: " . $e->getMessage(), 'error');
        return null;
    }
}

// Verify webhook signature
function pillars_verify_webhook($payload, $signature) {
    if (empty(WEBHOOK_SECRET)) {
        return false;
    }
    
    $expected_signature = hash_hmac('sha256', $payload, WEBHOOK_SECRET);
    
    return hash_equals($expected_signature, $signature);
}
?>
