<?php
/**
 * Admin Dashboard
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

// Set page title
$page_title = 'Admin Dashboard';

// Include admin header
require_once __DIR__ . '/../includes/admin_header.php';
?>

<!-- Admin Dashboard Content -->
<div class="row">
    <!-- Stats Overview -->
    <div class="row stats-container mb-4">
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="stats-card">
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3 id="totalUsers">1</h3>
                <p>Total Users</p>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="stats-card">
                <div class="icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3 id="totalOrders">3</h3>
                <p>Total Orders</p>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="stats-card">
                <div class="icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <h3 id="totalRevenue">$590.00</h3>
                <p>Total Revenue</p>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="stats-card">
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3 id="totalCommissions">$10.00</h3>
                <p>Total Commissions</p>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Orders -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Orders</h5>
                    <a href="/admin/orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
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
                </div>
            </div>
        </div>
        
        <!-- Recent Users -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Users</h5>
                    <a href="/admin/users.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
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
                        <div class="col-md-6 mb-3">
                            <div class="integration-item">
                                <div class="integration-icon">
                                    <i class="fas fa-cogs" id="ghlIcon"></i>
                                </div>
                                <div class="integration-details">
                                    <p class="integration-name">GoHighLevel Integration</p>
                                    <span class="integration-status-badge" id="ghlStatusBadge">Checking...</span>
                                </div>
                                <button class="btn btn-sm btn-outline-primary ms-auto" id="checkGhlStatus">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="integration-item">
                                <div class="integration-icon">
                                    <i class="fas fa-building" id="pillarsIcon"></i>
                                </div>
                                <div class="integration-details">
                                    <p class="integration-name">Pillars Integration</p>
                                    <span class="integration-status-badge" id="pillarsStatusBadge">Checking...</span>
                                </div>
                                <button class="btn btn-sm btn-outline-primary ms-auto" id="checkPillarsStatus">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- System Status -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">System Status</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="mb-2">Database</h6>
                                    <div class="status-indicator">
                                        <span class="badge bg-success">Connected</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="mb-2">Webhook Status</h6>
                                    <div class="status-indicator" id="webhookStatus">
                                        <span class="badge bg-info">See Setup Page</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="mb-2">System Version</h6>
                                    <div class="version-info">
                                        <span>v1.0.0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load admin dashboard data
    loadAdminDashboardData();
    
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
    // Fetch dashboard stats
    
    // Get base URL from the page
    // const baseUrl = document.querySelector('meta[name="base-url"]').getAttribute('content') || '';
    const apiUrl ='https://levartiportal.com//api/admin-stats.php';
    fetch(apiUrl)
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
        });
}

function updateDashboardStats(stats) {
    document.getElementById('totalUsers').textContent = stats.total_users || 0;
    document.getElementById('totalOrders').textContent = stats.total_orders || 0;
    document.getElementById('totalRevenue').textContent = formatCurrency(stats.total_revenue || 0);
    document.getElementById('totalCommissions').textContent = formatCurrency(stats.total_commissions || 0);
}

function loadRecentOrders() {
    fetch('https://levartiportal.com//api/admin-recent-orders.php')
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
                <td>${order.id}</td>
                <td>${order.user_name || 'Unknown'}</td>
                <td>${order.product_name}</td>
                <td>$${parseFloat(order.total_amount).toFixed(2)}</td>
                <td><span class="badge bg-${statusClass}">${order.status}</span></td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = html;
}

function loadRecentUsers() {
    fetch('https://levartiportal.com//api/admin-recent-users.php')
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
        let tierName = 'None';
        
        switch (parseInt(user.tier_id)) {
            case 1:
                tierName = 'Basic';
                break;
            case 2:
                tierName = 'Premium';
                break;
            case 3:
                tierName = 'Elite';
                break;
        }
        
        const name = `${user.first_name} ${user.last_name}`;
        const joinDate = new Date(user.created_at).toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
        
        html += `
            <tr>
                <td>${name}</td>
                <td>${user.email}</td>
                <td>${tierName}</td>
                <td>${joinDate}</td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = html;
}

function checkIntegrationStatus(integration) {
    const statusBadge = document.getElementById(`${integration}StatusBadge`);
    const statusIcon = document.getElementById(`${integration}Icon`);
    
    if (statusBadge && statusIcon) {
        // Set checking state
        statusBadge.textContent = 'Checking...';
        statusBadge.className = 'integration-status-badge';
        statusIcon.className = 'fas fa-spinner fa-spin';
        
        // Fetch integration status
        fetch(`https://levartiportal.com//api/check-integration.php?integration=${integration}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.connected) {
                        statusBadge.textContent = 'Connected';
                        statusBadge.className = 'integration-status-badge connected';
                        statusIcon.className = integration === 'ghl' ? 'fas fa-cogs' : 'fas fa-building';
                    } else {
                        statusBadge.textContent = 'Disconnected';
                        statusBadge.className = 'integration-status-badge disconnected';
                        statusIcon.className = 'fas fa-exclamation-circle';
                    }
                } else {
                    statusBadge.textContent = 'Status Error';
                    statusBadge.className = 'integration-status-badge disconnected';
                    statusIcon.className = 'fas fa-exclamation-triangle';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                statusBadge.textContent = 'Check Failed';
                statusBadge.className = 'integration-status-badge disconnected';
                statusIcon.className = 'fas fa-times-circle';
            });
    }
}

function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2);
}
</script>

<?php
// Include admin footer
require_once __DIR__ . '/../includes/admin_footer.php';
?>