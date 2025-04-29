<?php
$page_title = 'Store';

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
            <a href="/dashboard/store.php" class="menu-item active">
                <i class="fas fa-store"></i> Store
            </a>
            <a href="/dashboard/affiliate.php" class="menu-item">
                <i class="fas fa-users"></i> Affiliate
            </a>
            <a href="/dashboard/membership.php" class="menu-item">
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
            <h1>Store</h1>
            <p class="text-muted">Browse and purchase products</p>
        </div>
        
        <!-- Products grid -->
        <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
            <?php foreach ($products as $product): ?>
            <div class="col">
                <div class="card h-100 <?php echo $user['tier_id'] == $product['tier_level'] ? 'border-primary' : ''; ?>">
                    <?php if ($user['tier_id'] == $product['tier_level']): ?>
                    <div class="card-header bg-primary text-white">
                        <span class="badge bg-white text-primary">Current Plan</span>
                        <h4 class="card-title mt-2"><?php echo htmlspecialchars($product['name']); ?></h4>
                    </div>
                    <?php else: ?>
                    <div class="card-header">
                        <h4 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h4>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <div class="pricing-header text-center mb-4">
                            <h2 class="card-price"><?php echo format_currency($product['price']); ?><small class="text-muted">/month</small></h2>
                        </div>
                        
                        <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
                        
                        <ul class="list-group list-group-flush mb-4">
                            <?php if ($product['tier_level'] >= 1): ?>
                            <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Basic Travel Benefits</li>
                            <?php endif; ?>
                            
                            <?php if ($product['tier_level'] >= 2): ?>
                            <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Premium Travel Benefits</li>
                            <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Advanced Training Materials</li>
                            <?php endif; ?>
                            
                            <?php if ($product['tier_level'] >= 3): ?>
                            <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Elite Travel Benefits</li>
                            <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> VIP Support</li>
                            <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Exclusive Events Access</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <div class="card-footer">
                        <?php if ($user['tier_id'] == $product['tier_level']): ?>
                        <button class="btn btn-outline-primary w-100" disabled>Current Plan</button>
                        <?php else: ?>
                        <a href="#" class="btn btn-primary w-100 product-select" data-product-id="<?php echo $product['id']; ?>">
                            <?php echo $user['tier_id'] > 0 ? 'Change Plan' : 'Select Plan'; ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Dashboard scripts -->
    <script src="/assets/js/dashboard.js"></script>
</body>
</html>