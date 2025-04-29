<?php
/**
 * Homepage - Redirects to login
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

// Check for referral parameter
$ref = isset($_GET['ref']) ? $_GET['ref'] : null;

if ($ref) {
    // Store referral ID in session
    $_SESSION['referral'] = $ref;
}

// If user is already logged in, redirect to dashboard
if (is_logged_in()) {
    header("Location: /dashboard/");
    exit;
}

// Otherwise, redirect to login page
header("Location: /login.php");
exit;
?>