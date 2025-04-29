<?php
/**
 * Check Integration Status API
 * 
 * Checks the status of GHL or Pillars integration
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../api/ghl_api.php';
require_once __DIR__ . '/../api/pillars_api.php';

// Set content type to JSON
header('Content-Type: application/json');

// Require admin login
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$user = get_current_logged_user();

if (!isset($user['is_admin']) || !$user['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

// Get integration to check
$integration = isset($_GET['integration']) ? $_GET['integration'] : '';

// Validate integration
if (!in_array($integration, ['ghl', 'pillars'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid integration']);
    exit;
}

try {
    if ($integration === 'ghl') {
        // Check GHL integration
        $api_key = getenv('GHL_API_KEY');
        $location_id = getenv('GHL_LOCATION_ID');
        
        if (!$api_key || !$location_id) {
            echo json_encode(['success' => false, 'error' => 'GHL API Key or Location ID not configured']);
            exit;
        }
        
        // Try to make a test API call
        $ghl_api = ghl_api();
        $test_result = $ghl_api->test_connection();
        
        if ($test_result) {
            echo json_encode([
                'success' => true,
                'message' => 'GHL integration is working',
                'api_key' => $api_key
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to connect to GHL API']);
        }
    } else {
        // Check Pillars integration
        $api_key = getenv('PILLARS_API_KEY');
        
        if (!$api_key) {
            echo json_encode(['success' => false, 'error' => 'Pillars API Key not configured']);
            exit;
        }
        
        // Try to make a test API call
        $pillars_api = pillars_api();
        $test_result = $pillars_api->test_connection();
        
        if ($test_result) {
            echo json_encode([
                'success' => true,
                'message' => 'Pillars integration is working',
                'api_key' => $api_key
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to connect to Pillars API']);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}