<?php
/**
 * Webhook Testing Script
 * 
 * This script allows you to test the webhook implementation with sample GHL data
 */

// Sample GHL user data (based on your example)
$sampleUserData = [
  "id" => 5943,
  "fullName" => "Positive Disruption",
  "enrollDate" => "2023-11-07",
  "profileImage" => null,
  "webAlias" => "Excel",
  "status" => [
    "id" => null,
    "name" => "Affiliate",
    "statusClass" => "default"
  ],
  "emailAddress" => "wellnesswealth90@yahoo.com",
  "customerType" => [
    "id" => null,
    "name" => "Affiliate PL"
  ],
  "language" => "English",
  "customData" => [
    "sku" => "PASSLITE-US-AFF-SUB",
    "displayName" => "Passport Lite Monthly Renewal (Affiliate)",
    "portalAccess" => "Passport Lite",
    "birthDate" => "2003-05-13",
    "enrollerId" => 3020,
    "company" => "Positive Disruption LLC"
  ],
  "phoneNumbers" => [
    [
      "type" => "mobile",
      "number" => "4407990606"
    ]
  ],
  "addresses" => [
    [
      "type" => "primary",
      "line1" => "PO Box 470136",
      "line2" => null,
      "line3" => null,
      "city" => "Broadview Hts",
      "stateCode" => "Ohio",
      "zip" => "44147",
      "countryCode" => "United States"
    ]
  ]
];

// Default test configurations
$testConfig = [
    'endpoint' => 'https://thephoenixlb.com/lavartiportal/webhook_ghl.php',
    'event_type' => 'contact.created',
    'data' => $sampleUserData
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'test_webhook') {
        $testConfig['endpoint'] = $_POST['endpoint'];
        $testConfig['event_type'] = $_POST['event_type'];
        
        // Get data from textarea if provided
        if (!empty($_POST['custom_data'])) {
            try {
                $testConfig['data'] = json_decode($_POST['custom_data'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $error = "Invalid JSON data: " . json_last_error_msg();
                }
            } catch (Exception $e) {
                $error = "Error parsing JSON: " . $e->getMessage();
            }
        }
        
        // Only proceed if there's no error
        if (!isset($error)) {
            // Make the API call
            $response = sendWebhookRequest(
                $testConfig['endpoint'], 
                $testConfig['event_type'], 
                $testConfig['data']
            );
        }
    }
}

/**
 * Send a test webhook request
 */
function sendWebhookRequest($endpoint, $eventType, $data) {
    // Get the full URL
    $currentDir = dirname($_SERVER['PHP_SELF']);
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    // $fullUrl = $baseUrl . $currentDir . '/' . ltrim($endpoint, '/');
    $fullUrl = 'https://thephoenixlb.com/lavartiportal/webhook_ghl.php';
    
    // Prepare cURL
    $ch = curl_init($fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Event-Type: ' . $eventType
    ]);
    
    // Execute the request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'url' => $fullUrl,
        'response' => $response,
        'http_code' => $httpCode,
        'error' => $error
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webhook Testing Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            max-height: 500px;
            overflow-y: auto;
        }
        .code-block {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container mt-5 mb-5">
        <h1 class="mb-4">Webhook Testing Tool</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($response)): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Test Results</h3>
                </div>
                <div class="card-body">
                    <h4>Request Details</h4>
                    <p><strong>URL:</strong> <?php echo $response['url']; ?></p>
                    <p><strong>Event Type:</strong> <?php echo htmlspecialchars($testConfig['event_type']); ?></p>
                    
                    <h4>Request Payload</h4>
                    <pre><?php echo json_encode($testConfig['data'], JSON_PRETTY_PRINT); ?></pre>
                    
                    <h4>Response (HTTP <?php echo $response['http_code']; ?>)</h4>
                    <?php if ($response['error']): ?>
                        <div class="alert alert-danger">Error: <?php echo $response['error']; ?></div>
                    <?php else: ?>
                        <pre><?php 
                            $jsonResponse = json_decode($response['response'], true);
                            echo json_encode($jsonResponse, JSON_PRETTY_PRINT); 
                        ?></pre>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">Test Configuration</h3>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <input type="hidden" name="action" value="test_webhook">
                    
                    <div class="mb-3">
                        <label for="endpoint" class="form-label">Webhook Endpoint</label>
                        <input type="text" class="form-control" id="endpoint" name="endpoint" 
                               value="<?php echo htmlspecialchars($testConfig['endpoint']); ?>">
                        <div class="form-text">Relative path to the webhook endpoint (e.g., '../webhook_ghl.php')</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="event_type" class="form-label">Event Type</label>
                        <select class="form-select" id="event_type" name="event_type">
                            <option value="contact.created" <?php echo $testConfig['event_type'] === 'contact.created' ? 'selected' : ''; ?>>contact.created</option>
                            <option value="contact.updated" <?php echo $testConfig['event_type'] === 'contact.updated' ? 'selected' : ''; ?>>contact.updated</option>
                            <option value="order.created" <?php echo $testConfig['event_type'] === 'order.created' ? 'selected' : ''; ?>>order.created</option>
                            <option value="order.updated" <?php echo $testConfig['event_type'] === 'order.updated' ? 'selected' : ''; ?>>order.updated</option>
                        </select>
                        <div class="form-text">Select the type of event to simulate</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="custom_data" class="form-label">Custom JSON Data (Optional)</label>
                        <textarea class="form-control" id="custom_data" name="custom_data" rows="10" placeholder="Paste your JSON data here"><?php echo isset($_POST['custom_data']) ? htmlspecialchars($_POST['custom_data']) : ''; ?></textarea>
                        <div class="form-text">Paste custom JSON data or leave empty to use the default sample data</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Send Test Webhook</button>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="mb-0">Sample Data</h3>
            </div>
            <div class="card-body">
                <h4>Default Test User Data</h4>
                <div class="code-block">
                    <pre><?php echo json_encode($sampleUserData, JSON_PRETTY_PRINT); ?></pre>
                    <button class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('defaultUserData')">Copy to Clipboard</button>
                </div>
                
                <h4>Sample Order Data</h4>
                <div class="code-block">
                    <pre id="sampleOrderData">{
  "id": 12345,
  "customerId": 5943,
  "orderDate": "2023-11-07",
  "emailAddress": "wellnesswealth90@yahoo.com",
  "total": 25.00,
  "status": "completed",
  "customerType": {
    "id": null,
    "name": "Affiliate PL"
  },
  "items": [
    {
      "id": 67890,
      "name": "Passport Lite Monthly Subscription",
      "sku": "PASSLITE-US-AFF-SUB",
      "price": 25.00,
      "quantity": 1
    }
  ],
  "addresses": [
    {
      "type": "primary",
      "line1": "PO Box 470136",
      "line2": null,
      "line3": null,
      "city": "Broadview Hts",
      "stateCode": "Ohio",
      "zip": "44147",
      "countryCode": "United States"
    }
  ],
  "customData": {
    "sku": "PASSLITE-US-AFF-SUB",
    "displayName": "Passport Lite Monthly Subscription",
    "portalAccess": "Passport Lite"
  }
}</pre>
                    <button class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('sampleOrderData')">Copy to Clipboard</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function copyToClipboard(elementId) {
            const el = document.getElementById(elementId);
            const text = el.textContent;
            
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            
            alert('Copied to clipboard!');
        }
        
        document.getElementById('defaultUserData').textContent = <?php echo json_encode(json_encode($sampleUserData, JSON_PRETTY_PRINT)); ?>;
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>