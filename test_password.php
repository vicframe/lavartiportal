<?php
// Test password hashing for debugging

// Standard password_hash function
$plaintext_password = "password123";
$hash = password_hash($plaintext_password, PASSWORD_DEFAULT);

echo "Password: $plaintext_password\n";
echo "Generated Hash: $hash\n";

// Test verification against known hash from the database
$db_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$verification = password_verify($plaintext_password, $db_hash);

echo "Verification result against known DB hash: " . ($verification ? "SUCCESS" : "FAILED") . "\n";

// The test hash $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi is the hash of 'password'
// Let's test with 'password' too
$verification2 = password_verify("password", $db_hash);
echo "Verification with 'password': " . ($verification2 ? "SUCCESS" : "FAILED") . "\n";
?>