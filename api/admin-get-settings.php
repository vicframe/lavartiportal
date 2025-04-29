<?php
/**
 * Admin Get Settings API
 * 
 * Returns the system settings for the admin panel
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

try {
    // Get system settings
    $query = "SELECT * FROM system_settings";
    $result = db_query($query);
    
    // Check if query was successful
    if ($result) {
        $settings = [];
        $rows = db_fetch_all($result);
        
        // Convert to key-value format
        foreach ($rows as $row) {
            $value = $row['value'];
            
            // Parse JSON values
            if (in_array($row['setting_key'], ['smtp_auth', 'smtp_secure', 'allow_registration', 'require_email_verification', 'allow_guest_checkout', 'auto_approve_commissions', 'enable_debug_mode', 'enable_audit_log'])) {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif (in_array($row['setting_key'], ['min_password_length', 'session_timeout', 'smtp_port', 'cache_lifetime'])) {
                $value = intval($value);
            } elseif (in_array($row['setting_key'], ['direct_commission_rate', 'override_commission_rate', 'bonus_commission_threshold', 'minimum_payout'])) {
                $value = floatval($value);
            }
            
            $settings[$row['setting_key']] = $value;
        }
        
        echo json_encode([
            'success' => true,
            'settings' => $settings
        ]);
    } else {
        // If the table doesn't exist yet or is empty, return default settings
        $default_settings = [
            'site_name' => 'LaVarti Travel',
            'site_description' => 'Travel membership and affiliate platform',
            'contact_email' => 'contact@example.com',
            'time_zone' => 'UTC',
            'date_format' => 'm/d/Y',
            'currency' => 'USD',
            'allow_registration' => true,
            'require_email_verification' => true,
            'allow_guest_checkout' => false,
            'default_user_role' => 'customer',
            'min_password_length' => 8,
            'session_timeout' => 120,
            'direct_commission_rate' => 10.0,
            'override_commission_rate' => 5.0,
            'bonus_commission_threshold' => 1000.0,
            'commission_payout' => 'monthly',
            'minimum_payout' => 50.0,
            'auto_approve_commissions' => false,
            'log_level' => 'info',
            'maintenance_mode' => 'off',
            'cache_lifetime' => 60,
            'enable_debug_mode' => false,
            'enable_audit_log' => true
        ];
        
        echo json_encode([
            'success' => true,
            'settings' => $default_settings
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}