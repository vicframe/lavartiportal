<?php
/**
 * Admin Commissions API
 * 
 * Returns commissions with pagination and filtering for the admin panel
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
        SELECT c.id, c.user_id, c.order_id, c.amount, c.status, c.type, c.commission_date, 
               c.created_at, c.updated_at, c.notes,
               u.first_name, u.last_name, u.email
        FROM commissions c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Add status filter
    if ($status && $status !== 'all') {
        $query .= " AND c.status = ?";
        $params[] = $status;
    }
    
    // Add search filter
    if ($search) {
        $query .= " AND (
            u.first_name LIKE ? OR 
            u.last_name LIKE ? OR 
            u.email LIKE ? OR 
            c.id::text LIKE ? OR
            c.order_id::text LIKE ?
        )";
        
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Count total commissions with filters
    $count_query = str_replace(
        "SELECT c.id, c.user_id, c.order_id, c.amount, c.status, c.type, c.commission_date, 
               c.created_at, c.updated_at, c.notes,
               u.first_name, u.last_name, u.email",
        "SELECT COUNT(*) as count",
        $query
    );
    
    $count_result = db_query($count_query, $params);
    $count_data = db_fetch_one($count_result);
    $total_commissions = $count_data['count'];
    
    // Calculate total pages
    $total_pages = ceil($total_commissions / $limit);
    
    // Add order and limits
    $query .= " ORDER BY c.commission_date DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    // Execute query
    $commissions_result = db_query($query, $params);
    $commissions = db_fetch_all($commissions_result);
    
    // Return commissions with pagination info
    echo json_encode([
        'success' => true,
        'commissions' => $commissions,
        'page' => $page,
        'limit' => $limit,
        'totalCommissions' => $total_commissions,
        'totalPages' => $total_pages
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}