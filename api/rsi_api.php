<?php
/**
 * RSI (Travel Products) API Integration
 */

// Get RSI API client
function rsi_get_client() {
    return new RSIApiClient();
}

// RSI API Client Class
class RSIApiClient {
    private $api_key;
    private $base_url;
    
    public function __construct() {
        $this->api_key = RSI_API_KEY;
        $this->base_url = RSI_API_URL;
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
            throw new Exception("RSI API request failed: $curl_error");
        }
        
        $result = json_decode($response, true);
        
        if ($http_code >= 400) {
            $error_message = isset($result['message']) ? $result['message'] : 'Unknown error';
            throw new Exception("RSI API error ($http_code): $error_message");
        }
        
        return $result;
    }
}

// Get travel products
function rsi_get_travel_products() {
    try {
        $client = rsi_get_client();
        
        $response = $client->get('products/travel');
        
        if (isset($response['products'])) {
            return $response['products'];
        }
        
        return [];
        
    } catch (Exception $e) {
        log_event("RSI get travel products error: " . $e->getMessage(), 'error');
        return [];
    }
}

// Get user travel credits
function rsi_get_user_travel_credits($user_id) {
    try {
        $client = rsi_get_client();
        
        $response = $client->get("users/$user_id/travel-credits");
        
        if (isset($response['credits'])) {
            return $response['credits'];
        }
        
        return 0;
        
    } catch (Exception $e) {
        log_event("RSI get user travel credits error: " . $e->getMessage(), 'error');
        return 0;
    }
}

// Process travel dollars
function rsi_process_travel_dollars($data) {
    try {
        $client = rsi_get_client();
        
        $response = $client->post('travel-dollars/process', $data);
        
        return $response !== null;
        
    } catch (Exception $e) {
        log_event("RSI process travel dollars error: " . $e->getMessage(), 'error');
        return false;
    }
}

// Register user with RSI
function rsi_register_user($data) {
    try {
        $client = rsi_get_client();
        
        $response = $client->post('users/register', $data);
        
        if (isset($response['user'])) {
            return $response['user'];
        }
        
        return null;
        
    } catch (Exception $e) {
        log_event("RSI register user error: " . $e->getMessage(), 'error');
        return null;
    }
}

// Get user travel activity
function rsi_get_user_travel_activity($user_id) {
    try {
        $client = rsi_get_client();
        
        $response = $client->get("users/$user_id/travel-activity");
        
        if (isset($response['activity'])) {
            return $response['activity'];
        }
        
        return [];
        
    } catch (Exception $e) {
        log_event("RSI get user travel activity error: " . $e->getMessage(), 'error');
        return [];
    }
}

// Book travel product
function rsi_book_travel_product($data) {
    try {
        $client = rsi_get_client();
        
        $response = $client->post('bookings', $data);
        
        if (isset($response['booking'])) {
            return $response['booking'];
        }
        
        return null;
        
    } catch (Exception $e) {
        log_event("RSI book travel product error: " . $e->getMessage(), 'error');
        return null;
    }
}

// Get booking by ID
function rsi_get_booking_by_id($booking_id) {
    try {
        $client = rsi_get_client();
        
        $response = $client->get("bookings/$booking_id");
        
        if (isset($response['booking'])) {
            return $response['booking'];
        }
        
        return null;
        
    } catch (Exception $e) {
        log_event("RSI get booking error: " . $e->getMessage(), 'error');
        return null;
    }
}

// Get user bookings
function rsi_get_user_bookings($user_id) {
    try {
        $client = rsi_get_client();
        
        $response = $client->get("users/$user_id/bookings");
        
        if (isset($response['bookings'])) {
            return $response['bookings'];
        }
        
        return [];
        
    } catch (Exception $e) {
        log_event("RSI get user bookings error: " . $e->getMessage(), 'error');
        return [];
    }
}

// Verify webhook signature
function rsi_verify_webhook($payload, $signature) {
    if (empty(WEBHOOK_SECRET)) {
        return false;
    }
    
    $expected_signature = hash_hmac('sha256', $payload, WEBHOOK_SECRET);
    
    return hash_equals($expected_signature, $signature);
}
?>
