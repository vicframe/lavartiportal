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
                <h1 class="dashboard-title">Packages</h1>
                <p class="text-muted">Upgrade Package</p>
            </div>
            
            <!-- Products grid -->
            <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
                <?php foreach ($products as $product): 
                
                ?>
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
                                <?php if ($product['tier_level'] == 803): ?>
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Hotels & Resorts</li>
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Condos & Airbnbs</li>
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i>Cabins, Villas, & Inns</li>
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> PLUS Pick A Free Vacation ($1500 value) After 12mo </li>
                                <?php endif; ?>
                                
                                <?php if ($product['tier_level'] == 793): ?>
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Hotels, Resorts, Condos, Retreats</li>
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Flights, Rental Cars, Transportation</li>
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Fully Curated Trips</li>
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Handpicked Excursions</li>
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i>Daily Deals (restaurants, movies, & shows)</li>
                                
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i>+ A Free Vacation ($1500 value) AND A Free Cruise ($1500 value)</li>
                                <?php endif; ?>
                                
                                <?php if ($product['tier_level'] == 826): ?>
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i>Videos and Blogs</li>
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i>Updated Training</li>
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i>Free Certification Prep</li>
                                <li class="list-group-item"><i class="fas fa-check text-success me-2"></i>and More</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        
                        <div class="card-footer">
                            <?php if ($user['package_id'] == $product['tier_level']): ?>
                            <button class="btn btn-outline-primary w-100" disabled>Current Plan</button>
                            <?php else: ?>
                            <a href="#" class="btn btn-primary w-100 product-select" data-product-id="<?php echo $product['tier_level']; ?>" data-product-current-id="<?php echo $user['package_id']; ?>">
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
    const confirmCheckoutBtn = document.getElementById('confirmCheckout');
    let selectedProductId = null;

    document.querySelectorAll('.product-select').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            selectedProductId = this.getAttribute('data-product-id');
            currentProductId = this.getAttribute('data-product-current-id');
            const productCard = this.closest('.card');
            const productName = productCard.querySelector('.card-title').textContent;
            const productPrice = productCard.querySelector('.card-price').textContent;

            // Update modal
            document.getElementById('checkoutModalBody').innerHTML = `
                <p>You are about to subscribe to <strong>${productName}</strong> for ${productPrice}</p>
                <p>Your card will be charged immediately and your subscription will begin.</p>
            `;
            new bootstrap.Modal(document.getElementById('checkoutModal')).show();
        });
    });

    confirmCheckoutBtn.addEventListener('click', function () {
        document.getElementById('checkoutModalBody').innerHTML = `
            <div class="text-center">
                <i class="fas fa-spinner fa-spin fa-3x mb-3"></i>
                <p>Processing your subscription...</p>
            </div>
        `;

        fetch('/dashboard/rsi_upgrade.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                toProductId: selectedProductId,
                currentProductId: currentProductId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('checkoutModalBody').innerHTML = `
                    <div class="text-center">
                        <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                        <h5>Success!</h5>
                        <p>${data.message}</p>
                    </div>
                `;
                confirmCheckoutBtn.textContent = 'Go to Dashboard';
                confirmCheckoutBtn.onclick = () => location.href = '/dashboard';
            } else {
                document.getElementById('checkoutModalBody').innerHTML = `
                    <div class="alert alert-danger">${data.message}</div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('checkoutModalBody').innerHTML = `
                <div class="alert alert-danger">Something went wrong. Please try again.</div>
            `;
        });
    });
});
</script>


<?php
// Include dashboard footer
require_once __DIR__ . '/../includes/dashboard_footer.php';
?>