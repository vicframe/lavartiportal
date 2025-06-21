<?php
/**
 * Webhook Data Transformers
 * 
 * Functions to transform data between different integration platforms
 */

/**
 * Transform GoHighLevel webhook user data to Pillars format
 * 
 * @param array $ghlData The GoHighLevel webhook data
 * @return array The transformed data ready for Pillars API
 */
function transformGhlToPillarsUser($ghlData) {
    // Initialize the Pillars user array with default values
    $pillarsUser = [
        'externalId' => isset($ghlData['id']) ? (string)$ghlData['id'] : '',
        'firstName' => '',
        'lastName' => '',
        'email' => isset($ghlData['emailAddress']) ? $ghlData['emailAddress'] : '',
        'phone' => '',
        'profileImage' => isset($ghlData['profileImage']) ? $ghlData['profileImage'] : null,
        'enrollDate' => isset($ghlData['enrollDate']) ? $ghlData['enrollDate'] : date('Y-m-d'),
        'status' => 'active',
        'type' => 'affiliate',
        'sponsorId' => null,
        'address' => [
            'address1' => '',
            'address2' => '',
            'city' => '',
            'state' => '',
            'postalCode' => '',
            'country' => 'US'
        ],
        'customFields' => []
    ];
    
    // Extract full name into first and last name
    if (isset($ghlData['fullName'])) {
        $nameParts = explode(' ', trim($ghlData['fullName']), 2);
        $pillarsUser['firstName'] = $nameParts[0];
        $pillarsUser['lastName'] = isset($nameParts[1]) ? $nameParts[1] : '';
    }

    // If there's a company, set it as the last name if last name is empty
    if (empty($pillarsUser['lastName']) && isset($ghlData['customData']['company'])) {
        $pillarsUser['lastName'] = $ghlData['customData']['company'];
    }
    
    // Extract phone number
    if (isset($ghlData['phoneNumbers']) && is_array($ghlData['phoneNumbers']) && !empty($ghlData['phoneNumbers'])) {
        foreach ($ghlData['phoneNumbers'] as $phone) {
            if (isset($phone['number'])) {
                $pillarsUser['phone'] = preg_replace('/[^0-9]/', '', $phone['number']);
                break; // Just take the first phone number
            }
        }
    }
    
    // Extract address
    if (isset($ghlData['addresses']) && is_array($ghlData['addresses']) && !empty($ghlData['addresses'])) {
        foreach ($ghlData['addresses'] as $address) {
            if (isset($address['type']) && $address['type'] == 'primary') {
                $pillarsUser['address']['address1'] = isset($address['line1']) ? $address['line1'] : '';
                $pillarsUser['address']['address2'] = isset($address['line2']) ? $address['line2'] : '';
                $pillarsUser['address']['city'] = isset($address['city']) ? $address['city'] : '';
                $pillarsUser['address']['state'] = isset($address['stateCode']) ? $address['stateCode'] : '';
                $pillarsUser['address']['postalCode'] = isset($address['zip']) ? $address['zip'] : '';
                
                // Map country code properly
                if (isset($address['countryCode'])) {
                    $countryMap = [
                        'United States' => 'US',
                        'Canada' => 'CA',
                        'Mexico' => 'MX'
                        // Add more mappings as needed
                    ];
                    
                    $pillarsUser['address']['country'] = isset($countryMap[$address['countryCode']]) 
                        ? $countryMap[$address['countryCode']] 
                        : 'US'; // Default to US
                }
                
                break; // Just use the primary address
            }
        }
    }
    
    // Extract custom fields
    if (isset($ghlData['customData']) && is_array($ghlData['customData'])) {
        // Map specific custom fields
        if (isset($ghlData['customData']['enrollerId'])) {
            $pillarsUser['sponsorId'] = $ghlData['customData']['enrollerId'];
        }
        
        if (isset($ghlData['customData']['birthDate'])) {
            $pillarsUser['customFields']['birthDate'] = $ghlData['customData']['birthDate'];
        }
        
        if (isset($ghlData['customData']['portalAccess'])) {
            $pillarsUser['customFields']['membershipLevel'] = $ghlData['customData']['portalAccess'];
        }
        
        if (isset($ghlData['customData']['sku'])) {
            $pillarsUser['customFields']['productSku'] = $ghlData['customData']['sku'];
        }
        
        // Include any other custom fields that might be useful for Pillars
        if (isset($ghlData['customData']['displayName'])) {
            $pillarsUser['customFields']['productName'] = $ghlData['customData']['displayName'];
        }
        
        if (isset($ghlData['customData']['company'])) {
            $pillarsUser['customFields']['company'] = $ghlData['customData']['company'];
        }
    }
    
    // Get the correct user type based on GHL data
    if (isset($ghlData['customerType']) && isset($ghlData['customerType']['name'])) {
        $userType = strtolower($ghlData['customerType']['name']);
        if (strpos($userType, 'affiliate') !== false) {
            $pillarsUser['type'] = 'affiliate';
        } elseif (strpos($userType, 'customer') !== false) {
            $pillarsUser['type'] = 'customer';
        }
    }
    
    // Return the transformed user data
    return $pillarsUser;
}

/**
 * Transform GoHighLevel webhook order data to Pillars format
 * 
 * @param array $ghlData The GoHighLevel webhook order data
 * @return array The transformed data ready for Pillars API
 */
function transformGhlToPillarsOrder($ghlData) {
    // Initialize the Pillars order with default values
    $pillarsOrder = [
        'externalId' => isset($ghlData['id']) ? (string)$ghlData['id'] : '',
        'customerExternalId' => isset($ghlData['customerId']) ? (string)$ghlData['customerId'] : '',
        'orderDate' => isset($ghlData['orderDate']) ? $ghlData['orderDate'] : date('Y-m-d'),
        'status' => 'pending',
        'total' => 0,
        'subtotal' => 0,
        'tax' => 0,
        'shipping' => 0,
        'discount' => 0,
        'currency' => 'USD',
        'items' => [],
        'shippingAddress' => null,
        'billingAddress' => null,
        'customFields' => []
    ];
    
    // Extract order details
    if (isset($ghlData['total'])) {
        $pillarsOrder['total'] = (float)$ghlData['total'];
        $pillarsOrder['subtotal'] = (float)$ghlData['total']; // Default subtotal to total if no breakdown
    }
    
    if (isset($ghlData['status'])) {
        // Map GHL status to Pillars status
        $statusMap = [
            'completed' => 'completed',
            'processing' => 'processing',
            'pending' => 'pending',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'failed' => 'failed'
        ];
        
        $ghlStatus = strtolower($ghlData['status']);
        $pillarsOrder['status'] = isset($statusMap[$ghlStatus]) ? $statusMap[$ghlStatus] : 'pending';
    }
    
    // Handle order items
    if (isset($ghlData['items']) && is_array($ghlData['items'])) {
        foreach ($ghlData['items'] as $item) {
            $pillarsItem = [
                'externalId' => isset($item['id']) ? (string)$item['id'] : '',
                'name' => isset($item['name']) ? $item['name'] : 'Product',
                'sku' => isset($item['sku']) ? $item['sku'] : '',
                'price' => isset($item['price']) ? (float)$item['price'] : 0,
                'quantity' => isset($item['quantity']) ? (int)$item['quantity'] : 1,
                'subtotal' => isset($item['price']) && isset($item['quantity']) ? 
                    (float)$item['price'] * (int)$item['quantity'] : 0
            ];
            
            $pillarsOrder['items'][] = $pillarsItem;
        }
    } 
    // If no items array, try to create one from the product info
    else if (isset($ghlData['productName']) || isset($ghlData['customData']['displayName'])) {
        $productName = isset($ghlData['productName']) ? $ghlData['productName'] : 
            (isset($ghlData['customData']['displayName']) ? $ghlData['customData']['displayName'] : 'Product');
        
        $sku = isset($ghlData['customData']['sku']) ? $ghlData['customData']['sku'] : '';
        
        $pillarsItem = [
            'externalId' => $pillarsOrder['externalId'] . '-1', // Create a faux item ID
            'name' => $productName,
            'sku' => $sku,
            'price' => $pillarsOrder['total'],
            'quantity' => 1,
            'subtotal' => $pillarsOrder['total']
        ];
        
        $pillarsOrder['items'][] = $pillarsItem;
    }
    
    // Extract shipping address if available
    if (isset($ghlData['addresses']) && is_array($ghlData['addresses']) && !empty($ghlData['addresses'])) {
        foreach ($ghlData['addresses'] as $address) {
            if (isset($address['type']) && $address['type'] == 'primary') {
                $pillarsOrder['shippingAddress'] = [
                    'firstName' => isset($ghlData['firstName']) ? $ghlData['firstName'] : '',
                    'lastName' => isset($ghlData['lastName']) ? $ghlData['lastName'] : '',
                    'address1' => isset($address['line1']) ? $address['line1'] : '',
                    'address2' => isset($address['line2']) ? $address['line2'] : '',
                    'city' => isset($address['city']) ? $address['city'] : '',
                    'state' => isset($address['stateCode']) ? $address['stateCode'] : '',
                    'postalCode' => isset($address['zip']) ? $address['zip'] : '',
                    'country' => isset($address['countryCode']) ? mapCountryCode($address['countryCode']) : 'US'
                ];
                
                // Use the same for billing by default
                $pillarsOrder['billingAddress'] = $pillarsOrder['shippingAddress'];
                break;
            }
        }
    }
    
    // Add custom fields if relevant
    if (isset($ghlData['customData']) && is_array($ghlData['customData'])) {
        foreach ($ghlData['customData'] as $key => $value) {
            $pillarsOrder['customFields'][$key] = $value;
        }
    }
    
    return $pillarsOrder;
}

/**
 * Helper function to map country names to ISO codes
 * 
 * @param string $countryName Full country name
 * @return string ISO country code
 */
function mapCountryCode($countryName) {
    $countryMap = [
        'United States' => 'US',
        'Canada' => 'CA',
        'Mexico' => 'MX',
        'United Kingdom' => 'GB',
        'Australia' => 'AU',
        'New Zealand' => 'NZ'
        // Add more mappings as needed
    ];
    
    return isset($countryMap[$countryName]) ? $countryMap[$countryName] : 'US';
}

/**
 * Log webhook data transformation for debugging
 * 
 * @param string $direction Direction of transformation (e.g., 'ghl_to_pillars')
 * @param array $input Input data
 * @param array $output Output data
 * @return void
 */
function logTransformation($direction, $input, $output) {
    try {
        $logData = [
            'direction' => $direction,
            'timestamp' => date('Y-m-d H:i:s'),
            'input' => json_encode($input),
            'output' => json_encode($output)
        ];
        
        db_insert('webhook_logs', $logData);
    } catch (Exception $e) {
        error_log('Error logging transformation: ' . $e->getMessage());
    }
}
?>