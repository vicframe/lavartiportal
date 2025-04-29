<?php
$page_title = 'Products & Membership Tiers';
require_once __DIR__ . '/../includes/header.php';

// Require login for this page
require_login();

// Get user information
$user = get_current_user();
$user_tier = $user['tier_id'] ?: 0;

// Get all products
$products = get_all_products();

// Load extra JavaScript
$extra_js = '/assets/js/products.js';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1>Membership Tiers</h1>
        <p class="lead">Choose the membership tier that's right for you to unlock additional benefits.</p>
    </div>
</div>

<div class="row">
    <?php foreach ($products as $product): ?>
    <div class="col-md-4 mb-4">
        <div class="card pricing-card h-100 <?php echo $user_tier === $product['tier_level'] ? 'border-primary' : ''; ?>">
            <div class="card-header <?php echo $user_tier === $product['tier_level'] ? 'bg-primary text-white' : 'bg-light'; ?> text-center">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <h2 class="mb-0">
                    <?php echo format_currency($product['price']); ?>
                    <small>/month</small>
                </h2>
            </div>
            <div class="card-body">
                <p><?php echo htmlspecialchars($product['description']); ?></p>
                
                <h5 class="mt-4">Benefits:</h5>
                <ul class="list-group list-group-flush">
                    <?php if ($product['tier_level'] >= 1): ?>
                    <li class="list-group-item">Access to basic resources</li>
                    <li class="list-group-item">Entry-level affiliate commission rates</li>
                    <li class="list-group-item">Basic community access</li>
                    <li class="list-group-item">Standard support</li>
                    <?php endif; ?>
                    
                    <?php if ($product['tier_level'] >= 2): ?>
                    <li class="list-group-item">Access to premium resources</li>
                    <li class="list-group-item">Enhanced affiliate commission rates</li>
                    <li class="list-group-item">Premium community access</li>
                    <li class="list-group-item">Priority support</li>
                    <li class="list-group-item">Additional training materials</li>
                    <?php endif; ?>
                    
                    <?php if ($product['tier_level'] >= 3): ?>
                    <li class="list-group-item">Access to all resources</li>
                    <li class="list-group-item">Highest affiliate commission rates</li>
                    <li class="list-group-item">VIP community access</li>
                    <li class="list-group-item">Dedicated support</li>
                    <li class="list-group-item">Exclusive training and events</li>
                    <li class="list-group-item">Travel benefits and rewards</li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="card-footer bg-white text-center">
                <?php if ($user_tier === $product['tier_level']): ?>
                <button class="btn btn-success" disabled>Current Plan</button>
                <?php elseif ($user_tier > $product['tier_level']): ?>
                <button class="btn btn-outline-secondary" disabled>Lower Tier</button>
                <?php else: ?>
                <button class="btn btn-primary purchase-product" data-product-id="<?php echo $product['id']; ?>" data-product-name="<?php echo htmlspecialchars($product['name']); ?>" data-product-price="<?php echo $product['price']; ?>">
                    <?php echo $user_tier === 0 ? 'Subscribe Now' : 'Upgrade Now'; ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Subscribe to <span id="selectedPlan"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="paymentForm">
                    <input type="hidden" id="productId" name="productId">
                    
                    <div class="mb-3">
                        <label for="cardName" class="form-label">Name on Card</label>
                        <input type="text" class="form-control" id="cardName" name="cardName" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cardNumber" class="form-label">Card Number</label>
                        <input type="text" class="form-control" id="cardNumber" name="cardNumber" required placeholder="XXXX XXXX XXXX XXXX">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cardExpiry" class="form-label">Expiration Date</label>
                            <input type="text" class="form-control" id="cardExpiry" name="cardExpiry" required placeholder="MM/YY">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cardCvv" class="form-label">CVV</label>
                            <input type="text" class="form-control" id="cardCvv" name="cardCvv" required placeholder="XXX">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="billingAddress" class="form-label">Billing Address</label>
                        <input type="text" class="form-control" id="billingAddress" name="billingAddress" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="billingCity" class="form-label">City</label>
                            <input type="text" class="form-control" id="billingCity" name="billingCity" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="billingZip" class="form-label">ZIP / Postal Code</label>
                            <input type="text" class="form-control" id="billingZip" name="billingZip" required>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <p class="mb-0">You will be charged <strong><span id="planPrice"></span></strong> monthly for the <strong><span id="planName"></span></strong> membership.</p>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="processPayment">Subscribe</button>
            </div>
        </div>
    </div>
</div>

<!-- Processing Modal -->
<div class="modal fade" id="processingModal" tabindex="-1" aria-labelledby="processingModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5>Processing your payment...</h5>
                <p class="mb-0">Please don't close this window.</p>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="mb-3">
                    <i class="fas fa-check-circle text-success fa-4x"></i>
                </div>
                <h4>Payment Successful!</h4>
                <p>Your subscription to <strong><span id="successPlanName"></span></strong> has been processed successfully.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="window.location.reload()">Continue</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
