<?php
$page_title = 'Webhook Setup Guide';
require_once __DIR__ . '/includes/header.php';

// Check if user is an administrator
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// If not admin, redirect to dashboard
if (!is_admin && !DEBUG_MODE) {
    header('Location: /dashboard/index.php');
    exit;
}

// Get webhook URLs for display
$ghl_webhook_url = APP_URL . '/api/webhook_handler.php?source=ghl';
$pillars_webhook_url = APP_URL . '/api/webhook_handler.php?source=pillars';
$rsi_webhook_url = APP_URL . '/api/webhook_handler.php?source=rsi';

// Get webhook secret for display (only in debug mode)
$webhook_secret = DEBUG_MODE ? WEBHOOK_SECRET : '********';

// Handle test webhook submission
$test_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_webhook'])) {
    $source = sanitize_input($_POST['source']);
    $event = sanitize_input($_POST['event']);
    $payload = trim($_POST['payload']);
    
    // Validate inputs
    if (empty($source) || empty($event) || empty($payload)) {
        $test_result = [
            'success' => false,
            'message' => 'All fields are required for testing.'
        ];
    } else {
        try {
            // Parse payload as JSON
            $payload_data = json_decode($payload, true);
            if ($payload_data === null) {
                throw new Exception('Invalid JSON payload.');
            }
            
            // Create test payload
            $test_payload = [
                'event' => $event,
                $event_resource_map[$event] => $payload_data
            ];
            
            // Calculate signature
            $signature = hash_hmac('sha256', json_encode($test_payload), WEBHOOK_SECRET);
            
            // Set up cURL request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, APP_URL . "/api/webhook_handler.php?source={$source}");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-Webhook-Signature: ' . $signature
            ]);
            
            // Execute request
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Process response
            if ($http_code >= 200 && $http_code < 300) {
                $response_data = json_decode($response, true);
                $test_result = [
                    'success' => true,
                    'message' => 'Webhook test successful!',
                    'details' => $response_data
                ];
            } else {
                $test_result = [
                    'success' => false,
                    'message' => 'Webhook test failed. HTTP status code: ' . $http_code,
                    'details' => $response
                ];
            }
        } catch (Exception $e) {
            $test_result = [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
}

// Map of event types to their corresponding resource keys in the payload
$event_resource_map = [
    'contact.created' => 'contact',
    'contact.updated' => 'contact',
    'order.created' => 'order',
    'order.updated' => 'order',
    'user.created' => 'user',
    'user.updated' => 'user',
    'commission.created' => 'commission',
    'commission.updated' => 'commission',
    'travel_dollars.processed' => 'travel_dollars',
    'booking.created' => 'booking',
    'booking.updated' => 'booking'
];
?>

<div class="row mb-4">
    <div class="col-12">
        <h1>Webhook Setup Guide</h1>
        <p class="lead">Configure webhooks to enable real-time data synchronization between systems.</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Webhooks allow external systems to notify this application when events occur. Use this page to set up and test the webhook integrations.
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-plug me-2"></i> Go High Level (GHL) Webhooks
                </h5>
            </div>
            <div class="card-body">
                <p>Configure GHL to send webhook events to our system.</p>
                
                <h6 class="mt-3">Webhook URL:</h6>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" value="<?php echo $ghl_webhook_url; ?>" readonly>
                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard(this.previousElementSibling)">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                
                <h6>Events to Configure:</h6>
                <ul class="list-group mb-3">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Contact Created
                        <span class="badge bg-primary rounded-pill">Required</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Contact Updated
                        <span class="badge bg-primary rounded-pill">Required</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Order Created
                        <span class="badge bg-primary rounded-pill">Required</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Order Updated
                        <span class="badge bg-primary rounded-pill">Required</span>
                    </li>
                </ul>
                
                <a href="https://marketplace.gohighlevel.com/" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt me-1"></i> GHL Marketplace
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-plug me-2"></i> Pillars Webhooks
                </h5>
            </div>
            <div class="card-body">
                <p>Configure Pillars to send webhook events to our system.</p>
                
                <h6 class="mt-3">Webhook URL:</h6>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" value="<?php echo $pillars_webhook_url; ?>" readonly>
                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard(this.previousElementSibling)">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                
                <h6>Events to Configure:</h6>
                <ul class="list-group mb-3">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        User Created
                        <span class="badge bg-primary rounded-pill">Required</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        User Updated
                        <span class="badge bg-primary rounded-pill">Required</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Order Created
                        <span class="badge bg-primary rounded-pill">Required</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Order Updated
                        <span class="badge bg-primary rounded-pill">Required</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Commission Created
                        <span class="badge bg-primary rounded-pill">Required</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Commission Updated
                        <span class="badge bg-primary rounded-pill">Required</span>
                    </li>
                </ul>
                
                <a href="https://pillars.com/developers" target="_blank" class="btn btn-success">
                    <i class="fas fa-external-link-alt me-1"></i> Pillars Developer Portal
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-plug me-2"></i> RSI (Travel) Webhooks
                </h5>
            </div>
            <div class="card-body">
                <p>Configure RSI to send webhook events to our system.</p>
                
                <h6 class="mt-3">Webhook URL:</h6>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" value="<?php echo $rsi_webhook_url; ?>" readonly>
                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard(this.previousElementSibling)">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                
                <h6>Events to Configure:</h6>
                <ul class="list-group mb-3">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Travel Dollars Processed
                        <span class="badge bg-primary rounded-pill">Required</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Booking Created
                        <span class="badge bg-secondary rounded-pill">Optional</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Booking Updated
                        <span class="badge bg-secondary rounded-pill">Optional</span>
                    </li>
                </ul>
                
                <a href="https://rsi.com/api-docs" target="_blank" class="btn btn-warning">
                    <i class="fas fa-external-link-alt me-1"></i> RSI API Documentation
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-key me-2"></i> Webhook Secret
                </h5>
            </div>
            <div class="card-body">
                <p>This secret key is used to verify webhook requests. You should configure it in each system.</p>
                
                <div class="input-group mb-3">
                    <input type="text" class="form-control" value="<?php echo $webhook_secret; ?>" readonly>
                    <?php if (DEBUG_MODE): ?>
                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard(this.previousElementSibling)">
                        <i class="fas fa-copy"></i>
                    </button>
                    <?php endif; ?>
                </div>
                
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Keep this secret secure! Each system should use this key to generate a HMAC-SHA256 signature of the payload and send it in the <code>X-Webhook-Signature</code> header.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-cogs me-2"></i> Test Webhook
                </h5>
            </div>
            <div class="card-body">
                <p>Use this tool to test webhook payloads against your webhook handler.</p>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="source" class="form-label">Source System</label>
                            <select class="form-select" id="source" name="source" required>
                                <option value="">Select a source</option>
                                <option value="ghl">Go High Level (GHL)</option>
                                <option value="pillars">Pillars</option>
                                <option value="rsi">RSI (Travel)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="event" class="form-label">Event Type</label>
                            <select class="form-select" id="event" name="event" required>
                                <option value="">Select an event</option>
                                <optgroup label="GHL Events" class="ghl-events">
                                    <option value="contact.created">Contact Created</option>
                                    <option value="contact.updated">Contact Updated</option>
                                    <option value="order.created">Order Created</option>
                                    <option value="order.updated">Order Updated</option>
                                </optgroup>
                                <optgroup label="Pillars Events" class="pillars-events">
                                    <option value="user.created">User Created</option>
                                    <option value="user.updated">User Updated</option>
                                    <option value="order.created">Order Created</option>
                                    <option value="order.updated">Order Updated</option>
                                    <option value="commission.created">Commission Created</option>
                                    <option value="commission.updated">Commission Updated</option>
                                </optgroup>
                                <optgroup label="RSI Events" class="rsi-events">
                                    <option value="travel_dollars.processed">Travel Dollars Processed</option>
                                    <option value="booking.created">Booking Created</option>
                                    <option value="booking.updated">Booking Updated</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payload" class="form-label">JSON Payload</label>
                        <textarea class="form-control" id="payload" name="payload" rows="10" required></textarea>
                        <div class="form-text">Enter the payload in JSON format. This should match the data format expected by the handler.</div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="button" class="btn btn-outline-secondary" id="samplePayloadBtn">
                            <i class="fas fa-code me-1"></i> Generate Sample Payload
                        </button>
                        <button type="submit" class="btn btn-primary" name="test_webhook">
                            <i class="fas fa-paper-plane me-1"></i> Send Test Webhook
                        </button>
                    </div>
                </form>
                
                <?php if ($test_result !== null): ?>
                <div class="mt-4">
                    <h5>Test Result</h5>
                    <div class="alert alert-<?php echo $test_result['success'] ? 'success' : 'danger'; ?>">
                        <?php echo $test_result['message']; ?>
                    </div>
                    
                    <?php if (isset($test_result['details'])): ?>
                    <div class="card">
                        <div class="card-header bg-light">Response Details</div>
                        <div class="card-body">
                            <pre class="mb-0"><?php echo json_encode($test_result['details'], JSON_PRETTY_PRINT); ?></pre>
                        </div>
                    </div>
                    <?php endif; ?>
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
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i> Webhook Activity
                </h5>
            </div>
            <div class="card-body">
                <p>Recent webhook activity and processing status.</p>
                
                <?php
                // Get recent webhook logs
                $webhook_logs = db_query("SELECT * FROM webhook_logs ORDER BY created_at DESC LIMIT 10");
                $webhook_logs = db_fetch_all($webhook_logs);
                ?>
                
                <?php if (empty($webhook_logs)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No webhook activity has been recorded yet.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Source</th>
                                <th>Event Type</th>
                                <th>Status</th>
                                <th>Received</th>
                                <th>Processed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($webhook_logs as $log): ?>
                            <tr>
                                <td><?php echo $log['id']; ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $log['source'] === 'ghl' ? 'primary' : 
                                            ($log['source'] === 'pillars' ? 'success' : 'warning'); 
                                    ?>">
                                        <?php echo strtoupper($log['source']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['event_type']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $log['status'] === 'completed' ? 'success' : 
                                            ($log['status'] === 'failed' ? 'danger' : 
                                                ($log['status'] === 'processing' ? 'info' : 'secondary')); 
                                    ?>">
                                        <?php echo ucfirst($log['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo format_date($log['created_at'], 'M j, Y g:i A'); ?></td>
                                <td>
                                    <?php echo $log['processed_at'] ? format_date($log['processed_at'], 'M j, Y g:i A') : 'N/A'; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-secondary view-payload" data-payload='<?php echo htmlspecialchars($log['payload']); ?>'>
                                        <i class="fas fa-eye"></i> View
                                    </button>
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

<!-- Payload Modal -->
<div class="modal fade" id="payloadModal" tabindex="-1" aria-labelledby="payloadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="payloadModalLabel">Webhook Payload</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <pre id="payloadContent" class="bg-light p-3 rounded"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle source selection for event types
    const sourceSelect = document.getElementById('source');
    const eventSelect = document.getElementById('event');
    const ghlEvents = document.querySelector('.ghl-events');
    const pillarsEvents = document.querySelector('.pillars-events');
    const rsiEvents = document.querySelector('.rsi-events');
    
    // Hide all event optgroups initially
    ghlEvents.style.display = 'none';
    pillarsEvents.style.display = 'none';
    rsiEvents.style.display = 'none';
    
    sourceSelect.addEventListener('change', function() {
        // Reset event selection
        eventSelect.value = '';
        
        // Hide all event optgroups
        ghlEvents.style.display = 'none';
        pillarsEvents.style.display = 'none';
        rsiEvents.style.display = 'none';
        
        // Show relevant event optgroup based on source selection
        switch(this.value) {
            case 'ghl':
                ghlEvents.style.display = 'block';
                break;
            case 'pillars':
                pillarsEvents.style.display = 'block';
                break;
            case 'rsi':
                rsiEvents.style.display = 'block';
                break;
        }
    });
    
    // Handle view payload buttons
    const viewPayloadButtons = document.querySelectorAll('.view-payload');
    const payloadContent = document.getElementById('payloadContent');
    const payloadModal = new bootstrap.Modal(document.getElementById('payloadModal'));
    
    viewPayloadButtons.forEach(button => {
        button.addEventListener('click', function() {
            const payload = this.getAttribute('data-payload');
            try {
                const formattedPayload = JSON.stringify(JSON.parse(payload), null, 2);
                payloadContent.textContent = formattedPayload;
            } catch (e) {
                payloadContent.textContent = payload;
            }
            payloadModal.show();
        });
    });
    
    // Handle sample payload button
    const samplePayloadBtn = document.getElementById('samplePayloadBtn');
    const payloadTextarea = document.getElementById('payload');
    
    samplePayloadBtn.addEventListener('click', function() {
        const source = sourceSelect.value;
        const event = eventSelect.value;
        
        if (!source || !event) {
            alert('Please select a source and event type first.');
            return;
        }
        
        let samplePayload = {};
        
        switch(event) {
            case 'contact.created':
            case 'contact.updated':
                samplePayload = {
                    "id": "contact_" + Math.floor(Math.random() * 1000000),
                    "email": "user" + Math.floor(Math.random() * 1000) + "@example.com",
                    "firstName": "John",
                    "lastName": "Doe",
                    "phone": "+1234567890",
                    "status": "active",
                    "createdAt": new Date().toISOString()
                };
                break;
                
            case 'order.created':
            case 'order.updated':
                samplePayload = {
                    "id": "order_" + Math.floor(Math.random() * 1000000),
                    "contactId": "contact_" + Math.floor(Math.random() * 1000000),
                    "productId": ["basic_tier", "premium_tier", "elite_tier"][Math.floor(Math.random() * 3)],
                    "amount": [25, 65, 500][Math.floor(Math.random() * 3)],
                    "status": ["PENDING", "PROCESSING", "COMPLETED"][Math.floor(Math.random() * 3)],
                    "createdAt": new Date().toISOString()
                };
                break;
                
            case 'user.created':
            case 'user.updated':
                samplePayload = {
                    "id": "user_" + Math.floor(Math.random() * 1000000),
                    "email": "user" + Math.floor(Math.random() * 1000) + "@example.com",
                    "first_name": "John",
                    "last_name": "Doe",
                    "phone": "+1234567890",
                    "sponsor_id": "user_" + Math.floor(Math.random() * 1000000),
                    "replicated_site": "johndoe",
                    "tier_id": Math.floor(Math.random() * 3) + 1,
                    "external_id": "contact_" + Math.floor(Math.random() * 1000000)
                };
                break;
                
            case 'commission.created':
            case 'commission.updated':
                samplePayload = {
                    "id": "comm_" + Math.floor(Math.random() * 1000000),
                    "user_id": "user_" + Math.floor(Math.random() * 1000000),
                    "order_id": "order_" + Math.floor(Math.random() * 1000000),
                    "amount": Math.floor(Math.random() * 100) + 10,
                    "type": ["direct", "override", "bonus"][Math.floor(Math.random() * 3)],
                    "status": ["pending", "approved", "paid"][Math.floor(Math.random() * 3)],
                    "is_travel": Math.random() > 0.5
                };
                break;
                
            case 'travel_dollars.processed':
                samplePayload = {
                    "id": "td_" + Math.floor(Math.random() * 1000000),
                    "user_id": "user_" + Math.floor(Math.random() * 1000000),
                    "amount": Math.floor(Math.random() * 200) + 50,
                    "source": "commission",
                    "reference_id": "comm_" + Math.floor(Math.random() * 1000000)
                };
                break;
                
            case 'booking.created':
            case 'booking.updated':
                samplePayload = {
                    "id": "booking_" + Math.floor(Math.random() * 1000000),
                    "user_id": "user_" + Math.floor(Math.random() * 1000000),
                    "destination": ["Paris", "Tokyo", "New York", "Bali", "Rome"][Math.floor(Math.random() * 5)],
                    "amount": Math.floor(Math.random() * 1000) + 500,
                    "status": ["pending", "confirmed", "completed"][Math.floor(Math.random() * 3)]
                };
                break;
        }
        
        payloadTextarea.value = JSON.stringify(samplePayload, null, 2);
    });
});

// Function to copy text to clipboard
function copyToClipboard(element) {
    element.select();
    document.execCommand('copy');
    
    // Show copied confirmation
    const originalBtnHtml = element.nextElementSibling.innerHTML;
    element.nextElementSibling.innerHTML = '<i class="fas fa-check"></i>';
    
    setTimeout(() => {
        element.nextElementSibling.innerHTML = originalBtnHtml;
    }, 2000);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
