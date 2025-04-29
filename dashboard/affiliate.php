<?php
$page_title = 'Affiliate Dashboard';
require_once __DIR__ . '/../includes/header.php';

// Require login for this page
require_login();

// Get user information
$user = get_current_user();
$user_tier = $user['tier_id'] ?: 0;
$tier_name = get_tier_name($user_tier);

// Get all commissions
$commissions = get_user_commissions($user['id']);

// Calculate statistics
$total_commissions = 0;
$pending_commissions = 0;
$approved_commissions = 0;
$paid_commissions = 0;

foreach ($commissions as $commission) {
    $amount = $commission['amount'];
    
    switch ($commission['status']) {
        case 'pending':
            $pending_commissions += $amount;
            break;
        case 'approved':
            $approved_commissions += $amount;
            $total_commissions += $amount;
            break;
        case 'paid':
            $paid_commissions += $amount;
            $total_commissions += $amount;
            break;
    }
}

// Get affiliate link
$affiliate_link = generate_affiliate_link($user['id'], $user['replicated_site'] ?: '');

// Get commission rates based on tier
$direct_rate = $user_tier === 1 ? '10%' : ($user_tier === 2 ? '15%' : ($user_tier === 3 ? '20%' : '0%'));
$override_rate = $user_tier > 0 ? '5%' : '0%';
$bonus_rate = $user_tier === 1 ? '$10' : ($user_tier === 2 ? '$20' : ($user_tier === 3 ? '$50' : '$0'));

// Load extra JavaScript
$extra_js = '/assets/js/affiliate.js';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1>Affiliate Dashboard</h1>
        <p class="lead">Track your commissions and manage your affiliate business.</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Total Earnings</h5>
                <h2 class="mb-0"><?php echo format_currency($total_commissions); ?></h2>
                <p class="mb-0">Lifetime commissions</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Paid Commissions</h5>
                <h2 class="mb-0"><?php echo format_currency($paid_commissions); ?></h2>
                <p class="mb-0">Already paid out</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Approved Commissions</h5>
                <h2 class="mb-0"><?php echo format_currency($approved_commissions); ?></h2>
                <p class="mb-0">Ready for payment</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card bg-warning text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Pending Commissions</h5>
                <h2 class="mb-0"><?php echo format_currency($pending_commissions); ?></h2>
                <p class="mb-0">Awaiting approval</p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-8 mb-3">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Your Affiliate Link</h5>
            </div>
            <div class="card-body">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="affiliateLink" value="<?php echo htmlspecialchars($affiliate_link); ?>" readonly>
                    <button class="btn btn-outline-primary" type="button" id="copyAffiliateLink">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
                <p class="mb-0">Share this link with potential new members. When they join through your link, you'll earn commissions based on your tier level.</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Commission Rates</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Your Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Direct</td>
                                <td><?php echo $direct_rate; ?></td>
                            </tr>
                            <tr>
                                <td>Override</td>
                                <td><?php echo $override_rate; ?></td>
                            </tr>
                            <tr>
                                <td>Bonus</td>
                                <td><?php echo $bonus_rate; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <?php if ($user_tier < 3): ?>
                <div class="alert alert-info mb-0 mt-2">
                    <small>Upgrade your membership tier to increase your commission rates!</small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Commission History</h5>
            </div>
            <div class="card-body">
                <?php if (empty($commissions)): ?>
                <div class="text-center p-4">
                    <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                    <h5>No Commissions Yet</h5>
                    <p class="mb-0">Start sharing your affiliate link to earn commissions!</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="commissionsTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Product</th>
                                <th>Referral</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commissions as $commission): ?>
                            <tr>
                                <td><?php echo format_date($commission['created_at']); ?></td>
                                <td><?php echo ucfirst($commission['commission_type']); ?></td>
                                <td><?php echo htmlspecialchars($commission['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($commission['first_name'] . ' ' . $commission['last_name']); ?></td>
                                <td><?php echo format_currency($commission['amount']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $commission['status'] === 'paid' ? 'success' : ($commission['status'] === 'approved' ? 'info' : 'warning'); ?>">
                                        <?php echo ucfirst($commission['status']); ?>
                                    </span>
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

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Marketing Materials</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Social Media Graphics</h6>
                            <small class="text-muted">Ready-to-share images for Facebook, Instagram, etc.</small>
                        </div>
                        <span class="badge bg-primary rounded-pill">10</span>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Email Templates</h6>
                            <small class="text-muted">Pre-written emails to send to your leads</small>
                        </div>
                        <span class="badge bg-primary rounded-pill">5</span>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Promotional Videos</h6>
                            <small class="text-muted">Product demonstrations and testimonials</small>
                        </div>
                        <span class="badge bg-primary rounded-pill">3</span>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">FAQ Document</h6>
                            <small class="text-muted">Common questions and answers</small>
                        </div>
                        <span class="badge bg-primary rounded-pill">1</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Affiliate Tips & Best Practices</h5>
            </div>
            <div class="card-body">
                <div class="accordion" id="affiliateTipsAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                How to Get Started
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#affiliateTipsAccordion">
                            <div class="accordion-body">
                                <p>Start by sharing your affiliate link with friends, family, and colleagues who might be interested in our products. Use social media, email newsletters, or direct messages to reach potential customers.</p>
                                <p class="mb-0">Remember to highlight the benefits of our products and how they can help solve problems or improve lives.</p>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                Building Your Team
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#affiliateTipsAccordion">
                            <div class="accordion-body">
                                <p>Focus on recruiting motivated individuals who are passionate about our products. Quality is more important than quantity when building your downline.</p>
                                <p class="mb-0">Provide support and training to your team members to help them succeed. Your success is directly tied to their success!</p>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                Marketing Strategies
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#affiliateTipsAccordion">
                            <div class="accordion-body">
                                <ol>
                                    <li>Create valuable content that attracts your target audience</li>
                                    <li>Use testimonials and success stories to build credibility</li>
                                    <li>Leverage social media platforms to expand your reach</li>
                                    <li>Host webinars or live events to engage with potential customers</li>
                                    <li>Implement email marketing campaigns to nurture leads</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
