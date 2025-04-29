<?php
$page_title = 'Dashboard';

// Include all necessary files
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Require login to access this page
require_login();

// Get user data
$user = get_current_logged_user();

// Generate Affiliate Link
$affiliate_link = APP_URL . '/?ref=' . ($user['replicated_site'] ?? $user['id']);

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
            <a href="/dashboard/index.php" class="menu-item active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="/dashboard/store.php" class="menu-item">
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
        
        <!-- Welcome message -->
        <div class="welcome-header">
            <h1>Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
        </div>
        
        <!-- Stats cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="details">
                    <p class="value" id="team-members-count">24</p>
                    <p class="label">Active Team Members</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="details">
                    <p class="value" id="commissions-amount">$0.00</p>
                    <p class="label">Commissions (Month)</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="details">
                    <p class="value" id="membership-tier">Premium</p>
                    <p class="label">(<span id="membership-price">$65/mo</span>)</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-link"></i>
                </div>
                <div class="details">
                    <p class="value" id="affiliate-clicks">243</p>
                    <p class="label">Affiliate Clicks</p>
                </div>
            </div>
        </div>
        
        <!-- Quick actions tabs -->
        <div class="action-tabs">
            <div class="tab active" data-tab="quick-actions">Quick Actions</div>
            <div class="tab" data-tab="store">Store</div>
            <div class="tab" data-tab="affiliate">Affiliate</div>
            <div class="tab" data-tab="membership">Membership</div>
        </div>
        
        <!-- Quick actions cards -->
        <div class="action-cards">
            <div class="action-card tab-content" id="quick-actions">
                <div class="card-title">
                    <i class="fas fa-link"></i>
                    <h3>My Affiliate Link</h3>
                </div>
                <p>Copy your unique referral link</p>
                
                <div class="copy-input">
                    <input type="text" id="affiliate-link" value="<?php echo htmlspecialchars($affiliate_link); ?>" readonly>
                    <button id="copy-affiliate-link">Copy</button>
                </div>
            </div>
            
            <div class="action-card tab-content" id="store" style="display: none;">
                <div class="card-title">
                    <i class="fas fa-graduation-cap"></i>
                    <h3>Latest Training</h3>
                </div>
                <p>Continue where you left off</p>
                
                <button class="action-button">Resume Training</button>
            </div>
            
            <div class="action-card tab-content" id="affiliate" style="display: none;">
                <div class="card-title">
                    <i class="fas fa-users"></i>
                    <h3>Invite Team Members</h3>
                </div>
                <p>Grow your team and increase commissions</p>
                
                <button class="action-button">Send Invites</button>
            </div>
            
            <div class="action-card tab-content" id="membership" style="display: none;">
                <div class="card-title">
                    <i class="fas fa-arrow-up"></i>
                    <h3>Upgrade Plan</h3>
                </div>
                <p>Access more features and benefits</p>
                
                <button class="action-button">View Plans</button>
            </div>
        </div>
        
        <!-- Recent activity -->
        <div class="recent-activity">
            <h2>Recent Activity</h2>
            
            <div id="recent-activity-list">
                <p class="text-center text-muted">Loading activity data...</p>
            </div>
        </div>
    </div>
    
    <!-- Dashboard scripts -->
    <script src="/assets/js/dashboard.js"></script>
</body>
</html>