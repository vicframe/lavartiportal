<?php
/**
 * Dashboard header template
 */
require_once __DIR__ . '/auth.php';

if (!isset($page_title)) {
    $page_title = APP_NAME;
}

// Check if user is logged in
$current_user = null;
if (function_exists('is_logged_in') && is_logged_in()) {
    $current_user = get_current_logged_user();
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
    <?php if (isset($custom_css)) echo $custom_css; ?>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
    /* Dashboard specific overrides */
    body.dashboard-body {
        background-color: #f9f9f9;
        overflow-x: hidden;
        padding: 0;
        margin: 0;
        display: block;
    }
    
    .dashboard-body header, .dashboard-body footer {
        display: none;
    }
    
    .dashboard-container {
        display: flex;
    }
    
    .flash-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        width: 350px;
    }
    
    /* Sidebar Styles */
    .sidebar {
        width: var(--sidebar-width, 250px);
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        background: #ffffff;
        border-right: 1px solid #e6e6e6;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        z-index: 1000;
    }
    
    .sidebar-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e6e6e6;
    }
    
    .logo a {
        font-size: 1.5rem;
        font-weight: 700;
        color: #4d4c7d;
        text-decoration: none;
        display: flex;
        flex-direction: column;
    }
    
    .logo .systems {
        font-size: 1rem;
        color: #f99417;
    }
    
    .sidebar-nav {
        flex: 1;
        overflow-y: auto;
        padding: 1rem 0;
    }
    
    .sidebar-nav ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .sidebar-nav li {
        margin-bottom: 0.5rem;
    }
    
    .sidebar-nav a {
        display: flex;
        align-items: center;
        padding: 0.75rem 1.5rem;
        color: #363062;
        text-decoration: none;
        transition: all 0.2s;
    }
    
    .sidebar-nav a:hover {
        background-color: rgba(77, 76, 125, 0.08);
    }
    
    .sidebar-nav a.active {
        background-color: rgba(77, 76, 125, 0.1);
        border-left: 3px solid #4d4c7d;
        font-weight: 600;
    }
    
    .sidebar-nav i {
        margin-right: 0.75rem;
        width: 20px;
        text-align: center;
    }
    
    .user-info {
        display: flex;
        align-items: center;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e6e6e6;
    }
    
    .avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #4d4c7d;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        margin-right: 0.75rem;
    }
    
    .details {
        flex: 1;
    }
    
    .name {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .tier-badge {
        font-size: 0.75rem;
        padding: 0.2rem 0.5rem;
        border-radius: 0.25rem;
        background-color: #f0f0f0;
        display: inline-block;
    }
    
    .tier-badge.basic {
        background-color: #e9ecef;
        color: #495057;
    }
    
    .tier-badge.premium {
        background-color: #ffc107;
        color: #495057;
    }
    
    .tier-badge.elite {
        background-color: #4d4c7d;
        color: white;
    }
    
    .sidebar-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid #e6e6e6;
    }
    
    .logout-btn {
        display: flex;
        align-items: center;
        color: #6c757d;
        text-decoration: none;
        padding: 0.5rem;
        border-radius: 0.25rem;
        transition: all 0.2s;
    }
    
    .logout-btn:hover {
        background-color: #f8f9fa;
        color: #dc3545;
    }
    
    .logout-btn i {
        margin-right: 0.5rem;
    }
    
    /* Main Content Styles */
    .main-content {
        flex: 1;
        margin-left: var(--sidebar-width, 250px);
        padding: 1.5rem;
        min-height: 100vh;
    }
    
    /* Top navigation styles */
    .top-nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 0;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid #e6e6e6;
    }
    
    .top-nav .left {
        display: flex;
        align-items: center;
    }
    
    .top-nav .right {
        display: flex;
        align-items: center;
    }
    
    .top-nav-item {
        margin-left: 1rem;
        position: relative;
    }
    
    .notification-icon {
        position: relative;
        font-size: 1.25rem;
        color: #6c757d;
        transition: all 0.2s;
    }
    
    .notification-icon:hover {
        color: #4d4c7d;
    }
    
    .notification-icon .badge {
        position: absolute;
        top: -5px;
        right: -5px;
        font-size: 0.6rem;
        background-color: #f99417;
        color: white;
        min-width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }
    
    .avatar-small {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: #4d4c7d;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.85rem;
    }
    
    .user-dropdown {
        cursor: pointer;
    }
    
    .user-dropdown-header {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
    }
    
    .user-dropdown-header .user-info {
        margin-left: 0.75rem;
        border-bottom: none;
        padding: 0;
    }
    
    .user-dropdown-header .name {
        font-size: 0.9rem;
        margin-bottom: 0.1rem;
    }
    
    .user-dropdown-header .email {
        font-size: 0.75rem;
        color: #6c757d;
    }
    
    .notification-dropdown {
        width: 320px;
        padding: 0;
        overflow: hidden;
    }
    
    .notification-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e6e6e6;
    }
    
    .notification-header h6 {
        margin: 0;
        font-weight: 600;
    }
    
    .notification-header a {
        font-size: 0.75rem;
        color: #4d4c7d;
        text-decoration: none;
    }
    
    .notification-body {
        max-height: 320px;
        overflow-y: auto;
    }
    
    .notification-item {
        display: flex;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f2f2f2;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .notification-item:hover {
        background-color: #f9f9f9;
    }
    
    .notification-item.unread {
        background-color: rgba(77, 76, 125, 0.05);
    }
    
    .notification-icon {
        width: 32px;
        margin-right: 0.75rem;
        color: #4d4c7d;
    }
    
    .notification-content {
        flex: 1;
    }
    
    .notification-text {
        font-size: 0.85rem;
        margin-bottom: 0.25rem;
        color: #333;
    }
    
    .notification-time {
        font-size: 0.7rem;
        color: #6c757d;
    }
    
    .notification-footer {
        padding: 0.75rem 1rem;
        text-align: center;
        border-top: 1px solid #e6e6e6;
    }
    
    .notification-footer a {
        font-size: 0.8rem;
        color: #4d4c7d;
        text-decoration: none;
    }
    
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
        opacity: 0.6;
    }
    
    .empty-state p {
        margin: 0;
        font-size: 0.85rem;
    }
    
    /* Welcome banner styles */
    .welcome-banner {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    
    .welcome-text h1 {
        margin: 0;
        font-size: 1.75rem;
        font-weight: 700;
        color: #363062;
    }
    
    .welcome-text p {
        margin: 0.25rem 0 0 0;
        color: #6c757d;
    }
    
    /* Support for sidebar toggle on mobile */
    @media (max-width: 767.98px) {
        .sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        
        .sidebar.show {
            transform: translateX(0);
        }
        
        .main-content {
            margin-left: 0;
        }
        
        .welcome-banner {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .upgrade-cta {
            margin-top: 1rem;
        }
    }
    </style>
</head>
<body class="dashboard-body">
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="flash-container">
            <?php $flash = $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>