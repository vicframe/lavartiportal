<?php
/**
 * Database connection and functions
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
            // Get database credentials from environment variables
            $db_host = getenv('PGHOST');
            $db_port = getenv('PGPORT');
            $db_name = getenv('PGDATABASE');
            $db_user = getenv('PGUSER');
            $db_pass = getenv('PGPASSWORD');
            
            // Check if environment variables are set
            if (!$db_host || !$db_port || !$db_name || !$db_user || !$db_pass) {
                throw new Exception('Database configuration not found in environment variables');
            }
            
            // Create DSN
            $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name;";
            
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
        
        $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ") RETURNING id";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $stmt->fetchColumn();
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
        
        $set = [];
        foreach ($data as $column => $value) {
            $set[] = "$column = ?";
        }
        
        $whereClause = [];
        foreach ($where as $column => $value) {
            $whereClause[] = "$column = ?";
        }
        
        $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE " . implode(' AND ', $whereClause);
        
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
        
        $whereClause = [];
        foreach ($where as $column => $value) {
            $whereClause[] = "$column = ?";
        }
        
        $sql = "DELETE FROM $table WHERE " . implode(' AND ', $whereClause);
        
        $stmt = $conn->prepare($sql);
        $stmt->execute(array_values($where));
        
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log('Delete failed: ' . $e->getMessage());
        throw new Exception('Database delete failed: ' . $e->getMessage());
    }
}

/**
 * Fetch all rows from a statement
 *
 * @param PDOStatement $stmt The prepared statement
 * @return array The result rows
 */
function db_fetch_all($stmt) {
    return $stmt->fetchAll();
}

/**
 * Fetch a single row from a statement
 *
 * @param PDOStatement $stmt The prepared statement
 * @return array|false The result row or false if no rows
 */
function db_fetch_one($stmt) {
    return $stmt->fetch();
}

/**
 * Begin a transaction
 *
 * @return bool Success status
 */
function db_begin_transaction() {
    return db_connect()->beginTransaction();
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
    return db_connect()->rollBack();
}

/**
 * Ensure required tables exist in the database
 *
 * @param PDO $conn The database connection
 * @return void
 */
function ensure_tables_exist($conn) {
    // Check if tables exist
    $tables = [
        'users',
        'orders',
        'order_items',
        'commissions',
        'products'
    ];
    
    $existing_tables = [];
    
    $table_query = $conn->query("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'");
    while ($row = $table_query->fetch(PDO::FETCH_ASSOC)) {
        $existing_tables[] = $row['tablename'];
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
        } catch (PDOException $e) {
            // Rollback transaction
            $conn->rollBack();
            
            error_log('Table creation failed: ' . $e->getMessage());
            throw new Exception('Database initialization failed: ' . $e->getMessage());
        }
    }
}

/**
 * Create a table in the database
 *
 * @param PDO $conn The database connection
 * @param string $table The table name
 * @return void
 */
function create_table($conn, $table) {
    $create_sql = '';
    
    switch ($table) {
        case 'users':
            $create_sql = "
                CREATE TABLE users (
                    id SERIAL PRIMARY KEY,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    first_name VARCHAR(100) NOT NULL,
                    last_name VARCHAR(100) NOT NULL,
                    phone VARCHAR(20),
                    tier_id INTEGER DEFAULT 0,
                    is_admin BOOLEAN DEFAULT FALSE,
                    sponsor_id INTEGER,
                    replicated_site VARCHAR(255),
                    ghl_id VARCHAR(255),
                    pillars_id VARCHAR(255),
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (sponsor_id) REFERENCES users(id) ON DELETE SET NULL
                )
            ";
            break;
            
        case 'orders':
            $create_sql = "
                CREATE TABLE orders (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL,
                    ghl_order_id VARCHAR(255),
                    ghl_contact_id VARCHAR(255),
                    status VARCHAR(50) NOT NULL DEFAULT 'pending',
                    total DECIMAL(10, 2) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ";
            break;
            
        case 'order_items':
            $create_sql = "
                CREATE TABLE order_items (
                    id SERIAL PRIMARY KEY,
                    order_id INTEGER NOT NULL,
                    product_id INTEGER,
                    name VARCHAR(255) NOT NULL,
                    price DECIMAL(10, 2) NOT NULL,
                    quantity INTEGER NOT NULL DEFAULT 1,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
                )
            ";
            break;
            
        case 'commissions':
            $create_sql = "
                CREATE TABLE commissions (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL,
                    order_id INTEGER,
                    pillars_id VARCHAR(255),
                    status VARCHAR(50) NOT NULL DEFAULT 'pending',
                    amount DECIMAL(10, 2) NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
                )
            ";
            break;
            
        case 'products':
            $create_sql = "
                CREATE TABLE products (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    price DECIMAL(10, 2) NOT NULL,
                    tier_level INTEGER NOT NULL DEFAULT 0,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                )
            ";
            
            break;
    }
    
    if (!empty($create_sql)) {
        $conn->exec($create_sql);
        
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
 * @param PDO $conn The database connection
 * @return void
 */
function seed_products($conn) {
    $products = [
        [
            'name' => 'Basic Membership',
            'description' => 'Essential travel benefits and access to basic training materials.',
            'price' => 25.00,
            'tier_level' => 1
        ],
        [
            'name' => 'Premium Membership',
            'description' => 'Enhanced travel benefits and access to premium training materials.',
            'price' => 65.00,
            'tier_level' => 2
        ],
        [
            'name' => 'Elite Membership',
            'description' => 'VIP travel benefits, exclusive access to elite training materials, and premium support.',
            'price' => 500.00,
            'tier_level' => 3
        ]
    ];
    
    $sql = "
        INSERT INTO products (name, description, price, tier_level)
        VALUES (:name, :description, :price, :tier_level)
    ";
    
    $stmt = $conn->prepare($sql);
    
    foreach ($products as $product) {
        $stmt->bindParam(':name', $product['name']);
        $stmt->bindParam(':description', $product['description']);
        $stmt->bindParam(':price', $product['price']);
        $stmt->bindParam(':tier_level', $product['tier_level']);
        $stmt->execute();
    }
}

/**
 * Seed users table with admin user
 *
 * @param PDO $conn The database connection
 * @return void
 */
function seed_admin_user($conn) {
    // Check if a test user exists
    $sql = "SELECT COUNT(*) FROM users WHERE email = 'test@example.com'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $user_count = $stmt->fetchColumn();
    
    if ($user_count == 0) {
        // Create test user
        $test_user = [
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'first_name' => 'Test',
            'last_name' => 'User',
            'is_admin' => true,
            'tier_id' => 2 // Premium tier
        ];
        
        $sql = "
            INSERT INTO users (email, password, first_name, last_name, is_admin, tier_id)
            VALUES (:email, :password, :first_name, :last_name, :is_admin, :tier_id)
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $test_user['email']);
        $stmt->bindParam(':password', $test_user['password']);
        $stmt->bindParam(':first_name', $test_user['first_name']);
        $stmt->bindParam(':last_name', $test_user['last_name']);
        $stmt->bindParam(':is_admin', $test_user['is_admin']);
        $stmt->bindParam(':tier_id', $test_user['tier_id']);
        $stmt->execute();
    }
}