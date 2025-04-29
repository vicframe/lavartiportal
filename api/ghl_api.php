<?php
/**
 * GoHighLevel API Integration
 */

// Get GHL API client
function ghl_get_client() {
    return new GHLApiClient();
}

// GoHighLevel API Client Class
class GHLApiClient {
    private $api_key;
    private $base_url;
    private $location_id;
    
    public function __construct() {
        $this->api_key = GHL_API_KEY;
        $this->base_url = GHL_API_URL;
        $this->location_id = GHL_LOCATION_ID;
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
        
        if ($this->location_id) {
            $headers[] = 'Location-ID: ' . $this->location_id;
        }
        
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
            throw new Exception("GHL API request failed: $curl_error");
        }
        
        $result = json_decode($response, true);
        
        if ($http_code >= 400) {
            $error_message = isset($result['message']) ? $result['message'] : 'Unknown error';
            throw new Exception("GHL API error ($http_code): $error_message");
        }
        
        return $result;
    }
}

// Authenticate user with GHL
function ghl_authenticate($email, $password) {
    try {
        // For testing purposes - simulate authentication with test credentials
        if ($email === 'test@example.com' && $password === 'password123') {
            return [
                'id' => 'test_user_123',
                'email' => 'test@example.com',
                'firstName' => 'Test',
                'lastName' => 'User',
                'phone' => '555-123-4567'
            ];
        }
        
        // In production environment, use actual GHL API
        $client = ghl_get_client();
        
        // Note: This is a simulated endpoint as GHL doesn't have a public API for direct authentication
        // In a real implementation, you would likely use OAuth or another authentication method
        $response = $client->post('auth/login', [
            'email' => $email,
            'password' => $password
        ]);
        
        if (isset($response['contact'])) {
            return $response['contact'];
        }
        
        return null;
        
    } catch (Exception $e) {
        log_event("GHL authentication error: " . $e->getMessage(), 'error');
        return null;
    }
}

// Get contact by email
function ghl_get_contact_by_email($email) {
    try {
        $client = ghl_get_client();
        
        $response = $client->get('contacts/lookup', [
            'email' => $email
        ]);
        
        if (isset($response['contacts']) && count($response['contacts']) > 0) {
            return $response['contacts'][0];
        }
        
        return null;
        
    } catch (Exception $e) {
        log_event("GHL get contact error: " . $e->getMessage(), 'error');
        return null;
    }
}

// Get contact by ID
function ghl_get_contact_by_id($contact_id) {
    try {
        // For testing purposes - simulate contact lookup with test user
        if ($contact_id === 'test_user_123') {
            return [
                'id' => 'test_user_123',
                'email' => 'test@example.com',
                'firstName' => 'Test',
                'lastName' => 'User',
                'phone' => '555-123-4567'
            ];
        }
        
        $client = ghl_get_client();
        
        $response = $client->get("contacts/$contact_id");
        
        if (isset($response['contact'])) {
            return $response['contact'];
        }
        
        return null;
        
    } catch (Exception $e) {
        log_event("GHL get contact error: " . $e->getMessage(), 'error');
        return null;
    }
}

// Create a new contact
function ghl_create_contact($data) {
    try {
        $client = ghl_get_client();
        
        $response = $client->post('contacts', $data);
        
        if (isset($response['contact'])) {
            return $response['contact'];
        }
        
        return null;
        
    } catch (Exception $e) {
        log_event("GHL create contact error: " . $e->getMessage(), 'error');
        return null;
    }
}

// Update a contact
function ghl_update_contact($contact_id, $data) {
    try {
        $client = ghl_get_client();
        
        $response = $client->put("contacts/$contact_id", $data);
        
        if (isset($response['contact'])) {
            return $response['contact'];
        }
        
        return null;
        
    } catch (Exception $e) {
        log_event("GHL update contact error: " . $e->getMessage(), 'error');
        return null;
    }
}

// Get order by ID
function ghl_get_order_by_id($order_id) {
    try {
        $client = ghl_get_client();
        
        $response = $client->get("orders/$order_id");
        
        if (isset($response['order'])) {
            return $response['order'];
        }
        
        return null;
        
    } catch (Exception $e) {
        log_event("GHL get order error: " . $e->getMessage(), 'error');
        return null;
    }
}

// Get orders for a contact
function ghl_get_contact_orders($contact_id) {
    try {
        $client = ghl_get_client();
        
        $response = $client->get("contacts/$contact_id/orders");
        
        if (isset($response['orders'])) {
            return $response['orders'];
        }
        
        return [];
        
    } catch (Exception $e) {
        log_event("GHL get contact orders error: " . $e->getMessage(), 'error');
        return [];
    }
}

// Create a new order
function ghl_create_order($data) {
    try {
        $client = ghl_get_client();
        
        $response = $client->post('orders', $data);
        
        if (isset($response['order'])) {
            return $response['order'];
        }
        
        return null;
        
    } catch (Exception $e) {
        log_event("GHL create order error: " . $e->getMessage(), 'error');
        return null;
    }
}

// Update an order
function ghl_update_order($order_id, $data) {
    try {
        $client = ghl_get_client();
        
        $response = $client->put("orders/$order_id", $data);
        
        if (isset($response['order'])) {
            return $response['order'];
        }
        
        return null;
        
    } catch (Exception $e) {
        log_event("GHL update order error: " . $e->getMessage(), 'error');
        return null;
    }
}

// Get product by ID
function ghl_get_product_by_id($product_id) {
    try {
        $client = ghl_get_client();
        
        $response = $client->get("products/$product_id");
        
        if (isset($response['product'])) {
            return $response['product'];
        }
        
        return null;
        
    } catch (Exception $e) {
        log_event("GHL get product error: " . $e->getMessage(), 'error');
        return null;
    }
}

// Get all products
function ghl_get_products() {
    try {
        $client = ghl_get_client();
        
        $response = $client->get('products');
        
        if (isset($response['products'])) {
            return $response['products'];
        }
        
        return [];
        
    } catch (Exception $e) {
        log_event("GHL get products error: " . $e->getMessage(), 'error');
        return [];
    }
}

// Add tag to contact
function ghl_add_tag_to_contact($contact_id, $tag) {
    try {
        $client = ghl_get_client();
        
        $response = $client->post("contacts/$contact_id/tags", [
            'tags' => [$tag]
        ]);
        
        return $response !== null;
        
    } catch (Exception $e) {
        log_event("GHL add tag error: " . $e->getMessage(), 'error');
        return false;
    }
}

// Remove tag from contact
function ghl_remove_tag_from_contact($contact_id, $tag) {
    try {
        $client = ghl_get_client();
        
        $response = $client->delete("contacts/$contact_id/tags", [
            'tags' => [$tag]
        ]);
        
        return $response !== null;
        
    } catch (Exception $e) {
        log_event("GHL remove tag error: " . $e->getMessage(), 'error');
        return false;
    }
}

// Verify webhook signature
function ghl_verify_webhook($payload, $signature) {
    if (empty(WEBHOOK_SECRET)) {
        return false;
    }
    
    $expected_signature = hash_hmac('sha256', $payload, WEBHOOK_SECRET);
    
    return hash_equals($expected_signature, $signature);
}
?>
