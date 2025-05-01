<?php
/**
 * Admin header template
 */
require_once __DIR__ . '/auth.php';

if (!isset($page_title)) {
    $page_title = APP_NAME . ' Admin';
}

// Check if user is logged in
$current_user = null;
if (function_exists('is_logged_in') && is_logged_in()) {
    $current_user = get_current_logged_user();
}

// Check if user is admin
if (!isset($current_user['is_admin']) || !$current_user['is_admin']) {
    // Set flash message
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'You do not have permission to access this page.'
    ];
    
    // Redirect to dashboard
    header('Location: ' . url('dashboard'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo asset_url('assets/css/styles.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('assets/css/admin.css'); ?>" rel="stylesheet">
    <?php if (isset($custom_css)) echo $custom_css; ?>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="admin-container">
        <!-- Admin sidebar -->
        <div class="admin-sidebar">
            <div class="admin-logo">
                <a href="<?php echo url('admin'); ?>">
                    LaVarti Admin
                </a>
            </div>
            
            <ul class="admin-menu">
                <li>
                    <a href="<?php echo url('admin'); ?>" class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo url('admin/users.php'); ?>" class="<?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> User Management
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo url('admin/orders.php'); ?>" class="<?php echo basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-cart"></i> Order Management
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo url('admin/commissions.php'); ?>" class="<?php echo basename($_SERVER['PHP_SELF']) === 'commissions.php' ? 'active' : ''; ?>">
                        <i class="fas fa-money-bill-alt"></i> Commission Management
                    </a>
                </li>
                
                <h6>System</h6>
                
                <li>
                    <a href="<?php echo url('admin/integrations.php'); ?>" class="<?php echo basename($_SERVER['PHP_SELF']) === 'integrations.php' ? 'active' : ''; ?>">
                        <i class="fas fa-plug"></i> Integrations
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo url('webhook_setup.php'); ?>" class="<?php echo basename($_SERVER['PHP_SELF']) === 'webhook_setup.php' ? 'active' : ''; ?>">
                        <i class="fas fa-link"></i> Webhook Setup
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo url('admin/settings.php'); ?>" class="<?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo url('admin/logs.php'); ?>" class="<?php echo basename($_SERVER['PHP_SELF']) === 'logs.php' ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-list"></i> System Logs
                    </a>
                </li>
                
                <h6>Navigation</h6>
                
                <li>
                    <a href="<?php echo url('dashboard'); ?>">
                        <i class="fas fa-home"></i> User Dashboard
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo url('logout.php'); ?>">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Admin main content -->
        <div class="admin-main">
            <!-- Admin header -->
            <div class="admin-header">
                <div>
                    <h1><?php echo $page_title; ?></h1>
                </div>
                
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle text-decoration-none" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="me-2"><?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></span>
                            <div class="user-card-avatar" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                <?php echo substr($current_user['first_name'] ?? 'A', 0, 1) . substr($current_user['last_name'] ?? 'U', 0, 1); ?>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="<?php echo url('dashboard/account.php'); ?>"><i class="fas fa-user-circle me-2"></i> My Account</a></li>
                            <li><a class="dropdown-item" href="<?php echo url('admin/settings.php'); ?>"><i class="fas fa-cog me-2"></i> Admin Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo url('logout.php'); ?>"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <?php if (isset($_SESSION['flash_message'])): ?>
                <?php $flash = $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $flash['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>