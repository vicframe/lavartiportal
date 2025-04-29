<?php
$page_title = 'Products';

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

// Include header
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h2>Membership Products</h2>
        <p class="lead">Choose the membership tier that best fits your needs.</p>
    </div>
</div>

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

<!-- Products specific script -->
<script src="/assets/js/products.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>