<?php
// Get current user
$current_user = get_current_logged_user();
$is_admin = isset($current_user['is_admin']) && $current_user['is_admin'];


$query = "
    SELECT 
        o.id AS order_id,
        o.user_id, 
        o.product_id, 
        o.total_amount, 
        o.status, 
        o.order_date, 
        o.created_at, 
        o.updated_at,
        
        p.name AS product_name, 
        p.price AS product_price, 
        p.tier_level,
        
        CASE 
            WHEN p.tier_level = 1 THEN 'Basic'
            WHEN p.tier_level = 2 THEN 'Premium'
            WHEN p.tier_level = 3 THEN 'Elite'
            ELSE 'Unknown'
        END AS tier_name,
        
        CASE 
            WHEN o.status = 'completed' THEN 'success'
            WHEN o.status = 'pending' THEN 'warning'
            WHEN o.status = 'failed' THEN 'danger'
            ELSE 'secondary'
        END AS status_class,
        
        u.first_name, 
        u.last_name, 
        u.email, 
        u.is_admin,
        
        i.id AS item_id,
        i.name AS item_name,
        i.quantity AS item_quantity,
        i.sku AS item_sku,
        i.price AS item_price
    FROM orders o
    LEFT JOIN order_items i ON o.id = i.order_id
    LEFT JOIN products p ON o.product_id = p.id
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.user_id = ?
    ORDER BY o.id DESC
    LIMIT 1
";

// Execute query
$order_result = db_query($query, [$current_user['id']]);
$order = db_fetch_one($order_result);
// Get current page for highlighting active menu item
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <a href="<?php echo url('dashboard'); ?>">
                <span>Levarti</span>
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
                    $tier_name = isset($order['item_name']) ? $order['item_name'] : 'Member';
                
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
                <a href="<?php echo url('dashboard'); ?>" class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li>
                <a href="<?php echo url('dashboard/orders.php'); ?>" class="<?php echo $current_page === 'orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span>My Orders</span>
                </a>
            </li>
            
            <li>
                <a href="<?php echo url('dashboard/membership.php'); ?>" class="<?php echo $current_page === 'membership.php' ? 'active' : ''; ?>">
                    <i class="fas fa-crown"></i>
                    <span>Membership</span>
                </a>
            </li>
            
            <!--<li>-->
            <!--    <a href="<?php echo url('dashboard/store.php'); ?>" class="<?php echo $current_page === 'affiliate.php' ? 'active' : ''; ?>">-->
            <!--        <i class="fas fa-users"></i>-->
            <!--        <span>Affiliate Program</span>-->
            <!--    </a>-->
            <!--</li>-->
            
            <?php if ((int)($current_user['tier_level'] ?? 0) >= 2): ?>
            <li>
                <a href="<?php echo url('dashboard/resources.php'); ?>" class="<?php echo $current_page === 'resources.php' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span>Resources</span>
                </a>
            </li>
            <?php endif; ?>
            
            <?php if ((int)($current_user['tier_level'] ?? 0) >= 3): ?>
            <li>
                <a href="<?php echo url('dashboard/exclusive.php'); ?>" class="<?php echo $current_page === 'exclusive.php' ? 'active' : ''; ?>">
                    <i class="fas fa-gem"></i>
                    <span>Exclusive Content</span>
                </a>
            </li>
            <?php endif; ?>
            
            <li>
                <a href="<?php echo url('dashboard/account.php'); ?>" class="<?php echo $current_page === 'account.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i>
                    <span>My Account</span>
                </a>
            </li>
            
            <?php if ($is_admin): ?>
            <li class="divider">
                <span>Admin</span>
            </li>
            
            <li>
                <a href="<?php echo url('admin'); ?>" class="<?php echo strpos($current_page, 'admin') === 0 ? 'active' : ''; ?>">
                    <i class="fas fa-toolbox"></i>
                    <span>Admin Panel</span>
                </a>
            </li>
            
            <li>
                <a href="<?php echo url('webhook_setup.php'); ?>" class="<?php echo $current_page === 'webhook_setup.php' ? 'active' : ''; ?>">
                    <i class="fas fa-link"></i>
                    <span>Webhook Setup</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <a href="<?php echo url('logout.php'); ?>" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>