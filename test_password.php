<?php
/**
 * Test password verification
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/database.php';

// Test credentials
$email = 'test@example.com';
$password = 'password123';

// Get user by email
$query = db_query("SELECT * FROM users WHERE email = ?", [$email]);
$user = db_fetch_one($query);

if (!$user) {
    echo "User not found with email: $email";
    exit;
}

echo "User found: " . $user['email'] . " (ID: " . $user['id'] . ")<br>";
echo "Password hash in database: " . $user['password'] . "<br>";

// Test password verification
if (password_verify($password, $user['password'])) {
    echo "Password verification SUCCESS for 'password123'";
} else {
    echo "Password verification FAILED for 'password123'";
}