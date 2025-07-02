<?php
/**
 * Helper functions for the application
 */

/**
 * Get product tier name from tier ID
 *
 * @param int $tier_id The tier ID
 * @return string The tier name
 */
function get_tier_name($tier_id) {
    switch ($tier_id) {
        case 1:
            return 'Basic';
        case 2:
            return 'Premium';
        case 3:
            return 'Elite';
        default:
            return 'None';
    }
}

/**
 * Format currency with dollar sign
 *
 * @param float $amount The amount to format
 * @return string Formatted currency string
 */
function format_currency($amount) {
    return '$' . number_format((float)$amount, 2, '.', ',');
}

/**
 * Format date in a user-friendly way
 *
 * @param string $date_string The date string
 * @param string $format The format (default: 'F j, Y')
 * @return string Formatted date
 */
function format_date($date_string, $format = 'F j, Y') {
    return date($format, strtotime($date_string));
}

/**
 * Generate a random string
 *
 * @param int $length The length of the string
 * @return string Random string
 */
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $characters_length = strlen($characters);
    $random_string = '';
    
    for ($i = 0; $i < $length; $i++) {
        $random_string .= $characters[rand(0, $characters_length - 1)];
    }
    
    return $random_string;
}

/**
 * Get all products/membership tiers
 *
 * @return array Array of products
 */
function get_all_products() {
    try {
        $products = [
            [
                'id' => 1,
                'name' => 'PASSPORT LITE',
                'description' => 'Unlocks Monthly Access of up to 70% off bookings on places to stay and accommodations all around the world',
                'price' => 25.00,
                'tier_level' => 803
            ],
            [
                'id' => 2,
                'name' => 'PASSPORT',
                'description' => 'Unlocks Monthly Access of up to 70% off bookings for:',
                'price' => 65.00,
                'tier_level' => 793
            ],
            [
                'id' => 3,
                'name' => 'TRAVEL AGENT',
                'description' => 'Unlocks Monthly Access to resources.',
                'price' => 99.00,
                'tier_level' => 826
            ]
        ];
        
        return $products;
    } catch (Exception $e) {
        error_log('Error getting products: ' . $e->getMessage());
        return [];
    }
}
function get_product_by_tier_level($id) {
    $products = get_all_products();

    foreach ($products as $product) {
        if ((int)$product['tier_level'] === (int)$id) {
            return $product;
        }
    }

    return null; // Not found
}
/**
 * Get product by ID
 *
 * @param int $product_id The product ID
 * @return array|null The product or null if not found
 */
function get_product_by_id($product_id) {
    $products = get_all_products();
    
    foreach ($products as $product) {
        if ($product['id'] == $product_id) {
            return $product;
        }
    }
    
    return null;
}

/**
 * Sanitize user input
 *
 * @param string $input The input string
 * @return string Sanitized string
 */
function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if email is valid
 *
 * @param string $email The email to check
 * @return bool True if valid, false otherwise
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Note: redirect() function is now defined in config.php
// with improved URL handling for subdirectory installations

/**
 * Set flash message in session
 *
 * @param string $type The message type (success, error, warning, info)
 * @param string $message The message text
 * @return void
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message from session
 *
 * @return array|null The flash message or null if not set
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $flash_message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash_message;
    }
    
    return null;
}

/**
 * Generate a secure token
 *
 * @return string Secure token
 */
function generate_token() {
    return bin2hex(random_bytes(32));
}

/**
 * Log application activity
 *
 * @param string $message The message to log
 * @param string $level The log level (info, warning, error)
 * @return void
 */
function log_activity($message, $level = 'info') {
    $log_file = LOG_DIR . '/' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    // Create log directory if it doesn't exist
    if (!file_exists(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
}