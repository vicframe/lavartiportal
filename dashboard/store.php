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

// Set custom styles for the dashboard
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
                <h1 class="dashboard-title">Store</h1>
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
    </div>
</div>

<!-- Modal for checkout confirmation -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="checkoutModalLabel">Confirm Subscription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="checkoutModalBody">
                <!-- Content will be filled dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmCheckout">Proceed to Checkout</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Product selection
    const productSelects = document.querySelectorAll('.product-select');
    const checkoutModal = new bootstrap.Modal(document.getElementById('checkoutModal'));
    const checkoutModalBody = document.getElementById('checkoutModalBody');
    const confirmCheckoutBtn = document.getElementById('confirmCheckout');
    let selectedProductId = null;
    
    productSelects.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            selectedProductId = this.getAttribute('data-product-id');
            
            // Find the product details
            const productCard = this.closest('.card');
            const productName = productCard.querySelector('.card-title').textContent;
            const productPrice = productCard.querySelector('.card-price').textContent;
            
            // Update the modal with product details
            checkoutModalBody.innerHTML = `
                <p>You are about to subscribe to <strong>${productName}</strong> for ${productPrice}</p>
                <p>Your card will be charged immediately and your subscription will begin.</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    For demonstration purposes, you will not be actually charged.
                </div>
            `;
            
            // Show the modal
            checkoutModal.show();
        });
    });
    
    // Confirm checkout
    confirmCheckoutBtn.addEventListener('click', function() {
        // In a real application, this would submit to a payment processor
        // For now, just show a success message
        checkoutModalBody.innerHTML = `
            <div class="text-center">
                <i class="fas fa-spinner fa-spin fa-3x mb-3"></i>
                <p>Processing your subscription...</p>
            </div>
        `;
        
        // Simulate processing delay
        setTimeout(function() {
            checkoutModalBody.innerHTML = `
                <div class="text-center">
                    <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                    <h5>Success!</h5>
                    <p>Your subscription has been processed successfully.</p>
                </div>
            `;
            
            confirmCheckoutBtn.textContent = 'Continue';
            confirmCheckoutBtn.addEventListener('click', function() {
                window.location.href = '/dashboard';
            }, { once: true });
        }, 2000);
    });
});
</script>

<?php
// Include dashboard footer
require_once __DIR__ . '/../includes/dashboard_footer.php';
?>