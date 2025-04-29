<?php
require_once __DIR__ . '/includes/auth.php';

// Logout the user
logout_user();

// Redirect to home page
header('Location: /index.php');
exit;
?>
