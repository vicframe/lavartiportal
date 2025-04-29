<?php
/**
 * API Endpoint for Notifications
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Get current user ID
$user_id = $_SESSION['user_id'];

// Prepare response
$response = [
    'success' => true,
    'notifications' => []
];

try {
    // Query the database for notifications
    // In a real implementation, this would come from a notifications table
    // For now, we'll just return some sample notifications for demo purposes
    
    // In a production environment, you would query from a real notifications table
    // $query = db_query("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10", [$user_id]);
    // $notifications = db_fetch_all($query);
    
    // For now, let's create some placeholder notifications
    // In a real implementation, these would be real notifications from the database
    // This is just for UI demonstration purposes
    $response['notifications'] = [
        [
            'id' => 1,
            'user_id' => $user_id,
            'type' => 'system',
            'message' => 'Welcome to LaVarti Systems! Get started by exploring your dashboard.',
            'read' => false,
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ],
        [
            'id' => 2,
            'user_id' => $user_id,
            'type' => 'system',
            'message' => 'Your account was successfully created.',
            'read' => true,
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 day'))
        ]
    ];
    
    // Return the JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log the error
    error_log('Error in notifications API: ' . $e->getMessage());
    
    // Return an error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching notifications'
    ]);
}
?>