<?php
/**
 * Admin Orders Management
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

// Set page title
$page_title = 'Order Management';

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

// Handle single order view
$single_order = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $order_id = intval($_GET['id']);
    
    // Get order with user details
    $order_query = db_query(
        "SELECT o.*, 
                u.email as user_email, 
                u.first_name as user_first_name, 
                u.last_name as user_last_name,
                CONCAT(u.first_name, ' ', u.last_name) as user_name,
                p.tier_level,
                p.name as product_name,
                CASE 
                    WHEN p.tier_level = 1 THEN 'Basic'
                    WHEN p.tier_level = 2 THEN 'Premium'
                    WHEN p.tier_level = 3 THEN 'Elite'
                    ELSE 'None'
                END as tier_name
         FROM orders o
         JOIN users u ON o.user_id = u.id
         LEFT JOIN products p ON o.product_id = p.id
         WHERE o.id = ?",
        [$order_id]
    );
    
    $single_order = db_fetch_one($order_query);
}

// Get orders with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$query_params = [];
$where_clauses = [];

if ($status_filter) {
    $where_clauses[] = "o.status = ?";
    $query_params[] = $status_filter;
}

if ($search) {
    $where_clauses[] = "(u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR o.product_name LIKE ?)";
    $search_term = "%$search%";
    $query_params[] = $search_term;
    $query_params[] = $search_term;
    $query_params[] = $search_term;
    $query_params[] = $search_term;
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Count total filtered orders
$count_query = db_query(
    "SELECT COUNT(*) as total FROM orders o 
     JOIN users u ON o.user_id = u.id 
     LEFT JOIN products p ON o.product_id = p.id
     $where_sql",
    $query_params
);
$count_result = db_fetch_one($count_query);
$total_orders = $count_result['total'];
$total_pages = ceil($total_orders / $limit);

// Get orders for current page
$orders_query_params = array_merge($query_params, [$limit, $offset]);
$orders_query = db_query(
    "SELECT o.*, 
            u.email as user_email, 
            CONCAT(u.first_name, ' ', u.last_name) as user_name,
            p.tier_level,
            p.name as product_name,
            CASE 
                WHEN p.tier_level = 1 THEN 'Basic'
                WHEN p.tier_level = 2 THEN 'Premium'
                WHEN p.tier_level = 3 THEN 'Elite'
                ELSE 'None'
            END as tier_name,
            CASE 
                WHEN o.status = 'completed' THEN 'success'
                WHEN o.status = 'pending' THEN 'warning'
                WHEN o.status = 'failed' THEN 'danger'
                ELSE 'secondary'
            END as status_class
     FROM orders o
     JOIN users u ON o.user_id = u.id
     LEFT JOIN products p ON o.product_id = p.id
     $where_sql
     ORDER BY o.created_at DESC
     LIMIT ? OFFSET ?",
    $orders_query_params
);

$orders = db_fetch_all($orders_query);

// Include header
$custom_css = '<link href="/assets/css/admin.css" rel="stylesheet">';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Custom CSS for this page -->
<style>
    .status-filter {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
    }
    
    .status-filter .btn {
        border-radius: 20px;
        padding: 5px 15px;
    }
    
    .order-details-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .order-details-header .status {
        font-size: 1rem;
        padding: 5px 15px;
        border-radius: 20px;
    }
    
    .order-meta {
        background-color: #f8f9fa;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .order-meta-row {
        display: flex;
        margin-bottom: 10px;
    }
    
    .order-meta-label {
        flex: 0 0 150px;
        font-weight: 600;
    }
    
    .order-meta-value {
        flex: 1;
    }
</style>

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
                        <a class="nav-link" href="/admin">
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
                        <a class="nav-link active" href="/admin/orders.php">
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
            <?php if ($single_order): ?>
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Order Details</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="/admin/orders.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to All Orders
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="order-details-container">
                    <div class="order-details-header">
                        <h3>Order #<?php echo htmlspecialchars($single_order['id']); ?></h3>
                        <?php
                        $status_class = 'secondary';
                        switch ($single_order['status']) {
                            case 'completed':
                                $status_class = 'success';
                                break;
                            case 'pending':
                                $status_class = 'warning';
                                break;
                            case 'failed':
                                $status_class = 'danger';
                                break;
                        }
                        ?>
                        <span class="badge bg-<?php echo $status_class; ?> status">
                            <?php echo ucfirst(htmlspecialchars($single_order['status'])); ?>
                        </span>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Order Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="order-meta">
                                        <div class="order-meta-row">
                                            <div class="order-meta-label">Order ID:</div>
                                            <div class="order-meta-value"><?php echo htmlspecialchars($single_order['id']); ?></div>
                                        </div>
                                        <div class="order-meta-row">
                                            <div class="order-meta-label">GHL Order ID:</div>
                                            <div class="order-meta-value"><?php echo htmlspecialchars($single_order['ghl_order_id'] ?? 'N/A'); ?></div>
                                        </div>
                                        <div class="order-meta-row">
                                            <div class="order-meta-label">Product:</div>
                                            <div class="order-meta-value"><?php echo htmlspecialchars($single_order['product_name']); ?></div>
                                        </div>
                                        <div class="order-meta-row">
                                            <div class="order-meta-label">Tier Level:</div>
                                            <div class="order-meta-value"><?php echo htmlspecialchars($single_order['tier_name']); ?></div>
                                        </div>
                                        <div class="order-meta-row">
                                            <div class="order-meta-label">Amount:</div>
                                            <div class="order-meta-value">$<?php echo number_format($single_order['amount'], 2); ?></div>
                                        </div>
                                        <div class="order-meta-row">
                                            <div class="order-meta-label">Order Date:</div>
                                            <div class="order-meta-value"><?php echo date('F j, Y', strtotime($single_order['order_date'])); ?></div>
                                        </div>
                                        <div class="order-meta-row">
                                            <div class="order-meta-label">Created:</div>
                                            <div class="order-meta-value"><?php echo date('F j, Y g:i a', strtotime($single_order['created_at'])); ?></div>
                                        </div>
                                        <div class="order-meta-row">
                                            <div class="order-meta-label">Last Updated:</div>
                                            <div class="order-meta-value"><?php echo date('F j, Y g:i a', strtotime($single_order['updated_at'])); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <button class="btn btn-primary" id="syncOrderBtn" data-order-id="<?php echo $single_order['id']; ?>">
                                            <i class="fas fa-sync-alt me-1"></i> Sync with GHL
                                        </button>
                                        <button class="btn btn-outline-secondary" id="editOrderBtn" data-order-id="<?php echo $single_order['id']; ?>">
                                            <i class="fas fa-edit me-1"></i> Edit Order
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Customer Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="order-meta">
                                        <div class="order-meta-row">
                                            <div class="order-meta-label">Customer:</div>
                                            <div class="order-meta-value">
                                                <a href="/admin/users.php?id=<?php echo $single_order['user_id']; ?>">
                                                    <?php echo htmlspecialchars($single_order['user_name']); ?>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="order-meta-row">
                                            <div class="order-meta-label">Email:</div>
                                            <div class="order-meta-value">
                                                <a href="mailto:<?php echo htmlspecialchars($single_order['user_email']); ?>">
                                                    <?php echo htmlspecialchars($single_order['user_email']); ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Order Status</h5>
                                </div>
                                <div class="card-body">
                                    <form id="updateStatusForm" method="post" action="/api/admin-update-order-status.php">
                                        <input type="hidden" name="order_id" value="<?php echo $single_order['id']; ?>">
                                        
                                        <div class="mb-3">
                                            <label for="orderStatus" class="form-label">Status</label>
                                            <select class="form-select" id="orderStatus" name="status">
                                                <option value="pending" <?php echo $single_order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="processing" <?php echo $single_order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="completed" <?php echo $single_order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="failed" <?php echo $single_order['status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                                <option value="refunded" <?php echo $single_order['status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="statusNote" class="form-label">Status Note (Optional)</label>
                                            <textarea class="form-control" id="statusNote" name="note" rows="3"></textarea>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">Update Status</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Order Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshOrdersBtn">
                                <i class="fas fa-sync-alt me-1"></i> Refresh
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="exportOrdersBtn">
                                <i class="fas fa-download me-1"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="status-filter">
                            <a href="/admin/orders.php" class="btn <?php echo !$status_filter ? 'btn-primary' : 'btn-outline-secondary'; ?>">All</a>
                            <a href="/admin/orders.php?status=pending" class="btn <?php echo $status_filter === 'pending' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Pending</a>
                            <a href="/admin/orders.php?status=processing" class="btn <?php echo $status_filter === 'processing' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Processing</a>
                            <a href="/admin/orders.php?status=completed" class="btn <?php echo $status_filter === 'completed' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Completed</a>
                            <a href="/admin/orders.php?status=failed" class="btn <?php echo $status_filter === 'failed' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Failed</a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <form class="d-flex" action="/admin/orders.php" method="get">
                            <?php if ($status_filter): ?>
                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                            <?php endif; ?>
                            <input type="text" class="form-control me-2" name="search" placeholder="Search by email, name or product" value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-outline-primary">Search</button>
                        </form>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <?php if (empty($orders)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No orders found matching your criteria.
                                </div>
                            <?php else: ?>
                                <table class="table table-hover table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Customer</th>
                                            <th>Product</th>
                                            <th>Tier</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td><?php echo $order['id']; ?></td>
                                                <td>
                                                    <a href="/admin/users.php?id=<?php echo $order['user_id']; ?>">
                                                        <?php echo htmlspecialchars($order['user_name']); ?>
                                                    </a>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($order['user_email']); ?></div>
                                                </td>
                                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                                <td><?php echo htmlspecialchars($order['tier_name']); ?></td>
                                                <td>$<?php echo number_format($order['amount'], 2); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $order['status_class']; ?>">
                                                        <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="/admin/orders.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                
                                <?php if ($total_pages > 1): ?>
                                    <nav aria-label="Orders pagination">
                                        <ul class="pagination justify-content-center">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="/admin/orders.php?page=<?php echo $page - 1; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                                        <i class="fas fa-chevron-left"></i> Previous
                                                    </a>
                                                </li>
                                            <?php else: ?>
                                                <li class="page-item disabled">
                                                    <a class="page-link" href="#"><i class="fas fa-chevron-left"></i> Previous</a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php
                                            $start_page = max(1, $page - 2);
                                            $end_page = min($total_pages, $page + 2);
                                            
                                            if ($start_page > 1) {
                                                echo '<li class="page-item"><a class="page-link" href="/admin/orders.php?page=1' . ($status_filter ? '&status=' . urlencode($status_filter) : '') . ($search ? '&search=' . urlencode($search) : '') . '">1</a></li>';
                                                if ($start_page > 2) {
                                                    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                                }
                                            }
                                            
                                            for ($i = $start_page; $i <= $end_page; $i++) {
                                                echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '"><a class="page-link" href="/admin/orders.php?page=' . $i . ($status_filter ? '&status=' . urlencode($status_filter) : '') . ($search ? '&search=' . urlencode($search) : '') . '">' . $i . '</a></li>';
                                            }
                                            
                                            if ($end_page < $total_pages) {
                                                if ($end_page < $total_pages - 1) {
                                                    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                                }
                                                echo '<li class="page-item"><a class="page-link" href="/admin/orders.php?page=' . $total_pages . ($status_filter ? '&status=' . urlencode($status_filter) : '') . ($search ? '&search=' . urlencode($search) : '') . '">' . $total_pages . '</a></li>';
                                            }
                                            ?>
                                            
                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="/admin/orders.php?page=<?php echo $page + 1; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                                        Next <i class="fas fa-chevron-right"></i>
                                                    </a>
                                                </li>
                                            <?php else: ?>
                                                <li class="page-item disabled">
                                                    <a class="page-link" href="#">Next <i class="fas fa-chevron-right"></i></a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Refresh orders button
    const refreshOrdersBtn = document.getElementById('refreshOrdersBtn');
    if (refreshOrdersBtn) {
        refreshOrdersBtn.addEventListener('click', function() {
            window.location.reload();
        });
    }
    
    // Export orders button
    const exportOrdersBtn = document.getElementById('exportOrdersBtn');
    if (exportOrdersBtn) {
        exportOrdersBtn.addEventListener('click', function() {
            let url = '/api/admin-export-orders.php';
            const statusFilter = '<?php echo $status_filter; ?>';
            const search = '<?php echo $search; ?>';
            
            if (statusFilter) {
                url += '?status=' + encodeURIComponent(statusFilter);
                if (search) {
                    url += '&search=' + encodeURIComponent(search);
                }
            } else if (search) {
                url += '?search=' + encodeURIComponent(search);
            }
            
            window.location.href = url;
        });
    }
    
    // Update order status form
    const updateStatusForm = document.getElementById('updateStatusForm');
    if (updateStatusForm) {
        updateStatusForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            
            // Get form data
            const formData = new FormData(this);
            
            // Convert to URL params
            const params = new URLSearchParams();
            formData.forEach((value, key) => {
                params.append(key, value);
            });
            
            // Send request
            fetch('/api/admin-update-order-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: params
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Order status updated successfully.');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert('danger', 'Error updating order status: ' + data.error);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Failed to update order status. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });
    }
    
    // Sync order with GHL
    const syncOrderBtn = document.getElementById('syncOrderBtn');
    if (syncOrderBtn) {
        syncOrderBtn.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            
            // Show loading
            const originalBtnText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
            
            // Send request
            fetch(`/api/admin-sync-order.php?id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Order synchronized successfully with GHL.');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert('danger', 'Error syncing order: ' + data.error);
                    this.disabled = false;
                    this.innerHTML = originalBtnText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Failed to sync order. Please try again.');
                this.disabled = false;
                this.innerHTML = originalBtnText;
            });
        });
    }
});
</script>

<?php
// Include footer
require_once __DIR__ . '/../includes/footer.php';
?>