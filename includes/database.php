<?php
/**
 * FINAL MySQLi Database Implementation
 * NO PostgreSQL Functions, NO JSON fields
 */

// Global database connection
$mysqli = null;

/**
 * Get database connection
 */
function db_connect() {
    global $mysqli;
    
    if ($mysqli === null) {
        // Create connection
       // $mysqli = new mysqli('localhost', 'lavartiportal_user', 'dSMXNhI-cQ7+', 'lavartiportal');
        
//       $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $mysqli = new mysqli('localhost', 'lavartiportal_user', 'I-]5d+pH.sgK', 'lavartiportal');
        // Check connection
        if ($mysqli->connect_error) {
            die('Database connection failed: ' . $mysqli->connect_error);
        }
    }
    
    return $mysqli;
}

/**
 * Execute a SQL query without placeholders
 */
function db_execute($sql) {
    $conn = db_connect();
    $result = $conn->query($sql);
    
    if (!$result && $conn->errno) {
        throw new Exception("Database query failed: " . $conn->error);
    }
    
    return $result;
}

/**
 * Execute a SQL query with parameters
 */
function db_query($sql, $params = []) {
    // No parameters, just run the query directly
    if (empty($params)) {
        return db_execute($sql);
    }
    
    // With parameters, use prepared statement
    $conn = db_connect();
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }
    
    if (!empty($params)) {
        // Build types string
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i'; // integer
            } elseif (is_float($param)) {
                $types .= 'd'; // double
            } else {
                $types .= 's'; // string
            }
        }
        
        // Bind parameters
        $stmt->bind_param($types, ...$params);
    }
    
    // Execute the statement
    $stmt->execute();
    
    // Return result set
    return $stmt->get_result();
}

/**
 * Fetch all rows
 */
function db_fetch_all($result) {
    if (!$result) {
        return [];
    }
    
    if ($result instanceof mysqli_stmt) {
        $result = $result->get_result();
    }
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Fetch one row
 */
function db_fetch_one($result) {
    if (!$result) {
        return null;
    }
    
    if ($result instanceof mysqli_stmt) {
        $result = $result->get_result();
    }
    
    return $result->fetch_assoc();
}

/**
 * Insert data
 */
function db_insert($table, $data) {
    $conn = db_connect();
    print_r($table);
    // Build column names and placeholders
    $columns = implode(', ', array_keys($data));
    $placeholders = rtrim(str_repeat('?, ', count($data)), ', ');
    
    // Create SQL
    $sql = "INSERT INTO `$table` ($columns) VALUES ($placeholders)";
    
    // Prepare statement
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Database insert failed: " . $conn->error);
    }
    
    // Build types string
    $types = '';
    $values = array_values($data);
    foreach ($values as $value) {
        if (is_int($value)) {
            $types .= 'i'; // integer
        } elseif (is_float($value)) {
            $types .= 'd'; // double
        } else {
            $types .= 's'; // string
        }
    }
    
    // Bind parameters
    $stmt->bind_param($types, ...$values);
    
    // Execute
    $stmt->execute();
    
    // Get insert ID
    $id = $conn->insert_id;
    
    // Close statement
    $stmt->close();
    
    return $id;
}

/**
 * Update data
 */
function db_update($table, $data, $where) {
    $conn = db_connect();
    
    // Build SET clause
    $set = [];
    foreach ($data as $column => $value) {
        $set[] = "`$column` = ?";
    }
    $set = implode(', ', $set);
    
    // Build WHERE clause
    $whereClause = [];
    foreach ($where as $column => $value) {
        $whereClause[] = "`$column` = ?";
    }
    $whereClause = implode(' AND ', $whereClause);
    
    // Create SQL
    $sql = "UPDATE `$table` SET $set WHERE $whereClause";
    
    // Prepare statement
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Database update failed: " . $conn->error);
    }
    
    // Build types string and merge params
    $types = '';
    $values = array_merge(array_values($data), array_values($where));
    foreach ($values as $value) {
        if (is_int($value)) {
            $types .= 'i'; // integer
        } elseif (is_float($value)) {
            $types .= 'd'; // double
        } else {
            $types .= 's'; // string
        }
    }
    
    // Bind parameters
    $stmt->bind_param($types, ...$values);
    
    // Execute
    $stmt->execute();
    
    // Get affected rows
    $affected = $stmt->affected_rows;
    
    // Close statement
    $stmt->close();
    
    return $affected;
}

/**
 * Delete data
 */
function db_delete($table, $where) {
    $conn = db_connect();
    
    // Build WHERE clause
    $whereClause = [];
    foreach ($where as $column => $value) {
        $whereClause[] = "`$column` = ?";
    }
    $whereClause = implode(' AND ', $whereClause);
    
    // Create SQL
    $sql = "DELETE FROM `$table` WHERE $whereClause";
    
    // Prepare statement
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Database delete failed: " . $conn->error);
    }
    
    // Build types string
    $types = '';
    $values = array_values($where);
    foreach ($values as $value) {
        if (is_int($value)) {
            $types .= 'i'; // integer
        } elseif (is_float($value)) {
            $types .= 'd'; // double
        } else {
            $types .= 's'; // string
        }
    }
    
    // Bind parameters
    $stmt->bind_param($types, ...$values);
    
    // Execute
    $stmt->execute();
    
    // Get affected rows
    $affected = $stmt->affected_rows;
    
    // Close statement
    $stmt->close();
    
    return $affected;
}

/**
 * Transaction functions
 */
function db_begin_transaction() {
    return db_connect()->begin_transaction();
}

function db_commit() {
    return db_connect()->commit();
}

function db_rollback() {
    return db_connect()->rollback();
}

// Initialize database
function setup_database() {
    // Connect to MySQL without database
    //$conn =new mysqli('localhost', 'lavartiportal_user', 'dSMXNhI-cQ7+', 'lavartiportal');
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

    // Create database if not exists
    $conn->query("CREATE DATABASE IF NOT EXISTS `lavartiportal`");
    
    // Close connection
    $conn->close();
    
    // Connect to the database
    $conn = db_connect();
    
    // Create tables if they don't exist
    // NOTE: Removed all JSON fields and PostgreSQL specific syntax
    $tables = [
        'users' => "CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `email` VARCHAR(255) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `first_name` VARCHAR(100) NOT NULL,
            `last_name` VARCHAR(100) NOT NULL,
            `phone` VARCHAR(20),
            `tier_id` INT DEFAULT 0,
            `is_admin` TINYINT(1) DEFAULT 0,
            `sponsor_id` INT NULL,
            `replicated_site` VARCHAR(255),
            `ghl_id` VARCHAR(255),
            `pillars_id` VARCHAR(255),
            `status` VARCHAR(50) DEFAULT 'active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'products' => "CREATE TABLE IF NOT EXISTS `products` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `price` DECIMAL(10, 2) NOT NULL,
            `tier_level` INT NOT NULL DEFAULT 0,
            `ghl_id` VARCHAR(255),
            `recurring` TINYINT(1) DEFAULT 0,
            `recurring_interval` VARCHAR(50) DEFAULT 'monthly',
            `status` VARCHAR(50) DEFAULT 'active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'orders' => "CREATE TABLE IF NOT EXISTS `orders` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `product_id` INT,
            `amount` DECIMAL(10, 2) NOT NULL DEFAULT 0,
            `product_name` VARCHAR(255),
            `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
            `order_date` DATE,
            `ghl_id` VARCHAR(255),
            `pillars_id` VARCHAR(255),
            `sync_status` VARCHAR(50) DEFAULT 'pending',
            `error_message` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'order_items' => "CREATE TABLE IF NOT EXISTS `order_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT NOT NULL,
            `product_id` INT,
            `product_name` VARCHAR(255) NOT NULL,
            `quantity` INT NOT NULL DEFAULT 1,
            `price` DECIMAL(10, 2) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'commissions' => "CREATE TABLE IF NOT EXISTS `commissions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `order_id` INT,
            `amount` DECIMAL(10, 2) NOT NULL,
            `type` VARCHAR(50) NOT NULL DEFAULT 'direct',
            `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
            `external_id` VARCHAR(255),
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'integration_settings' => "CREATE TABLE IF NOT EXISTS `integration_settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `integration_name` VARCHAR(50) NOT NULL,
            `api_key` VARCHAR(255),
            `secret_key` VARCHAR(255),
            `webhook_url` VARCHAR(255),
            `webhook_secret` VARCHAR(255),
            `location_id` VARCHAR(255),
            `organization_id` VARCHAR(255),
            `settings_json` TEXT,
            `is_active` TINYINT(1) DEFAULT 1,
            `last_sync_at` TIMESTAMP NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'sync_history' => "CREATE TABLE IF NOT EXISTS `sync_history` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `integration` VARCHAR(50) NOT NULL,
            `action` VARCHAR(100) NOT NULL,
            `status` VARCHAR(50) NOT NULL,
            `records_processed` INT DEFAULT 0,
            `summary` TEXT,
            `details` TEXT,
            `duration_seconds` DECIMAL(10, 3) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'system_settings' => "CREATE TABLE IF NOT EXISTS `system_settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `setting_key` VARCHAR(100) NOT NULL UNIQUE,
            `setting_value` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    ];
    
    foreach ($tables as $name => $sql) {
        $conn->query($sql);
    }
    
    // Add test user if doesn't exist
    $result = $conn->query("SELECT * FROM `users` WHERE `email` = 'test@example.com'");
    
    if ($result->num_rows == 0) {
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO `users` (`email`, `password`, `first_name`, `last_name`, `is_admin`) VALUES (?, ?, 'Test', 'User', 1)");
        $email = 'test@example.com';
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
    }
}

// Create database and tables
setup_database();

// Fix for XAMPP compatibility
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

// Success message
// echo "<!-- Final MySQLi database functions loaded successfully -->\n";
?>