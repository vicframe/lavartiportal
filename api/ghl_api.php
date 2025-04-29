<?php
/**
 * GoHighLevel API Wrapper
 * 
 * Provides methods to interact with the GoHighLevel API
 */

class GHL_API {
    private $api_key;
    private $location_id;
    private $api_base_url = 'https://rest.gohighlevel.com/v1/';

    /**
     * Constructor
     *
     * @param string $api_key GHL API Key
     * @param string $location_id GHL Location ID
     */
    public function __construct($api_key, $location_id) {
        $this->api_key = $api_key;
        $this->location_id = $location_id;
    }

    /**
     * Test API connection
     *
     * @return bool Success status
     */
    public function test_connection() {
        try {
            // Try to get location details as a simple test
            $response = $this->make_request('GET', 'locations/' . $this->location_id);
            return isset($response['id']);
        } catch (Exception $e) {
            error_log('GHL API connection test failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get contacts
     *
     * @param array $params Optional query parameters
     * @return array Contact data
     */
    public function get_contacts($params = []) {
        $response = $this->make_request('GET', 'contacts/', $params);
        return $response;
    }

    /**
     * Get contact by ID
     *
     * @param string $contact_id Contact ID
     * @return array Contact data
     */
    public function get_contact($contact_id) {
        $response = $this->make_request('GET', 'contacts/' . $contact_id);
        return $response;
    }

    /**
     * Create contact
     *
     * @param array $contact_data Contact data
     * @return array Created contact data
     */
    public function create_contact($contact_data) {
        $response = $this->make_request('POST', 'contacts/', null, $contact_data);
        return $response;
    }

    /**
     * Update contact
     *
     * @param string $contact_id Contact ID
     * @param array $contact_data Contact data
     * @return array Updated contact data
     */
    public function update_contact($contact_id, $contact_data) {
        $response = $this->make_request('PUT', 'contacts/' . $contact_id, null, $contact_data);
        return $response;
    }

    /**
     * Get orders
     *
     * @param array $params Optional query parameters
     * @return array Order data
     */
    public function get_orders($params = []) {
        $response = $this->make_request('GET', 'orders/', $params);
        return $response;
    }

    /**
     * Get order by ID
     *
     * @param string $order_id Order ID
     * @return array Order data
     */
    public function get_order($order_id) {
        $response = $this->make_request('GET', 'orders/' . $order_id);
        return $response;
    }

    /**
     * Create order
     *
     * @param array $order_data Order data
     * @return array Created order data
     */
    public function create_order($order_data) {
        $response = $this->make_request('POST', 'orders/', null, $order_data);
        return $response;
    }
    
    /**
     * Update order status
     *
     * @param string $order_id Order ID
     * @param string $status New status
     * @return array Updated order data
     */
    public function update_order_status($order_id, $status) {
        $data = [
            'status' => $status
        ];
        
        $response = $this->make_request('PUT', 'orders/' . $order_id, null, $data);
        return $response;
    }

    /**
     * Make API request
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @param array $data Request body data
     * @return array Response data
     */
    private function make_request($method, $endpoint, $params = null, $data = null) {
        $url = $this->api_base_url . $endpoint;
        
        // Add query parameters if provided
        if ($params) {
            $url .= '?' . http_build_query($params);
        }
        
        $ch = curl_init($url);
        
        // Set request method
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        // Set request headers
        $headers = [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Set request body if provided
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        // Set response options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        // Execute request
        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Check for cURL errors
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('cURL error: ' . $error);
        }
        
        curl_close($ch);
        
        // Parse response
        $response_data = json_decode($response, true);
        
        // Check for API errors
        if ($http_status >= 400) {
            $error_message = isset($response_data['message']) ? $response_data['message'] : 'API error';
            throw new Exception('GHL API error: ' . $error_message . ' (HTTP status: ' . $http_status . ')');
        }
        
        return $response_data;
    }
}

/**
 * Get GHL API instance
 *
 * @return GHL_API GHL API instance
 */
function ghl_api() {
    static $instance = null;
    
    if ($instance === null) {
        $api_key = getenv('GHL_API_KEY');
        $location_id = getenv('GHL_LOCATION_ID');
        
        if (!$api_key || !$location_id) {
            throw new Exception('GHL API Key or Location ID not configured');
        }
        
        $instance = new GHL_API($api_key, $location_id);
    }
    
    return $instance;
}