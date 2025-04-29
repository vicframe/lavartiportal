<?php
/**
 * Authentication and authorization functions
 */

// Start the session if not already started
function session_start_safe() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Authenticate a user with GHL
function authenticate_user($email, $password) {
    require_once __DIR__ . '/../api/ghl_api.php';
    
    try {
        $user_data = ghl_authenticate($email, $password);
        
        if (!$user_data) {
            return false;
        }
        
        // Check if user exists in our database
        $user = get_user_by_ghl_id($user_data['id']);
        
        if (!$user) {
            // Create new user
            $user_id = create_user([
                'ghl_id' => $user_data['id'],
                'email' => $user_data['email'],
                'first_name' => $user_data['firstName'] ?? '',
                'last_name' => $user_data['lastName'] ?? '',
                'phone' => $user_data['phone'] ?? '',
                'status' => 'active'
            ]);
            
            if (!$user_id) {
                return false;
            }
            
            $user = get_user_by_id($user_id);
        }
        
        // Check if user is active
        if ($user['status'] !== 'active') {
            return false;
        }
        
        // Set session variables
        session_start_safe();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['ghl_id'] = $user['ghl_id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['tier_id'] = $user['tier_id'];
        $_SESSION['last_activity'] = time();
        
        // Authenticate with Pillars if ID exists
        if ($user['pillars_id']) {
            require_once __DIR__ . '/../api/pillars_api.php';
            $pillars_session = pillars_authenticate_by_id($user['pillars_id']);
            
            if ($pillars_session) {
                $_SESSION['pillars_token'] = $pillars_session['token'];
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        log_event("Authentication error: " . $e->getMessage(), 'error');
        return false;
    }
}

// Check if user is logged in
function is_logged_in() {
    session_start_safe();
    
    if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity'])) {
        // Check if session has expired
        if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
            logout_user();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    return false;
}

// Logout user
function logout_user() {
    session_start_safe();
    
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
    
    return true;
}

// Require login for page access
function require_login() {
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

// Get current user data
function get_current_logged_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    return get_user_by_id($_SESSION['user_id']);
}

// Check if user has access to specific tier content
function current_user_has_tier_access($required_tier) {
    if (!is_logged_in()) {
        return false;
    }
    
    return $_SESSION['tier_id'] >= $required_tier;
}

// Verify the current user is at least at the given tier level
function require_tier_access($required_tier) {
    require_login();
    
    if (!current_user_has_tier_access($required_tier)) {
        header('Location: /dashboard/index.php?error=permission_denied');
        exit;
    }
}

// Generate a authentication token for API calls
function generate_auth_token($user_id) {
    $payload = [
        'user_id' => $user_id,
        'exp' => time() + 3600, // 1 hour expiration
        'iat' => time(),
        'nonce' => generate_random_string(16)
    ];
    
    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];
    
    $header_encoded = base64_encode(json_encode($header));
    $payload_encoded = base64_encode(json_encode($payload));
    
    $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", WEBHOOK_SECRET);
    
    return "$header_encoded.$payload_encoded.$signature";
}

// Verify a authentication token
function verify_auth_token($token) {
    if (empty($token)) {
        return false;
    }
    
    $parts = explode('.', $token);
    
    if (count($parts) !== 3) {
        return false;
    }
    
    list($header_encoded, $payload_encoded, $signature) = $parts;
    
    $expected_signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", WEBHOOK_SECRET);
    
    if (!hash_equals($expected_signature, $signature)) {
        return false;
    }
    
    $payload = json_decode(base64_decode($payload_encoded), true);
    
    if ($payload === null) {
        return false;
    }
    
    if (!isset($payload['exp']) || $payload['exp'] < time()) {
        return false;
    }
    
    return $payload;
}
?>
