<?php
$page_title = 'My Orders';
require_once __DIR__ . '/../includes/header.php';

// Require login for this page
require_login();

// Get user information
$user = get_current_user();

// Get all orders for the user
$orders = get_user_orders($user['id']);

// Calculate statistics
$total_spent = 0;
$total_orders = count($orders);
$completed_orders = 0;

foreach ($orders as $order) {
    if ($order['status'] === 'completed') {
        $total_spent += $order['amount'];
        $completed_orders++;
    }
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h1>My Orders</h1>
        <p class="lead">View your order history and subscription details.</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Total Spent</h5>
                <h2 class="mb-0"><?php echo format_currency($total_spent); ?></h2>
                <p class="mb-0">Lifetime purchases</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Total Orders</h5>
                <h2 class="mb-0"><?php echo $total_orders; ?></h2>
                <p class="mb-0">All-time orders</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Completed Orders</h5>
                <h2 class="mb-0"><?php echo $completed_orders; ?></h2>
                <p class="mb-0">Successfully processed</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Order History</h5>
            </div>
            <div class="card-body">
                <?php if (empty($orders)): ?>
                <div class="text-center p-4">
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <h5>No Orders Yet</h5>
                    <p class="mb-3">You haven't placed any orders yet.</p>
                    <a href="/dashboard/products.php" class="btn btn-primary">View Products</a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="ordersTable">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo $order['id']; ?></td>
                                <td><?php echo format_date($order['order_date']); ?></td>
                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                <td><?php echo format_currency($order['amount']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : ($order['status'] === 'pending' ? 'warning' : ($order['status'] === 'processing' ? 'info' : 'secondary')); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary view-order" data-order-id="<?php echo $order['id']; ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
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
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Order Information</h6>
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Order ID:</span>
                                <span id="orderDetailId"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Date:</span>
                                <span id="orderDetailDate"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Status:</span>
                                <span id="orderDetailStatus"></span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Product Information</h6>
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Product:</span>
                                <span id="orderDetailProduct"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Amount:</span>
                                <span id="orderDetailAmount"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Billing Type:</span>
                                <span id="orderDetailBillingType">Monthly Subscription</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <h6>Tier Access Granted</h6>
                        <div class="alert alert-info" id="orderDetailTierAccess">
                            This order provides access to Tier X benefits.
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <h6>Next Steps</h6>
                        <div id="orderNextStepsPending" class="d-none">
                            <p>Your order is being processed. You will receive an email confirmation once completed.</p>
                        </div>
                        <div id="orderNextStepsCompleted" class="d-none">
                            <p>Your order has been completed. You now have access to all the benefits included with your membership tier.</p>
                        </div>
                        <div id="orderNextStepsFailed" class="d-none">
                            <p>There was an issue processing your order. Please contact support for assistance.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="/dashboard/membership.php" class="btn btn-primary">Access Membership</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle view order button clicks
    const viewOrderButtons = document.querySelectorAll('.view-order');
    viewOrderButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            
            // Find the order in the list
            <?php echo 'const orders = ' . json_encode($orders) . ';'; ?>
            
            const order = orders.find(o => o.id == orderId);
            
            if (order) {
                // Fill modal with order details
                document.getElementById('orderDetailId').textContent = order.id;
                document.getElementById('orderDetailDate').textContent = order.order_date;
                document.getElementById('orderDetailProduct').textContent = order.product_name;
                document.getElementById('orderDetailAmount').textContent = '$' + parseFloat(order.amount).toFixed(2);
                
                // Set status with badge
                const statusBadgeClass = order.status === 'completed' ? 'bg-success' : 
                                        (order.status === 'pending' ? 'bg-warning' : 
                                        (order.status === 'processing' ? 'bg-info' : 'bg-secondary'));
                document.getElementById('orderDetailStatus').innerHTML = `<span class="badge ${statusBadgeClass}">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span>`;
                
                // Set tier access info
                document.getElementById('orderDetailTierAccess').textContent = `This order provides access to Tier ${order.tier_level} benefits.`;
                
                // Show appropriate next steps
                document.getElementById('orderNextStepsPending').classList.add('d-none');
                document.getElementById('orderNextStepsCompleted').classList.add('d-none');
                document.getElementById('orderNextStepsFailed').classList.add('d-none');
                
                if (order.status === 'pending' || order.status === 'processing') {
                    document.getElementById('orderNextStepsPending').classList.remove('d-none');
                } else if (order.status === 'completed') {
                    document.getElementById('orderNextStepsCompleted').classList.remove('d-none');
                } else {
                    document.getElementById('orderNextStepsFailed').classList.remove('d-none');
                }
                
                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
                modal.show();
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
