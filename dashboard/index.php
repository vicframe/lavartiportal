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

// Include header
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Account Overview</h5>
            </div>
            <div class="card-body">
                <h6 class="card-subtitle mb-3 text-muted">Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h6>
                
                <p><strong>Membership Tier:</strong> <?php echo get_tier_name($user['tier_id']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Join Date:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                
                <a href="account.php" class="btn btn-outline-primary mt-2">Manage Account</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Recent Activity</h5>
            </div>
            <div class="card-body">
                <!-- This area will be populated by JavaScript -->
                <div id="recent-activity">
                    <p class="text-center text-muted">Loading recent activity...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Orders Summary</h5>
            </div>
            <div class="card-body">
                <!-- This area will be populated by JavaScript -->
                <div id="orders-summary">
                    <p class="text-center text-muted">Loading orders summary...</p>
                </div>
            </div>
            <div class="card-footer">
                <a href="orders.php" class="btn btn-outline-primary">View All Orders</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Commission Summary</h5>
            </div>
            <div class="card-body">
                <!-- This area will be populated by JavaScript -->
                <div id="commission-summary">
                    <p class="text-center text-muted">Loading commission data...</p>
                </div>
            </div>
            <div class="card-footer">
                <a href="affiliate.php" class="btn btn-outline-primary">Affiliate Dashboard</a>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard specific script -->
<script src="/assets/js/dashboard.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>