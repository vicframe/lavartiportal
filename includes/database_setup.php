<?php
/**
 * Database Setup
 * 
 * This script sets up the database tables
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/database.php';

// Check if database setup has already been run
$setup_query = db_query("SELECT EXISTS (
    SELECT FROM information_schema.tables 
    WHERE table_schema = 'public' 
    AND table_name = 'users'
)");

$setup_result = db_fetch_one($setup_query);
$tables_exist = isset($setup_result['exists']) && $setup_result['exists'];

if (!$tables_exist) {
    // Create users table
    db_query("
        CREATE TABLE users (
            id SERIAL PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(100),
            last_name VARCHAR(100),
            phone VARCHAR(50),
            is_admin BOOLEAN DEFAULT FALSE,
            tier_level INTEGER DEFAULT 0,
            ghl_id VARCHAR(100),
            pillars_id VARCHAR(100),
            sponsor_id INTEGER,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sponsor_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    
    // Create orders table
    db_query("
        CREATE TABLE orders (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            ghl_order_id VARCHAR(100),
            product_name VARCHAR(255),
            amount DECIMAL(10, 2) DEFAULT 0,
            status VARCHAR(50) DEFAULT 'pending',
            tier_level INTEGER DEFAULT 0,
            order_date TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Create commissions table
    db_query("
        CREATE TABLE commissions (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            pillars_commission_id VARCHAR(100),
            amount DECIMAL(10, 2) DEFAULT 0,
            status VARCHAR(50) DEFAULT 'pending',
            source VARCHAR(50),
            source_id VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Create activities table
    db_query("
        CREATE TABLE activities (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            type VARCHAR(50) NOT NULL,
            description TEXT,
            amount DECIMAL(10, 2) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Create system logs table
    db_query("
        CREATE TABLE logs (
            id SERIAL PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create test admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    
    db_query("
        INSERT INTO users (email, password, first_name, last_name, is_admin, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ", ['admin@example.com', $admin_password, 'Admin', 'User', true]);
    
    // Create test regular user
    $user_password = password_hash('password123', PASSWORD_DEFAULT);
    
    db_query("
        INSERT INTO users (email, password, first_name, last_name, tier_level, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ", ['test@example.com', $user_password, 'Test', 'User', 2]);
    
    // Log setup completion
    log_activity('Database setup completed', 'info');
    
    echo "Database setup completed successfully.\n";
} else {
    echo "Database already set up.\n";
    
    // Check if logs table exists
    $logs_query = db_query("SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'logs'
    )");
    
    $logs_result = db_fetch_one($logs_query);
    $logs_exist = isset($logs_result['exists']) && $logs_result['exists'];
    
    if (!$logs_exist) {
        // Create logs table
        db_query("
            CREATE TABLE logs (
                id SERIAL PRIMARY KEY,
                type VARCHAR(50) NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        echo "Created logs table.\n";
    }
    
    // Log that setup is complete
    db_query("
        INSERT INTO logs (type, message, created_at)
        VALUES (?, ?, CURRENT_TIMESTAMP)
    ", ['info', 'Database setup completed']);
}