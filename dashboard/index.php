<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';

// Require login for this page
require_login();

// Get user information
$user = get_current_user();
$user_tier = $user['tier_id'] ?: 0;
$tier_name = get_tier_name($user_tier);

// Get recent orders
$recent_orders = get_user_orders($user['id']);

// Get recent commissions
$recent_commissions = get_user_commissions($user['id']);

// Calculate total commissions
$total_commissions = 0;
foreach ($recent_commissions as $commission) {
    if ($commission['status'] === 'approved' || $commission['status'] === 'paid') {
        $total_commissions += $commission['amount'];
    }
}

// Get affiliate link
$affiliate_link = generate_affiliate_link($user['id'], $user['replicated_site'] ?: '');

// Load extra JavaScript
$extra_js = '/assets/js/dashboard.js';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="mb-3">Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
        <p class="lead">Your LaVarti Systems Dashboard</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Membership Status</h5>
                <h2 class="text-primary mb-0"><?php echo htmlspecialchars($tier_name); ?></h2>
                <p class="text-muted">Tier <?php echo $user_tier ?: 'None'; ?></p>
                <?php if ($user_tier < 3): ?>
                <a href="/dashboard/products.php" class="btn btn-outline-primary btn-sm">Upgrade Now</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Commissions Earned</h5>
                <h2 class="text-success mb-0"><?php echo format_currency($total_commissions); ?></h2>
                <p class="text-muted">Lifetime earnings</p>
                <a href="/dashboard/affiliate.php" class="btn btn-outline-success btn-sm">View Details</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Quick Actions</h5>
                <div class="d-grid gap-2">
                    <a href="/dashboard/products.php" class="btn btn-primary btn-sm">View Products</a>
                    <a href="/dashboard/affiliate.php" class="btn btn-primary btn-sm">Affiliate Dashboard</a>
                    <a href="/dashboard/membership.php" class="btn btn-primary btn-sm">Access Membership Content</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Your Affiliate Link</h5>
            </div>
            <div class="card-body">
                <div class="input-group">
                    <input type="text" class="form-control" id="affiliateLink" value="<?php echo htmlspecialchars($affiliate_link); ?>" readonly>
                    <button class="btn btn-outline-secondary" type="button" id="copyAffiliateLink">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
                <p class="text-muted mt-2 mb-0">Share this link to earn commissions when people sign up through you.</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Orders</h5>
                <a href="/dashboard/orders.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent_orders)): ?>
                <div class="p-4 text-center">
                    <p class="mb-0 text-muted">You haven't placed any orders yet.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($recent_orders, 0, 5) as $order): ?>
                            <tr>
                                <td><?php echo format_date($order['order_date']); ?></td>
                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                <td><?php echo format_currency($order['amount']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : ($order['status'] === 'pending' ? 'warning' : 'secondary'); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Commissions</h5>
                <a href="/dashboard/affiliate.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent_commissions)): ?>
                <div class="p-4 text-center">
                    <p class="mb-0 text-muted">You haven't earned any commissions yet.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($recent_commissions, 0, 5) as $commission): ?>
                            <tr>
                                <td><?php echo format_date($commission['created_at']); ?></td>
                                <td><?php echo ucfirst($commission['commission_type']); ?></td>
                                <td><?php echo format_currency($commission['amount']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $commission['status'] === 'paid' ? 'success' : ($commission['status'] === 'approved' ? 'info' : 'secondary'); ?>">
                                        <?php echo ucfirst($commission['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
