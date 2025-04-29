<?php
/**
 * Logout page
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

// Log out the user
logout();

// Redirect to login page
header('Location: /login.php');
exit;