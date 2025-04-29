<?php
/**
 * API endpoint to get recent activity
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode([
        'success' => false,
        'error' => 'Authentication required'
    ]);
    exit;
}

// Get current user
$user = get_current_logged_user();

if (!$user) {
    echo json_encode([
        'success' => false,
        'error' => 'User not found'
    ]);
    exit;
}

try {
    // Get page from request, default to 1
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 5;
    $offset = ($page - 1) * $limit;
    
    // In a real application, we would query a dedicated activity or events table
    // For this demo, we'll return sample activity data
    
    // Sample activities - in reality, this would be fetched from database
    $activities = [
        [
            'id' => 1,
            'type' => 'commission',
            'title' => 'Commission Earned',
            'description' => '$45.00 from Referral #TRX-2358',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ],
        [
            'id' => 2,
            'type' => 'signup',
            'title' => 'New Team Member',
            'description' => 'John Doe joined your team',
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
        ],
        [
            'id' => 3,
            'type' => 'purchase',
            'title' => 'Subscription Renewed',
            'description' => 'Premium Membership - $65.00',
            'created_at' => date('Y-m-d H:i:s', strtotime('-7 days'))
        ],
        [
            'id' => 4,
            'type' => 'login',
            'title' => 'New Login',
            'description' => 'Login from new device',
            'created_at' => date('Y-m-d H:i:s', strtotime('-10 days'))
        ],
        [
            'id' => 5,
            'type' => 'commission',
            'title' => 'Commission Earned',
            'description' => '$32.50 from Referral #TRX-1987',
            'created_at' => date('Y-m-d H:i:s', strtotime('-15 days'))
        ]
    ];
    
    // Return response
    echo json_encode([
        'success' => true,
        'data' => $activities,
        'pagination' => [
            'page' => $page,
            'total_pages' => 1,
            'total_items' => count($activities)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching activity data'
    ]);
}