<?php
/**
 * Authentication functions
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Attempt to log in a user
 *
 * @param string $email The user's email
 * @param string $password The user's password
 * @param bool $remember Whether to remember the login
 * @return array|false The user data or false on failure
 */
function login($email, $password, $remember = false) {
    try {
        // Sanitize input
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Get user by email
        $query = db_query("SELECT * FROM users WHERE email = ?", [$email]);
        $user = db_fetch_one($query);
        
        if (!$user) {
            // User not found
            return false;
        }
        
        // For debugging
        error_log('Attempting password verify: email=' . $email . ', password hash=' . $user['password']);
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            // Invalid password
            error_log('Password verification failed for: ' . $email);
            return false;
        }
        
        // Update password hash if necessary
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            db_update('users', ['password' => $new_hash], ['id' => $user['id']]);
        }
        
        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_admin'] = (bool)$user['is_admin'];
        $_SESSION['login_time'] = time();
        
        // Set remember cookie if requested
        if ($remember) {
            $token = generate_token();
            $expires = time() + (30 * 24 * 60 * 60); // 30 days
            
            // Store token in database
            $remember_data = [
                'user_id' => $user['id'],
                'token' => password_hash($token, PASSWORD_DEFAULT),
                'expires' => date('Y-m-d H:i:s', $expires)
            ];
            
            // Check if remember token table exists
            try {
                $table_exists = db_query("SELECT to_regclass('remember_tokens')");
                $exists = db_fetch_one($table_exists);
                
                if (!$exists || $exists['to_regclass'] === null) {
                    // Create table
                    db_query("
                        CREATE TABLE remember_tokens (
                            id SERIAL PRIMARY KEY,
                            user_id INTEGER NOT NULL,
                            token VARCHAR(255) NOT NULL,
                            expires TIMESTAMP NOT NULL,
                            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                        )
                    ");
                }
                
                // Insert token
                db_insert('remember_tokens', $remember_data);
                
                // Set cookie
                setcookie('remember_token', $token, $expires, '/', '', true, true);
                setcookie('remember_user', $user['id'], $expires, '/', '', true, true);
                
            } catch (Exception $e) {
                // Log error but continue
                error_log('Failed to store remember token: ' . $e->getMessage());
            }
        }
        
        // Log login activity
        log_activity("User logged in: {$user['email']}", 'info');
        
        return $user;
    } catch (Exception $e) {
        error_log('Login failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Log out the current user
 *
 * @return void
 */
function logout() {
    // Clear remember token
    if (isset($_COOKIE['remember_token']) && isset($_COOKIE['remember_user'])) {
        $user_id = $_COOKIE['remember_user'];
        
        try {
            // Delete token from database
            db_query("DELETE FROM remember_tokens WHERE user_id = ?", [$user_id]);
        } catch (Exception $e) {
            // Log error but continue
            error_log('Failed to delete remember token: ' . $e->getMessage());
        }
        
        // Clear cookies
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        setcookie('remember_user', '', time() - 3600, '/', '', true, true);
    }
    
    // Clear session
    session_unset();
    session_destroy();
    
    // Start new session
    session_start();
}

/**
 * Check if a user is logged in
 *
 * @return bool True if logged in, false otherwise
 */
function is_logged_in() {
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    
    // Check for remember token
    if (isset($_COOKIE['remember_token']) && isset($_COOKIE['remember_user'])) {
        $token = $_COOKIE['remember_token'];
        $user_id = $_COOKIE['remember_user'];
        
        try {
            // Get token from database
            $query = db_query("
                SELECT * FROM remember_tokens 
                WHERE user_id = ? AND expires > CURRENT_TIMESTAMP
                ORDER BY created_at DESC
                LIMIT 1
            ", [$user_id]);
            
            $stored = db_fetch_one($query);
            
            if ($stored && password_verify($token, $stored['token'])) {
                // Token is valid, get user data
                $user_query = db_query("SELECT * FROM users WHERE id = ?", [$user_id]);
                $user = db_fetch_one($user_query);
                
                if ($user) {
                    // Set session data
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['is_admin'] = (bool)$user['is_admin'];
                    $_SESSION['login_time'] = time();
                    
                    // Log login activity
                    log_activity("User logged in via remember token: {$user['email']}", 'info');
                    
                    return true;
                }
            }
        } catch (Exception $e) {
            // Log error but continue
            error_log('Remember token verification failed: ' . $e->getMessage());
        }
        
        // Invalid token, clear cookies
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        setcookie('remember_user', '', time() - 3600, '/', '', true, true);
    }
    
    return false;
}

/**
 * Get the current logged in user
 *
 * @return array|false The user data or false if not logged in
 */
function get_current_logged_user() {
    if (isset($_SESSION['user_id'])) {
        $query = db_query("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
        return db_fetch_one($query);
    }
    
    return false;
}

/**
 * Require login to access a page
 *
 * @param string $redirect_url The URL to redirect to if not logged in
 * @return void
 */
function require_login($redirect_url = '/login.php') {
    if (!is_logged_in()) {
        // Store current URL for redirect after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login page
        header("Location: $redirect_url");
        exit;
    }
}

/**
 * Register a new user
 *
 * @param array $user_data The user data
 * @return int|false The user ID or false on failure
 */
function register_user($user_data) {
    try {
        // Sanitize input
        $email = filter_var($user_data['email'], FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address');
        }
        
        // Check if email already exists
        $query = db_query("SELECT id FROM users WHERE email = ?", [$email]);
        $existing_user = db_fetch_one($query);
        
        if ($existing_user) {
            throw new Exception('Email address already registered');
        }
        
        // Hash password
        $password_hash = password_hash($user_data['password'], PASSWORD_DEFAULT);
        
        // Prepare user data
        $insert_data = [
            'email' => $email,
            'password' => $password_hash,
            'first_name' => $user_data['first_name'],
            'last_name' => $user_data['last_name'],
            'phone' => $user_data['phone'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Set sponsor if provided
        if (isset($user_data['sponsor_id']) && !empty($user_data['sponsor_id'])) {
            // Verify sponsor exists
            $sponsor_query = db_query("SELECT id FROM users WHERE id = ?", [$user_data['sponsor_id']]);
            $sponsor = db_fetch_one($sponsor_query);
            
            if ($sponsor) {
                $insert_data['sponsor_id'] = $user_data['sponsor_id'];
            }
        }
        
        // Insert user
        $user_id = db_insert('users', $insert_data);
        
        if (!$user_id) {
            throw new Exception('Failed to create user account');
        }
        
        // Log activity
        log_activity("New user registered: {$email}", 'info');
        
        return $user_id;
    } catch (Exception $e) {
        error_log('Registration failed: ' . $e->getMessage());
        throw $e; // Re-throw for caller to handle
    }
}

/**
 * Update user profile
 *
 * @param int $user_id The user ID
 * @param array $user_data The user data to update
 * @return bool Success status
 */
function update_user_profile($user_id, $user_data) {
    try {
        // Get current user data
        $query = db_query("SELECT * FROM users WHERE id = ?", [$user_id]);
        $user = db_fetch_one($query);
        
        if (!$user) {
            throw new Exception('User not found');
        }
        
        // Prepare update data
        $update_data = [
            'first_name' => $user_data['first_name'] ?? $user['first_name'],
            'last_name' => $user_data['last_name'] ?? $user['last_name'],
            'phone' => $user_data['phone'] ?? $user['phone'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Update user
        $result = db_update('users', $update_data, ['id' => $user_id]);
        
        // Log activity
        log_activity("User profile updated: {$user['email']}", 'info');
        
        return $result > 0;
    } catch (Exception $e) {
        error_log('Profile update failed: ' . $e->getMessage());
        throw $e; // Re-throw for caller to handle
    }
}

/**
 * Change user password
 *
 * @param int $user_id The user ID
 * @param string $current_password The current password
 * @param string $new_password The new password
 * @return bool Success status
 */
function change_user_password($user_id, $current_password, $new_password) {
    try {
        // Get current user data
        $query = db_query("SELECT * FROM users WHERE id = ?", [$user_id]);
        $user = db_fetch_one($query);
        
        if (!$user) {
            throw new Exception('User not found');
        }
        
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            throw new Exception('Current password is incorrect');
        }
        
        // Hash new password
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password
        $result = db_update('users', ['password' => $password_hash, 'updated_at' => date('Y-m-d H:i:s')], ['id' => $user_id]);
        
        // Log activity
        log_activity("User password changed: {$user['email']}", 'info');
        
        return $result > 0;
    } catch (Exception $e) {
        error_log('Password change failed: ' . $e->getMessage());
        throw $e; // Re-throw for caller to handle
    }
}

/**
 * Check if a user is an admin
 *
 * @return bool True if admin, false otherwise
 */
function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Require admin access to a page
 *
 * @param string $redirect_url The URL to redirect to if not admin
 * @return void
 */
function require_admin($redirect_url = '/dashboard') {
    require_login();
    
    if (!is_admin()) {
        // Redirect to dashboard
        header("Location: $redirect_url");
        exit;
    }
}