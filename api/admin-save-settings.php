<?php
/**
 * Admin Save Settings API
 * 
 * Saves system settings from the admin panel
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
    // Get request body
    $json_data = file_get_contents('php://input');
    $settings = json_decode($json_data, true);
    
    if (!$settings) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid settings data']);
        exit;
    }
    
    // Check if system_settings table exists
    $table_check = db_query("SELECT to_regclass('public.system_settings')");
    $table_exists = db_fetch_one($table_check);
    
    if (!$table_exists['to_regclass']) {
        // Create system_settings table
        $create_table_query = "
            CREATE TABLE system_settings (
                id SERIAL PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                value TEXT NOT NULL,
                description TEXT,
                created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP NOT NULL DEFAULT NOW()
            )
        ";
        
        db_query($create_table_query);
    }
    
    // Begin transaction
    db_query("BEGIN");
    
    // Save settings
    foreach ($settings as $key => $value) {
        // Convert boolean and other types to string for storage
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        } elseif ($value === null) {
            $value = '';
        } else {
            $value = (string) $value;
        }
        
        // Check if setting already exists
        $check_query = db_query("SELECT id FROM system_settings WHERE setting_key = ?", [$key]);
        $setting_exists = db_fetch_one($check_query);
        
        if ($setting_exists) {
            // Update existing setting
            db_query("UPDATE system_settings SET value = ?, updated_at = NOW() WHERE setting_key = ?", [$value, $key]);
        } else {
            // Insert new setting
            db_query("INSERT INTO system_settings (setting_key, value) VALUES (?, ?)", [$key, $value]);
        }
    }
    
    // Commit transaction
    db_query("COMMIT");
    
    // Log settings update
    $log_message = "System settings updated by admin (ID: {$user['id']}, Name: {$user['first_name']} {$user['last_name']}).";
    
    // Insert log entry (if logs table exists)
    $logs_check = db_query("SELECT to_regclass('public.logs')");
    $logs_exists = db_fetch_one($logs_check);
    
    if ($logs_exists['to_regclass']) {
        db_query("
            INSERT INTO logs (user_id, action, reference_id, reference_type, message, data, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ", [
            $user['id'],
            'settings.update',
            $user['id'],
            'settings',
            $log_message,
            json_encode(['settings_updated' => array_keys($settings)])
        ]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
} catch (Exception $e) {
    // Rollback transaction if an error occurred
    db_query("ROLLBACK");
    
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}