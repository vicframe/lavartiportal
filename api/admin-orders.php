<?php
/**
 * Admin Orders API
 * 
 * Returns orders with pagination and filtering for the admin panel
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

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

try {
    // Get pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    // Get filter parameters
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $search = isset($_GET['search']) ? $_GET['search'] : null;
    
    // Ensure valid values
    $page = max(1, $page);
    $limit = max(1, min(100, $limit));
    
    // Calculate offset
    $offset = ($page - 1) * $limit;
    
    // Build query
    $query = "
        SELECT o.id, o.user_id, o.product_id, o.total_amount, o.status, o.order_date, o.created_at, 
               o.updated_at,
               i.name as product_name, i.price as product_price,
               p.tier_level,
               u.first_name, u.last_name, u.email

        FROM orders o
        LEFT JOIN order_items i ON o.id = i.order_id
        LEFT JOIN products p ON i.product_id = p.id
        LEFT JOIN users u ON o.user_id = u.id
        WHERE 1=1
    ";

    $count_query = "
        SELECT COUNT(DISTINCT o.id) as count
        FROM orders o
        LEFT JOIN order_items i ON o.id = i.order_id
        LEFT JOIN products p ON i.product_id = p.id
        LEFT JOIN users u ON o.user_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Add status filter
    if ($status && $status !== 'all') {
        $query .= " AND o.status = ?";
        $count_query .= " AND o.status = ?";
        $params[] = $status;
    }
    
    // Add search filter
    if ($search) {
        $query .= " AND (
            u.first_name LIKE ? OR 
            u.last_name LIKE ? OR 
            u.email LIKE ? OR 
            i.name LIKE ? OR 
            CAST(o.id AS CHAR) LIKE ?
              
             
        )";
        $count_query .= " AND (
            u.first_name LIKE ? OR 
            u.last_name LIKE ? OR 
            u.email LIKE ? OR 
            i.name LIKE ? OR 
            CAST(o.id AS CHAR) LIKE ?
        )";


        
        $searchTerm = "%{$search}%";
        $params = array_merge($params, array_fill(0, 5, $searchTerm));
     
    }
    
   // Get total count
    
    $count_result = db_query($count_query, $params);
    $count_data = db_fetch_one($count_result);
    $total_orders = $count_data['count'];
    
    // Calculate total pages
    $total_pages = ceil($total_orders / $limit);
    
    // Add order and limits
    $query .= " ORDER BY o.order_date DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    // Execute query
    $orders_result = db_query($query, $params);
    $orders = db_fetch_all($orders_result);
    
    // Return orders with pagination info
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'page' => $page,
        'limit' => $limit,
        'totalOrders' => $total_orders,
        'totalPages' => $total_pages
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}