<?php
/**
 * MySQL Database Setup Script for XAMPP
 * This script will set up all necessary database tables for the LaVarti Travel Portal
 */

// Original database file
require_once __DIR__ . '/config.php';

// Override the database include to use MySQL version
require_once __DIR__ . '/includes/database_mysql.php';

// Display header
echo "=======================================================\n";
echo "LaVarti Travel Portal - MySQL Database Setup (XAMPP)\n";
echo "=======================================================\n\n";

try {
    // Check if database exists, create if not
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $dbname = 'lavartiportal';
    
    echo "Checking if database exists...\n";
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if database exists
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
    $databaseExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$databaseExists) {
        echo "  - Creating database: $dbname\n";
        $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "    ✓ Database created successfully\n\n";
    } else {
        echo "  ✓ Database already exists\n\n";
    }
    
    // Connect to the database
    echo "Connecting to MySQL database...\n";
    $conn = db_connect();
    echo "✓ Database connection successful\n\n";
    
    // Check tables and create them if needed
    echo "Checking tables and creating if needed...\n";
    ensure_tables_exist($conn);
    echo "✓ Tables setup completed successfully\n\n";
    
    // Add test admin user if it doesn't exist
    echo "Checking for admin user...\n";
    $result = db_query("SELECT COUNT(*) as count FROM users WHERE email = 'test@example.com'");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    
    if ($row['count'] == 0) {
        echo "  - Creating admin user: test@example.com (password: password123)\n";
        
        // Create test user with hashed password
        $password = password_hash('password123', PASSWORD_DEFAULT);
        
        $data = [
            'email' => 'test@example.com',
            'password' => $password,
            'first_name' => 'Test',
            'last_name' => 'User',
            'is_admin' => true,
            'tier_id' => 1 // Basic tier
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
                'status' => 'active'
            ],
            [
                'name' => 'Premium Membership',
                'description' => 'Premium tier membership at $65/month',
                'price' => 65.00,
                'tier_level' => 2,
                'ghl_id' => 'premium_tier',
                'recurring' => true,
                'recurring_interval' => 'monthly',
                'status' => 'active'
            ],
            [
                'name' => 'Elite Membership',
                'description' => 'Elite tier membership at $500/month',
                'price' => 500.00,
                'tier_level' => 3,
                'ghl_id' => 'elite_tier',
                'recurring' => true,
                'recurring_interval' => 'monthly',
                'status' => 'active'
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
            //'config_data' => json_encode([
            'settings_json' => json_encode([
                'api_key' => 'placeholder',
                'location_id' => 'placeholder',
                'webhook_secret' => 'placeholder',
                'webhook_url' => 'http://localhost/lavartiportal/webhook_ghl.php'
            ]),
            'is_active' => false
        ];
        
        db_insert('integration_settings', $ghl_settings);
        
        // Pillars default settings
        $pillars_settings = [
            'integration_name' => 'pillars',
            //'config_data' => json_encode([
            'settings_json' => json_encode([
            'api_key' => 'placeholder',
                'organization_id' => 'placeholder',
                'webhook_url' => 'http://localhost/lavartiportal/webhook_pillars.php'
            ]),
            'is_active' => false
        ];
        
        db_insert('integration_settings', $pillars_settings);
        
        echo "    ✓ Default integration settings added successfully\n\n";
    } else {
        echo "  ✓ Integration settings already exist\n\n";
    }
    
    // Success message
    echo "=======================================================\n";
    echo "✅ MySQL Database Installation completed successfully!\n";
    echo "=======================================================\n\n";
    echo "You can now login with the following credentials:\n";
    echo "  - Email: test@example.com\n";
    echo "  - Password: password123\n\n";
    echo "Remember to change the default password for security reasons.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}