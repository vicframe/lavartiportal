<?php
/**
 * Database connection and functions
 */

// Database connection instance
static $db_conn = null;

/**
 * Get database connection
 *
 * @return mysqli The database connection
 */
function db_connect() {
    global $db_conn;
    
    if ($db_conn === null) {
        try {
            // Get database credentials from configuration or environment variables
            $db_host = defined('DB_HOST') ? DB_HOST : 'localhost';
            $db_name = defined('DB_NAME') ? DB_NAME : 'lavartiportal';
            $db_user = defined('DB_USER') ? DB_USER : 'root';
            $db_pass = defined('DB_PASS') ? DB_PASS : '';
            $db_port = defined('DB_PORT') ? DB_PORT : 3306;
            
            // Create mysqli connection
            $db_conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
            
            // Check connection
            if ($db_conn->connect_error) {
                throw new Exception('Database connection failed: ' . $db_conn->connect_error);
            }
            
            // Set charset
            $db_conn->set_charset('utf8mb4');
            
            // Check if required tables exist, create if not
            ensure_tables_exist($db_conn);
            
        } catch (Exception $e) {
            // Log error
            error_log('Database connection failed: ' . $e->getMessage());
            
            throw new Exception('Database connection failed. Please check your configuration.');
        }
    }
    
    return $db_conn;
}

/**
 * Execute a SQL query
 *
 * @param string $sql The SQL query
 * @param array $params The query parameters
 * @return mysqli_stmt|mysqli_result The result of the query
 */
function db_query($sql, array $params = []) {
    try {
        $conn = db_connect();
        
        if (empty($params)) {
            // Simple query without parameters
            $result = $conn->query($sql);
            
            if ($result === false) {
                throw new Exception($conn->error);
            }
            
            return $result;
        } else {
            // Prepared statement with parameters
            $stmt = $conn->prepare($sql);
            
            if ($stmt === false) {
                throw new Exception($conn->error);
            }
            
            // Bind parameters
            if (!empty($params)) {
                $types = '';
                $bindParams = [];
                
                // Add references to the $bindParams array
                foreach ($params as $key => $value) {
                    // Determine the type of the parameter
                    if (is_int($value)) {
                        $types .= 'i';
                    } elseif (is_float($value)) {
                        $types .= 'd';
                    } elseif (is_string($value)) {
                        $types .= 's';
                    } else {
                        $types .= 'b';
                    }
                    
                    $bindParams[] = &$params[$key];
                }
                
                // Prepend $types to the parameters array
                array_unshift($bindParams, $types);
                
                // Call bind_param with the unpacked $bindParams array
                call_user_func_array([$stmt, 'bind_param'], $bindParams);
            }
            
            // Execute the statement
            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }
            
            return $stmt;
        }
    } catch (Exception $e) {
        error_log('Query execution failed: ' . $e->getMessage() . ' - SQL: ' . $sql);
        throw new Exception('Database query failed: ' . $e->getMessage());
    }
}

/**
 * Insert a record into a table
 *
 * @param string $table The table name
 * @param array $data The data to insert
 * @return int The inserted record ID
 */
function db_insert($table, array $data) {
    try {
        $conn = db_connect();
        
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = db_query($sql, array_values($data));
        
        // Get the last inserted ID
        $insertId = $conn->insert_id;
        
        // Close the statement
        $stmt->close();
        
        return $insertId;
    } catch (Exception $e) {
        error_log('Insert failed: ' . $e->getMessage());
        throw new Exception('Database insert failed: ' . $e->getMessage());
    }
}

/**
 * Update a record in a table
 *
 * @param string $table The table name
 * @param array $data The data to update
 * @param array $where The where conditions
 * @return int The number of affected rows
 */
function db_update($table, array $data, array $where) {
    try {
        $conn = db_connect();
        
        $set = [];
        foreach ($data as $column => $value) {
            $set[] = "$column = ?";
        }
        
        $whereClause = [];
        foreach ($where as $column => $value) {
            $whereClause[] = "$column = ?";
        }
        
        $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE " . implode(' AND ', $whereClause);
        
        $params = array_merge(array_values($data), array_values($where));
        $stmt = db_query($sql, $params);
        
        // Get the number of affected rows
        $affectedRows = $stmt->affected_rows;
        
        // Close the statement
        $stmt->close();
        
        return $affectedRows;
    } catch (Exception $e) {
        error_log('Update failed: ' . $e->getMessage());
        throw new Exception('Database update failed: ' . $e->getMessage());
    }
}

/**
 * Delete a record from a table
 *
 * @param string $table The table name
 * @param array $where The where conditions
 * @return int The number of affected rows
 */
function db_delete($table, array $where) {
    try {
        $conn = db_connect();
        
        $whereClause = [];
        foreach ($where as $column => $value) {
            $whereClause[] = "$column = ?";
        }
        
        $sql = "DELETE FROM $table WHERE " . implode(' AND ', $whereClause);
        
        $stmt = db_query($sql, array_values($where));
        
        // Get the number of affected rows
        $affectedRows = $stmt->affected_rows;
        
        // Close the statement
        $stmt->close();
        
        return $affectedRows;
    } catch (Exception $e) {
        error_log('Delete failed: ' . $e->getMessage());
        throw new Exception('Database delete failed: ' . $e->getMessage());
    }
}

/**
 * Fetch all rows from a result
 *
 * @param mysqli_result $result The result object
 * @return array The result rows
 */
function db_fetch_all($result) {
    $rows = [];
    
    if ($result instanceof mysqli_stmt) {
        // Get the result from the statement
        $result = $result->get_result();
    }
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    
    return $rows;
}

/**
 * Fetch a single row from a result
 *
 * @param mysqli_result $result The result object
 * @return array|false The result row or false if no rows
 */
function db_fetch_one($result) {
    if ($result instanceof mysqli_stmt) {
        // Get the result from the statement
        $result = $result->get_result();
    }
    
    if ($result) {
        return $result->fetch_assoc();
    }
    
    return false;
}

/**
 * Begin a transaction
 *
 * @return bool Success status
 */
function db_begin_transaction() {
    return db_connect()->begin_transaction();
}

/**
 * Commit a transaction
 *
 * @return bool Success status
 */
function db_commit() {
    return db_connect()->commit();
}

/**
 * Rollback a transaction
 *
 * @return bool Success status
 */
function db_rollback() {
    return db_connect()->rollback();
}

/**
 * Ensure required tables exist in the database
 *
 * @param mysqli $conn The database connection
 * @return void
 */
function ensure_tables_exist($conn) {
    // Check if tables exist
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
    
    // Get existing tables
    $table_query = $conn->query("SHOW TABLES");
    while ($row = $table_query->fetch_assoc()) {
        $existing_tables[] = reset($row); // First value in the row
    }
    
    // Create missing tables
    $missing_tables = array_diff($tables, $existing_tables);
    
    if (!empty($missing_tables)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            foreach ($missing_tables as $table) {
                create_table($conn, $table);
            }
            
            // Commit transaction
            $conn->commit();
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            
            error_log('Table creation failed: ' . $e->getMessage());
            throw new Exception('Database initialization failed: ' . $e->getMessage());
        }
    }
}

/**
 * Create a table in the database
 *
 * @param mysqli $conn The database connection
 * @param string $table The table name
 * @return void
 */
function create_table($conn, $table) {
    $create_sql = '';
    
    switch ($table) {
        case 'users':
            $create_sql = "
                CREATE TABLE users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    first_name VARCHAR(100) NOT NULL,
                    last_name VARCHAR(100) NOT NULL,
                    phone VARCHAR(20),
                    tier_id INT DEFAULT 0,
                    is_admin BOOLEAN DEFAULT FALSE,
                    sponsor_id INT,
                    replicated_site VARCHAR(255),
                    ghl_id VARCHAR(255),
                    pillars_id VARCHAR(255),
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX (sponsor_id),
                    FOREIGN KEY (sponsor_id) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            break;
            
        case 'orders':
            $create_sql = "
                CREATE TABLE orders (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    product_id INT,
                    amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
                    product_name VARCHAR(255),
                    status ENUM('pending', 'processing', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
                    order_date DATE,
                    ghl_id VARCHAR(255),
                    pillars_id VARCHAR(255),
                    sync_status ENUM('pending', 'synced', 'error') DEFAULT 'pending',
                    error_message TEXT,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX (user_id),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            break;
            
        case 'order_items':
            $create_sql = "
                CREATE TABLE order_items (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT NOT NULL,
                    product_id INT,
                    name VARCHAR(255) NOT NULL,
                    price DECIMAL(10, 2) NOT NULL,
                    quantity INT NOT NULL DEFAULT 1,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX (order_id),
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            break;
            
        case 'commissions':
            $create_sql = "
                CREATE TABLE commissions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    order_id INT,
                    amount DECIMAL(10, 2) NOT NULL,
                    commission_type ENUM('direct', 'override', 'bonus') DEFAULT 'direct',
                    status ENUM('pending', 'approved', 'paid', 'declined') NOT NULL DEFAULT 'pending',
                    processed_date TIMESTAMP NULL,
                    commission_date TIMESTAMP NULL,
                    external_id VARCHAR(255),
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX (user_id),
                    INDEX (order_id),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            break;
            
        case 'products':
            $create_sql = "
                CREATE TABLE products (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    price DECIMAL(10, 2) NOT NULL,
                    tier_level INT NOT NULL DEFAULT 0,
                    ghl_id VARCHAR(255),
                    recurring BOOLEAN DEFAULT FALSE,
                    recurring_interval ENUM('monthly', 'yearly') DEFAULT 'monthly',
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            break;
            
        case 'integration_settings':
            $create_sql = "
                CREATE TABLE integration_settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    integration_name VARCHAR(50) NOT NULL,
                    config_data JSON NOT NULL,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            break;
            
        case 'sync_history':
            $create_sql = "
                CREATE TABLE sync_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    integration VARCHAR(50) NOT NULL,
                    action VARCHAR(100) NOT NULL,
                    status VARCHAR(20) NOT NULL,
                    records_processed INT DEFAULT 0,
                    duration_seconds FLOAT DEFAULT 0,
                    summary TEXT,
                    details JSON,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    created_by INT,
                    INDEX (created_by),
                    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            break;
            
        case 'system_settings':
            $create_sql = "
                CREATE TABLE system_settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(100) NOT NULL UNIQUE,
                    value TEXT NOT NULL,
                    description TEXT,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            break;
    }
    
    if (!empty($create_sql)) {
        $conn->query($create_sql);
        
        // Insert seed data if needed
        if ($table == 'products') {
            seed_products($conn);
        } else if ($table == 'users') {
            seed_admin_user($conn);
        }
    }
}

/**
 * Seed products table with initial data
 *
 * @param mysqli $conn The database connection
 * @return void
 */
function seed_products($conn) {
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
}

/**
 * Seed users table with admin user
 *
 * @param mysqli $conn The database connection
 * @return void
 */
function seed_admin_user($conn) {
    // Check if a test user exists
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE email = 'test@example.com'");
    $row = $result->fetch_assoc();
    $user_count = $row['count'];
    
    if ($user_count == 0) {
        // Create test user with hashed password
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $is_admin = 1; // true
        $tier_id = 1; // Basic tier
        
        $stmt = $conn->prepare("
            INSERT INTO users (email, password, first_name, last_name, is_admin, tier_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            'sssiii',
            $email,
            $password,
            $first_name,
            $last_name,
            $is_admin,
            $tier_id
        );
        
        $email = 'test@example.com';
        $first_name = 'Test';
        $last_name = 'User';
        
        $stmt->execute();
        $stmt->close();
    }
}