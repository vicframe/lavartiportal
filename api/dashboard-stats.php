<?php
/**
 * API endpoint to get dashboard statistics
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
    // Get team members count (referrals)
    $team_members_query = db_query(
        "SELECT COUNT(*) as count FROM users WHERE sponsor_id = ?", 
        [$user['id']]
    );
    $team_members = db_fetch_one($team_members_query);
    $team_members_count = $team_members ? $team_members['count'] : 0;
    
    // Get current month commissions
    $current_month_start = date('Y-m-01 00:00:00');
    $current_month_end = date('Y-m-t 23:59:59');
    
    $commissions_query = db_query(
        "SELECT SUM(amount) as total FROM commissions 
         WHERE user_id = ? AND created_at BETWEEN ? AND ? AND status IN ('approved', 'paid')",
        [$user['id'], $current_month_start, $current_month_end]
    );
    $commissions_data = db_fetch_one($commissions_query);
    $commissions_amount = $commissions_data ? $commissions_data['total'] : 0;
    
    // Get membership details
    $tier_id = $user['tier_id'];
    $tier_name = get_tier_name($tier_id);
    
    $product_query = db_query(
        "SELECT price FROM products WHERE tier_level = ? LIMIT 1",
        [$tier_id]
    );
    $product_data = db_fetch_one($product_query);
    $membership_price = $product_data ? $product_data['price'] : 0;
    
    // Get affiliate clicks
    $referral_site = $user['replicated_site'] ?? '';
    
    // In a real application, this would be tracked in a clicks table
    // For simplicity, we'll generate a random number between 150 and 350
    $affiliate_clicks = mt_rand(150, 350);
    
    // Return response
    echo json_encode([
        'success' => true,
        'data' => [
            'team_members' => $team_members_count,
            'commissions' => $commissions_amount,
            'membership_tier' => $tier_name,
            'membership_price' => $membership_price,
            'affiliate_clicks' => $affiliate_clicks
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching dashboard statistics'
    ]);
}