<?php
/**
 * Database connection and query helper functions
 */

// Create database connection
function db_connect() {
    static $conn;
    
    if ($conn === NULL) {
        try {
            // Get PostgreSQL connection details from environment variables
            $host = getenv('PGHOST') ?: DB_HOST;
            $port = getenv('PGPORT') ?: '5432';
            $dbname = getenv('PGDATABASE') ?: DB_NAME;
            $user = getenv('PGUSER') ?: DB_USER;
            $password = getenv('PGPASSWORD') ?: DB_PASS;
            
            $conn_string = "host=$host port=$port dbname=$dbname user=$user password=$password";
            $conn = pg_connect($conn_string);
            
            if (!$conn) {
                throw new Exception("PostgreSQL connection failed: " . pg_last_error());
            }
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
        // Replace ? with $1, $2, etc. for PostgreSQL
        if (!empty($params)) {
            $count = 0;
            $sql = preg_replace_callback('/\?/', function($matches) use (&$count) {
                $count++;
                return '$' . $count;
            }, $sql);
        }
        
        $result = !empty($params) ? pg_query_params($conn, $sql, $params) : pg_query($conn, $sql);
        
        if ($result === false) {
            throw new Exception("Query execution failed: " . pg_last_error($conn));
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
    
    if ($result && pg_num_rows($result) > 0) {
        while ($row = pg_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    
    return $rows;
}

// Fetch single row from a result
function db_fetch_one($result) {
    if ($result && pg_num_rows($result) > 0) {
        return pg_fetch_assoc($result);
    }
    
    return null;
}

// Get the ID from last insert
function db_last_insert_id() {
    $conn = db_connect();
    $result = pg_query($conn, "SELECT lastval()");
    $row = pg_fetch_row($result);
    return $row[0];
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
    
    return $result !== false && pg_num_rows($result) > 0;
}

// Create the database tables if they don't exist
function db_initialize() {
    $conn = db_connect();
    
    // Create enum types for PostgreSQL
    pg_query($conn, "
        DO $$
        BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'user_status') THEN
                CREATE TYPE user_status AS ENUM ('active', 'inactive', 'pending');
            END IF;
            
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'order_status') THEN
                CREATE TYPE order_status AS ENUM ('pending', 'processing', 'completed', 'failed', 'refunded');
            END IF;
            
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'sync_status') THEN
                CREATE TYPE sync_status AS ENUM ('pending', 'synced', 'failed');
            END IF;
            
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'recurring_interval') THEN
                CREATE TYPE recurring_interval AS ENUM ('monthly', 'yearly');
            END IF;
            
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'product_status') THEN
                CREATE TYPE product_status AS ENUM ('active', 'inactive');
            END IF;
            
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'commission_type') THEN
                CREATE TYPE commission_type AS ENUM ('direct', 'override', 'bonus');
            END IF;
            
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'commission_status') THEN
                CREATE TYPE commission_status AS ENUM ('pending', 'approved', 'paid', 'declined');
            END IF;
            
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'entity_type') THEN
                CREATE TYPE entity_type AS ENUM ('user', 'order', 'commission');
            END IF;
            
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'system_type') THEN
                CREATE TYPE system_type AS ENUM ('ghl', 'pillars', 'rsi');
            END IF;
            
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'webhook_status') THEN
                CREATE TYPE webhook_status AS ENUM ('received', 'processing', 'completed', 'failed');
            END IF;
        END
        $$;
    ");
    
    // Create users table
    pg_query($conn, "
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            ghl_id VARCHAR(255) UNIQUE,
            pillars_id VARCHAR(255) UNIQUE,
            email VARCHAR(255) UNIQUE,
            first_name VARCHAR(255),
            last_name VARCHAR(255),
            phone VARCHAR(50),
            tier_id INT DEFAULT 0,
            sponsor_id VARCHAR(255),
            replicated_site VARCHAR(255),
            status user_status DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create products table
    pg_query($conn, "
        CREATE TABLE IF NOT EXISTS products (
            id SERIAL PRIMARY KEY,
            ghl_id VARCHAR(255) UNIQUE,
            name VARCHAR(255),
            description TEXT,
            tier_level INT,
            price DECIMAL(10, 2),
            recurring BOOLEAN DEFAULT FALSE,
            recurring_interval recurring_interval DEFAULT 'monthly',
            status product_status DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create orders table
    pg_query($conn, "
        CREATE TABLE IF NOT EXISTS orders (
            id SERIAL PRIMARY KEY,
            ghl_id VARCHAR(255) UNIQUE,
            pillars_id VARCHAR(255) UNIQUE,
            user_id INT REFERENCES users(id),
            product_id INT REFERENCES products(id),
            amount DECIMAL(10, 2),
            status order_status DEFAULT 'pending',
            order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            sync_status sync_status DEFAULT 'pending',
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create commissions table
    pg_query($conn, "
        CREATE TABLE IF NOT EXISTS commissions (
            id SERIAL PRIMARY KEY,
            user_id INT REFERENCES users(id),
            order_id INT REFERENCES orders(id),
            amount DECIMAL(10, 2),
            commission_type commission_type DEFAULT 'direct',
            status commission_status DEFAULT 'pending',
            processed_date TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create sync_logs table
    pg_query($conn, "
        CREATE TABLE IF NOT EXISTS sync_logs (
            id SERIAL PRIMARY KEY,
            entity_type entity_type,
            entity_id INT,
            source_system system_type,
            target_system system_type,
            status VARCHAR(50),
            message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create webhook_logs table
    pg_query($conn, "
        CREATE TABLE IF NOT EXISTS webhook_logs (
            id SERIAL PRIMARY KEY,
            source VARCHAR(50),
            event_type VARCHAR(100),
            payload TEXT,
            processed BOOLEAN DEFAULT FALSE,
            status webhook_status DEFAULT 'received',
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            processed_at TIMESTAMP
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
