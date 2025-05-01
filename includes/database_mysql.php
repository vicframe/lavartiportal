<?php
/**
 * Database connection and functions for MySQL (XAMPP)
 */

// Database connection instance
static $db_conn = null;

/**
 * Get database connection
 *
 * @return PDO The database connection
 */
function db_connect() {
    global $db_conn;
    
    if ($db_conn === null) {
        try {
            // MySQL database configuration for XAMPP
            $db_host = 'localhost';
            $db_name = 'lavartiportal';
            $db_user = 'root';
            $db_pass = '';
            
            // Create DSN
            $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
            
            // Connection options
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            // Create PDO instance
            $db_conn = new PDO($dsn, $db_user, $db_pass, $options);
            
            // Check if required tables exist, create if not
            ensure_tables_exist($db_conn);
            
        } catch (PDOException $e) {
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
 * @return PDOStatement The prepared statement
 */
function db_query($sql, array $params = []) {
    try {
        $conn = db_connect();
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
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
        
        $stmt = $conn->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $conn->lastInsertId();
    } catch (PDOException $e) {
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
        
        $set_clauses = [];
        foreach (array_keys($data) as $column) {
            $set_clauses[] = "$column = ?";
        }
        
        $where_clauses = [];
        foreach (array_keys($where) as $column) {
            $where_clauses[] = "$column = ?";
        }
        
        $sql = "UPDATE $table SET " . implode(', ', $set_clauses);
        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute(array_merge(array_values($data), array_values($where)));
        
        return $stmt->rowCount();
    } catch (PDOException $e) {
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
        
        $where_clauses = [];
        foreach (array_keys($where) as $column) {
            $where_clauses[] = "$column = ?";
        }
        
        $sql = "DELETE FROM $table";
        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute(array_values($where));
        
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log('Delete failed: ' . $e->getMessage());
        throw new Exception('Database delete failed: ' . $e->getMessage());
    }
}

/**
 * Check if tables exist and create them if they don't
 *
 * @param PDO $conn The database connection
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
    while ($row = $table_query->fetch(PDO::FETCH_NUM)) {
        $existing_tables[] = $row[0];
    }
    
    // Create missing tables
    $missing_tables = array_diff($tables, $existing_tables);
    
    if (!empty($missing_tables)) {
        // Start transaction
        $conn->beginTransaction();
        
        try {
            foreach ($missing_tables as $table) {
                create_table($conn, $table);
            }
            
            // Commit transaction
            $conn->commit();
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollBack();
            throw new Exception('Failed to create tables: ' . $e->getMessage());
        }
    }
}

/**
 * Create a specific table
 *
 * @param PDO $conn The database connection
 * @param string $table The table name
 */
function create_table($conn, $table) {
    switch ($table) {
        case 'users':
            $sql = "CREATE TABLE users (
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
            )";
            break;
        
        case 'orders':
            $sql = "CREATE TABLE orders (
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
            )";
            break;
        
        case 'order_items':
            $sql = "CREATE TABLE order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                price DECIMAL(10, 2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            )";
            break;
        
        case 'commissions':
            $sql = "CREATE TABLE commissions (
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
            )";
            break;
        
        case 'products':
            $sql = "CREATE TABLE products (
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
            )";
            break;
        
        case 'integration_settings':
            $sql = "CREATE TABLE integration_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                integration_name VARCHAR(100) NOT NULL,
                config_data JSON NOT NULL,
                is_active BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            break;
        
        case 'sync_history':
            $sql = "CREATE TABLE sync_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                integration VARCHAR(100) NOT NULL,
                action VARCHAR(100) NOT NULL,
                status VARCHAR(50) NOT NULL,
                records_processed INT DEFAULT 0,
                duration_seconds DECIMAL(10, 2) DEFAULT 0,
                summary TEXT,
                details JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_by INT
            )";
            break;
        
        case 'system_settings':
            $sql = "CREATE TABLE system_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            break;
        
        default:
            throw new Exception("Unknown table: $table");
    }
    
    $conn->exec($sql);
}