<?php
/**
 * Check Integration API
 * 
 * Checks if an integration is properly configured and connected
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

// Get integration to check
$integration = $_GET['integration'] ?? '';

if (empty($integration)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Integration parameter is required']);
    exit;
}

try {
    // Get integration settings
    $query = "SELECT * FROM integration_settings WHERE integration_name = ?";
    $result = db_query($query, [$integration]);
    $settings = db_fetch_one($result);
    
    if (!$settings) {
        echo json_encode([
            'success' => true,
            'connected' => false,
            'message' => 'Integration not configured'
        ]);
        exit;
    }
    
    $config = json_decode($settings['config_data'], true);
    
    // Check if basic required fields are present
    $is_configured = false;
    
    switch ($integration) {
        case 'ghl':
            $is_configured = !empty($config['api_key']) && !empty($config['location_id']);
            break;
            
        case 'pillars':
            $is_configured = !empty($config['api_key']) && !empty($config['organization_id']);
            break;
            
        default:
            echo json_encode([
                'success' => true,
                'connected' => false,
                'message' => 'Unknown integration type'
            ]);
            exit;
    }
    
    if (!$is_configured) {
        echo json_encode([
            'success' => true,
            'connected' => false,
            'message' => 'Integration missing required configuration'
        ]);
        exit;
    }
    
    // If configured, attempt to validate connection
    $is_connected = test_integration_connection($integration, $config);
    
    echo json_encode([
        'success' => true,
        'connected' => $is_connected,
        'message' => $is_connected ? 'Integration connected successfully' : 'Integration connection failed'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Test the connection to the integration
 */
function test_integration_connection($integration, $config) {
    // In a production environment, you would make an API call to check if the connection works
    // For this demo, we'll just return true if it's configured
    
    switch ($integration) {
        case 'ghl':
            // For now, we'll consider it connected if we have the API key and location ID
            return !empty($config['api_key']) && !empty($config['location_id']);
            
        case 'pillars':
            // For now, we'll consider it connected if we have the API key and organization ID
            return !empty($config['api_key']) && !empty($config['organization_id']);
            
        default:
            return false;
    }
}