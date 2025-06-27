<?php
require 'vendor/autoload.php'; // Run: composer require firebase/php-jwt
use Firebase\JWT\JWT;

define('RSI_SECRET_KEY', '%#c@r#vRS022'); // For MD5 SSO hash
define('RSI_JWT_SECRET', '%#c@r#vRS022'); // For JWT

function getDBConnection() {
    $host = 'localhost';
    $user = 'lavartiportal_user';
    $pass = 'I-]5d+pH.sgK';
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

function findOrCreateUser($mysqli, $email, $data,$customer) {
    $email = $data['email'] ?? '';
    $url = $data['contact']['lastAttributionSource']['url'] ?? '';
    $email = $data['email'] ?? '';
    // Step 1: Parse URL and extract original `src` (enroller_id)
    $parts = parse_url($url);
    parse_str($parts['query'] ?? '', $queryParams);
    $enroller_id = $queryParams['src'] ?? null;
    
    // Step 2: Remove existing `src`
    unset($queryParams['src']);
    
    // Step 3: Extract email username
    $username = strstr($email, '@', true) ?: 'anonymous';
    
    // Step 4: Rebuild query string with new `src`
    $queryParams['src'] = $username;
    $newQuery = http_build_query($queryParams);
    
    // Step 5: Build final URL
    $newUrl = $parts['scheme'] . '://' . $parts['host'];
    if (!empty($parts['path'])) {
        $newUrl .= $parts['path'];
    }
    $newUrl .= '?' . $newQuery;
    
    // ✅ Result variables
    $whole_url = $newUrl;  
    $password_default='leverati@123';
    $defaultPassword = password_hash($password_default, PASSWORD_DEFAULT);

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
    $customer_type=$customer['customerType'];
    $stmt = $mysqli->prepare("
        INSERT INTO users (
            email, first_name, last_name, full_name, phone, password,
            address1, city, state, country, postal_code,
            date_of_birth, locationName, locationId, src_url, enroller_id, customerType) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        logMessage("Prepare failed in user insert: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param(
        "sssssssssssssssss",
        $email, $first_name, $last_name, $full_name, $phone, $defaultPassword,
        $address1, $city, $state, $country, $postal_code,
        $date_of_birth, $locationName, $locationId,$src_url,$enroller_id,$customer_type
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

function createOrder($mysqli, $userId, $orderId, $orderDate, $total, $status, $order,$data,$customer) {
    $orderId=$data['order']['line_items'][0]['meta']['order_id'];
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
    $status = 'completed';
    $stmt = $mysqli->prepare("INSERT INTO orders (user_id, order_number, name, total_amount, sub_total, order_date, status, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        logMessage("Prepare failed in order insert: " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("isssssss", $userId, $orderNumber,$name, $total, $subTotal, $orderDate, $status, $payment_gateway);
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

function syncCustomerAndPlaceOrder($authToken, array $customerData, array $orderData, $userId) {
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
    sendToRsiApi($customerData,$userId);
    // Place order API logic can go here if needed
    return true;
}

// 🔐 Generate JWT from member data
function generateJwtPayload(array $data): string
{
    $payload = $data;
    unset($payload['productCategory']); // remove extra field not needed in JWT
    return JWT::encode($payload, RSI_JWT_SECRET, 'HS256');
}

// 🔄 Send POST to RSI Member Create/Update
function sendToRsiApi($data, $userId)
{   
    log_debug("📦 Preparing to send data to RSI API", ['user_id' => $userId, 'data' => $data]);

    $categoryMap = [
        'Passport Lite' => [
            'orgId' => 803,
            'url' => 'https://passportlite.thedash.life/index.php',
        ],
        'Passport' => [
            'orgId' => 793,
            'url' => 'https://sso.thedash.life/index.php',
        ],
        'Passport Travel Agent' => [
            'orgId' => 826,
            'url' => 'https://travelagent.thedash.life/index.php',
        ],
    ];

    $data = [
        'id' => $data['id'],
        'firstName' => $data['firstName'],
        'lastName' => $data['lastName'],
        'email' => $data['email'],
        'phone1' => $data['phone'],
        'address1' => $data['addressLine1'],
        'city' => $data['city'],
        'state' => $data['state'],
        'postalCode' => $data['postalCode'],
        'country' => $data['country'],
        'productCategory' => $data['productCategory']
        // 'productCategory' => 'Passport Lite'
    ];

    $productCategory = $data['productCategory'];
    if (!isset($categoryMap[$productCategory])) {
        $errorMsg = "Invalid product category: $productCategory";
        log_debug("❌ $errorMsg");
        throw new Exception($errorMsg);
    }

    $orgId = $categoryMap[$productCategory]['orgId'];
    $url = "https://middleware.accessrsi.com/api/members/createupdate/$orgId/jwt";

    $jwtPayload = generateJwtPayload($data);
    log_debug("🔑 JWT Payload", $jwtPayload);

    $postData = http_build_query(['value' => $jwtPayload]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);

    log_debug("🚀 Sending POST to RSI API", ['url' => $url, 'postData' => $postData]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    log_debug("📬 RSI API Response Raw", ['status' => $httpCode, 'response' => $response, 'error' => $error]);

    $decoded = json_decode($response, true);

    $apiResult = [
        'status' => $httpCode,
        'response' => $decoded,
        'error' => $error
    ];

    displayApiResult($apiResult, array_merge($data, ['user_id' => $userId]), $categoryMap);
    return $apiResult;
}

// 🔗 Generate SSO Login Redirect URL
function generateRsiUrl(string $memberId, string $productCategory, array $categoryMap): string
{
    if (!isset($categoryMap[$productCategory])) {
        throw new Exception("Invalid product category: $productCategory");
    }

    $url = $categoryMap[$productCategory]['url'];
    $hash = md5(RSI_SECRET_KEY . $memberId);
    return "$url?uid=" . urlencode($memberId) . "&sk=$hash";
}

// ✅ Save redirect URL to users table
function saveRedirectUrlToUser(int $userId, string $url): void
{
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE users SET rsi_redirect_url = ? WHERE id = ?");
    $stmt->bind_param('si', $url, $userId);
    if ($stmt->execute()) {
        echo "✅ Redirect URL saved for user ID: $userId\n";
    } else {
        echo "❌ Failed to save redirect URL: " . $stmt->error . "\n";
    }
    $stmt->close();
    $conn->close();
}

function displayApiResult(array $apiResult, array $input, array $categoryMap): void
{
    if (in_array($apiResult['status'], [200, 201, 202, 409])) {
        echo "✅ RSI API Response:\n";
        print_r($apiResult['response']);

        $redirectUrl = generateRsiUrl($input['id'], $input['productCategory'], $categoryMap);
        echo "\n🔗 RSI Login URL: $redirectUrl\n";

        if (!empty($input['user_id'])) {
            saveRedirectUrlToUser((int)$input['user_id'], $redirectUrl);
        }

    } else {
        echo "❌ API Error [{$apiResult['status']}]: ";
        print_r($apiResult['response']);
        if (!empty($apiResult['error'])) {
            echo "\nCurl Error: {$apiResult['error']}\n";
        }
    }
}

if (!function_exists('log_debug')) {
    function log_debug($message, $data = null)
    {
        $logFile = __DIR__ . '/webhook_debug.log';
        $timestamp = date('[Y-m-d H:i:s]');
        $logEntry = $timestamp . ' ' . $message;

        if ($data !== null) {
            $logEntry .= ' ' . print_r($data, true);
        }

        $logEntry .= "\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}

function checkOrderType($lineItems) {
    foreach ($lineItems as $item) {
        $title = strtolower($item['title'] ?? '');

        if (strpos($title, 'affiliate') !== false) {
            return 1;
        }
    }
    return 2;
}
function checkProductCategory($lineItems) {
    foreach ($lineItems as $item) {
        $title = strtolower($item['title'] ?? '');

        if (strpos($title, 'passport lite') !== false) {
            return 'Passport Lite';
        } elseif (strpos($title, 'passport travel') !== false) {
            return 'Passport Travel Agent';
        } elseif (strpos($title, 'passport') !== false) {
            return 'Passport';
        }
    }
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


    $url = $data['contact']['lastAttributionSource']['url'];
    
    $src = null;
    
    if (!empty($url)) {
        $parts = parse_url($url);
    
        // 1. Try to get from query string
        if (isset($parts['query'])) {
            parse_str($parts['query'], $queryParams);
            $src = $queryParams['src'] ?? null;
        }
    
        // 2. If not found, try to find it in the path
        if (!$src && isset($parts['path'])) {
            // Use regex to find src in path like "/pass-us-travel-2446/src=1234"
            if (preg_match('/src=([\w\d]+)/i', $parts['path'], $matches)) {
                $src = $matches[1];
            }
        }
    }
    
$address = [
    "type"        => "primary",
    "line1"       => $data['full_address'] ?? "Unknown Address",
    "line2"       => null,
    "line3"       => null,
    "city"        => $data['city'] ?? "Unknown City",
    "stateCode"   => strtoupper(substr($data['location']['state'] ?? "NA", 0, 2)),
    "zip"         => $data['postal_code'] ?? "00000",
    "countryCode" => strtoupper($data['country'] ?? "US")
];
// 2. Map customer fields
$customer = [
    'id'           => $data['contact_id'] ?? null,
    'firstName'    => $data['first_name'] ?? '',
    'lastName'     => $data['last_name'] ?? '',
    'fullName'     => $data['full_name'] ?? '',
    'password'     => $data['Password'] ?? '',
    'email'        => $data['email'] ?? '',
    'customerType' => isset($data['order']['line_items']) && is_array($data['order']['line_items']) 
    ? checkOrderType($data['order']['line_items']) 
    : 2, 
    'productCategory' => isset($data['order']['line_items']) && is_array($data['order']['line_items']) 
    ? checkProductCategory($data['order']['line_items']) 
    : 2, 
    'phone'        => $data['phone'] ?? '',
    'addressLine1' => $data['address1'] ?? '',
    'externalIds' => array_filter([
        'OrderId:' . ($data['order']['line_items'][0]['meta']['order_id'] ?? ''),
        'PlanNickname:' . ($data['order']['line_items'][0]['title'] ?? ''),
        'PlanProduct:' . ($data['order']['line_items'][0]['title'] ?? '')
    ]),
    'enrollerId'   => $src ?? '',
    'emailAddress'         => $data['email'] ?? '',
    'city'         => $data['city'] ?? '',
    'state'        => $data['state'] ?? '',
    'country'      => $data['country'] ?? '',
    'postalCode'   => $data['postal_code'] ?? '',
    'dateOfBirth'  => $data['date_of_birth'] ?? '',
    'source'       => $data['contact_source'] ?? '',
    'locationName' => $data['location']['name'] ?? '',
    'addresses'    => [$address], 
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
$user = findOrCreateUser($mysqli,$data['email'], $data,$customer);
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
$orderId = createOrder($mysqli, $userId, $order['id'], $order['orderDate'], $order['total'], 'new', $order,$data,$customer);
if ($orderId) {
    saveOrderItems($mysqli, $orderId, $order['lineItems'],$data);
    log_debug("✅ Order and items saved", ['orderId' => $orderId]);
} else {
    log_debug("❌ Order creation failed", ['userId' => $userId]);
}

// 7. Sync with Pillars
$authToken = 'ZFge8sWV3T8JLB0sH9N5oQg89IJl40pjSLcx7Zhsu2mv'; // Move this to config if needed
syncCustomerAndPlaceOrder($authToken, $customer, $order,$userId);

http_response_code(200);
echo json_encode(['status' => 'success']);
