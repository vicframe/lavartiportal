<?php
$page_title = 'Affiliate';

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
            <a href="/dashboard/index.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="/dashboard/store.php" class="menu-item">
                <i class="fas fa-store"></i> Store
            </a>
            <a href="/dashboard/affiliate.php" class="menu-item active">
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
            <h1>Affiliate Dashboard</h1>
            <p class="text-muted">Manage your team and commissions</p>
        </div>
        
        <!-- Affiliate Stats -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="details">
                    <p class="value" id="team-members-count">24</p>
                    <p class="label">Team Members</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="details">
                    <p class="value" id="monthly-commissions">$0.00</p>
                    <p class="label">Monthly Commissions</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-link"></i>
                </div>
                <div class="details">
                    <p class="value" id="clicks-count">243</p>
                    <p class="label">Link Clicks</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="icon">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="details">
                    <p class="value" id="conversion-rate">3.2%</p>
                    <p class="label">Conversion Rate</p>
                </div>
            </div>
        </div>
        
        <!-- Affiliate Tools -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Your Affiliate Link</h5>
                    </div>
                    <div class="card-body">
                        <p>Share this link with potential customers:</p>
                        <div class="copy-input mb-3">
                            <input type="text" id="affiliate-link" value="<?php echo htmlspecialchars($affiliate_link); ?>" readonly>
                            <button id="copy-affiliate-link">Copy</button>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary btn-sm"><i class="fab fa-facebook-f me-1"></i> Share on Facebook</button>
                            <button class="btn btn-info btn-sm text-white"><i class="fab fa-twitter me-1"></i> Share on Twitter</button>
                            <button class="btn btn-success btn-sm"><i class="fab fa-whatsapp me-1"></i> Share on WhatsApp</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Invite Team Members</h5>
                    </div>
                    <div class="card-body">
                        <p>Send invitations to grow your team:</p>
                        <div class="input-group mb-3">
                            <input type="email" class="form-control" placeholder="Email address">
                            <button class="btn btn-primary">Send Invite</button>
                        </div>
                        <p class="small text-muted">Or share your affiliate link directly.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Team Members -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Your Team Members</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Join Date</th>
                                <th>Membership</th>
                                <th>Commissions Generated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Sample data - would be populated from database -->
                            <tr>
                                <td>John Doe</td>
                                <td>March 15, 2025</td>
                                <td>Premium</td>
                                <td>$124.50</td>
                            </tr>
                            <tr>
                                <td>Jane Smith</td>
                                <td>April 2, 2025</td>
                                <td>Basic</td>
                                <td>$22.75</td>
                            </tr>
                            <tr>
                                <td>Robert Johnson</td>
                                <td>April 10, 2025</td>
                                <td>Elite</td>
                                <td>$375.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Commission History -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Commission History</h5>
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
                                <td>Commission from Jane Smith</td>
                                <td><span class="badge bg-success">Paid</span></td>
                                <td>$13.00</td>
                            </tr>
                            <tr>
                                <td>April 15, 2025</td>
                                <td>Commission from Robert Johnson</td>
                                <td><span class="badge bg-warning text-dark">Pending</span></td>
                                <td>$100.00</td>
                            </tr>
                            <tr>
                                <td>April 5, 2025</td>
                                <td>Commission from John Doe</td>
                                <td><span class="badge bg-success">Paid</span></td>
                                <td>$32.50</td>
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