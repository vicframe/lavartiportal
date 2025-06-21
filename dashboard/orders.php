<?php
/**
 * Orders Dashboard
 * 
 * Displays user's orders from GHL
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

// Set page title
$page_title = 'My Orders';

// Require login
require_login();
$user = get_current_logged_user();

// Get user's orders
$orders_query = db_query(
    "SELECT 
        o.id AS order_id,
        o.created_at AS order_date,
        o.total_amount AS amount,
        o.status,
        i.id AS item_id,
        i.name AS product_name,
        i.quantity,
        i.price,
        p.tier_level,
        CASE 
            WHEN p.tier_level = 1 THEN 'Basic'
            WHEN p.tier_level = 2 THEN 'Premium'
            WHEN p.tier_level = 3 THEN 'Elite'
            ELSE 'Unknown'
        END AS tier_name,
        CASE 
            WHEN o.status = 'completed' THEN 'success'
            WHEN o.status = 'pending' THEN 'warning'
            WHEN o.status = 'failed' THEN 'danger'
            ELSE 'secondary'
        END AS status_class
     FROM orders o
     LEFT JOIN order_items i ON o.id = i.order_id
     LEFT JOIN products p ON i.product_id = p.id
     WHERE o.user_id = ?
     ORDER BY o.created_at DESC
     LIMIT 3",
     [$user['id']]
);

$orders = db_fetch_all($orders_query);

// Include dashboard header
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
                <h1 class="dashboard-title">My Orders</h1>
                <button id="refreshOrdersBtn" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <?php if (empty($orders)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> You have no orders yet. Visit our product page to make a purchase.
                            </div>
                        <?php else: ?>
                            <table class="table table-hover table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Product</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                            <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                            <td>$<?php echo number_format($order['amount'], 2); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $order['status_class']; ?>">
                                                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary view-order-btn" data-order-id="<?php echo $order['order_id']; ?>">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($orders)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php
                            // Calculate totals
                            $total_orders = count($orders);
                            $total_amount = array_sum(array_column($orders, 'amount'));
                            
                            // Count statuses
                            $completed_orders = count(array_filter($orders, function($order) {
                                return $order['status'] === 'completed';
                            }));
                            
                            $pending_orders = count(array_filter($orders, function($order) {
                                return $order['status'] === 'pending';
                            }));
                            ?>
                            
                            <div class="col-md-4 mb-3">
                                <div class="stat-card">
                                    <div class="icon">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <div class="details">
                                        <h3 class="value"><?php echo $total_orders; ?></h3>
                                        <p class="label">Total Orders</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="stat-card">
                                    <div class="icon">
                                        <i class="fas fa-dollar-sign"></i>
                                    </div>
                                    <div class="details">
                                        <h3 class="value">$<?php echo number_format($total_amount, 2); ?></h3>
                                        <p class="label">Total Amount</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <div class="stat-card">
                                    <div class="icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="details">
                                        <h3 class="value"><?php echo $completed_orders; ?></h3>
                                        <p class="label">Completed Orders</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderDetailsModalLabel">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading order details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Setup view order buttons
    const viewOrderButtons = document.querySelectorAll('.view-order-btn');
    const orderDetailsModal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    
    viewOrderButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            loadOrderDetails(orderId);
            orderDetailsModal.show();
        });
    });
    
    // Refresh orders button
    const refreshOrdersBtn = document.getElementById('refreshOrdersBtn');
    if (refreshOrdersBtn) {
        refreshOrdersBtn.addEventListener('click', function() {
            window.location.reload();
        });
    }
});

function loadOrderDetails(orderId) {
    const orderDetailsContent = document.getElementById('orderDetailsContent');
    
    // Show loading state
    orderDetailsContent.innerHTML = `
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading order details...</p>
        </div>
    `;
    
    // Fetch order details
    fetch(`https://thephoenixlb.com/lavartiportal/api/order-details.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayOrderDetails(data.order);
            } else {
                orderDetailsContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> 
                        Error loading order details: ${data.error}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            orderDetailsContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> 
                    Failed to load order details. Please try again.
                </div>
            `;
        });
}

function displayOrderDetails(order) {
    const orderDetailsContent = document.getElementById('orderDetailsContent');
    
    // Format status badge
    let statusClass = 'secondary';
    switch (order.status) {
        case 'completed':
            statusClass = 'success';
            break;
        case 'pending':
            statusClass = 'warning';
            break;
        case 'failed':
            statusClass = 'danger';
            break;
    }
    
    // Format tier level
    let tierName = 'Unknown';
    switch (order.tier_level) {
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
    
    // Set modal content
    orderDetailsContent.innerHTML = `
        <div class="order-details">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6>Order Information</h6>
                    <table class="table table-sm">
                        <tr>
                            <th>Order ID:</th>
                            <td>${order.order_id}</td>
                        </tr>
                        <tr>
                            <th>Date:</th>
                            <td>${new Date(order.order_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td><span class="badge bg-${statusClass}">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span></td>
                        </tr>
                        <tr>
                            <th>GHL Order ID:</th>
                            <td>${order.ghl_order_id || 'N/A'}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Product Information</h6>
                    <table class="table table-sm">
                        <tr>
                            <th>Product:</th>
                            <td>${order.item_name}</td>
                        </tr>
                        <tr>
                            <th>Membership Tier:</th>
                            <td>${tierName}</td>
                        </tr>
                        <tr>
                            <th>Amount:</th>
                            <td>$${parseFloat(order.total_amount).toFixed(2)}</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle"></i>
                If you have any questions about this order, please contact customer support.
            </div>
        </div>
    `;
}
</script>

<?php
// Include dashboard footer
require_once __DIR__ . '/../includes/dashboard_footer.php';
?>