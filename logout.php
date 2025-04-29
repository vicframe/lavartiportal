<?php
require_once __DIR__ . '/includes/auth.php';

// Log the user out
logout_user();

// Redirect to login page
header('Location: /login.php?success=You have been successfully logged out.');
exit;
?>