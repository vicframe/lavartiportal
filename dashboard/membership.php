<?php
$page_title = 'Membership Content';
require_once __DIR__ . '/../includes/header.php';

// Require login for this page
require_login();

// Get user information
$user = get_current_user();
$user_tier = $user['tier_id'] ?: 0;
$tier_name = get_tier_name($user_tier);

// Define content sections by tier
$content_sections = [
    // Basic Tier (Tier 1) Content
    [
        'name' => 'Getting Started Guide',
        'description' => 'Learn the fundamentals of our platform and how to maximize your benefits.',
        'tier_required' => 1,
        'type' => 'guide',
        'icon' => 'fas fa-book'
    ],
    [
        'name' => 'Basic Training Videos',
        'description' => 'Step-by-step tutorials on using our platform and basic strategies.',
        'tier_required' => 1,
        'type' => 'videos',
        'icon' => 'fas fa-video'
    ],
    [
        'name' => 'Monthly Community Call',
        'description' => 'Join our monthly call to get updates and connect with other members.',
        'tier_required' => 1,
        'type' => 'event',
        'icon' => 'fas fa-users'
    ],
    
    // Premium Tier (Tier 2) Content
    [
        'name' => 'Advanced Marketing Strategies',
        'description' => 'Take your marketing to the next level with these proven techniques.',
        'tier_required' => 2,
        'type' => 'guide',
        'icon' => 'fas fa-chart-line'
    ],
    [
        'name' => 'Premium Resource Library',
        'description' => 'Access our extensive library of templates, scripts, and tools.',
        'tier_required' => 2,
        'type' => 'resources',
        'icon' => 'fas fa-folder-open'
    ],
    [
        'name' => 'Weekly Group Coaching',
        'description' => 'Get regular guidance and feedback from our expert coaches.',
        'tier_required' => 2,
        'type' => 'event',
        'icon' => 'fas fa-chalkboard-teacher'
    ],
    
    // Elite Tier (Tier 3) Content
    [
        'name' => 'Executive Business Strategy',
        'description' => 'High-level business strategies for scaling your operations.',
        'tier_required' => 3,
        'type' => 'guide',
        'icon' => 'fas fa-crown'
    ],
    [
        'name' => 'One-on-One Mentoring',
        'description' => 'Personal mentoring sessions with industry leaders.',
        'tier_required' => 3,
        'type' => 'event',
        'icon' => 'fas fa-user-tie'
    ],
    [
        'name' => 'Exclusive Mastermind Group',
        'description' => 'Collaborate with other elite members in our private mastermind.',
        'tier_required' => 3,
        'type' => 'community',
        'icon' => 'fas fa-brain'
    ],
    [
        'name' => 'Travel Benefits Portal',
        'description' => 'Access exclusive travel discounts and reward opportunities.',
        'tier_required' => 3,
        'type' => 'travel',
        'icon' => 'fas fa-plane'
    ]
];

// Function to check if content is accessible
function can_access_content($content_tier, $user_tier) {
    return $user_tier >= $content_tier;
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h1>Membership Content</h1>
        <p class="lead">Access your exclusive membership content and resources.</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Your Membership</h5>
                <h2 class="text-primary mb-0"><?php echo htmlspecialchars($tier_name); ?></h2>
                <p class="text-muted">Tier <?php echo $user_tier ?: 'None'; ?></p>
                
                <div class="progress mt-3 mb-3">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo ($user_tier / 3) * 100; ?>%" aria-valuenow="<?php echo $user_tier; ?>" aria-valuemin="0" aria-valuemax="3"></div>
                </div>
                
                <?php if ($user_tier < 3): ?>
                <a href="/dashboard/products.php" class="btn btn-outline-primary">Upgrade Membership</a>
                <?php else: ?>
                <button class="btn btn-outline-success" disabled>Elite Member</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Membership Benefits</h5>
                
                <ul class="list-group list-group-flush mt-3">
                    <?php if ($user_tier >= 1): ?>
                    <li class="list-group-item">
                        <i class="fas fa-check-circle text-success me-2"></i> Access to basic resources and training
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-check-circle text-success me-2"></i> Monthly community calls
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-check-circle text-success me-2"></i> Standard support
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($user_tier >= 2): ?>
                    <li class="list-group-item">
                        <i class="fas fa-check-circle text-success me-2"></i> Advanced marketing strategies
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-check-circle text-success me-2"></i> Premium resource library
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-check-circle text-success me-2"></i> Weekly group coaching
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($user_tier >= 3): ?>
                    <li class="list-group-item">
                        <i class="fas fa-check-circle text-success me-2"></i> Executive business strategy
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-check-circle text-success me-2"></i> One-on-one mentoring
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-check-circle text-success me-2"></i> Exclusive mastermind group
                    </li>
                    <li class="list-group-item">
                        <i class="fas fa-check-circle text-success me-2"></i> Travel benefits and rewards
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <ul class="nav nav-pills mb-3" id="membershipTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="all-tab" data-bs-toggle="pill" data-bs-target="#all-content" type="button" role="tab" aria-controls="all-content" aria-selected="true">All Content</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="guides-tab" data-bs-toggle="pill" data-bs-target="#guides-content" type="button" role="tab" aria-controls="guides-content" aria-selected="false">Guides</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="videos-tab" data-bs-toggle="pill" data-bs-target="#videos-content" type="button" role="tab" aria-controls="videos-content" aria-selected="false">Videos</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="events-tab" data-bs-toggle="pill" data-bs-target="#events-content" type="button" role="tab" aria-controls="events-content" aria-selected="false">Events</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="resources-tab" data-bs-toggle="pill" data-bs-target="#resources-content" type="button" role="tab" aria-controls="resources-content" aria-selected="false">Resources</button>
            </li>
            <?php if ($user_tier >= 3): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="travel-tab" data-bs-toggle="pill" data-bs-target="#travel-content" type="button" role="tab" aria-controls="travel-content" aria-selected="false">Travel</button>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<div class="tab-content" id="membershipTabContent">
    <div class="tab-pane fade show active" id="all-content" role="tabpanel" aria-labelledby="all-tab">
        <div class="row">
            <?php foreach ($content_sections as $content): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 <?php echo can_access_content($content['tier_required'], $user_tier) ? '' : 'bg-light'; ?>">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-<?php echo can_access_content($content['tier_required'], $user_tier) ? 'primary' : 'secondary'; ?> text-white p-3 me-3">
                                <i class="<?php echo $content['icon']; ?>"></i>
                            </div>
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($content['name']); ?></h5>
                        </div>
                        <p class="card-text"><?php echo htmlspecialchars($content['description']); ?></p>
                        
                        <?php if (can_access_content($content['tier_required'], $user_tier)): ?>
                        <a href="#" class="btn btn-outline-primary">Access Content</a>
                        <?php else: ?>
                        <div class="text-center">
                            <span class="badge bg-secondary mb-2">Tier <?php echo $content['tier_required']; ?> Required</span>
                            <a href="/dashboard/products.php" class="btn btn-sm btn-outline-primary d-block">Upgrade to Access</a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent">
                        <small class="text-muted">Content Type: <?php echo ucfirst($content['type']); ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="tab-pane fade" id="guides-content" role="tabpanel" aria-labelledby="guides-tab">
        <div class="row">
            <?php 
            $guides = array_filter($content_sections, function($content) {
                return $content['type'] === 'guide';
            });
            
            foreach ($guides as $content): 
            ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 <?php echo can_access_content($content['tier_required'], $user_tier) ? '' : 'bg-light'; ?>">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-<?php echo can_access_content($content['tier_required'], $user_tier) ? 'primary' : 'secondary'; ?> text-white p-3 me-3">
                                <i class="<?php echo $content['icon']; ?>"></i>
                            </div>
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($content['name']); ?></h5>
                        </div>
                        <p class="card-text"><?php echo htmlspecialchars($content['description']); ?></p>
                        
                        <?php if (can_access_content($content['tier_required'], $user_tier)): ?>
                        <a href="#" class="btn btn-outline-primary">Access Guide</a>
                        <?php else: ?>
                        <div class="text-center">
                            <span class="badge bg-secondary mb-2">Tier <?php echo $content['tier_required']; ?> Required</span>
                            <a href="/dashboard/products.php" class="btn btn-sm btn-outline-primary d-block">Upgrade to Access</a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent">
                        <small class="text-muted">Content Type: Guide</small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="tab-pane fade" id="videos-content" role="tabpanel" aria-labelledby="videos-tab">
        <div class="row">
            <?php 
            $videos = array_filter($content_sections, function($content) {
                return $content['type'] === 'videos';
            });
            
            if (empty($videos)): 
            ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> No video content available in this category yet.
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($videos as $content): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 <?php echo can_access_content($content['tier_required'], $user_tier) ? '' : 'bg-light'; ?>">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-<?php echo can_access_content($content['tier_required'], $user_tier) ? 'primary' : 'secondary'; ?> text-white p-3 me-3">
                                <i class="<?php echo $content['icon']; ?>"></i>
                            </div>
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($content['name']); ?></h5>
                        </div>
                        <p class="card-text"><?php echo htmlspecialchars($content['description']); ?></p>
                        
                        <?php if (can_access_content($content['tier_required'], $user_tier)): ?>
                        <a href="#" class="btn btn-outline-primary">Watch Videos</a>
                        <?php else: ?>
                        <div class="text-center">
                            <span class="badge bg-secondary mb-2">Tier <?php echo $content['tier_required']; ?> Required</span>
                            <a href="/dashboard/products.php" class="btn btn-sm btn-outline-primary d-block">Upgrade to Access</a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent">
                        <small class="text-muted">Content Type: Videos</small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="tab-pane fade" id="events-content" role="tabpanel" aria-labelledby="events-tab">
        <div class="row">
            <?php 
            $events = array_filter($content_sections, function($content) {
                return $content['type'] === 'event';
            });
            
            foreach ($events as $content): 
            ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 <?php echo can_access_content($content['tier_required'], $user_tier) ? '' : 'bg-light'; ?>">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-<?php echo can_access_content($content['tier_required'], $user_tier) ? 'primary' : 'secondary'; ?> text-white p-3 me-3">
                                <i class="<?php echo $content['icon']; ?>"></i>
                            </div>
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($content['name']); ?></h5>
                        </div>
                        <p class="card-text"><?php echo htmlspecialchars($content['description']); ?></p>
                        
                        <?php if (can_access_content($content['tier_required'], $user_tier)): ?>
                        <a href="#" class="btn btn-outline-primary">View Schedule</a>
                        <?php else: ?>
                        <div class="text-center">
                            <span class="badge bg-secondary mb-2">Tier <?php echo $content['tier_required']; ?> Required</span>
                            <a href="/dashboard/products.php" class="btn btn-sm btn-outline-primary d-block">Upgrade to Access</a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent">
                        <small class="text-muted">Content Type: Event</small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="tab-pane fade" id="resources-content" role="tabpanel" aria-labelledby="resources-tab">
        <div class="row">
            <?php 
            $resources = array_filter($content_sections, function($content) {
                return $content['type'] === 'resources';
            });
            
            if (empty($resources)): 
            ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> No resource content available in this category yet.
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($resources as $content): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 <?php echo can_access_content($content['tier_required'], $user_tier) ? '' : 'bg-light'; ?>">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-<?php echo can_access_content($content['tier_required'], $user_tier) ? 'primary' : 'secondary'; ?> text-white p-3 me-3">
                                <i class="<?php echo $content['icon']; ?>"></i>
                            </div>
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($content['name']); ?></h5>
                        </div>
                        <p class="card-text"><?php echo htmlspecialchars($content['description']); ?></p>
                        
                        <?php if (can_access_content($content['tier_required'], $user_tier)): ?>
                        <a href="#" class="btn btn-outline-primary">Access Resources</a>
                        <?php else: ?>
                        <div class="text-center">
                            <span class="badge bg-secondary mb-2">Tier <?php echo $content['tier_required']; ?> Required</span>
                            <a href="/dashboard/products.php" class="btn btn-sm btn-outline-primary d-block">Upgrade to Access</a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent">
                        <small class="text-muted">Content Type: Resources</small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($user_tier >= 3): ?>
    <div class="tab-pane fade" id="travel-content" role="tabpanel" aria-labelledby="travel-tab">
        <div class="row">
            <?php 
            $travel = array_filter($content_sections, function($content) {
                return $content['type'] === 'travel';
            });
            
            foreach ($travel as $content): 
            ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-primary text-white p-3 me-3">
                                <i class="<?php echo $content['icon']; ?>"></i>
                            </div>
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($content['name']); ?></h5>
                        </div>
                        <p class="card-text"><?php echo htmlspecialchars($content['description']); ?></p>
                        <a href="#" class="btn btn-outline-primary">Access Travel Portal</a>
                    </div>
                    <div class="card-footer bg-transparent">
                        <small class="text-muted">Content Type: Travel</small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-primary text-white p-3 me-3">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <h5 class="card-title mb-0">Travel Dollars</h5>
                        </div>
                        <p class="card-text">Your earned travel dollars that can be used towards exclusive travel offers and packages.</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="text-success mb-0">$0.00</h3>
                            <a href="#" class="btn btn-outline-primary">View Travel Options</a>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <small class="text-muted">Content Type: Travel</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
