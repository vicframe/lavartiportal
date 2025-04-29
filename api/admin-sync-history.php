<?php
/**
 * Admin Sync History API
 * 
 * Returns the sync history for integrations
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

// Get request parameters
$integration = $_GET['integration'] ?? null;
$limit = intval($_GET['limit'] ?? 20);
$page = intval($_GET['page'] ?? 1);

// Validate limit
if ($limit < 1 || $limit > 100) {
    $limit = 20;
}

// Validate page
if ($page < 1) {
    $page = 1;
}

// Calculate offset
$offset = ($page - 1) * $limit;

try {
    // Build query
    $params = [];
    $query = "SELECT * FROM sync_history";
    
    // Add integration filter if provided
    if ($integration) {
        $query .= " WHERE integration = ?";
        $params[] = $integration;
    }
    
    // Add order by and limit
    $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    // Execute query
    $result = db_query($query, $params);
    $history = db_fetch_all($result);
    
    // Get total count for pagination
    $count_query = "SELECT COUNT(*) as total FROM sync_history";
    $count_params = [];
    
    if ($integration) {
        $count_query .= " WHERE integration = ?";
        $count_params[] = $integration;
    }
    
    $count_result = db_query($count_query, $count_params);
    $count_data = db_fetch_one($count_result);
    $total = $count_data ? intval($count_data['total']) : 0;
    
    echo json_encode([
        'success' => true,
        'history' => $history,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}