<?php
/**
 * Dashboard Home
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Set page title
$page_title = 'Dashboard';

// Require login
require_login();
$user = get_current_logged_user();
$is_admin = isset($user['is_admin']) && $user['is_admin'];
$user_id = $user['id'];


$stats = [
    'orders_count' => 0,
    'total_spent' => 0,
    'membership_tier' => $user['tier_level'] ?? 0,
    'affiliate_commissions' => 0,
    'referrals_count' => 0
];

// Get order stats
$orders_query = db_query(
    "SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total 
     FROM orders 
     WHERE user_id = ? ",
    [$user_id]
);
$orders_result = db_fetch_one($orders_query);

if ($orders_result) {
    $stats['orders_count'] = $orders_result['count'];
    $stats['total_spent'] = $orders_result['total'];
}

// Get commission stats
$commissions_query = db_query(
    "SELECT COALESCE(SUM(amount), 0) as total 
     FROM commissions 
     WHERE user_id = ? AND status = 'paid'",
    [$user_id]
);
$commissions_result = db_fetch_one($commissions_query);

if ($commissions_result) {
    $stats['affiliate_commissions'] = $commissions_result['total'];
}

// Get referrals count
$referrals_query = db_query(
    "SELECT COUNT(*) as count 
     FROM users 
     WHERE sponsor_id = ?",
    [$user_id]
);
$referrals_result = db_fetch_one($referrals_query);

if ($referrals_result) {
    $stats['referrals_count'] = $referrals_result['count'];
}

// Get recent activity
$activity_query = db_query(
    "SELECT * FROM activities 
     WHERE user_id = ? 
     ORDER BY created_at DESC 
     LIMIT 10",
    [$user_id]
);
$activities = db_fetch_all($activity_query);

// Get tier information
$tier_info = [
    'name' => 'No Membership',
    'description' => 'Sign up for a membership to access exclusive benefits',
    'color' => 'secondary',
    'features' => []
];

switch ($stats['membership_tier']) {
    case 1:
        $tier_info = [
            'name' => 'Basic Membership',
            'description' => 'Essential travel benefits and access to basic training materials',
            'color' => 'primary',
            'features' => [
                'Access to basic travel deals',
                'Standard customer support',
                'Basic training materials',
                'Affiliate program participation'
            ]
        ];
        break;
    
    case 2:
        $tier_info = [
            'name' => 'Premium Membership',
            'description' => 'Enhanced travel benefits and access to premium training materials',
            'color' => 'info',
            'features' => [
                'Access to premium travel deals',
                'Priority customer support',
                'Advanced training materials',
                'Enhanced affiliate commissions',
                'Exclusive webinars and events'
            ]
        ];
        break;
    
    case 3:
        $tier_info = [
            'name' => 'Elite Membership',
            'description' => 'VIP travel benefits, exclusive access to elite training materials, and premium support',
            'color' => 'warning',
            'features' => [
                'VIP travel deals and packages',
                'Dedicated customer support',
                'Elite training and resources',
                'Highest affiliate commissions',
                'Exclusive mastermind events',
                'One-on-one coaching sessions',
                'Priority access to new features'
            ]
        ];
        break;
}
// Include dashboard header
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
//p.name AS item_name, from i.name AS item_name,

// Execute query
$order_result = db_query($query, [$user_id]);
$order = db_fetch_one($order_result);

$toProduct = get_product_by_tier_level($user['package_id']);

$custom_css = '<link href="' . asset_url('/assets/css/dashboard.css') . '" rel="stylesheet">';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>
<style>
    .upgrade-cta {
        display: flex;
        gap: 10px; /* spacing between buttons */
        align-items: center;
    }

    .upgrade-cta form {
        margin: 0; /* remove default margin */
    }
</style>
<div class="dashboard-container">
    <!-- Sidebar -->
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    
    <!-- Main content -->
    <div class="main-content">
        <!-- Top navigation -->
        <?php include __DIR__ . '/partials/topnav.php'; ?>
        
        <div class="container-fluid px-4">
            <div class="welcome-banner">
                <div class="welcome-text">
                    <h1>Welcome back, <?php echo htmlspecialchars($user['first_name'] ?? 'User'); ?>!</h1>
                    <p class="text-muted"><?php echo date('l, F j, Y'); ?></p>
                </div>
                
                <?php if ($stats['membership_tier'] === 0): ?>
                    <div class="upgrade-cta">
                   
                </div>
                <div class="upgrade-cta">
                    <a href="package.php" class="btn btn-warning">
                     <i class="fas fa-arrow-up me-1"></i> Upgrade to Travel Agent
                    </a>

                    <a href="<?php echo $user['rsi_redirect_url']; ?>"  target="_blank" class="btn btn-primary">
                        <i class="fas fa-crown me-1"></i> Access Travel Portal
                    </a>
                </div>
                <?php endif; ?>
            </div>
<?php
function getProductFromUrl($url) {
    if (strpos($url, 'passportlite.thedash.life') !== false) {
        return 'Passport Lite';
    } elseif (strpos($url, 'sso.thedash.life') !== false) {
        return 'Passport';
    } elseif (strpos($url, 'travelagent.thedash.life') !== false) {
        return 'Travel Agent';
    }
    return '';
}

$currentProduct = getProductFromUrl($user['rsi_redirect_url'] ?? '');
?>



            
            
            <!-- Stats cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="details">
                        <h3 class="value"><?php echo $stats['orders_count']; ?></h3>
                        <p class="label">Total Orders</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="details">
                        <h3 class="value">$<?php echo number_format($stats['total_spent'], 2); ?></h3>
                        <p class="label">Total Spent</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div class="details">
                        <h3>
                        <?php 
                        echo (isset($toProduct)) ? $toProduct['name']: '';
                        ?>
                        </h3>
                        <p class="label">Membership Tier</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <div class="details">
                        <h3 class="value">$<?php echo number_format($stats['affiliate_commissions'], 2); ?></h3>
                        <p class="label">Affiliate Earnings</p>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Recent orders -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h2 class="card-title">Recent Orders</h2>
                            <a href="https://levartiportal.com//dashboard/orders.php" class="btn btn-sm btn-outline-primary">
                              
                            View All
                            </a>
                        </div>
                        <div class="card-body">
                            <?php
                            // Get recent orders
                            $recent_orders_query = db_query(
                                            "SELECT 
                                                o.id AS order_id,
                                                o.created_at AS order_date,
                                                o.total_amount as amount,
                                                o.status,
                                                o.created_at,
                                                i.id AS item_id,
                                                i.name AS product_name,
                                                i.quantity,
                                                i.price
                                             FROM orders o
                                             LEFT JOIN order_items i ON o.id = i.order_id
                                             WHERE o.user_id = ?
                                             ORDER BY o.created_at DESC
                                             LIMIT 3",
                                            [$user_id]
                                        );
                            $recent_orders = db_fetch_all($recent_orders_query);
                            
                            // print_r($recent_orders);
                            ?>
                            
                            <?php if (empty($recent_orders)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-shopping-cart"></i>
                                    <p>No orders yet</p>
                                    <a href="/products.php" class="btn btn-primary btn-sm">Browse Products</a>
                                </div>
                            <?php else: ?>
                                <div class="recent-orders">
                                    <?php foreach ($recent_orders as $order): ?>
                                        <?php
                                        $status_class = 'secondary';
                                        switch ($order['status']) {
                                            case 'completed':
                                                $status_class = 'success';
                                                break;
                                            case 'pending':
                                                $status_class = 'warning';
                                                break;
                                            case 'failed':
                                                $status_class = 'danger';
                                                break;
                                        }
                                        ?>
                                        <div class="order-item">
                                            <div class="order-info">
                                                <div class="order-name">
                                                    <?php echo $order['product_name']; ?>
                                                </div>
                                                <div class="order-date">
                                                    <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                                </div>
                                            </div>
                                            <div class="order-details">
                                                <div class="order-amount">
                                                    $<?php echo number_format($order['amount'], 2); ?>
                                                </div>
                                                <div class="order-status">
                                                    <span class="badge bg-<?php echo $status_class; ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h2 class="card-title">Affiliate Program</h2>
                            <a href="/dashboard/affiliate.php" class="btn btn-sm btn-outline-primary">
                                View Details
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="affiliate-stats">
                                <div class="stat">
                                    <h3>$<?php echo number_format($stats['affiliate_commissions'], 2); ?></h3>
                                    <p>Total Commissions</p>
                                </div>
                                
                                <div class="stat">
                                    <h3><?php echo $stats['referrals_count']; ?></h3>
                                    <p>Referred Members</p>
                                </div>
                            </div>
                            
                            <div class="card-title">
                                <i class="fas fa-link"></i>
                                <h3>Your Affiliate Link</h3>
                            </div>
                            
                            <div class="copy-input">
                                <input type="text" value="<?php echo AFFILIATE_URL; ?>/?ref=<?php echo $user_id; ?>" id="affiliateLink" readonly>
                                <button id="copyLinkBtn">Copy</button>
                            </div>
                            
                            <a href="/dashboard/affiliate.php" class="action-button">
                                Promote & Earn
                            </a>
                        </div>
                    </div>
                </div>   

                <!-- Recent activity -->
                <!--<div class="col-md-6 mb-4">-->
                <!--    <div class="recent-activity">-->
                <!--        <h2>Recent Activity</h2>-->
                        
                <!--        <?php if (empty($activities)): ?>-->
                <!--            <div class="empty-state">-->
                <!--                <i class="fas fa-history"></i>-->
                <!--                <p>No recent activity</p>-->
                <!--            </div>-->
                <!--        <?php else: ?>-->
                <!--            <?php foreach ($activities as $activity): ?>-->
                <!--                <div class="activity-item">-->
                <!--                    <div class="activity-icon">-->
                <!--                        <i class="fas <?php echo getActivityIcon($activity['type']); ?>"></i>-->
                <!--                    </div>-->
                <!--                    <div class="activity-details">-->
                <!--                        <h3><?php echo htmlspecialchars($activity['description']); ?></h3>-->
                <!--                        <p>-->
                <!--                            <?php if ($activity['amount'] > 0): ?>-->
                <!--                                $<?php echo number_format($activity['amount'], 2); ?>-->
                <!--                            <?php endif; ?>-->
                <!--                        </p>-->
                <!--                    </div>-->
                <!--                    <div class="activity-time">-->
                <!--                        <?php echo formatTimeAgo($activity['created_at']); ?>-->
                <!--                    </div>-->
                <!--                </div>-->
                <!--            <?php endforeach; ?>-->
                <!--        <?php endif; ?>-->
                <!--    </div>-->
                <!--</div>-->
            </div>
            <div class="row">
                <!-- Membership section -->
                <!--<div class="col-md-8 mb-4">-->
                <!--    <div class="card h-100">-->
                <!--        <div class="card-header d-flex justify-content-between align-items-center">-->
                <!--            <h2 class="card-title">Your Membership</h2>-->
                <!--            <a href="/dashboard/membership.php" class="btn btn-sm btn-outline-primary">-->
                <!--                Manage Membership-->
                <!--            </a>-->
                <!--        </div>-->
                <!--        <div class="card-body">-->
                <!--            <div class="membership-status">-->
                <!--                <div class="membership-info">-->
                <!--                    <div class="tier-badge bg-<?php echo $tier_info['color']; ?>">-->
                <!--                        <?php echo $tier_info['name']; ?>-->
                <!--                    </div>-->
                <!--                    <p class="tier-description">-->
                <!--                        <?php echo $tier_info['description']; ?>-->
                <!--                    </p>-->
                <!--                </div>-->
                                
                <!--                <div class="membership-features">-->
                <!--                    <h3>Benefits</h3>-->
                <!--                    <ul class="features-list">-->
                <!--                        <?php if (empty($tier_info['features'])): ?>-->
                <!--                            <li class="empty">No active membership benefits</li>-->
                <!--                        <?php else: ?>-->
                <!--                            <?php foreach ($tier_info['features'] as $feature): ?>-->
                <!--                                <li>-->
                <!--                                    <i class="fas fa-check"></i>-->
                <!--                                    <span><?php echo $feature; ?></span>-->
                <!--                                </li>-->
                <!--                            <?php endforeach; ?>-->
                <!--                        <?php endif; ?>-->
                <!--                    </ul>-->
                                    
                <!--                    <?php if ($stats['membership_tier'] < 3): ?>-->
                <!--                    <a href="/dashboard/membership.php" class="action-button">-->
                <!--                        <?php if ($stats['membership_tier'] === 0): ?>-->
                <!--                            Get Started-->
                <!--                        <?php else: ?>-->
                <!--                            Upgrade Membership-->
                <!--                        <?php endif; ?>-->
                <!--                    </a>-->
                <!--                    <?php endif; ?>-->
                <!--                </div>-->
                <!--            </div>-->
                <!--        </div>-->
                <!--    </div>-->
                <!--</div>-->
                
                <!-- Affiliate section -->
                <!--<div class="col-md-4 mb-4">-->
                <!--    <div class="card h-100">-->
                <!--        <div class="card-header d-flex justify-content-between align-items-center">-->
                <!--            <h2 class="card-title">Affiliate Program</h2>-->
                <!--            <a href="/dashboard/affiliate.php" class="btn btn-sm btn-outline-primary">-->
                <!--                View Details-->
                <!--            </a>-->
                <!--        </div>-->
                <!--        <div class="card-body">-->
                <!--            <div class="affiliate-stats">-->
                <!--                <div class="stat">-->
                <!--                    <h3>$<?php echo number_format($stats['affiliate_commissions'], 2); ?></h3>-->
                <!--                    <p>Total Commissions</p>-->
                <!--                </div>-->
                                
                <!--                <div class="stat">-->
                <!--                    <h3><?php echo $stats['referrals_count']; ?></h3>-->
                <!--                    <p>Referred Members</p>-->
                <!--                </div>-->
                <!--            </div>-->
                            
                <!--            <div class="card-title">-->
                <!--                <i class="fas fa-link"></i>-->
                <!--                <h3>Your Affiliate Link</h3>-->
                <!--            </div>-->
                            
                <!--            <div class="copy-input">-->
                <!--                <input type="text" value="<?php echo APP_URL; ?>/?ref=<?php echo $user_id; ?>" id="affiliateLink" readonly>-->
                <!--                <button id="copyLinkBtn">Copy</button>-->
                <!--            </div>-->
                            
                <!--            <a href="/dashboard/affiliate.php" class="action-button">-->
                <!--                Promote & Earn-->
                <!--            </a>-->
                <!--        </div>-->
                <!--    </div>-->
                <!--</div>-->
            </div>
            
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy affiliate link
    const copyLinkBtn = document.getElementById('copyLinkBtn');
    const affiliateLink = document.getElementById('affiliateLink');
    
    if (copyLinkBtn && affiliateLink) {
        copyLinkBtn.addEventListener('click', function() {
            affiliateLink.select();
            document.execCommand('copy');
            
            const originalText = copyLinkBtn.textContent;
            copyLinkBtn.textContent = 'Copied!';
            
            setTimeout(() => {
                copyLinkBtn.textContent = originalText;
            }, 2000);
        });
    }
});
</script>

<?php
// Include footer
require_once __DIR__ . '/../includes/footer.php';

/**
 * Get activity icon
 */
function getActivityIcon($type) {
    switch ($type) {
        case 'order':
            return 'fa-shopping-cart';
        case 'payment':
            return 'fa-credit-card';
        case 'commission':
            return 'fa-hand-holding-usd';
        case 'login':
            return 'fa-sign-in-alt';
        case 'referral':
            return 'fa-user-plus';
        default:
            return 'fa-circle';
    }
}

/**
 * Format time ago
 */
function formatTimeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = round($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = round($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = round($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $weeks = round($diff / 604800);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 31536000) {
        $months = round($diff / 2592000);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    } else {
        $years = round($diff / 31536000);
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }
}

// Include dashboard footer
require_once __DIR__ . '/../includes/dashboard_footer.php';
?>