<?php
$page_title = 'Membership';

// Include all necessary files
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Require login to access this page
require_login();

// Get user data
$user = get_current_logged_user();

// Get all available products
$products = get_all_products();

// Get the current product
$current_product = null;
foreach ($products as $product) {
    if ($product['tier_level'] == $user['tier_id']) {
        $current_product = $product;
        break;
    }
}
function formatOrderDate($datetime) {
    $date = new DateTime($datetime);
    return $date->format('F j, Y'); // e.g. "April 20, 2025"
}
$orders_query = db_query(
    "SELECT 
        o.id AS order_id,
        o.created_at AS order_date,
        o.total_amount AS amount,
        o.status,
        i.id AS item_id,
        i.name AS product_name,
        i.quantity,
        i.price,
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
        END AS status_class
     FROM orders o
     LEFT JOIN order_items i ON o.id = i.order_id
     LEFT JOIN products p ON i.product_id = p.id
     WHERE o.user_id = ?
     ORDER BY o.created_at DESC
     LIMIT 3",
     [$user['id']]
);

$orders = db_fetch_all($orders_query);


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
$order_result = db_query($query, [$user['id']]);
$order = db_fetch_one($order_result);
// print_r($order);
// Set custom styles for the dashboard
$custom_css = '<link href="/assets/css/dashboard.css" rel="stylesheet">';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<div class="dashboard-container">
    <!-- Sidebar -->
    <?php include __DIR__ . '/partials/sidebar.php'; ?>
    
    <!-- Main content -->
    <div class="main-content">
        <!-- Top navigation -->
        <?php include __DIR__ . '/partials/topnav.php'; ?>
        
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="dashboard-title">Membership Management</h1>
            </div>
            
            <!-- Current Membership Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Current Membership</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h3><?php echo  $order['item_name']; ?> </h3>
                            
                            <div class="mb-3">
                                <strong>Sku:</strong> <?php echo $order['item_sku'] ? $order['item_sku'] . '' : 'N/A'; ?>
                            </div>
                            <div class="mb-3">
                                <strong>Price:</strong> <?php echo $order['item_price'] ? format_currency($order['item_price']) . '' : 'N/A'; ?>
                            </div>
                            
                            <div class="mb-3">
                                <!--<strong>Next Billing Date:</strong> <?php echo date('F j, Y', strtotime('+1 month')); ?>-->
                            </div>
                            
                            <!--<div class="mb-3">-->
                            <!--    <strong>Payment Method:</strong> •••• •••• •••• 4242-->
                            <!--</div>-->
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Membership Benefits</h6>
                                    <ul class="list-unstyled">
                                        <?php if ($user['tier_id'] >= 1): ?>
                                        <li><i class="fas fa-check text-success me-2"></i> Basic Travel Benefits</li>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['tier_id'] >= 2): ?>
                                        <li><i class="fas fa-check text-success me-2"></i> Premium Travel Benefits</li>
                                        <li><i class="fas fa-check text-success me-2"></i> Advanced Training Materials</li>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['tier_id'] >= 3): ?>
                                        <li><i class="fas fa-check text-success me-2"></i> Elite Travel Benefits</li>
                                        <li><i class="fas fa-check text-success me-2"></i> VIP Support</li>
                                        <li><i class="fas fa-check text-success me-2"></i> Exclusive Events Access</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="https://dashlifetravel.com/pass-us-travel-2446" class="btn btn-primary">Change Plan</a>
                        <!--<button class="btn btn-outline-secondary">Update Payment Method</button>-->
                    </div>
                </div>
            </div>
            
            <!-- Membership History -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Billing History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                            
                            <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><?php echo formatOrderDate($order['order_date']); ?></td>
                                            <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                            <td><span class="badge bg-success">Paid</span></td>
                                            <td>$<?php echo number_format($order['price'], 2); ?></td>
                                        </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set up modal behavior for buttons if needed
    
    // Any membership-specific JavaScript can go here
});
</script>

<?php
// Include dashboard footer
require_once __DIR__ . '/../includes/dashboard_footer.php';
?>