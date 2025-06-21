<?php
/**
 * Pillars API Wrapper
 * 
 * Provides methods to interact with the Pillars API
 */
 
class Pillars_API {
    private $api_key;
    private $api_base_url = 'https://api.pillars.com/v1/'; // Example URL, replace with actual Pillars API URL

    /**
     * Constructor
     *
     * @param string $api_key Pillars API Key
     */
    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    /**
     * Test API connection
     *
     * @return bool Success status
     */
    public function test_connection() {
        try {
            // Try to get account details as a simple test
            $response = $this->make_request('GET', 'account');
            return isset($response['id']);
        } catch (Exception $e) {
            error_log('Pillars API connection test failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get members
     *
     * @param array $params Optional query parameters
     * @return array Member data
     */
    public function get_members($params = []) {
        $response = $this->make_request('GET', 'members/', $params);
        return $response;
    }

    /**
     * Get member by ID
     *
     * @param string $member_id Member ID
     * @return array Member data
     */
    public function get_member($member_id) {
        $response = $this->make_request('GET', 'members/' . $member_id);
        return $response;
    }

    /**
     * Create member
     *
     * @param array $member_data Member data
     * @return array Created member data
     */
    public function create_member($member_data) {
        $response = $this->make_request('POST', 'members/', null, $member_data);
        return $response;
    }

    /**
     * Update member
     *
     * @param string $member_id Member ID
     * @param array $member_data Member data
     * @return array Updated member data
     */
    public function update_member($member_id, $member_data) {
        $response = $this->make_request('PUT', 'members/' . $member_id, null, $member_data);
        return $response;
    }

    /**
     * Get commissions
     *
     * @param array $params Optional query parameters
     * @return array Commission data
     */
    public function get_commissions($params = []) {
        $response = $this->make_request('GET', 'commissions/', $params);
        return $response;
    }

    /**
     * Get commission by ID
     *
     * @param string $commission_id Commission ID
     * @return array Commission data
     */
    public function get_commission($commission_id) {
        $response = $this->make_request('GET', 'commissions/' . $commission_id);
        return $response;
    }

    /**
     * Create commission
     *
     * @param array $commission_data Commission data
     * @return array Created commission data
     */
    public function create_commission($commission_data) {
        $response = $this->make_request('POST', 'commissions/', null, $commission_data);
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
            throw new Exception('Pillars API error: ' . $error_message . ' (HTTP status: ' . $http_status . ')');
        }
        
        return $response_data;
    }
}

/**
 * Get Pillars API instance
 *
 * @return Pillars_API Pillars API instance
 */
function pillars_api() {
    static $instance = null;
    
    if ($instance === null) {
        $api_key = getenv('PILLARS_API_KEY');
        
        if (!$api_key) {
            throw new Exception('Pillars API Key not configured');
        }
        
        $instance = new Pillars_API($api_key);
    }
    
    return $instance;
}