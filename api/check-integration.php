<?php
/**
 * Check Integration API
 * 
 * Checks the status of the specified integration
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

// Set content type to JSON
header('Content-Type: application/json');

// Require login
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$user = get_current_logged_user();

// Check if user is admin
if (!isset($user['is_admin']) || !$user['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

// Get integration name
if (!isset($_GET['integration'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Integration name is required']);
    exit;
}

$integration = strtolower($_GET['integration']);

// Validate integration name
$valid_integrations = ['ghl', 'pillars', 'rsi'];
if (!in_array($integration, $valid_integrations)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid integration']);
    exit;
}

try {
    // Get integration config
    $query = "SELECT * FROM integration_settings WHERE integration_name = ?";
    $result = db_query($query, [$integration]);
    $config = db_fetch_one($result);
    
    $status = [
        'success' => true,
        'connected' => false,
        'last_updated' => null
    ];
    
    if (!$config) {
        if ($integration === 'ghl') {
            $status['error'] = 'GHL API Key or Location ID not configured';
        } elseif ($integration === 'pillars') {
            $status['error'] = 'Pillars API Key not configured';
        } else {
            $status['error'] = 'RSI API Key not configured';
        }
        
        echo json_encode($status);
        exit;
    }
    
    $config_data = json_decode($config['config_data'], true);
    $status['last_updated'] = $config['updated_at'];
    
    // Check if integration is connected based on the integration name
    if ($integration === 'ghl') {
        if (!empty($config_data['api_key']) && !empty($config_data['location_id'])) {
            // For a real implementation, we would make an API call to GHL to verify the connection
            // For now, we'll just check if the required settings are set
            $status['connected'] = true;
        } else {
            $status['error'] = 'GHL API Key or Location ID not configured';
        }
    } elseif ($integration === 'pillars') {
        if (!empty($config_data['api_key'])) {
            // For a real implementation, we would make an API call to Pillars to verify the connection
            // For now, we'll just check if the required settings are set
            $status['connected'] = true;
        } else {
            $status['error'] = 'Pillars API Key not configured';
        }
    } elseif ($integration === 'rsi') {
        if (!empty($config_data['api_key']) && !empty($config_data['endpoint'])) {
            // For a real implementation, we would make an API call to RSI to verify the connection
            // For now, we'll just check if the required settings are set
            $status['connected'] = true;
        } else {
            $status['error'] = 'RSI API Key or Endpoint not configured';
        }
    }
    
    echo json_encode($status);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}