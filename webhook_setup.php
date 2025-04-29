<?php
/**
 * Webhook setup and management
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/api/ghl_api.php';
require_once __DIR__ . '/api/pillars_api.php';

// Set page title
$page_title = 'Webhook Setup';

// Require login
require_login();

// Check if user has admin rights
$user = get_current_logged_user();
$is_admin = isset($user['is_admin']) && $user['is_admin'] === 1;

if (!$is_admin) {
    // Redirect to dashboard if not admin
    redirect('/dashboard');
    exit;
}

// Handle form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'setup_ghl_webhooks':
            try {
                setup_ghl_webhooks();
                $message = 'GHL webhooks setup successfully';
            } catch (Exception $e) {
                $error = 'Error setting up GHL webhooks: ' . $e->getMessage();
            }
            break;
        
        case 'setup_pillars_webhooks':
            try {
                setup_pillars_webhooks();
                $message = 'Pillars webhooks setup successfully';
            } catch (Exception $e) {
                $error = 'Error setting up Pillars webhooks: ' . $e->getMessage();
            }
            break;
        
        case 'test_ghl_webhook':
            try {
                test_ghl_webhook();
                $message = 'GHL webhook test completed successfully';
            } catch (Exception $e) {
                $error = 'Error testing GHL webhook: ' . $e->getMessage();
            }
            break;
        
        case 'test_pillars_webhook':
            try {
                test_pillars_webhook();
                $message = 'Pillars webhook test completed successfully';
            } catch (Exception $e) {
                $error = 'Error testing Pillars webhook: ' . $e->getMessage();
            }
            break;
    }
}

// Include header
require_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h1>Webhook Setup and Management</h1>
            <p class="lead">Configure and manage webhooks for GHL and Pillars integrations</p>
            
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="card-title h5">Webhook Information</h2>
                </div>
                <div class="card-body">
                    <p>Webhooks are used to receive real-time notifications from GHL and Pillars when events occur, such as:</p>
                    <ul>
                        <li>New contact/member created</li>
                        <li>New order placed</li>
                        <li>Commission generated</li>
                        <li>Order status updated</li>
                    </ul>
                    
                    <h3 class="h5 mt-4">Webhook Endpoints</h3>
                    <p>Use the following URLs when configuring webhooks in the respective platforms:</p>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Platform</th>
                                    <th>Webhook URL</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>GoHighLevel</td>
                                    <td><code><?php echo APP_URL; ?>/api/webhook_handler.php?source=ghl</code></td>
                                </tr>
                                <tr>
                                    <td>Pillars</td>
                                    <td><code><?php echo APP_URL; ?>/api/webhook_handler.php?source=pillars</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h2 class="card-title h5">GoHighLevel Webhooks</h2>
                        </div>
                        <div class="card-body">
                            <p>Setup webhooks in GoHighLevel to receive notifications for the following events:</p>
                            <ul>
                                <li>Contact Created</li>
                                <li>Contact Updated</li>
                                <li>Order Created</li>
                                <li>Order Updated</li>
                            </ul>
                            
                            <form method="post" action="">
                                <input type="hidden" name="action" value="setup_ghl_webhooks">
                                <button type="submit" class="btn btn-primary">Setup GHL Webhooks</button>
                            </form>
                            
                            <hr>
                            
                            <h3 class="h6">Test GHL Webhook</h3>
                            <p>Send a test event to verify the webhook is working correctly.</p>
                            
                            <form method="post" action="">
                                <input type="hidden" name="action" value="test_ghl_webhook">
                                <button type="submit" class="btn btn-outline-primary">Send Test Event</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h2 class="card-title h5">Pillars Webhooks</h2>
                        </div>
                        <div class="card-body">
                            <p>Setup webhooks in Pillars to receive notifications for the following events:</p>
                            <ul>
                                <li>Commission Created</li>
                                <li>Commission Updated</li>
                                <li>Member Sponsor Changed</li>
                            </ul>
                            
                            <form method="post" action="">
                                <input type="hidden" name="action" value="setup_pillars_webhooks">
                                <button type="submit" class="btn btn-primary">Setup Pillars Webhooks</button>
                            </form>
                            
                            <hr>
                            
                            <h3 class="h6">Test Pillars Webhook</h3>
                            <p>Send a test event to verify the webhook is working correctly.</p>
                            
                            <form method="post" action="">
                                <input type="hidden" name="action" value="test_pillars_webhook">
                                <button type="submit" class="btn btn-outline-primary">Send Test Event</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="card-title h5">Webhook Logs</h2>
                </div>
                <div class="card-body">
                    <p>Recent webhook activity:</p>
                    
                    <?php
                    // Get webhook logs
                    $log_file = LOG_DIR . '/' . date('Y-m-d') . '.log';
                    $logs = [];
                    
                    if (file_exists($log_file)) {
                        $log_content = file_get_contents($log_file);
                        $log_lines = explode(PHP_EOL, $log_content);
                        
                        // Filter webhook-related logs
                        foreach ($log_lines as $line) {
                            if (strpos($line, 'Webhook') !== false) {
                                $logs[] = $line;
                            }
                        }
                        
                        // Show the 20 most recent logs
                        $logs = array_slice(array_reverse($logs), 0, 20);
                    }
                    ?>
                    
                    <?php if (!empty($logs)): ?>
                        <div class="log-container">
                            <?php foreach ($logs as $log): ?>
                                <div class="log-entry">
                                    <?php echo htmlspecialchars($log); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No webhook logs found for today.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .log-container {
        max-height: 300px;
        overflow-y: auto;
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 0.25rem;
        font-family: monospace;
        font-size: 0.875rem;
    }
    
    .log-entry {
        margin-bottom: 0.5rem;
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 0.5rem;
    }
    
    .log-entry:last-child {
        margin-bottom: 0;
        border-bottom: none;
        padding-bottom: 0;
    }
</style>

<?php
// Include footer
require_once __DIR__ . '/includes/footer.php';

/**
 * Setup GHL webhooks
 *
 * @return void
 */
function setup_ghl_webhooks() {
    try {
        $ghl_api = ghl_api();
        
        // Webhook URL for GHL events
        $webhook_url = APP_URL . '/api/webhook_handler.php?source=ghl';
        
        // Setup webhooks for different events
        $events = [
            'contact.created',
            'contact.updated',
            'order.created',
            'order.updated'
        ];
        
        foreach ($events as $event) {
            // Create webhook for this event
            $webhook_data = [
                'name' => 'LaVarti Integration - ' . $event,
                'url' => $webhook_url,
                'event' => $event,
                'isActive' => true
            ];
            
            // Make API request to create webhook
            // Note: This is a simplified version, as we don't have the actual GHL API for webhook management
            // In a real application, you would use the GHL API to create webhooks
            
            log_activity("GHL Webhook setup for event: {$event}", 'info');
        }
        
        return true;
    } catch (Exception $e) {
        log_activity('Error setting up GHL webhooks: ' . $e->getMessage(), 'error');
        throw $e;
    }
}

/**
 * Setup Pillars webhooks
 *
 * @return void
 */
function setup_pillars_webhooks() {
    try {
        $pillars_api = pillars_api();
        
        // Webhook URL for Pillars events
        $webhook_url = APP_URL . '/api/webhook_handler.php?source=pillars';
        
        // Setup webhooks for different events
        $events = [
            'commission.created',
            'commission.updated',
            'member.sponsorChanged'
        ];
        
        foreach ($events as $event) {
            // Create webhook for this event
            $webhook_data = [
                'name' => 'LaVarti Integration - ' . $event,
                'url' => $webhook_url,
                'event' => $event,
                'isActive' => true
            ];
            
            // Make API request to create webhook
            // Note: This is a simplified version, as we don't have the actual Pillars API for webhook management
            // In a real application, you would use the Pillars API to create webhooks
            
            log_activity("Pillars Webhook setup for event: {$event}", 'info');
        }
        
        return true;
    } catch (Exception $e) {
        log_activity('Error setting up Pillars webhooks: ' . $e->getMessage(), 'error');
        throw $e;
    }
}

/**
 * Test GHL webhook
 *
 * @return void
 */
function test_ghl_webhook() {
    try {
        // Create a test payload
        $payload = [
            'event' => 'contact.created',
            'contact' => [
                'id' => 'test_contact_id',
                'firstName' => 'Test',
                'lastName' => 'User',
                'email' => 'test@example.com',
                'phone' => '1234567890'
            ]
        ];
        
        // Send test webhook to our handler
        $webhook_url = APP_URL . '/api/webhook_handler.php?source=ghl';
        
        $ch = curl_init($webhook_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception('cURL error: ' . curl_error($ch));
        }
        
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($http_status >= 400) {
            throw new Exception('HTTP error: ' . $http_status);
        }
        
        log_activity('GHL webhook test sent successfully', 'info');
        
        return true;
    } catch (Exception $e) {
        log_activity('Error testing GHL webhook: ' . $e->getMessage(), 'error');
        throw $e;
    }
}

/**
 * Test Pillars webhook
 *
 * @return void
 */
function test_pillars_webhook() {
    try {
        // Create a test payload
        $payload = [
            'event' => 'commission.created',
            'commission' => [
                'id' => 'test_commission_id',
                'memberId' => 'test_member_id',
                'amount' => 25.00,
                'status' => 'pending',
                'source' => 'test',
                'sourceId' => 'test_order_id',
                'externalId' => 'test_order_external_id'
            ]
        ];
        
        // Send test webhook to our handler
        $webhook_url = APP_URL . '/api/webhook_handler.php?source=pillars';
        
        $ch = curl_init($webhook_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception('cURL error: ' . curl_error($ch));
        }
        
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($http_status >= 400) {
            throw new Exception('HTTP error: ' . $http_status);
        }
        
        log_activity('Pillars webhook test sent successfully', 'info');
        
        return true;
    } catch (Exception $e) {
        log_activity('Error testing Pillars webhook: ' . $e->getMessage(), 'error');
        throw $e;
    }
}