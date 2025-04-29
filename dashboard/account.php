<?php
$page_title = 'Account';

// Include all necessary files
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Require login to access this page
require_login();

// Get user data
$user = get_current_logged_user();

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
            <a href="/dashboard/membership.php" class="menu-item">
                <i class="fas fa-id-card"></i> Membership
            </a>
            <a href="/dashboard/account.php" class="menu-item active">
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
            <h1>Account Settings</h1>
            <p class="text-muted">Manage your personal information and preferences</p>
        </div>
        
        <!-- Profile Information -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <form id="profile-form">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                <div class="form-text">Your email is used for login and cannot be changed.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
                
                <!-- Change Password -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form id="password-form">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </form>
                    </div>
                </div>
                
                <!-- Notification Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Notification Settings</h5>
                    </div>
                    <div class="card-body">
                        <form id="notification-form">
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="email_news" name="email_news" checked>
                                <label class="form-check-label" for="email_news">Receive newsletter and announcements</label>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="email_commission" name="email_commission" checked>
                                <label class="form-check-label" for="email_commission">Commission notifications</label>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="email_team" name="email_team" checked>
                                <label class="form-check-label" for="email_team">Team member activity</label>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="sms_commission" name="sms_commission">
                                <label class="form-check-label" for="sms_commission">Receive SMS for commission activity</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Save Preferences</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Account Summary -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Account Summary</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Member Since</span>
                                <span><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Membership Tier</span>
                                <span><?php echo get_tier_name($user['tier_id']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Affiliate ID</span>
                                <span><?php echo htmlspecialchars($user['replicated_site'] ?? $user['id']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Account Status</span>
                                <span class="badge bg-success">Active</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Connected Accounts -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Connected Accounts</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3">
                                <i class="fab fa-google fs-4"></i>
                            </div>
                            <div class="flex-grow-1">
                                <strong>Google</strong>
                                <div class="small text-muted">Not connected</div>
                            </div>
                            <button class="btn btn-sm btn-outline-primary">Connect</button>
                        </div>
                        
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3">
                                <i class="fab fa-facebook fs-4"></i>
                            </div>
                            <div class="flex-grow-1">
                                <strong>Facebook</strong>
                                <div class="small text-muted">Not connected</div>
                            </div>
                            <button class="btn btn-sm btn-outline-primary">Connect</button>
                        </div>
                        
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fab fa-apple fs-4"></i>
                            </div>
                            <div class="flex-grow-1">
                                <strong>Apple</strong>
                                <div class="small text-muted">Not connected</div>
                            </div>
                            <button class="btn btn-sm btn-outline-primary">Connect</button>
                        </div>
                    </div>
                </div>
                
                <!-- Danger Zone -->
                <div class="card mb-4 border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">Danger Zone</h5>
                    </div>
                    <div class="card-body">
                        <p>These actions are permanent and cannot be undone.</p>
                        
                        <button class="btn btn-outline-danger mb-2">Delete All Data</button>
                        <button class="btn btn-outline-danger">Close Account</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Dashboard scripts -->
    <script src="/assets/js/dashboard.js"></script>
</body>
</html>