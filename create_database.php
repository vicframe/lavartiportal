<?php
/**
 * Create Database Script
 * 
 * This script creates the MySQL database if it doesn't exist
 */

// Include configuration without database connection
require_once 'config.php';

// Display header
echo "=======================================================\n";
echo "LaVarti Travel Portal - Database Creation\n";
echo "=======================================================\n\n";

try {
    // Connect to MySQL without specifying database
    echo "Connecting to MySQL server...\n";
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    echo "✓ Connected to MySQL server\n\n";
    
    // Create database if it doesn't exist
    echo "Checking if database exists...\n";
    $result = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    
    if ($result->num_rows == 0) {
        echo "  - Database '" . DB_NAME . "' does not exist\n";
        echo "  - Creating database...\n";
        
        // Create database
        $sql = "CREATE DATABASE " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        if ($conn->query($sql) === TRUE) {
            echo "    ✓ Database created successfully\n\n";
        } else {
            throw new Exception("Error creating database: " . $conn->error);
        }
    } else {
        echo "  ✓ Database '" . DB_NAME . "' already exists\n\n";
    }
    
    // Close connection
    $conn->close();
    
    // Success message
    echo "=======================================================\n";
    echo "✅ Database creation completed!\n";
    echo "=======================================================\n\n";
    echo "You can now run install.php to create the tables and seed data.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}