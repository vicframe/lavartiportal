<?php
/**
 * API Endpoint for Order Details
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Authentication required'
    ]);
    exit;
}

// Check for order ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Order ID is required'
    ]);
    exit;
}

// Get parameters
$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Prepare response
$response = [
    'success' => false,
    'error' => 'Order not found'
];

try {
    // Query the database for the order
    $order_query = db_query(
        "SELECT o.*, p.tier_level, p.product_name,
                CASE 
                    WHEN p.tier_level = 1 THEN 'Basic'
                    WHEN p.tier_level = 2 THEN 'Premium'
                    WHEN p.tier_level = 3 THEN 'Elite'
                    ELSE 'Unknown'
                END as tier_name
         FROM orders o
         LEFT JOIN products p ON o.product_id = p.id
         WHERE o.id = ? AND o.user_id = ?",
        [$order_id, $user_id]
    );
    
    $order = db_fetch_assoc($order_query);
    
    if ($order) {
        $response = [
            'success' => true,
            'order' => $order
        ];
    }
    
} catch (Exception $e) {
    // Log the error
    error_log('Error fetching order details: ' . $e->getMessage());
    
    $response = [
        'success' => false,
        'error' => 'An error occurred while fetching order details'
    ];
}

// Return the JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>