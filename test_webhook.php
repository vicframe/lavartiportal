<?php

function getDBConnection() {
    $host = 'localhost';
    $user = 'lavartiportal_user';
    $pass = 'dSMXNhI-cQ7+';
    $dbname = 'lavartiportal';

    $mysqli = new mysqli($host, $user, $pass, $dbname);

    if ($mysqli->connect_error) {
        error_log("[" . date('Y-m-d H:i:s') . "] DB Connection failed: " . $mysqli->connect_error . "\n", 3, "webhook.log");
        die('Database connection error');
    }

    $mysqli->set_charset("utf8mb4");
    return $mysqli;
}

function logMessage($msg) {
    $date = date('Y-m-d H:i:s');
    file_put_contents('webhook.log', "[$date] $msg\n", FILE_APPEND);
}

function log_debug($message, $data = null) {
    $logFile = 'webhook_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if ($data !== null) {
        $logMessage .= ' => ' . print_r($data, true);
    }
    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
}

function findOrCreateUser($mysqli, $email, $data) {
    $email = $data['email'] ?? '';
    $url = $data['lastAttributionSource']['url'];
    // Step 1: Parse the URL
    $parts = parse_url($url);
    
    // Step 2: Parse query string if it exists
    parse_str($parts['query'] ?? '', $queryParams);
    
    // Step 3: Remove 'src' from query params
    unset($queryParams['src']);
    
    // Step 4: Rebuild query string
    $newQuery = http_build_query($queryParams);
    
    // Step 5: Reconstruct the URL without 'src'
    $baseUrl = $parts['scheme'] . '://' . $parts['host'];
    if (isset($parts['path'])) {
        $baseUrl .= $parts['path'];
    }
    if (!empty($newQuery)) {
        $baseUrl .= '?' . $newQuery;
    }
    $emailtest = $email;
    $username = strstr($emailtest, '@', true);
    $whole_url=$baseUrl.'src='.$username;

    $enroller_id = null;
    
    if (isset($parts['query'])) {
        parse_str($parts['query'], $queryParams);
        $enroller_id = $queryParams['src'] ?? null;
    }
    
    $defaultPassword = password_hash($data['Password'], PASSWORD_DEFAULT);

    $stmt = $mysqli->prepare("SELECT id, email FROM users WHERE email = ?");
    if (!$stmt) {
        logMessage("Prepare failed in findOrCreateUser: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        logMessage("User found: ID {$user['id']} email $email");
        return ['user_id' => $user['id'], 'email' => $email];
    }
    $contact_id = $data['contact_id'] ?? null;
    $first_name = $data['first_name'] ?? '';
    $last_name = $data['last_name'] ?? '';
    $full_name = $data['full_name'] ?? '';
    $url = $data['url'] ?? '';
    $phone = $data['phone'] ?? '';
    $address1 = $data['address1'] ?? '';
    $city = $data['city'] ?? '';
    $state = $data['state'] ?? '';
    $country = $data['country'] ?? '';
    $postal_code = $data['postal_code'] ?? '';
    $date_of_birth = $data['date_of_birth'] ?? '';
    $locationName = $data['location']['name'] ?? '';
    $locationId = $data['location']['id'] ?? '';
    $src_url = $whole_url ?? '';
    $enroller_id = $enroller_id ?? '';

    $stmt = $mysqli->prepare("
        INSERT INTO users (
            email, first_name, last_name, full_name, url, phone, password,
            address1, city, state, country, postal_code,
            date_of_birth, locationName, locationId, src_url, enroller_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ? ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        logMessage("Prepare failed in user insert: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param(
        "ssssssssssssssss",
        $email, $first_name, $last_name, $full_name, $baseUrl, $phone, $defaultPassword,
        $address1, $city, $state, $country, $postal_code,
        $date_of_birth, $locationName, $locationId,$src_url,$enroller_id
    );

    if (!$stmt->execute()) {
        logMessage("Execute failed in user insert: " . $stmt->error);
        return false;
    }

    $userId = $stmt->insert_id;
    $stmt->close();

    logMessage("User created: ID $userId email $email");
    return ['user_id' => $userId, 'email' => $email];
}

function createOrder($mysqli, $userId, $orderId, $orderDate, $total, $status, $order,$data) {
    $orderId=$data['order']['orderId'];
    $payment_gateway=$data['order']['payment_gateway'];
    $stmt = $mysqli->prepare("SELECT id FROM orders WHERE order_number = ?");
    if (!$stmt) {
        logMessage("Prepare failed in createOrder check: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param("s", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->fetch_assoc();
    $stmt->close();

    if ($exists) {
        logMessage("Order already exists: order_number $orderId");
        return false;
    }

    $subTotal = $total;
    $name = $data['full_name'];
    $orderNumber = $order['id'] ?? '';
    $orderDate = $order['orderDate'] ?? '';
    $total = $order['total'] ?? 0.0;

    $stmt = $mysqli->prepare("INSERT INTO orders (user_id, order_number, name, total_amount, sub_total, order_date, status, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        logMessage("Prepare failed in order insert: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param("isssdsss", $userId, $orderNumber,$name, $total, $subTotal, $orderDate, 'completed', $payment_gateway);
    $stmt->execute();
    $newOrderId = $stmt->insert_id;
    $stmt->close();

    logMessage("Order created: ID $newOrderId for User ID $userId");
    return $newOrderId;
}

function saveOrderItems($mysqli, $orderId, $items,$data) {
    foreach ($data['order']['line_items'] as $item) {
        $productId = $item['id'] ?? '';
        $price = $item['price'] ?? 0.0;
        $quantity = $item['quantity'] ?? 1;
        $volume = json_encode($item['volume'] ?? []);
        $name = $item['title'] ?? '';
        $pro_image = $item['image'] ?? '';
        $product_type = $item['product_type'] ?? '';
        $line_price = $item['line_price'] ?? '';
        $ghl_order_id = $item['meta']['order_id'] ?? '';

        $stmt = $mysqli->prepare("SELECT id FROM order_items WHERE order_id = ? AND sku = ?");
        if (!$stmt) {
            logMessage("Prepare failed in saveOrderItems select: " . $mysqli->error);
            continue;
        }

        $stmt->bind_param("is", $orderId, $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->fetch_assoc();
        $stmt->close();

        if ($exists) {
            logMessage("Item $productId already exists in order $orderId");
            continue;
        }

        $stmt = $mysqli->prepare("INSERT INTO order_items (order_id, sku, name, image, product_type, line_price, ghl_order_id, quantity, price, volume) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            logMessage("Prepare failed in saveOrderItems insert: " . $mysqli->error);
            continue;
        }

        $stmt->bind_param("issidsssss", $orderId, $productId, $name,$pro_image,$product_type,$line_price, $ghl_order_id, $quantity, $price, $volume);
        $stmt->execute();
        $stmt->close();

        logMessage("Item $productId added to order $orderId");
    }
}

function saveaddress($mysqli, $userId, $order_id, $address) {
    $type = $address['type'] ?? '';
    $line1 = $address['line1'] ?? '';
    $city = $address['city'] ?? '';
    $stateCode = $address['stateCode'] ?? '';
    $zip = $address['zip'] ?? '';
    $countryCode = $address['countryCode'] ?? '';

    $stmt = $mysqli->prepare("INSERT INTO customer_address (order_id, user_id, type, line1, city, stateCode, zip, countryCode) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        logMessage("Prepare failed in saveaddress: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param("iissssss", $order_id, $userId, $type, $line1, $city, $stateCode, $zip, $countryCode);
    $stmt->execute();
    $addressId = $stmt->insert_id;
    $stmt->close();

    logMessage("Address created: ID $addressId for User ID $userId");
    return $addressId;
}

function generateTemporaryPassword() {
    return substr(md5(uniqid(rand(), true)), 0, 10);
}

function syncCustomerAndPlaceOrder($authToken, array $customerData, array $orderData) {
    $baseUrl = 'https://api.pillarshub.com/api/v1';

    $headers = [
        "Authorization: $authToken",
        'Accept: application/json',
        'Content-Type: application/json'
    ];

    $customerId = $customerData['id'] ?? null;
    if (!$customerId) {
        log_debug("❌ Customer ID missing in payload.");
        echo "❌ Customer ID missing in payload.";
        return false;
    }

    $checkUrl = "$baseUrl/Customers/$customerId";
    log_debug("🔍 Checking if customer exists: $checkUrl");

    $ch = curl_init($checkUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $checkResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        log_debug("❌ cURL error (check): " . curl_error($ch));
        curl_close($ch);
        return false;
    }

    curl_close($ch);

    if ($httpCode !== 200) {
        $customerPayload = json_encode($customerData);
        log_debug("📤 Creating Customer", $customerData);

        $ch = curl_init("$baseUrl/Customers");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $customerPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $createResponse = curl_exec($ch);
        $createHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            log_debug("❌ cURL error (create customer): " . curl_error($ch));
            curl_close($ch);
            return false;
        }

        curl_close($ch);
        log_debug("📬 Create Customer Response ($createHttpCode)", $createResponse);

        if ($createHttpCode !== 201 && $createHttpCode !== 200) {
            log_debug("❌ Failed to create customer.");
            return false;
        }
    }

    // Place order API logic can go here if needed
    return true;
}


$rawPayload = file_get_contents('php://input');
file_put_contents('webhook_raw.log', $rawPayload);

if (!$rawPayload) {
    http_response_code(400);
    log_debug("❌ No input data received.");
    echo "❌ No input data received.";
    exit;
}

$data = json_decode($rawPayload, true);
if (!$data) {
    http_response_code(400);
    log_debug("❌ Invalid JSON received.");
    echo "❌ Invalid JSON received.";
    exit;
}

log_debug("📥 Incoming Payload", $data);


$url = $data['lastAttributionSource']['url'];

// Parse the URL and get the query part
$parts = parse_url($url);

// Default value
$src = null;

if (isset($parts['query'])) {
    parse_str($parts['query'], $queryParams);
    $src = $queryParams['src'] ?? null;
}
// 2. Map customer fields
$customer = [
    'id'           => $data['contact_id'] ?? null,
    'firstName'    => $data['first_name'] ?? '',
    'lastName'     => $data['last_name'] ?? '',
    'fullName'     => $data['full_name'] ?? '',
    'password'     => $data['Password'] ?? '',
    'email'        => $data['email'] ?? '',
    'phone'        => $data['phone'] ?? '',
    'addressLine1' => $data['address1'] ?? '',
    'enrollerId'   => $src ?? '',
    'city'         => $data['city'] ?? '',
    'state'        => $data['state'] ?? '',
    'country'      => $data['country'] ?? '',
    'postalCode'   => $data['postal_code'] ?? '',
    'dateOfBirth'  => $data['date_of_birth'] ?? '',
    'source'       => $data['contact_source'] ?? '',
    'locationName' => $data['location']['name'] ?? '',
    'locationId'   => $data['location']['id'] ?? '',
];

// 3. Build line items
$lineItems = [];
if (!empty($data['order']['line_items'])) {
    foreach ($data['order']['line_items'] as $product) {
        $lineItems[] = [
            'productId' => $product['id'] ?? '',
            'volume' => [
                ['volumeId' => 'FOCV', 'volume' => 100],
                ['volumeId' => 'CV', 'volume' => 0],
                ['volumeId' => 'QV', 'volume' => 50],
                ['volumeId' => 'ASQV', 'volume' => 50]
            ],
            'description' => null,
            'price' => ($product['plan']['amount'] ?? 0) / 100,
            'quantity' => 1
        ];
    }
}

// 4. Map order fields
$order = [
    // 'id' => $data['order']['orderId'] ?? '', // optional fallback
    'id'            => $data['email'] ?? '',
    'orderDate'     => $data['date_created'] ?? '',
    'externalIds'   => !empty($data['order']['productId']) ? [$data['order']['productId']] : [],
    'total'         => $data['order']['total_price'] ?? 0,
    'notes'         => $data['email'] ?? '',
    'lineItems'     => $lineItems,
    'shipAddress'   => [
        'line1'       => $data['address1'] ?? '',
        'line2'       => null,
        'line3'       => null,
        'stateCode'   => $data['state'] ?? '',
        'city'        => $data['city'] ?? '',
        'zip'         => $data['postal_code'] ?? '',
        'countryCode' => $data['country'] ?? ''
    ],
    'paymentMethod' => $data['order']['paymentMethod'] ?? '',
    'quantity'      => $data['order']['quantity'] ?? 1,
    'locationId'    => $data['location']['id'] ?? '',
    'workflowId'    => $data['workflow']['id'] ?? '',
    'workflowName'  => $data['workflow']['name'] ?? '',
];
$mysqli = getDBConnection();
// 5. Find or create user in local DB
$user = findOrCreateUser($mysqli,$data['email'], $data);
if (!$user) {
    logMessage("❌ Failed to find or create user.");
    http_response_code(500);
    echo json_encode(['error' => 'User creation failed']);
    exit;
}

$userId = $user['user_id'] ?? null;
if (!$userId) {
    logMessage("❌ User ID missing after creation.");
    http_response_code(500);
    echo json_encode(['error' => 'User ID missing']);
    exit;
}

// 6. Create order and line items
$orderId = createOrder($mysqli, $userId, $order['id'], $order['orderDate'], $order['total'], 'new', $order,$data);
if ($orderId) {
    saveOrderItems($mysqli, $orderId, $order['lineItems'],$data);
    log_debug("✅ Order and items saved", ['orderId' => $orderId]);
} else {
    log_debug("❌ Order creation failed", ['userId' => $userId]);
}

// 7. Sync with Pillars
$authToken = 'ZFge8sWV3T8JLB0sH9N5oQg89IJl40pjSLcx7Zhsu2mv'; // Move this to config if needed
syncCustomerAndPlaceOrder($authToken, $customer, $order);

http_response_code(200);
echo json_encode(['status' => 'success']);
