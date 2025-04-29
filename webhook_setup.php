<?php
/**
 * Webhook Setup Page
 * 
 * This page allows admins to set up and test webhooks for GHL and Pillars integrations.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// Require login
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$user = get_current_logged_user();

// Check if user is admin
if (!isset($user['is_admin']) || !$user['is_admin']) {
    header('Location: dashboard');
    exit;
}

// Initialize error and success messages
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_webhook_urls') {
        try {
            // Get base URL
            $base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
            $base_url .= $_SERVER['HTTP_HOST'];
            
            // Save GHL webhook config
            $ghl_config = [
                'webhook_url' => $base_url . '/webhook_ghl.php',
                'webhook_secret' => $_POST['ghl_webhook_secret'] ?? '',
                'api_key' => $_POST['ghl_api_key'] ?? '',
                'location_id' => $_POST['ghl_location_id'] ?? ''
            ];
            
            // Save Pillars webhook config
            $pillars_config = [
                'webhook_url' => $base_url . '/webhook_pillars.php',
                'webhook_secret' => $_POST['pillars_webhook_secret'] ?? '',
                'api_key' => $_POST['pillars_api_key'] ?? '',
                'organization_id' => $_POST['pillars_organization_id'] ?? ''
            ];
            
            // Save configurations to database
            save_integration_config('ghl', $ghl_config);
            save_integration_config('pillars', $pillars_config);
            
            $success_message = 'Webhook configurations updated successfully.';
        } catch (Exception $e) {
            $error_message = 'Error updating webhook configurations: ' . $e->getMessage();
        }
    } elseif ($action === 'test_ghl_webhook') {
        try {
            // Get GHL config
            $ghl_config = get_integration_config('ghl');
            
            // Create test data
            $test_data = [
                'event' => 'contact.created',
                'contact' => [
                    'id' => 'test-' . time(),
                    'email' => 'test-' . time() . '@example.com',
                    'firstName' => 'Test',
                    'lastName' => 'User',
                    'phone' => '+1234567890'
                ]
            ];
            
            // Send test webhook to our own endpoint
            $result = send_test_webhook('/webhook_ghl.php', $test_data, $ghl_config['webhook_secret'] ?? '');
            
            if ($result['success']) {
                $success_message = 'GHL webhook test successful: ' . $result['message'];
            } else {
                $error_message = 'GHL webhook test failed: ' . $result['error'];
            }
        } catch (Exception $e) {
            $error_message = 'Error testing GHL webhook: ' . $e->getMessage();
        }
    } elseif ($action === 'test_pillars_webhook') {
        try {
            // Get Pillars config
            $pillars_config = get_integration_config('pillars');
            
            // Create test data
            $test_data = [
                'event' => 'affiliate.created',
                'affiliate' => [
                    'id' => 'test-' . time(),
                    'email' => 'test-' . time() . '@example.com',
                    'firstName' => 'Test',
                    'lastName' => 'Affiliate'
                ]
            ];
            
            // Send test webhook to our own endpoint
            $result = send_test_webhook('/webhook_pillars.php', $test_data, $pillars_config['webhook_secret'] ?? '');
            
            if ($result['success']) {
                $success_message = 'Pillars webhook test successful: ' . $result['message'];
            } else {
                $error_message = 'Pillars webhook test failed: ' . $result['error'];
            }
        } catch (Exception $e) {
            $error_message = 'Error testing Pillars webhook: ' . $e->getMessage();
        }
    }
}

// Get current configurations
$ghl_config = get_integration_config('ghl');
$pillars_config = get_integration_config('pillars');

// Get sync history
$ghl_sync_history = get_sync_history('ghl', 5);
$pillars_sync_history = get_sync_history('pillars', 5);

/**
 * Get integration configuration
 */
function get_integration_config($integration_name) {
    $query = "SELECT config_data FROM integration_settings WHERE integration_name = ?";
    $result = db_query($query, [$integration_name]);
    $config = db_fetch_one($result);
    
    if ($config) {
        return json_decode($config['config_data'], true);
    }
    
    return [
        'webhook_url' => '',
        'webhook_secret' => '',
        'api_key' => '',
        'location_id' => '',
        'organization_id' => ''
    ];
}

/**
 * Save integration configuration
 */
function save_integration_config($integration_name, $config) {
    // Check if config already exists
    $query = "SELECT id FROM integration_settings WHERE integration_name = ?";
    $result = db_query($query, [$integration_name]);
    $existing = db_fetch_one($result);
    
    if ($existing) {
        // Update existing config
        $query = "UPDATE integration_settings SET config_data = ?, updated_at = NOW() WHERE integration_name = ?";
        db_query($query, [json_encode($config), $integration_name]);
    } else {
        // Insert new config
        $query = "INSERT INTO integration_settings (integration_name, config_data, created_at, updated_at) VALUES (?, ?, NOW(), NOW())";
        db_query($query, [$integration_name, json_encode($config)]);
    }
    
    return true;
}

/**
 * Get sync history for an integration
 */
function get_sync_history($integration, $limit = 5) {
    $query = "
        SELECT * FROM sync_history 
        WHERE integration = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ";
    
    $result = db_query($query, [$integration, $limit]);
    return db_fetch_all($result);
}

/**
 * Send a test webhook to our own endpoint
 */
function send_test_webhook($endpoint, $data, $secret = '') {
    // Get base URL
    $base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
    $base_url .= $_SERVER['HTTP_HOST'];
    
    $url = $base_url . $endpoint;
    $payload = json_encode($data);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload),
        'X-Webhook-Signature: ' . $secret
    ]);
    
    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => $error];
    }
    
    if ($status_code >= 200 && $status_code < 300) {
        $response_data = json_decode($response, true);
        return ['success' => true, 'message' => $response_data['message'] ?? 'Webhook received successfully'];
    } else {
        return ['success' => false, 'error' => "HTTP Error: {$status_code} - {$response}"];
    }
}

// Include admin header
include 'includes/admin_header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <h4 class="page-title">Webhook Setup</h4>
                </div>
            </div>
        </div>
        
        <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Webhook Configuration</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="webhook_setup.php">
                            <input type="hidden" name="action" value="update_webhook_urls">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="card-title mb-0">GoHighLevel (GHL)</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="ghl_api_key" class="form-label">API Key</label>
                                                <input type="text" class="form-control" id="ghl_api_key" name="ghl_api_key" value="<?php echo htmlspecialchars($ghl_config['api_key'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label for="ghl_location_id" class="form-label">Location ID</label>
                                                <input type="text" class="form-control" id="ghl_location_id" name="ghl_location_id" value="<?php echo htmlspecialchars($ghl_config['location_id'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label for="ghl_webhook_secret" class="form-label">Webhook Secret</label>
                                                <input type="text" class="form-control" id="ghl_webhook_secret" name="ghl_webhook_secret" value="<?php echo htmlspecialchars($ghl_config['webhook_secret'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label for="ghl_webhook_url" class="form-label">Webhook URL (use this in GHL)</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="ghl_webhook_url" value="<?php echo htmlspecialchars($ghl_config['webhook_url'] ?? ''); ?>" readonly>
                                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('ghl_webhook_url')">Copy</button>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <button type="button" class="btn btn-secondary" onclick="testWebhook('ghl')">Test Webhook</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-info text-white">
                                            <h5 class="card-title mb-0">Pillars</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="pillars_api_key" class="form-label">API Key</label>
                                                <input type="text" class="form-control" id="pillars_api_key" name="pillars_api_key" value="<?php echo htmlspecialchars($pillars_config['api_key'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label for="pillars_organization_id" class="form-label">Organization ID</label>
                                                <input type="text" class="form-control" id="pillars_organization_id" name="pillars_organization_id" value="<?php echo htmlspecialchars($pillars_config['organization_id'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label for="pillars_webhook_secret" class="form-label">Webhook Secret</label>
                                                <input type="text" class="form-control" id="pillars_webhook_secret" name="pillars_webhook_secret" value="<?php echo htmlspecialchars($pillars_config['webhook_secret'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label for="pillars_webhook_url" class="form-label">Webhook URL (use this in Pillars)</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="pillars_webhook_url" value="<?php echo htmlspecialchars($pillars_config['webhook_url'] ?? ''); ?>" readonly>
                                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('pillars_webhook_url')">Copy</button>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <button type="button" class="btn btn-secondary" onclick="testWebhook('pillars')">Test Webhook</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary">Save Configuration</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">GHL Sync History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Action</th>
                                        <th>Status</th>
                                        <th>Records</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($ghl_sync_history)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No sync history available</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($ghl_sync_history as $sync): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y H:i:s', strtotime($sync['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($sync['action']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $sync['status'] === 'success' ? 'bg-success' : ($sync['status'] === 'error' ? 'bg-danger' : 'bg-warning'); ?>">
                                                    <?php echo htmlspecialchars($sync['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($sync['records_processed']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Pillars Sync History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Action</th>
                                        <th>Status</th>
                                        <th>Records</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pillars_sync_history)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No sync history available</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($pillars_sync_history as $sync): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y H:i:s', strtotime($sync['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($sync['action']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $sync['status'] === 'success' ? 'bg-success' : ($sync['status'] === 'error' ? 'bg-danger' : 'bg-warning'); ?>">
                                                    <?php echo htmlspecialchars($sync['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($sync['records_processed']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    element.select();
    document.execCommand('copy');
    
    // Show success alert
    const alertPlaceholder = document.createElement('div');
    alertPlaceholder.innerHTML = `
        <div class="alert alert-success alert-dismissible fade show fixed-top m-3" role="alert">
            Copied to clipboard!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    document.body.appendChild(alertPlaceholder);
    
    // Auto-remove alert after 2 seconds
    setTimeout(() => {
        alertPlaceholder.remove();
    }, 2000);
}

function testWebhook(integration) {
    // Get the form
    const form = document.createElement('form');
    form.method = 'post';
    form.action = 'webhook_setup.php';
    
    // Create hidden input for action
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = `test_${integration}_webhook`;
    
    // Add inputs to form
    form.appendChild(actionInput);
    
    // Add form to document and submit
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php include 'includes/admin_footer.php'; ?>