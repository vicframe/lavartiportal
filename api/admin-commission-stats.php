<?php
/**
 * Admin Commission Stats API
 * 
 * Returns commission statistics for the admin panel
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
    // Get total commissions amount
    $total_query = db_query("SELECT SUM(amount) as total FROM commissions");
    $total_result = db_fetch_one($total_query);
    $total_commissions = $total_result['total'] ?: 0;
    
    // Get total paid commissions
    $paid_query = db_query("SELECT SUM(amount) as total FROM commissions WHERE status = 'paid'");
    $paid_result = db_fetch_one($paid_query);
    $total_paid = $paid_result['total'] ?: 0;
    
    // Get pending commissions amount
    $pending_query = db_query("SELECT SUM(amount) as total FROM commissions WHERE status = 'pending'");
    $pending_result = db_fetch_one($pending_query);
    $pending_commissions = $pending_result['total'] ?: 0;
    
    // Get active affiliates (users with at least one commission)
    $affiliates_query = db_query("SELECT COUNT(DISTINCT user_id) as count FROM commissions");
    $affiliates_result = db_fetch_one($affiliates_query);
    $active_affiliates = $affiliates_result['count'];
    
    // Get commission type distribution
    $direct_query = db_query("SELECT SUM(amount) as total FROM commissions WHERE type = 'direct'");
    $direct_result = db_fetch_one($direct_query);
    $direct_commissions = $direct_result['total'] ?: 0;
    
    $override_query = db_query("SELECT SUM(amount) as total FROM commissions WHERE type = 'override'");
    $override_result = db_fetch_one($override_query);
    $override_commissions = $override_result['total'] ?: 0;
    
    $bonus_query = db_query("SELECT SUM(amount) as total FROM commissions WHERE type = 'bonus'");
    $bonus_result = db_fetch_one($bonus_query);
    $bonus_commissions = $bonus_result['total'] ?: 0;
    
    // Return stats
    echo json_encode([
        'success' => true,
        'stats' => [
            'totalCommissions' => $total_commissions,
            'totalPaid' => $total_paid,
            'pendingCommissions' => $pending_commissions,
            'activeAffiliates' => $active_affiliates,
            'directCommissions' => $direct_commissions,
            'overrideCommissions' => $override_commissions,
            'bonusCommissions' => $bonus_commissions
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}