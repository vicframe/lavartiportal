<?php
/**
 * Pillars API Integration
 * 
 * Functions to interact with the Pillars API for data synchronization
 */

/**
 * Get Pillars API settings from the database
 * 
 * @return array Array containing api_url, api_key, and other settings
 * @throws Exception If settings are not configured
 */
function get_pillars_api_settings() {
    // Get Pillars API settings from database
    $result = db_query("SELECT * FROM integration_settings WHERE integration_name = 'pillars'");
    $settings = db_fetch_one($result);
    
    if (!$settings || empty($settings['api_url']) || empty($settings['api_key'])) {
        throw new Exception('Pillars API settings not configured');
    }
    
    return [
        'api_url' => rtrim($settings['api_url'], '/'),
        'api_key' => $settings['api_key'],
        'organization_id' => isset($settings['organization_id']) ? $settings['organization_id'] : null
    ];
}

/**
 * Make a request to the Pillars API
 * 
 * @param string $endpoint API endpoint (without leading slash)
 * @param string $method HTTP method (GET, POST, PUT, DELETE)
 * @param array $data Request payload data
 * @return array|null Response data
 * @throws Exception On request failure
 */
function pillars_api_request($endpoint, $method = 'GET', $data = null) {
    try {
        $settings = get_pillars_api_settings();
        $url = $settings['api_url'] . '/' . ltrim($endpoint, '/');
        
        // Initialize cURL session
        $ch = curl_init($url);
        
        // Set common options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Set headers
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-API-Key: ' . $settings['api_key']
        ];
        
        // Add Organization ID header if available
        if (!empty($settings['organization_id'])) {
            $headers[] = 'X-Organization-ID: ' . $settings['organization_id'];
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Set method-specific options
        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
                
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
                
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
                
            case 'GET':
                if ($data) {
                    $url .= '?' . http_build_query($data);
                    curl_setopt($ch, CURLOPT_URL, $url);
                }
                break;
        }
        
        // Log the API call
        $logData = [
            'integration' => 'pillars',
            'endpoint' => $endpoint,
            'method' => $method,
            'request' => $data ? json_encode($data) : null,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $logId = db_insert('sync_logs', $logData);
        
        // Execute the request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        // Update the log with the response
        $logUpdateData = [
            'response' => $response,
            'status_code' => $httpCode,
            'completed' => 1
        ];
        
        if ($error) {
            $logUpdateData['error'] = $error;
            $logUpdateData['success'] = 0;
        } else {
            $logUpdateData['success'] = ($httpCode >= 200 && $httpCode < 300) ? 1 : 0;
            
            if ($httpCode >= 400) {
                $logUpdateData['error'] = "HTTP Error $httpCode: $response";
            }
        }
        
        db_update('sync_logs', $logUpdateData, ['id' => $logId]);
        
        // Handle errors
        if ($error) {
            throw new Exception("cURL Error: $error");
        }
        
        if ($httpCode >= 400) {
            $errorData = json_decode($response, true);
            $errorMessage = isset($errorData['message']) ? $errorData['message'] : "HTTP Error: $httpCode";
            throw new Exception("API Error ($httpCode): $errorMessage");
        }
        
        // Return the response data
        return json_decode($response, true);
    } catch (Exception $e) {
        error_log('Pillars API Request Error: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Update the sync status of a record
 * 
 * @param string $table The table name (users, orders, etc)
 * @param int $id The record ID
 * @param string $status The sync status (pending, synced, error)
 * @param string $message Optional status message
 * @return bool Success indicator
 */
function update_sync_status($table, $id, $status, $message = '') {
    try {
        $data = [
            'sync_status' => $status,
            'sync_message' => $message,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        db_update($table, $data, ['id' => $id]);
        
        // Also log this in sync_history for auditing
        $historyData = [
            'table_name' => $table,
            'record_id' => $id,
            'status' => $status,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        db_insert('sync_history', $historyData);
        
        return true;
    } catch (Exception $e) {
        error_log('Error updating sync status: ' . $e->getMessage());
        return false;
    }
}

/**
 * Send user data to Pillars API
 * 
 * @param array $userData The prepared user data for Pillars API
 * @return array Response data from Pillars API
 * @throws Exception On API request failure
 */
function pillars_api_send_user($userData) {
    // Make sure required fields are present
    if (empty($userData['email'])) {
        throw new Exception('User email is required for Pillars API');
    }
    
    // If no firstName or lastName, set defaults from email
    if (empty($userData['firstName']) && empty($userData['lastName'])) {
        $emailParts = explode('@', $userData['email']);
        $userData['firstName'] = $emailParts[0];
        $userData['lastName'] = 'User';
    }
    
    // Determine if this is a new user or update based on externalId
    $endpoint = 'users';
    $method = 'POST';
    
    if (!empty($userData['externalId'])) {
        // Check if user already exists in Pillars by external ID
        try {
            $existingUser = pillars_api_request("users/external/{$userData['externalId']}", 'GET');
            if ($existingUser && isset($existingUser['id'])) {
                // Update existing user
                $endpoint = "users/{$existingUser['id']}";
                $method = 'PUT';
            }
        } catch (Exception $e) {
            // User doesn't exist yet, continue with create
            error_log("User with externalId {$userData['externalId']} not found in Pillars, will create new: " . $e->getMessage());
        }
    }
    
    // Send the user data to Pillars
    return pillars_api_request($endpoint, $method, $userData);
}

/**
 * Send order data to Pillars API
 * 
 * @param array $orderData The prepared order data for Pillars API
 * @return array Response data from Pillars API
 * @throws Exception On API request failure
 */
function pillars_api_send_order($orderData) {
    // Make sure required fields are present
    if (empty($orderData['externalId'])) {
        throw new Exception('Order externalId is required for Pillars API');
    }
    
    if (empty($orderData['customerExternalId']) && !isset($orderData['customerId'])) {
        throw new Exception('Either customerExternalId or customerId is required for Pillars API');
    }
    
    // Determine if this is a new order or update based on externalId
    $endpoint = 'orders';
    $method = 'POST';
    
    // Check if order already exists in Pillars by external ID
    try {
        $existingOrder = pillars_api_request("orders/external/{$orderData['externalId']}", 'GET');
        if ($existingOrder && isset($existingOrder['id'])) {
            // Update existing order
            $endpoint = "orders/{$existingOrder['id']}";
            $method = 'PUT';
        }
    } catch (Exception $e) {
        // Order doesn't exist yet, continue with create
        error_log("Order with externalId {$orderData['externalId']} not found in Pillars, will create new: " . $e->getMessage());
    }
    
    // Send the order data to Pillars
    return pillars_api_request($endpoint, $method, $orderData);
}

/**
 * Get user data from Pillars API by external ID
 * 
 * @param string $externalId The external ID of the user
 * @return array|null User data or null if not found
 */
function pillars_api_get_user_by_external_id($externalId) {
    try {
        return pillars_api_request("users/external/{$externalId}", 'GET');
    } catch (Exception $e) {
        error_log("Error getting user from Pillars API: " . $e->getMessage());
        return null;
    }
}

/**
 * Get order data from Pillars API by external ID
 * 
 * @param string $externalId The external ID of the order
 * @return array|null Order data or null if not found
 */
function pillars_api_get_order_by_external_id($externalId) {
    try {
        return pillars_api_request("orders/external/{$externalId}", 'GET');
    } catch (Exception $e) {
        error_log("Error getting order from Pillars API: " . $e->getMessage());
        return null;
    }
}

/**
 * Retry failed synchronizations
 * 
 * @param string $table The table to check for failed syncs
 * @param int $limit Maximum number of records to retry
 * @return array Results of retry attempts
 */
function pillars_api_retry_failed_syncs($table, $limit = 10) {
    $results = [
        'success' => 0,
        'failure' => 0,
        'details' => []
    ];
    
    try {
        // Get records that failed to sync
        $query = "SELECT * FROM {$table} WHERE sync_status = 'error' ORDER BY updated_at DESC LIMIT ?";
        $stmt = db_query($query, [$limit]);
        $records = db_fetch_all($stmt);
        
        foreach ($records as $record) {
            try {
                if ($table === 'users') {
                    // Transform to Pillars format and retry
                    $pillarsData = transformUserToPillarsFormat($record);
                    $response = pillars_api_send_user($pillarsData);
                    
                    update_sync_status($table, $record['id'], 'synced', 'Retry successful');
                    $results['success']++;
                    $results['details'][] = [
                        'id' => $record['id'],
                        'type' => 'user',
                        'status' => 'success'
                    ];
                } 
                else if ($table === 'orders') {
                    // Transform to Pillars format and retry
                    $pillarsData = transformOrderToPillarsFormat($record);
                    $response = pillars_api_send_order($pillarsData);
                    
                    update_sync_status($table, $record['id'], 'synced', 'Retry successful');
                    $results['success']++;
                    $results['details'][] = [
                        'id' => $record['id'],
                        'type' => 'order',
                        'status' => 'success'
                    ];
                }
            } catch (Exception $e) {
                update_sync_status($table, $record['id'], 'error', $e->getMessage());
                $results['failure']++;
                $results['details'][] = [
                    'id' => $record['id'],
                    'type' => ($table === 'users') ? 'user' : 'order',
                    'status' => 'failure',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    } catch (Exception $e) {
        error_log('Error retrying failed syncs: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Transform a user record from the database to Pillars format
 * 
 * @param array $user User record from the database
 * @return array Transformed data for Pillars API
 */
function transformUserToPillarsFormat($user) {
    return [
        'externalId' => isset($user['ghl_id']) ? $user['ghl_id'] : 'user-' . $user['id'],
        'firstName' => $user['first_name'],
        'lastName' => $user['last_name'],
        'email' => $user['email'],
        'phone' => isset($user['phone']) ? $user['phone'] : '',
        'status' => isset($user['status']) ? mapUserStatus($user['status']) : 'active',
        'type' => 'affiliate', // Default to affiliate
        'address' => [
            'address1' => isset($user['address_line1']) ? $user['address_line1'] : '',
            'address2' => isset($user['address_line2']) ? $user['address_line2'] : '',
            'city' => isset($user['city']) ? $user['city'] : '',
            'state' => isset($user['state']) ? $user['state'] : '',
            'postalCode' => isset($user['postal_code']) ? $user['postal_code'] : '',
            'country' => isset($user['country']) ? $user['country'] : 'US'
        ]
    ];
}

/**
 * Transform an order record from the database to Pillars format
 * 
 * @param array $order Order record from the database
 * @return array Transformed data for Pillars API
 */
function transformOrderToPillarsFormat($order) {
    // Get the user for this order
    $userId = $order['user_id'];
    $result = db_query("SELECT * FROM users WHERE id = ?", [$userId]);
    $user = db_fetch_one($result);
    
    // Get the order items
    $result = db_query("SELECT * FROM order_items WHERE order_id = ?", [$order['id']]);
    $items = db_fetch_all($result);
    
    $pillarsItems = [];
    foreach ($items as $item) {
        $pillarsItems[] = [
            'externalId' => 'item-' . $item['id'],
            'name' => $item['product_name'],
            'sku' => isset($item['sku']) ? $item['sku'] : '',
            'price' => (float)$item['price'],
            'quantity' => (int)$item['quantity'],
            'subtotal' => (float)$item['price'] * (int)$item['quantity']
        ];
    }
    
    return [
        'externalId' => isset($order['ghl_id']) ? $order['ghl_id'] : 'order-' . $order['id'],
        'customerExternalId' => isset($user['ghl_id']) ? $user['ghl_id'] : 'user-' . $user['id'],
        'orderDate' => isset($order['order_date']) ? $order['order_date'] : date('Y-m-d'),
        'status' => mapOrderStatus($order['status']),
        'total' => (float)$order['amount'],
        'subtotal' => (float)$order['amount'],
        'currency' => 'USD',
        'items' => $pillarsItems
    ];
}

/**
 * Map internal user status to Pillars status
 * 
 * @param string $status Internal user status
 * @return string Pillars user status
 */
function mapUserStatus($status) {
    $statusMap = [
        'active' => 'active',
        'inactive' => 'inactive',
        'pending' => 'pending',
        'suspended' => 'inactive'
    ];
    
    return isset($statusMap[$status]) ? $statusMap[$status] : 'active';
}

/**
 * Map internal order status to Pillars order status
 * 
 * @param string $status Internal order status
 * @return string Pillars order status
 */
function mapOrderStatus($status) {
    $statusMap = [
        'completed' => 'completed',
        'processing' => 'processing',
        'pending' => 'pending',
        'cancelled' => 'cancelled',
        'refunded' => 'refunded',
        'failed' => 'failed'
    ];
    
    return isset($statusMap[$status]) ? $statusMap[$status] : 'pending';
}
?>