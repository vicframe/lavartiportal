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

// Get the first letter of the first name for avatar
$avatar_letter = substr($user['first_name'] ?? 'U', 0, 1);

// Set custom styles for the dashboard
$custom_css = '<link href="/assets/css/dashboard.css" rel="stylesheet">';
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
    <link href="/assets/css/styles.css" rel="stylesheet">
    <?php echo $custom_css; ?>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <!-- Mobile menu toggle -->
    <div class="menu-toggle d-md-none">
        <i class="fas fa-bars"></i>
    </div>
    
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>LaVarti</h2>
        </div>
        
        <div class="sidebar-menu">
            <a href="/dashboard/index.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="/dashboard/store.php" class="menu-item">
                <i class="fas fa-store"></i> Store
            </a>
            <a href="/dashboard/affiliate.php" class="menu-item">
                <i class="fas fa-users"></i> Affiliate
            </a>
            <a href="/dashboard/membership.php" class="menu-item active">
                <i class="fas fa-id-card"></i> Membership
            </a>
            <a href="/dashboard/account.php" class="menu-item">
                <i class="fas fa-user-cog"></i> Account
            </a>
            <a href="/logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i> Sign Out
            </a>
        </div>
    </div>
    
    <!-- Main content -->
    <div class="main-content">
        <!-- User profile dropdown -->
        <div class="user-profile">
            <div class="dropdown">
                <div class="dropdown-toggle" id="userDropdown">
                    <div class="avatar"><?php echo htmlspecialchars($avatar_letter); ?></div>
                    <span class="name"><?php echo htmlspecialchars($user['first_name']); ?></span>
                </div>
                <div class="dropdown-menu" aria-labelledby="userDropdown">
                    <a href="account.php" class="dropdown-item">My Account</a>
                    <a href="membership.php" class="dropdown-item">Membership</a>
                    <div class="dropdown-divider"></div>
                    <a href="/logout.php" class="dropdown-item">Sign Out</a>
                </div>
            </div>
        </div>
        
        <!-- Page header -->
        <div class="welcome-header">
            <h1>Membership Management</h1>
            <p class="text-muted">Manage your subscription and access premium content</p>
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
    
    <!-- Dashboard scripts -->
    <script src="/assets/js/dashboard.js"></script>
</body>
</html>