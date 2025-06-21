<?php
/**
 * Admin User Stats API
 * 
 * Returns user statistics for the admin panel
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

// try {
    // Get total users count
    $total_query = db_query("SELECT COUNT(*) as count FROM users");
    $total_result = db_fetch_one($total_query);
    $total_users = $total_result['count'];
    
    // Get new users (last 30 days)
    $new_users_query = db_query("SELECT COUNT(*) as count FROM users
    WHERE created_at >= NOW() - INTERVAL 30 DAY");
    $new_users_result = db_fetch_one($new_users_query);
    $new_users = $new_users_result['count'];
    
    // Get premium users (tier_id >= 2)
    $premium_users_query = db_query("
        SELECT COUNT(*) as count FROM users
        WHERE tier_id >= 2
    ");
    $premium_users_result = db_fetch_one($premium_users_query);
    $premium_users = $premium_users_result['count'];
    
    // Get basic tier count
    $basic_tier_query = db_query("
        SELECT COUNT(*) as count FROM users
        WHERE tier_id = 1
    ");
    $basic_tier_result = db_fetch_one($basic_tier_query);
    $basic_tier_count = $basic_tier_result['count'];
    
    // Get premium tier count
    $premium_tier_query = db_query("
        SELECT COUNT(*) as count FROM users
        WHERE tier_id = 2
    ");
    $premium_tier_result = db_fetch_one($premium_tier_query);
    $premium_tier_count = $premium_tier_result['count'];
    
    // Get elite tier count
    $elite_tier_query = db_query("
        SELECT COUNT(*) as count FROM users
        WHERE tier_id = 3
    ");
    $elite_tier_result = db_fetch_one($elite_tier_query);
    $elite_tier_count = $elite_tier_result['count'];
    
    // Return stats
    echo json_encode([
        'success' => true,
        'stats' => [
            'totalUsers' => $total_users,
            'newUsers' => $new_users,
            'premiumUsers' => $premium_users,
            'basicTierCount' => $basic_tier_count,
            'premiumTierCount' => $premium_tier_count,
            'eliteTierCount' => $elite_tier_count
        ]
    ]);
// } catch (Exception $e) {
//     http_response_code(500);
//     echo json_encode(['success' => false, 'error' => $e->getMessage()]);
// }