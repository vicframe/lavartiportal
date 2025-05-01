<?php
/**
 * Database Installation Script
 * 
 * This script creates all necessary database tables and seed data
 * for the LaVarti Travel Portal using PostgreSQL
 */

// Include configuration
require_once 'config.php';
require_once 'includes/database.php';

// Display header
echo "=======================================================\n";
echo "LaVarti Travel Portal - Database Installation\n";
echo "=======================================================\n\n";

try {
    // Connect to database
    echo "Connecting to database...\n";
    $conn = db_connect();
    echo "✓ Database connection successful\n\n";
    
    // Add test admin user if it doesn't exist
    echo "Checking for admin user...\n";
    $result = db_query("SELECT COUNT(*) as count FROM users WHERE email = 'test@example.com'");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    
    if ($row['count'] == 0) {
        echo "  - Creating admin user: test@example.com (password: password123)\n";
        
        // Create test user with hashed password
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $is_admin = true;
        $tier_id = 1; // Basic tier
        
        $data = [
            'email' => 'test@example.com',
            'password' => $password,
            'first_name' => 'Test',
            'last_name' => 'User',
            'is_admin' => $is_admin,
            'tier_id' => $tier_id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        db_insert('users', $data);
        
        echo "    ✓ Admin user created successfully\n\n";
    } else {
        echo "  ✓ Admin user already exists\n\n";
    }
    
    // Check for product data
    echo "Checking for product data...\n";
    $result = db_query("SELECT COUNT(*) as count FROM products");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    
    if ($row['count'] == 0) {
        echo "  - Adding product data...\n";
        
        $products = [
            [
                'name' => 'Basic Membership',
                'description' => 'Basic tier membership at $25/month',
                'price' => 25.00,
                'tier_level' => 1,
                'ghl_id' => 'basic_tier',
                'recurring' => true,
                'recurring_interval' => 'monthly',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Premium Membership',
                'description' => 'Premium tier membership at $65/month',
                'price' => 65.00,
                'tier_level' => 2,
                'ghl_id' => 'premium_tier',
                'recurring' => true,
                'recurring_interval' => 'monthly',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Elite Membership',
                'description' => 'Elite tier membership at $500/month',
                'price' => 500.00,
                'tier_level' => 3,
                'ghl_id' => 'elite_tier',
                'recurring' => true,
                'recurring_interval' => 'monthly',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        foreach ($products as $product) {
            db_insert('products', $product);
        }
        
        echo "    ✓ Product data added successfully\n\n";
    } else {
        echo "  ✓ Product data already exists\n\n";
    }
    
    // Add default integration settings if needed
    echo "Checking for integration settings...\n";
    $result = db_query("SELECT COUNT(*) as count FROM integration_settings");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    
    if ($row['count'] == 0) {
        echo "  - Adding default integration settings...\n";
        
        // GHL default settings
        $ghl_settings = [
            'integration_name' => 'gohighlevel',
            'config_data' => json_encode([
                'api_key' => 'placeholder',
                'location_id' => 'placeholder',
                'webhook_secret' => 'placeholder',
                'webhook_url' => APP_URL . '/webhook_ghl.php'
            ]),
            'is_active' => false,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        db_insert('integration_settings', $ghl_settings);
        
        // Pillars default settings
        $pillars_settings = [
            'integration_name' => 'pillars',
            'config_data' => json_encode([
                'api_key' => 'placeholder',
                'organization_id' => 'placeholder',
                'webhook_url' => APP_URL . '/webhook_pillars.php'
            ]),
            'is_active' => false,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        db_insert('integration_settings', $pillars_settings);
        
        echo "    ✓ Default integration settings added successfully\n\n";
    } else {
        echo "  ✓ Integration settings already exist\n\n";
    }
    
    // Success message
    echo "=======================================================\n";
    echo "✅ Installation completed successfully!\n";
    echo "=======================================================\n\n";
    echo "You can now login with the following credentials:\n";
    echo "  - Email: test@example.com\n";
    echo "  - Password: password123\n\n";
    echo "Remember to change the default password for security reasons.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}