<?php
/**
 * Database functions using MySQLi
 */

// Database connection instance
$db_conn = null;

/**
 * Connect to the database
 * 
 * @return mysqli Database connection
 */
function db_connect() {
    global $db_conn;
    
    if ($db_conn === null) {
        // Database configuration
        $db_host = 'localhost';
        $db_user = 'root';
        $db_pass = '';
        $db_name = 'lavartiportal';
        
        // Create connection
        $db_conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        // Check connection
        if ($db_conn->connect_error) {
            error_log("Connection failed: " . $db_conn->connect_error);
            die("Database connection failed. Please check your configuration.");
        }
        
        // Set character set
        $db_conn->set_charset("utf8mb4");
    }
    
    return $db_conn;
}

/**
 * Execute a query
 * 
 * @param string $sql SQL query
 * @return mysqli_result|bool Query result
 */
function db_query($sql) {
    $conn = db_connect();
    $result = $conn->query($sql);
    
    if ($result === false) {
        error_log("Query error: " . $conn->error . " - SQL: " . $sql);
        throw new Exception("Database query error: " . $conn->error);
    }
    
    return $result;
}

/**
 * Prepare and execute a statement
 * 
 * @param string $sql SQL query
 * @param string $types Parameter types (e.g., 'ssi' for string, string, integer)
 * @param array $params Parameters to bind
 * @return mysqli_stmt Prepared statement
 */
function db_prepare($sql, $types = '', $params = []) {
    $conn = db_connect();
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        error_log("Prepare error: " . $conn->error . " - SQL: " . $sql);
        throw new Exception("Database prepare error: " . $conn->error);
    }
    
    if (!empty($params)) {
        // Dynamically bind parameters
        $bindParams = array();
        $bindParams[] = $types;
        
        for ($i = 0; $i < count($params); $i++) {
            $bindParams[] = &$params[$i];
        }
        
        call_user_func_array(array($stmt, 'bind_param'), $bindParams);
    }
    
    $result = $stmt->execute();
    
    if ($result === false) {
        error_log("Execute error: " . $stmt->error . " - SQL: " . $sql);
        throw new Exception("Database execute error: " . $stmt->error);
    }
    
    return $stmt;
}

/**
 * Insert data into a table
 * 
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @return int Inserted ID
 */
function db_insert($table, $data) {
    $conn = db_connect();
    
    $columns = array_keys($data);
    $values = array_values($data);
    
    $placeholder = '';
    $types = '';
    
    foreach ($values as $value) {
        $placeholder .= '?,';
        
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } elseif (is_bool($value)) {
            $types .= 'i';
            // Convert boolean to integer (0 or 1)
            $key = array_search($value, $values);
            $values[$key] = $value ? 1 : 0;
        } else {
            $types .= 's';
        }
    }
    
    $placeholder = rtrim($placeholder, ',');
    
    $sql = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES ($placeholder)";
    
    $stmt = db_prepare($sql, $types, $values);
    $insertId = $stmt->insert_id;
    $stmt->close();
    
    return $insertId;
}

/**
 * Update data in a table
 * 
 * @param string $table Table name
 * @param array $data Associative array of column => value to update
 * @param array $where Associative array of column => value for WHERE clause
 * @return int Number of affected rows
 */
function db_update($table, $data, $where) {
    $conn = db_connect();
    
    $set = '';
    $whereClause = '';
    $values = [];
    $types = '';
    
    foreach ($data as $column => $value) {
        $set .= "$column = ?,";
        $values[] = $value;
        
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } elseif (is_bool($value)) {
            $types .= 'i';
            // Convert boolean to integer (0 or 1)
            $key = array_search($value, $values);
            $values[$key] = $value ? 1 : 0;
        } else {
            $types .= 's';
        }
    }
    
    $set = rtrim($set, ',');
    
    foreach ($where as $column => $value) {
        $whereClause .= "$column = ? AND ";
        $values[] = $value;
        
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } elseif (is_bool($value)) {
            $types .= 'i';
            // Convert boolean to integer (0 or 1)
            $key = array_search($value, $values);
            $values[$key] = $value ? 1 : 0;
        } else {
            $types .= 's';
        }
    }
    
    $whereClause = rtrim($whereClause, ' AND ');
    
    $sql = "UPDATE $table SET $set WHERE $whereClause";
    
    $stmt = db_prepare($sql, $types, $values);
    $affectedRows = $stmt->affected_rows;
    $stmt->close();
    
    return $affectedRows;
}

/**
 * Delete data from a table
 * 
 * @param string $table Table name
 * @param array $where Associative array of column => value for WHERE clause
 * @return int Number of affected rows
 */
function db_delete($table, $where) {
    $conn = db_connect();
    
    $whereClause = '';
    $values = [];
    $types = '';
    
    foreach ($where as $column => $value) {
        $whereClause .= "$column = ? AND ";
        $values[] = $value;
        
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } elseif (is_bool($value)) {
            $types .= 'i';
            // Convert boolean to integer (0 or 1)
            $key = array_search($value, $values);
            $values[$key] = $value ? 1 : 0;
        } else {
            $types .= 's';
        }
    }
    
    $whereClause = rtrim($whereClause, ' AND ');
    
    $sql = "DELETE FROM $table WHERE $whereClause";
    
    $stmt = db_prepare($sql, $types, $values);
    $affectedRows = $stmt->affected_rows;
    $stmt->close();
    
    return $affectedRows;
}

/**
 * Get a single row from a table
 * 
 * @param string $table Table name
 * @param array $where Associative array of column => value for WHERE clause
 * @param string $columns Columns to select (default: *)
 * @return array|null Row data or null if not found
 */
function db_get_row($table, $where, $columns = '*') {
    $conn = db_connect();
    
    $whereClause = '';
    $values = [];
    $types = '';
    
    foreach ($where as $column => $value) {
        $whereClause .= "$column = ? AND ";
        $values[] = $value;
        
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } elseif (is_bool($value)) {
            $types .= 'i';
            // Convert boolean to integer (0 or 1)
            $key = array_search($value, $values);
            $values[$key] = $value ? 1 : 0;
        } else {
            $types .= 's';
        }
    }
    
    $whereClause = rtrim($whereClause, ' AND ');
    
    $sql = "SELECT $columns FROM $table WHERE $whereClause LIMIT 1";
    
    $stmt = db_prepare($sql, $types, $values);
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row;
}

/**
 * Get multiple rows from a table
 * 
 * @param string $table Table name
 * @param array $where Associative array of column => value for WHERE clause
 * @param string $columns Columns to select (default: *)
 * @param string $orderBy ORDER BY clause (default: '')
 * @param int $limit LIMIT clause (default: 0 = no limit)
 * @param int $offset OFFSET clause (default: 0)
 * @return array Array of rows
 */
function db_get_rows($table, $where = [], $columns = '*', $orderBy = '', $limit = 0, $offset = 0) {
    $conn = db_connect();
    
    $whereClause = '';
    $values = [];
    $types = '';
    
    if (!empty($where)) {
        $whereClause = 'WHERE ';
        
        foreach ($where as $column => $value) {
            $whereClause .= "$column = ? AND ";
            $values[] = $value;
            
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } elseif (is_bool($value)) {
                $types .= 'i';
                // Convert boolean to integer (0 or 1)
                $key = array_search($value, $values);
                $values[$key] = $value ? 1 : 0;
            } else {
                $types .= 's';
            }
        }
        
        $whereClause = rtrim($whereClause, ' AND ');
    }
    
    $sql = "SELECT $columns FROM $table $whereClause";
    
    if (!empty($orderBy)) {
        $sql .= " ORDER BY $orderBy";
    }
    
    if ($limit > 0) {
        $sql .= " LIMIT $limit";
    }
    
    if ($offset > 0) {
        $sql .= " OFFSET $offset";
    }
    
    if (empty($where)) {
        $result = db_query($sql);
    } else {
        $stmt = db_prepare($sql, $types, $values);
        $result = $stmt->get_result();
    }
    
    $rows = [];
    
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    
    if (isset($stmt)) {
        $stmt->close();
    }
    
    return $rows;
}

/**
 * Escape a string for use in a query
 * 
 * @param string $str String to escape
 * @return string Escaped string
 */
function db_escape($str) {
    $conn = db_connect();
    return $conn->real_escape_string($str);
}