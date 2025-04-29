<?php
/**
 * Database connection and query helper functions
 */

// Create database connection
function db_connect() {
    static $conn;
    
    if ($conn === NULL) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                throw new Exception("Database connection failed: " . $conn->connect_error);
            }
            
            $conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            error_log($e->getMessage());
            die("Database connection error. Please try again later.");
        }
    }
    
    return $conn;
}

// Execute a database query
function db_query($sql, $params = []) {
    $conn = db_connect();
    
    try {
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        
        if (!empty($params)) {
            $types = '';
            $bindParams = [];
            
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } elseif (is_string($param)) {
                    $types .= 's';
                } else {
                    $types .= 'b';
                }
                
                $bindParams[] = $param;
            }
            
            // Create array of references
            $bindValues = array_merge([$types], $bindParams);
            $refs = [];
            
            foreach($bindValues as $key => $value) {
                $refs[$key] = &$bindValues[$key];
            }
            
            call_user_func_array([$stmt, 'bind_param'], $refs);
        }
        
        $stmt->execute();
        
        $result = $stmt->get_result();
        $stmt->close();
        
        if ($result === false && $stmt->errno) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

// Fetch all rows from a result
function db_fetch_all($result) {
    $rows = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    
    return $rows;
}

// Fetch single row from a result
function db_fetch_one($result) {
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Get the ID from last insert
function db_last_insert_id() {
    return db_connect()->insert_id;
}

// Execute an insert query
function db_insert($table, $data) {
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    
    $result = db_query($sql, array_values($data));
    
    if ($result) {
        return db_last_insert_id();
    }
    
    return false;
}

// Execute an update query
function db_update($table, $data, $where, $whereParams = []) {
    $set = [];
    
    foreach (array_keys($data) as $column) {
        $set[] = "$column = ?";
    }
    
    $setClause = implode(', ', $set);
    
    $sql = "UPDATE $table SET $setClause WHERE $where";
    
    $params = array_merge(array_values($data), $whereParams);
    
    $result = db_query($sql, $params);
    
    return $result !== false;
}

// Check if a record exists
function db_record_exists($table, $where, $params = []) {
    $sql = "SELECT 1 FROM $table WHERE $where LIMIT 1";
    
    $result = db_query($sql, $params);
    
    return $result !== false && $result->num_rows > 0;
}

// Create the database tables if they don't exist
function db_initialize() {
    $conn = db_connect();
    
    // Create users table
    $conn->query("
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            ghl_id VARCHAR(255) UNIQUE,
            pillars_id VARCHAR(255) UNIQUE NULL,
            email VARCHAR(255) UNIQUE,
            first_name VARCHAR(255),
            last_name VARCHAR(255),
            phone VARCHAR(50),
            tier_id INT DEFAULT 0,
            sponsor_id VARCHAR(255) NULL,
            replicated_site VARCHAR(255) NULL,
            status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Create orders table
    $conn->query("
        CREATE TABLE IF NOT EXISTS orders (
            id INT PRIMARY KEY AUTO_INCREMENT,
            ghl_id VARCHAR(255) UNIQUE,
            pillars_id VARCHAR(255) UNIQUE NULL,
            user_id INT,
            product_id INT,
            amount DECIMAL(10, 2),
            status ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
            order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            sync_status ENUM('pending', 'synced', 'failed') DEFAULT 'pending',
            error_message TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");
    
    // Create products table
    $conn->query("
        CREATE TABLE IF NOT EXISTS products (
            id INT PRIMARY KEY AUTO_INCREMENT,
            ghl_id VARCHAR(255) UNIQUE,
            name VARCHAR(255),
            description TEXT,
            tier_level INT,
            price DECIMAL(10, 2),
            recurring BOOLEAN DEFAULT FALSE,
            recurring_interval ENUM('monthly', 'yearly') DEFAULT 'monthly',
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Create commissions table
    $conn->query("
        CREATE TABLE IF NOT EXISTS commissions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            order_id INT,
            amount DECIMAL(10, 2),
            commission_type ENUM('direct', 'override', 'bonus') DEFAULT 'direct',
            status ENUM('pending', 'approved', 'paid', 'declined') DEFAULT 'pending',
            processed_date TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (order_id) REFERENCES orders(id)
        )
    ");
    
    // Create sync_logs table
    $conn->query("
        CREATE TABLE IF NOT EXISTS sync_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            entity_type ENUM('user', 'order', 'commission'),
            entity_id INT,
            source_system ENUM('ghl', 'pillars', 'rsi'),
            target_system ENUM('ghl', 'pillars', 'rsi'),
            status ENUM('success', 'failed'),
            message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create webhook_logs table
    $conn->query("
        CREATE TABLE IF NOT EXISTS webhook_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            source VARCHAR(50),
            event_type VARCHAR(100),
            payload TEXT,
            processed BOOLEAN DEFAULT FALSE,
            status ENUM('received', 'processing', 'completed', 'failed') DEFAULT 'received',
            error_message TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            processed_at TIMESTAMP NULL
        )
    ");
    
    // Insert default products if they don't exist
    if (!db_record_exists('products', 'tier_level = ?', [1])) {
        db_insert('products', [
            'ghl_id' => 'basic_tier',
            'name' => 'Basic Membership',
            'description' => 'Basic tier membership at $25/month',
            'tier_level' => 1,
            'price' => PRODUCT_TIER_BASIC,
            'recurring' => true,
            'recurring_interval' => 'monthly',
            'status' => 'active'
        ]);
    }
    
    if (!db_record_exists('products', 'tier_level = ?', [2])) {
        db_insert('products', [
            'ghl_id' => 'premium_tier',
            'name' => 'Premium Membership',
            'description' => 'Premium tier membership at $65/month',
            'tier_level' => 2,
            'price' => PRODUCT_TIER_PREMIUM,
            'recurring' => true,
            'recurring_interval' => 'monthly',
            'status' => 'active'
        ]);
    }
    
    if (!db_record_exists('products', 'tier_level = ?', [3])) {
        db_insert('products', [
            'ghl_id' => 'elite_tier',
            'name' => 'Elite Membership',
            'description' => 'Elite tier membership at $500/month',
            'tier_level' => 3,
            'price' => PRODUCT_TIER_ELITE,
            'recurring' => true,
            'recurring_interval' => 'monthly',
            'status' => 'active'
        ]);
    }
}

// Initialize the database when included
db_initialize();
?>
