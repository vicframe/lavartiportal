<?php
// Get current user
$current_user = get_current_logged_user();
$is_admin = isset($current_user['is_admin']) && $current_user['is_admin'];

// Get current page for highlighting active menu item
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <a href="/dashboard">
                <span>LaVarti</span>
                <span class="systems">Systems</span>
            </a>
        </div>
        <button class="menu-toggle d-md-none" id="sidebarToggle">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="user-info">
        <div class="avatar">
            <span><?php echo substr($current_user['first_name'] ?? 'U', 0, 1) . substr($current_user['last_name'] ?? 'U', 0, 1); ?></span>
        </div>
        <div class="details">
            <div class="name"><?php echo ($current_user['first_name'] ?? '') . ' ' . ($current_user['last_name'] ?? ''); ?></div>
            <div class="membership">
                <?php
                $tier_badge = '';
                $tier_name = 'No Membership';
                
                switch ($current_user['tier_level'] ?? 0) {
                    case 1:
                        $tier_badge = 'basic';
                        $tier_name = 'Basic Member';
                        break;
                    case 2:
                        $tier_badge = 'premium';
                        $tier_name = 'Premium Member';
                        break;
                    case 3:
                        $tier_badge = 'elite';
                        $tier_name = 'Elite Member';
                        break;
                }
                ?>
                
                <span class="tier-badge <?php echo $tier_badge; ?>"><?php echo $tier_name; ?></span>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="/dashboard" class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li>
                <a href="/dashboard/orders.php" class="<?php echo $current_page === 'orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span>My Orders</span>
                </a>
            </li>
            
            <li>
                <a href="/dashboard/membership.php" class="<?php echo $current_page === 'membership.php' ? 'active' : ''; ?>">
                    <i class="fas fa-crown"></i>
                    <span>Membership</span>
                </a>
            </li>
            
            <li>
                <a href="/dashboard/affiliate.php" class="<?php echo $current_page === 'affiliate.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Affiliate Program</span>
                </a>
            </li>
            
            <?php if ((int)($current_user['tier_level'] ?? 0) >= 2): ?>
            <li>
                <a href="/dashboard/resources.php" class="<?php echo $current_page === 'resources.php' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span>Resources</span>
                </a>
            </li>
            <?php endif; ?>
            
            <?php if ((int)($current_user['tier_level'] ?? 0) >= 3): ?>
            <li>
                <a href="/dashboard/exclusive.php" class="<?php echo $current_page === 'exclusive.php' ? 'active' : ''; ?>">
                    <i class="fas fa-gem"></i>
                    <span>Exclusive Content</span>
                </a>
            </li>
            <?php endif; ?>
            
            <li>
                <a href="/dashboard/account.php" class="<?php echo $current_page === 'account.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i>
                    <span>My Account</span>
                </a>
            </li>
            
            <?php if ($is_admin): ?>
            <li class="divider">
                <span>Admin</span>
            </li>
            
            <li>
                <a href="/admin" class="<?php echo strpos($current_page, 'admin') === 0 ? 'active' : ''; ?>">
                    <i class="fas fa-toolbox"></i>
                    <span>Admin Panel</span>
                </a>
            </li>
            
            <li>
                <a href="/webhook_setup.php" class="<?php echo $current_page === 'webhook_setup.php' ? 'active' : ''; ?>">
                    <i class="fas fa-link"></i>
                    <span>Webhook Setup</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <a href="/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>