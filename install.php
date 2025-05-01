<?php
/**
 * Database Installation Script
 * 
 * This script creates all necessary database tables and seed data
 * for the LaVarti Travel Portal
 */

// Include configuration
require_once 'config.php';

// Display header
echo "=======================================================\n";
echo "LaVarti Travel Portal - Database Installation\n";
echo "=======================================================\n\n";

try {
    // Connect to database
    echo "Connecting to database...\n";
    $conn = db_connect();
    echo "✓ Database connection successful\n\n";
    
    // Create tables
    echo "Creating database tables...\n";
    
    // Get list of tables from database
    $tables = [
        'users',
        'orders',
        'order_items',
        'commissions',
        'products',
        'integration_settings',
        'sync_history',
        'system_settings'
    ];
    
    $existing_tables = [];
    
    try {
        $table_query = $conn->query("SHOW TABLES");
        while ($row = $table_query->fetch_assoc()) {
            $existing_tables[] = reset($row); // First value in the row
        }
    } catch (Exception $e) {
        // No tables exist yet
    }
    
    // Create missing tables
    $missing_tables = array_diff($tables, $existing_tables);
    
    if (!empty($missing_tables)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            foreach ($missing_tables as $table) {
                echo "  - Creating table: {$table}...\n";
                create_table($conn, $table);
                echo "    ✓ Table {$table} created successfully\n";
            }
            
            // Commit transaction
            $conn->commit();
            echo "\n✓ All tables created successfully\n\n";
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            
            echo "✗ Error creating tables: " . $e->getMessage() . "\n";
            exit(1);
        }
    } else {
        echo "✓ All required tables already exist\n\n";
    }
    
    // Add test admin user if it doesn't exist
    echo "Checking for admin user...\n";
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE email = 'test@example.com'");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        echo "  - Creating admin user: test@example.com (password: password123)\n";
        
        // Create test user with hashed password
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $is_admin = 1; // true
        $tier_id = 1; // Basic tier
        
        $stmt = $conn->prepare("
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
    $result = $conn->query("SELECT COUNT(*) as count FROM products");
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
            $stmt = $conn->prepare("
                INSERT INTO products (name, description, price, tier_level, ghl_id, recurring, recurring_interval, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $recurring = $product['recurring'] ? 1 : 0;
            
            $stmt->bind_param(
                'ssdiisss',
                $product['name'],
                $product['description'],
                $product['price'],
                $product['tier_level'],
                $product['ghl_id'],
                $recurring,
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