<?php
/**
 * Database Setup Script
 * This script will set up all necessary database tables for the LaVarti Travel Portal
 */

// Include the configuration and database functions
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/database.php';

// Display header
echo "=======================================================\n";
echo "LaVarti Travel Portal - Database Setup\n";
echo "=======================================================\n\n";

try {
    // Connect to database
    echo "Connecting to PostgreSQL database...\n";
    $conn = db_connect();
    echo "✓ Database connection successful\n\n";
    
    // Check if tables exist and create them
    echo "Checking tables and creating if needed...\n";
    ensure_tables_exist($conn);
    echo "✓ Database setup completed successfully\n\n";
    
    // Success message
    echo "=======================================================\n";
    echo "✅ Database setup completed!\n";
    echo "=======================================================\n\n";
    echo "You can now access the portal at: " . APP_URL . "\n";
    echo "If you need test users, run the install.php script.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}