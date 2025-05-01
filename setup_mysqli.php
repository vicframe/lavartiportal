<?php
/**
 * Database Setup Script using MySQLi
 * This script creates all necessary database tables for the LaVarti Travel Portal
 */

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'lavartiportal';

// Display header
echo "=======================================================\n";
echo "LaVarti Travel Portal - Database Setup (MySQLi)\n";
echo "=======================================================\n\n";

try {
    // Connect to MySQL server without selecting a database
    $mysqli = new mysqli($db_host, $db_user, $db_pass);
    
    // Check connection
    if ($mysqli->connect_error) {
        throw new Exception("MySQL Connection failed: " . $mysqli->connect_error);
    }
    
    echo "Connected to MySQL server successfully\n\n";
    
    // Check if database exists, create if not
    echo "Checking if database exists...\n";
    $result = $mysqli->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db_name'");
    
    if ($result->num_rows == 0) {
        echo "  - Creating database: $db_name\n";
        if (!$mysqli->query("CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
            throw new Exception("Error creating database: " . $mysqli->error);
        }
        echo "    ✓ Database created successfully\n\n";
    } else {
        echo "  ✓ Database already exists\n\n";
    }
    
    // Select the database
    $mysqli->select_db($db_name);
    echo "Using database: $db_name\n\n";
    
    // Define tables to create
    $tables = [
        'users' => "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(100),
            last_name VARCHAR(100),
            is_admin BOOLEAN DEFAULT FALSE,
            tier_id INT,
            ghl_id VARCHAR(100),
            pillars_id VARCHAR(100),
            status VARCHAR(50) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        'products' => "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10, 2) NOT NULL,
            tier_level INT,
            ghl_id VARCHAR(100),
            recurring BOOLEAN DEFAULT FALSE,
            recurring_interval VARCHAR(50),
            status VARCHAR(50) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        'orders' => "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            order_number VARCHAR(50) NOT NULL,
            total_amount DECIMAL(10, 2) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            payment_method VARCHAR(50),
            ghl_id VARCHAR(100),
            pillars_id VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        
        'order_items' => "CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            price DECIMAL(10, 2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )",
        
        'commissions' => "CREATE TABLE IF NOT EXISTS commissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            order_id INT NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            type VARCHAR(50) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            pillars_id VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        )",
        
        'integration_settings' => "CREATE TABLE IF NOT EXISTS integration_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            integration_name VARCHAR(100) NOT NULL,
            config_data JSON NOT NULL,
            is_active BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        'sync_history' => "CREATE TABLE IF NOT EXISTS sync_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            integration VARCHAR(100) NOT NULL,
            action VARCHAR(100) NOT NULL,
            status VARCHAR(50) NOT NULL,
            records_processed INT DEFAULT 0,
            duration_seconds DECIMAL(10, 2) DEFAULT 0,
            summary TEXT,
            details JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_by INT,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        )",
        
        'system_settings' => "CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    ];
    
    // Create tables
    echo "Creating tables...\n";
    
    foreach ($tables as $table_name => $table_sql) {
        echo "  - Creating table: $table_name...\n";
        
        if (!$mysqli->query($table_sql)) {
            throw new Exception("Error creating table $table_name: " . $mysqli->error);
        }
        
        echo "    ✓ Table $table_name created/verified successfully\n";
    }
    
    echo "\n✓ All tables created successfully\n\n";
    
    // Add test admin user if it doesn't exist
    echo "Checking for admin user...\n";
    $result = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE email = 'test@example.com'");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        echo "  - Creating admin user: test@example.com (password: password123)\n";
        
        // Create test user with hashed password
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $is_admin = 1; // true
        $tier_id = 1; // Basic tier
        
        $stmt = $mysqli->prepare("
            INSERT INTO users (email, password, first_name, last_name, is_admin, tier_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $email = 'test@example.com';
        $first_name = 'Test';
        $last_name = 'User';
        
        $stmt->bind_param('sssiii', $email, $password, $first_name, $last_name, $is_admin, $tier_id);
        $stmt->execute();
        $stmt->close();
        
        echo "    ✓ Admin user created successfully\n\n";
    } else {
        echo "  ✓ Admin user already exists\n\n";
    }
    
    // Check for product data
    echo "Checking for product data...\n";
    $result = $mysqli->query("SELECT COUNT(*) as count FROM products");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        echo "  - Adding product data...\n";
        
        $products = [
            [
                'name' => 'Basic Membership',
                'description' => 'Basic tier membership at $25/month',
                'price' => 25.00,
                'tier_level' => 1,
                'ghl_id' => 'basic_tier',
                'recurring' => 1, // true
                'recurring_interval' => 'monthly',
                'status' => 'active'
            ],
            [
                'name' => 'Premium Membership',
                'description' => 'Premium tier membership at $65/month',
                'price' => 65.00,
                'tier_level' => 2,
                'ghl_id' => 'premium_tier',
                'recurring' => 1, // true
                'recurring_interval' => 'monthly',
                'status' => 'active'
            ],
            [
                'name' => 'Elite Membership',
                'description' => 'Elite tier membership at $500/month',
                'price' => 500.00,
                'tier_level' => 3,
                'ghl_id' => 'elite_tier',
                'recurring' => 1, // true
                'recurring_interval' => 'monthly',
                'status' => 'active'
            ]
        ];
        
        foreach ($products as $product) {
            $stmt = $mysqli->prepare("
                INSERT INTO products (name, description, price, tier_level, ghl_id, recurring, recurring_interval, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                'ssdiisss',
                $product['name'],
                $product['description'],
                $product['price'],
                $product['tier_level'],
                $product['ghl_id'],
                $product['recurring'],
                $product['recurring_interval'],
                $product['status']
            );
            
            $stmt->execute();
            $stmt->close();
        }
        
        echo "    ✓ Product data added successfully\n\n";
    } else {
        echo "  ✓ Product data already exists\n\n";
    }
    
    // Add default integration settings if needed
    echo "Checking for integration settings...\n";
    $result = $mysqli->query("SELECT COUNT(*) as count FROM integration_settings");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        echo "  - Adding default integration settings...\n";
        
        // GHL default settings
        $ghl_settings_json = json_encode([
            'api_key' => 'placeholder',
            'location_id' => 'placeholder',
            'webhook_secret' => 'placeholder',
            'webhook_url' => 'http://localhost/lavartiportal/webhook_ghl.php'
        ]);
        
        $ghl_name = 'gohighlevel';
        $is_active = 0; // false
        
        $stmt = $mysqli->prepare("
            INSERT INTO integration_settings (integration_name, config_data, is_active)
            VALUES (?, ?, ?)
        ");
        
        $stmt->bind_param('ssi', $ghl_name, $ghl_settings_json, $is_active);
        $stmt->execute();
        $stmt->close();
        
        // Pillars default settings
        $pillars_settings_json = json_encode([
            'api_key' => 'placeholder',
            'organization_id' => 'placeholder',
            'webhook_url' => 'http://localhost/lavartiportal/webhook_pillars.php'
        ]);
        
        $pillars_name = 'pillars';
        
        $stmt = $mysqli->prepare("
            INSERT INTO integration_settings (integration_name, config_data, is_active)
            VALUES (?, ?, ?)
        ");
        
        $stmt->bind_param('ssi', $pillars_name, $pillars_settings_json, $is_active);
        $stmt->execute();
        $stmt->close();
        
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
    
    // Close connection
    $mysqli->close();
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}