<?php
/**
 * Admin Dashboard
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

// Set page title
$page_title = 'Admin Dashboard';

// Require admin login
require_login();
$user = get_current_logged_user();

// Check if user is admin
if (!isset($user['is_admin']) || !$user['is_admin']) {
    // Set flash message
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'You do not have permission to access this page.'
    ];
    
    // Redirect to dashboard
    header('Location: /dashboard');
    exit;
}

// Include header
$custom_css = '<link href="/assets/css/admin.css" rel="stylesheet">';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>Admin Menu</span>
                </h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="/admin">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Overview
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/users.php">
                            <i class="fas fa-users me-2"></i>
                            User Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/orders.php">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Order Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/commissions.php">
                            <i class="fas fa-money-bill-alt me-2"></i>
                            Commission Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/integrations.php">
                            <i class="fas fa-plug me-2"></i>
                            Integrations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/webhook_setup.php">
                            <i class="fas fa-link me-2"></i>
                            Webhook Setup
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/logs.php">
                            <i class="fas fa-clipboard-list me-2"></i>
                            System Logs
                        </a>
                    </li>
                </ul>
                
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>Quick Links</span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard">
                            <i class="fas fa-arrow-left me-2"></i>
                            Return to Dashboard
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Admin Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshStats">
                            <i class="fas fa-sync-alt me-1"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Stats Overview -->
            <div class="row stats-container mb-4">
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="details">
                            <h3 class="value" id="totalUsers">--</h3>
                            <p class="label">Total Users</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="details">
                            <h3 class="value" id="totalOrders">--</h3>
                            <p class="label">Total Orders</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="details">
                            <h3 class="value" id="totalRevenue">--</h3>
                            <p class="label">Total Revenue</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="details">
                            <h3 class="value" id="totalCommissions">--</h3>
                            <p class="label">Total Commissions</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Recent Orders -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Orders</h5>
                        </div>
                        <div class="card-body">
                            <div class="recent-orders-table">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>User</th>
                                            <th>Product</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recentOrdersTable">
                                        <!-- Orders will be loaded here -->
                                        <tr>
                                            <td colspan="5" class="text-center">Loading recent orders...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end mt-3">
                                <a href="/admin/orders.php" class="btn btn-sm btn-outline-primary">View All Orders</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Users -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Users</h5>
                        </div>
                        <div class="card-body">
                            <div class="recent-users-table">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Tier</th>
                                            <th>Date Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recentUsersTable">
                                        <!-- Users will be loaded here -->
                                        <tr>
                                            <td colspan="4" class="text-center">Loading recent users...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end mt-3">
                                <a href="/admin/users.php" class="btn btn-sm btn-outline-primary">View All Users</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Integration Status -->
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Integration Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="integration-status">
                                        <h6>GoHighLevel Integration</h6>
                                        <div class="status-indicator mb-3">
                                            <div class="status-icon" id="ghlStatus">
                                                <i class="fas fa-question-circle"></i>
                                            </div>
                                            <div class="status-text" id="ghlStatusText">
                                                Checking status...
                                            </div>
                                        </div>
                                        <button class="btn btn-sm btn-outline-primary" id="checkGhlStatus">
                                            <i class="fas fa-sync-alt me-1"></i> Check Connection
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="integration-status">
                                        <h6>Pillars Integration</h6>
                                        <div class="status-indicator mb-3">
                                            <div class="status-icon" id="pillarsStatus">
                                                <i class="fas fa-question-circle"></i>
                                            </div>
                                            <div class="status-text" id="pillarsStatusText">
                                                Checking status...
                                            </div>
                                        </div>
                                        <button class="btn btn-sm btn-outline-primary" id="checkPillarsStatus">
                                            <i class="fas fa-sync-alt me-1"></i> Check Connection
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load admin dashboard data
    loadAdminDashboardData();
    
    // Refresh stats button
    document.getElementById('refreshStats').addEventListener('click', function() {
        loadAdminDashboardData();
    });
    
    // Check GHL status button
    document.getElementById('checkGhlStatus').addEventListener('click', function() {
        checkIntegrationStatus('ghl');
    });
    
    // Check Pillars status button
    document.getElementById('checkPillarsStatus').addEventListener('click', function() {
        checkIntegrationStatus('pillars');
    });
});

function loadAdminDashboardData() {
    // Show loading
    showLoading();
    
    // Fetch dashboard stats
    fetch('/api/admin-stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDashboardStats(data.stats);
                loadRecentOrders();
                loadRecentUsers();
                checkIntegrationStatus('ghl');
                checkIntegrationStatus('pillars');
            } else {
                showAlert('danger', 'Error loading dashboard data: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Failed to load dashboard data. Please try again.');
        })
        .finally(() => {
            hideLoading();
        });
}

function updateDashboardStats(stats) {
    document.getElementById('totalUsers').textContent = stats.total_users || 0;
    document.getElementById('totalOrders').textContent = stats.total_orders || 0;
    document.getElementById('totalRevenue').textContent = formatCurrency(stats.total_revenue || 0);
    document.getElementById('totalCommissions').textContent = formatCurrency(stats.total_commissions || 0);
}

function loadRecentOrders() {
    fetch('/api/admin-recent-orders.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayRecentOrders(data.orders);
            } else {
                document.getElementById('recentOrdersTable').innerHTML = 
                    '<tr><td colspan="5" class="text-center text-danger">Error loading orders: ' + data.error + '</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('recentOrdersTable').innerHTML = 
                '<tr><td colspan="5" class="text-center text-danger">Failed to load orders</td></tr>';
        });
}

function displayRecentOrders(orders) {
    const tableBody = document.getElementById('recentOrdersTable');
    
    if (orders.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center">No orders found</td></tr>';
        return;
    }
    
    let html = '';
    
    orders.forEach(order => {
        let statusClass = '';
        
        switch (order.status.toLowerCase()) {
            case 'completed':
                statusClass = 'success';
                break;
            case 'pending':
                statusClass = 'warning';
                break;
            case 'failed':
                statusClass = 'danger';
                break;
            case 'processing':
                statusClass = 'info';
                break;
            default:
                statusClass = 'secondary';
        }
        
        html += `
            <tr>
                <td><a href="/admin/orders.php?id=${order.id}">${order.id}</a></td>
                <td>${order.user_name}</td>
                <td>${order.product_name}</td>
                <td>${formatCurrency(order.amount)}</td>
                <td><span class="badge bg-${statusClass}">${order.status}</span></td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = html;
}

function loadRecentUsers() {
    fetch('/api/admin-recent-users.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayRecentUsers(data.users);
            } else {
                document.getElementById('recentUsersTable').innerHTML = 
                    '<tr><td colspan="4" class="text-center text-danger">Error loading users: ' + data.error + '</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('recentUsersTable').innerHTML = 
                '<tr><td colspan="4" class="text-center text-danger">Failed to load users</td></tr>';
        });
}

function displayRecentUsers(users) {
    const tableBody = document.getElementById('recentUsersTable');
    
    if (users.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="4" class="text-center">No users found</td></tr>';
        return;
    }
    
    let html = '';
    
    users.forEach(user => {
        let tierBadge = '';
        
        switch (user.tier_level) {
            case 1:
                tierBadge = '<span class="badge bg-secondary">Basic</span>';
                break;
            case 2:
                tierBadge = '<span class="badge bg-primary">Premium</span>';
                break;
            case 3:
                tierBadge = '<span class="badge bg-warning text-dark">Elite</span>';
                break;
            default:
                tierBadge = '<span class="badge bg-light text-dark">None</span>';
        }
        
        html += `
            <tr>
                <td><a href="/admin/users.php?id=${user.id}">${user.first_name} ${user.last_name}</a></td>
                <td>${user.email}</td>
                <td>${tierBadge}</td>
                <td>${formatDate(user.created_at)}</td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = html;
}

function checkIntegrationStatus(integration) {
    const statusIcon = document.getElementById(`${integration}Status`);
    const statusText = document.getElementById(`${integration}StatusText`);
    
    // Update status to checking
    statusIcon.innerHTML = '<i class="fas fa-sync fa-spin"></i>';
    statusText.textContent = 'Checking connection...';
    
    fetch(`/api/check-integration.php?integration=${integration}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusIcon.innerHTML = '<i class="fas fa-check-circle text-success"></i>';
                statusText.innerHTML = `Connected <span class="text-muted">(API Key: ${maskApiKey(data.api_key)})</span>`;
            } else {
                statusIcon.innerHTML = '<i class="fas fa-times-circle text-danger"></i>';
                statusText.textContent = data.error || 'Connection failed';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            statusIcon.innerHTML = '<i class="fas fa-exclamation-triangle text-warning"></i>';
            statusText.textContent = 'Connection check failed';
        });
}

function maskApiKey(key) {
    if (!key) return '';
    
    // Show only first 4 and last 4 characters
    const len = key.length;
    if (len <= 8) return key;
    
    return key.substring(0, 4) + '•'.repeat(len - 8) + key.substring(len - 4);
}
</script>

<?php
// Include footer
require_once __DIR__ . '/../includes/footer.php';
?>