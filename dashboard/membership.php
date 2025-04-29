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
                            <h3><?php echo htmlspecialchars(get_tier_name($user['tier_id'])); ?> Membership</h3>
                            <p class="text-muted">Active since: <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                            
                            <div class="mb-3">
                                <strong>Price:</strong> <?php echo $current_product ? format_currency($current_product['price']) . '/month' : 'N/A'; ?>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Next Billing Date:</strong> <?php echo date('F j, Y', strtotime('+1 month')); ?>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Payment Method:</strong> •••• •••• •••• 4242
                            </div>
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
                        <a href="/dashboard/store.php" class="btn btn-primary">Change Plan</a>
                        <button class="btn btn-outline-secondary">Update Payment Method</button>
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
                                <!-- Sample data - would be populated from database -->
                                <tr>
                                    <td>April 20, 2025</td>
                                    <td>Premium Membership - Monthly</td>
                                    <td><span class="badge bg-success">Paid</span></td>
                                    <td>$65.00</td>
                                </tr>
                                <tr>
                                    <td>March 20, 2025</td>
                                    <td>Premium Membership - Monthly</td>
                                    <td><span class="badge bg-success">Paid</span></td>
                                    <td>$65.00</td>
                                </tr>
                                <tr>
                                    <td>February 20, 2025</td>
                                    <td>Basic Membership - Monthly</td>
                                    <td><span class="badge bg-success">Paid</span></td>
                                    <td>$25.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Download Receipts -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Download Receipts</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Sample data - would be populated from database -->
                                <tr>
                                    <td>INV-2025-042</td>
                                    <td>April 20, 2025</td>
                                    <td>$65.00</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary"><i class="fas fa-download me-1"></i> PDF</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>INV-2025-032</td>
                                    <td>March 20, 2025</td>
                                    <td>$65.00</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary"><i class="fas fa-download me-1"></i> PDF</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>INV-2025-022</td>
                                    <td>February 20, 2025</td>
                                    <td>$25.00</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary"><i class="fas fa-download me-1"></i> PDF</button>
                                    </td>
                                </tr>
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