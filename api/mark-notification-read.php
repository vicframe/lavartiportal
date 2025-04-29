<?php
/**
 * API Endpoint for Marking a Notification as Read
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

// Check for notification ID
if (!isset($_POST['id']) || empty($_POST['id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Notification ID is required'
    ]);
    exit;
}

// Get parameters
$notification_id = $_POST['id'];
$user_id = $_SESSION['user_id'];

// Prepare response
$response = [
    'success' => true
];

try {
    // In a production environment, update the notification in the database
    // $result = db_query(
    //     "UPDATE notifications SET read = TRUE WHERE id = ? AND user_id = ?",
    //     [$notification_id, $user_id]
    // );
    
    // For demo purposes, we'll just return success
    // In a real implementation, you'd check if the update was successful
    
    // Return the JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log the error
    error_log('Error marking notification as read: ' . $e->getMessage());
    
    // Return an error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while updating the notification'
    ]);
}
?>