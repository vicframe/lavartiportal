<?php
/**
 * Homepage
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

// Set page title
$page_title = 'Home';

// Check for referral parameter
$ref = isset($_GET['ref']) ? $_GET['ref'] : null;

if ($ref) {
    // Store referral ID in session
    $_SESSION['referral'] = $ref;
}

// Include header
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 hero-content">
                <h1>Travel More, Earn More</h1>
                <p class="lead mb-4">Join our travel community and earn commissions by sharing exclusive travel deals with your network.</p>
                <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                    <a href="/login.php" class="btn btn-primary btn-lg px-4 me-md-2">Get Started</a>
                    <a href="#features" class="btn btn-outline-light btn-lg px-4">Learn More</a>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <img src="/assets/img/hero-img.svg" alt="Travel illustration" class="img-fluid">
            </div>
        </div>
    </div>
</section>

<section class="py-5" id="features">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-4">Why Choose Us</h2>
            <p class="lead text-muted">We offer the best travel affiliate program in the industry</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card shadow-sm rounded p-4">
                    <div class="feature-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h3>Exclusive Travel Deals</h3>
                    <p>Access to special rates and packages not available to the general public.</p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="feature-card shadow-sm rounded p-4">
                    <div class="feature-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <h3>Competitive Commissions</h3>
                    <p>Earn up to 15% commission on every booking made through your referral link.</p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="feature-card shadow-sm rounded p-4">
                    <div class="feature-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3>Powerful Tools</h3>
                    <p>Track your performance, manage your team, and grow your business with our platform.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2>Join Our Community</h2>
                <p class="lead">Start your journey with us today and experience the benefits of our travel affiliate program.</p>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-check text-primary me-2"></i> Easy sign-up process</li>
                    <li class="mb-2"><i class="fas fa-check text-primary me-2"></i> Comprehensive training materials</li>
                    <li class="mb-2"><i class="fas fa-check text-primary me-2"></i> Active community support</li>
                    <li class="mb-2"><i class="fas fa-check text-primary me-2"></i> Regular payouts</li>
                </ul>
                <a href="/login.php" class="btn btn-primary mt-3">Get Started Now</a>
            </div>
            <div class="col-md-6">
                <img src="/assets/img/community.svg" alt="Community" class="img-fluid">
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2>Membership Tiers</h2>
            <p class="lead text-muted">Choose the plan that fits your needs</p>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header text-center">
                        <h3 class="my-0 fw-normal">Basic</h3>
                    </div>
                    <div class="card-body">
                        <h2 class="card-title pricing-card-title text-center">$25 <small class="text-muted">/ mo</small></h2>
                        <ul class="list-unstyled mt-3 mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Essential travel benefits</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Basic affiliate tools</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Standard commission rates</li>
                            <li class="mb-2 text-muted"><i class="fas fa-times text-danger me-2"></i> Advanced training materials</li>
                            <li class="mb-2 text-muted"><i class="fas fa-times text-danger me-2"></i> VIP support</li>
                        </ul>
                        <div class="d-grid gap-2">
                            <a href="/login.php" class="btn btn-outline-primary">Sign Up Now</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow border-primary">
                    <div class="card-header bg-primary text-white text-center">
                        <h3 class="my-0 fw-normal">Premium</h3>
                        <span class="badge bg-warning text-dark">Most Popular</span>
                    </div>
                    <div class="card-body">
                        <h2 class="card-title pricing-card-title text-center">$65 <small class="text-muted">/ mo</small></h2>
                        <ul class="list-unstyled mt-3 mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Enhanced travel benefits</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Full affiliate toolkit</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Higher commission rates</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Advanced training materials</li>
                            <li class="mb-2 text-muted"><i class="fas fa-times text-danger me-2"></i> VIP support</li>
                        </ul>
                        <div class="d-grid gap-2">
                            <a href="/login.php" class="btn btn-primary">Sign Up Now</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header text-center">
                        <h3 class="my-0 fw-normal">Elite</h3>
                    </div>
                    <div class="card-body">
                        <h2 class="card-title pricing-card-title text-center">$500 <small class="text-muted">/ mo</small></h2>
                        <ul class="list-unstyled mt-3 mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> VIP travel benefits</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Premium affiliate tools</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Highest commission rates</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Exclusive training materials</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> 24/7 VIP support</li>
                        </ul>
                        <div class="d-grid gap-2">
                            <a href="/login.php" class="btn btn-outline-primary">Sign Up Now</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
require_once __DIR__ . '/includes/footer.php';
?>