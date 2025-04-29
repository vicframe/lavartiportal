<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Start session if not already started
session_start_safe();

// Get current user if logged in
$current_user = get_current_user();
$is_logged_in = is_logged_in();

// Get any error or success messages
$error_message = isset($_GET['error']) ? get_error_message($_GET['error']) : '';
$success_message = isset($_GET['success']) ? $_GET['success'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/assets/css/styles.css" rel="stylesheet">
    
    <?php if (isset($extra_css) && $extra_css): ?>
    <link href="<?php echo $extra_css; ?>" rel="stylesheet">
    <?php endif; ?>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="/">
                    <strong>LaVarti</strong> Systems
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarMain">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <?php if ($is_logged_in): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && dirname($_SERVER['PHP_SELF']) == '/dashboard' ? 'active' : ''; ?>" href="/dashboard/index.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" href="/dashboard/products.php">Products</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'affiliate.php' ? 'active' : ''; ?>" href="/dashboard/affiliate.php">Affiliate</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'membership.php' ? 'active' : ''; ?>" href="/dashboard/membership.php">Membership</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" href="/dashboard/orders.php">Orders</a>
                        </li>
                        <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && dirname($_SERVER['PHP_SELF']) == '/' ? 'active' : ''; ?>" href="/index.php">Home</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <div class="d-flex">
                        <?php if ($is_logged_in): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="/dashboard/account.php">My Account</a></li>
                                <li><a class="dropdown-item" href="/dashboard/orders.php">My Orders</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/logout.php">Logout</a></li>
                            </ul>
                        </div>
                        <?php else: ?>
                        <a href="/login.php" class="btn btn-outline-light">Login</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <div class="container mt-4">
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
    </div>
    
    <main class="container my-4">
