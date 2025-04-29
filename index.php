<?php
$page_title = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<div class="jumbotron bg-light p-5 rounded">
    <h1 class="display-4">Welcome to LaVarti Systems</h1>
    <p class="lead">Your all-in-one portal for managing storefront, affiliate relationships, and tiered membership benefits.</p>
    <hr class="my-4">
    <p>Join our platform to access exclusive content, earn commissions, and grow your business.</p>
    
    <?php if (!is_logged_in()): ?>
    <div class="mt-4">
        <a href="/login.php" class="btn btn-primary btn-lg me-2">Login</a>
    </div>
    <?php else: ?>
    <div class="mt-4">
        <a href="/dashboard/index.php" class="btn btn-primary btn-lg me-2">Go to Dashboard</a>
        <a href="/dashboard/products.php" class="btn btn-outline-primary btn-lg">View Products</a>
    </div>
    <?php endif; ?>
</div>

<div class="row mt-5">
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-shopping-cart fa-3x text-primary mb-3"></i>
                <h3 class="card-title">Storefront</h3>
                <p class="card-text">Access our marketplace of products and services tailored to your needs.</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-handshake fa-3x text-primary mb-3"></i>
                <h3 class="card-title">Affiliate Program</h3>
                <p class="card-text">Earn commissions by referring others to our platform and products.</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-crown fa-3x text-primary mb-3"></i>
                <h3 class="card-title">Tiered Memberships</h3>
                <p class="card-text">Gain access to exclusive content and benefits based on your membership level.</p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-5">
    <div class="col-12">
        <h2 class="text-center mb-4">Membership Tiers</h2>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card pricing-card h-100">
            <div class="card-header bg-primary text-white text-center">
                <h3>Basic</h3>
                <h2 class="mb-0">$25<small>/month</small></h2>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">Access to basic resources</li>
                    <li class="list-group-item">Entry-level affiliate commission rates</li>
                    <li class="list-group-item">Basic community access</li>
                    <li class="list-group-item">Standard support</li>
                </ul>
            </div>
            <div class="card-footer bg-white text-center">
                <a href="/dashboard/products.php" class="btn btn-outline-primary">Join Now</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card pricing-card h-100">
            <div class="card-header bg-primary text-white text-center">
                <h3>Premium</h3>
                <h2 class="mb-0">$65<small>/month</small></h2>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">Access to premium resources</li>
                    <li class="list-group-item">Enhanced affiliate commission rates</li>
                    <li class="list-group-item">Premium community access</li>
                    <li class="list-group-item">Priority support</li>
                    <li class="list-group-item">Additional training materials</li>
                </ul>
            </div>
            <div class="card-footer bg-white text-center">
                <a href="/dashboard/products.php" class="btn btn-outline-primary">Join Now</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card pricing-card h-100">
            <div class="card-header bg-primary text-white text-center">
                <h3>Elite</h3>
                <h2 class="mb-0">$500<small>/month</small></h2>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">Access to all resources</li>
                    <li class="list-group-item">Highest affiliate commission rates</li>
                    <li class="list-group-item">VIP community access</li>
                    <li class="list-group-item">Dedicated support</li>
                    <li class="list-group-item">Exclusive training and events</li>
                    <li class="list-group-item">Travel benefits and rewards</li>
                </ul>
            </div>
            <div class="card-footer bg-white text-center">
                <a href="/dashboard/products.php" class="btn btn-outline-primary">Join Now</a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-5">
    <div class="col-12">
        <h2 class="text-center mb-4">How It Works</h2>
    </div>
    
    <div class="col-md-3 mb-4 text-center">
        <div class="rounded-circle bg-light p-4 d-inline-block mb-3">
            <i class="fas fa-user-plus fa-3x text-primary"></i>
        </div>
        <h4>1. Sign Up</h4>
        <p>Create your account and choose your membership tier.</p>
    </div>
    
    <div class="col-md-3 mb-4 text-center">
        <div class="rounded-circle bg-light p-4 d-inline-block mb-3">
            <i class="fas fa-sign-in-alt fa-3x text-primary"></i>
        </div>
        <h4>2. Access Portal</h4>
        <p>Log in to your personalized dashboard.</p>
    </div>
    
    <div class="col-md-3 mb-4 text-center">
        <div class="rounded-circle bg-light p-4 d-inline-block mb-3">
            <i class="fas fa-share-alt fa-3x text-primary"></i>
        </div>
        <h4>3. Share & Refer</h4>
        <p>Invite others using your affiliate link.</p>
    </div>
    
    <div class="col-md-3 mb-4 text-center">
        <div class="rounded-circle bg-light p-4 d-inline-block mb-3">
            <i class="fas fa-dollar-sign fa-3x text-primary"></i>
        </div>
        <h4>4. Earn Rewards</h4>
        <p>Get commissions and unlock travel benefits.</p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
