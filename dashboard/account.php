<?php
$page_title = 'My Account';
require_once __DIR__ . '/../includes/header.php';

// Require login for this page
require_login();

// Get user information
$user = get_current_user();
$user_tier = $user['tier_id'] ?: 0;
$tier_name = get_tier_name($user_tier);

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    
    if (empty($first_name)) {
        $error_message = 'First name is required.';
    } elseif (empty($last_name)) {
        $error_message = 'Last name is required.';
    } else {
        // Update user information
        $update_result = update_user($user['id'], [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone
        ]);
        
        if ($update_result) {
            $success_message = 'Account information updated successfully.';
            
            // Refresh user data
            $user = get_user_by_id($user['id']);
            
            // Update GHL if connected
            if (!empty($user['ghl_id'])) {
                require_once __DIR__ . '/../api/ghl_api.php';
                ghl_update_contact($user['ghl_id'], [
                    'firstName' => $first_name,
                    'lastName' => $last_name,
                    'phone' => $phone
                ]);
            }
            
            // Update Pillars if connected
            if (!empty($user['pillars_id'])) {
                require_once __DIR__ . '/../api/pillars_api.php';
                pillars_update_user($user['pillars_id'], [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'phone' => $phone
                ]);
            }
        } else {
            $error_message = 'Failed to update account information.';
        }
    }
}

// Generate replicated site URL if not exists
$replicated_site = $user['replicated_site'];
if (empty($replicated_site)) {
    $replicated_site = generate_replicated_site($user['id'], $user['first_name'], $user['last_name']);
    update_user($user['id'], ['replicated_site' => $replicated_site]);
    
    // Refresh user data
    $user = get_user_by_id($user['id']);
}

// Get affiliate link
$affiliate_link = generate_affiliate_link($user['id'], $replicated_site);
?>

<div class="row mb-4">
    <div class="col-12">
        <h1>My Account</h1>
        <p class="lead">Manage your personal information and account settings.</p>
    </div>
</div>

<?php if (!empty($success_message)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $success_message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $error_message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Account Summary</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Email:</span>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Membership:</span>
                        <span><?php echo htmlspecialchars($tier_name); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Status:</span>
                        <span>
                            <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Member Since:</span>
                        <span><?php echo format_date($user['created_at']); ?></span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Your Affiliate Link</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Replicated Site Name</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="replicatedSite" value="<?php echo htmlspecialchars($replicated_site); ?>" readonly>
                        <button class="btn btn-outline-secondary" type="button" id="copyReplicatedSite">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-0">
                    <label class="form-label">Full Affiliate Link</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="affiliateLink" value="<?php echo htmlspecialchars($affiliate_link); ?>" readonly>
                        <button class="btn btn-outline-secondary" type="button" id="copyAffiliateLink">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Personal Information</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        <div class="form-text">Email address cannot be changed. Contact support if you need to update your email.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Connected Accounts</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Go High Level (GHL)</h6>
                            <span class="text-muted">Primary authentication system</span>
                        </div>
                        <span class="badge bg-success rounded-pill">Connected</span>
                    </li>
                    
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Pillars</h6>
                            <span class="text-muted">Commission and affiliate management</span>
                        </div>
                        <?php if (!empty($user['pillars_id'])): ?>
                        <span class="badge bg-success rounded-pill">Connected</span>
                        <?php else: ?>
                        <span class="badge bg-warning rounded-pill">Pending</span>
                        <?php endif; ?>
                    </li>
                    
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">RSI (Travel)</h6>
                            <span class="text-muted">Travel products and benefits</span>
                        </div>
                        <?php if ($user_tier >= 3): ?>
                        <span class="badge bg-success rounded-pill">Connected</span>
                        <?php else: ?>
                        <span class="badge bg-secondary rounded-pill">Elite Tier Only</span>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy replicated site
    document.getElementById('copyReplicatedSite').addEventListener('click', function() {
        const replicatedSiteInput = document.getElementById('replicatedSite');
        replicatedSiteInput.select();
        document.execCommand('copy');
        alert('Replicated site name copied to clipboard!');
    });
    
    // Copy affiliate link
    document.getElementById('copyAffiliateLink').addEventListener('click', function() {
        const affiliateLinkInput = document.getElementById('affiliateLink');
        affiliateLinkInput.select();
        document.execCommand('copy');
        alert('Affiliate link copied to clipboard!');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
