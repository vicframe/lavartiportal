<?php
// webhook_ghl.php
require_once __DIR__ . '/./rsi_api.php'; //

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

function findOrCreateUser($mysqli, $email, $data) {
    // Create new user
    $defaultPassword = password_hash(generateTemporaryPassword(), PASSWORD_DEFAULT);
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
        // return $user;
         return ['user_id' => $user['id'], 'email' => $email];
    }

    $stmt = $mysqli->prepare("INSERT INTO users (email,password) VALUES (?,?)");
    if (!$stmt) {
        logMessage("Prepare failed in user insert: " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("ss", $email,$defaultPassword);
    $stmt->execute();
    $userId = $stmt->insert_id;
    $stmt->close();

    logMessage("User created: ID $userId email $email");

    return ['user_id' => $userId, 'email' => $email];
}

function createOrder($mysqli, $userId, $orderId, $orderDate, $total, $status,$order) {
    $stmt = $mysqli->prepare("SELECT id FROM orders WHERE id = ?");
    if (!$stmt) {
        logMessage("Prepare failed in createOrder check: " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->fetch_assoc();
    $stmt->close();

    if ($exists) {
        logMessage("Order already exists: order_id $orderId");
        return false;
    }
    $subTotal=$order['subTotal'];
    $total=$order['total'];
    $orderDate=$order['orderDate'];
    // $invoiceDate=$order['invoiceDate'];
    $order_number=$order['id'];
    $stmt = $mysqli->prepare("INSERT INTO orders (user_id, order_number, total_amount, sub_total, order_date, status) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        logMessage("Prepare failed in order insert: " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("isssds", $userId, $order_number, $total, $subTotal, $orderDate, $status);
    $stmt->execute();
    $newOrderId = $stmt->insert_id;
    $stmt->close();

    logMessage("Order created: ID $newOrderId for User ID $userId");

    return $newOrderId;
}
function saveaddress($mysqli, $userId, $order_id,$address) {
    $type=$address['type'];
    $line1=$address['line1'];
    $city=$address['city'];
    $stateCode=$address['stateCode'];
    $zip=$address['zip'];
    $countryCode=$address['countryCode'];
    $stmt = $mysqli->prepare("INSERT INTO customer_address (order_id, user_id, type, line1, city, stateCode, zip, countryCode) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        logMessage("Prepare failed in order insert: " . $mysqli->error);
        return false;
    }
    $stmt->bind_param("iissssss", $order_id, $userId, $type, $line1, $city, $stateCode, $zip, $countryCode);
    $stmt->execute();
    $addressId = $stmt->insert_id;
    $stmt->close();

    logMessage("Addresss created: ID $newOrderId for User ID $userId");

    return $addressId;
}
function saveOrderItems($mysqli, $orderId, $items) {
    foreach ($items as $item) {
        $stmt = $mysqli->prepare("SELECT id FROM order_items WHERE order_id = ? AND item_id = ?");
        if (!$stmt) {
            logMessage("Prepare failed in saveOrderItems select: " . $mysqli->error);
            continue;
        }
        $stmt->bind_param("ii", $orderId, $item['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->fetch_assoc();
        $stmt->close();

        if ($exists) {
            logMessage("Item {$item['id']} already exists in order $orderId");
            continue;
        }

        $stmt = $mysqli->prepare("INSERT INTO order_items (order_id, sku, name, description, quantity, price,volume) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            logMessage("Prepare failed in saveOrderItems insert: " . $mysqli->error);
            continue;
        }
        $stmt->bind_param(
            "iissdi",
            $orderId,
            $item['id'],
            $item['sku'],
            $item['name'],
            $item['description'],
            $item['price'],
            $item['quantity'],
            $item['volume']
        );
        $stmt->execute();
        $stmt->close();
        logMessage("Item {$item['id']} added to order $orderId");
    }
}
function sendToPillars($data) {
    $pillarsUrl = 'https://api.pillarshub.com/api/v1/Orders'; // API URL
    $apiKey = 'ZFge8sWV3T8JLB0sH9N5oQg89IJl40pjSLcx7Zhsu2mv'; //

    $payload = [
        'order_id' => $data['id'],
        'customer_id' => $data['customerId'],
        'order_date' => $data['orderDate'],
        'email' => $data['emailAddress'],
        'total' => $data['total'],
        'status' => $data['status'],
        'customer_type' => $data['customerType']['name'] ?? null,
        'items' => $data['items'] ?? [],
        'addresses' => $data['addresses'] ?? [],
        'custom_data' => $data['customData'] ?? [],
    ];

    $ch = curl_init($pillarsUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        logMessage("Pillars API request failed: $error");
        return false;
    }

    logMessage("Pillars API response: $response");
    return true;
}
function generateTemporaryPassword() {
    return substr(md5(uniqid(rand(), true)), 0, 10);
}
function piller_customer_creation(){
$url = 'https://api.pillarshub.com/api/v1/Customers';

$payload = json_encode([
    "id" => "7005",
    "firstName" => "Stephanie",
    "middleName" => "Golka",
    "lastName" => "Stephanie Golka",
    "signupDate" => "2025-04-27T18:12:25Z",
    "emailAddress" => "stephaniegolka@yahoo.com",
    "phoneNumbers" => [
        ["type" => "mobile", "number" => "7809653128"]
    ],
    "addresses" => [
        [
            "type" => "primary",
            "line1" => "1197, 5328 Calgary Trail NW",
            "city" => "Edmonton",
            "stateCode" => "AB",
            "zip" => "T6H4J8",
            "countryCode" => "CA"
        ]
    ],
    "language" => "English",
    "customData" => "ANNUAL - Passport (New Affiliate)"
]);

$headers = [
    'Authorization: gfgfgfgfgfgfgfggfgfg',
    'Accept: application/json',
    'Content-Type: application/*+json',
];

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
} else {
    echo $response;
}

curl_close($ch);

}
function handleWebhook() {
    $mysqli = getDBConnection();

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        logMessage("Invalid JSON input");
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    if (empty($data['emailAddress'])) {
        logMessage("Missing emailAddress in webhook");
        http_response_code(400);
        echo json_encode(['error' => 'Email address is required']);
        exit;
    }

    $user = findOrCreateUser($mysqli, $data['emailAddress'],$data);
    if (!$user) {
        logMessage("Failed to find or create user");
        http_response_code(500);
        echo json_encode(['error' => 'User error']);
        exit;
    }

    $orderId = createOrder(
        $mysqli,
        $user['user_id'],
        $data['id'],
        $data['orderDate'],
        $data['total'],
        $data['status'],
        $data['orders']
    );

    if (!$orderId) {
        http_response_code(200);
        echo json_encode(['status' => 'order exists or failed']);
        exit;
    }

    // === RSI AUTO REGISTER ===
try {
    $first_name = $data['firstName'] ?? 'Unknown';
    $last_name = $data['lastName'] ?? 'Unknown';
    $email = $data['emailAddress'];
    $phone = $data['phoneNumbers'][0]['number'] ?? '';

    // Assume first SKU from line items
    $sku = $data['orders']['lineItems'][0]['sku'] ?? null;

    if ($sku) {
        $rsi_payload = [
            'id' => $user['user_id'],
            'firstName' => $first_name,
            'lastName' => $last_name,
            'email' => $email,
            'phone1' => $phone,
            'address1' => $address['line1'] ?? '',
            'city' => $address['city'] ?? '',
            'state' => $address['stateCode'] ?? '',
            'postalCode' => $address['zip'] ?? '',
            'country' => $address['countryCode'] ?? 'US',
            'sku' => $sku
        ];

        $rsi_response = rsi_register_user($rsi_payload);

        if ($rsi_response) {
            logMessage("✅ RSI registration successful for user {$email}");
        } else {
            logMessage("❌ RSI registration failed for user {$email}");
        }
    } else {
        logMessage("❌ No SKU found for RSI registration");
    }
} catch (Exception $e) {
    logMessage("❌ RSI Exception: " . $e->getMessage());
}


    if (!empty($data['addresses'])) {
        foreach ($data['addresses'] as $address) {
            if ($address['type'] === 'primary') {
              saveaddress($mysqli, $user['user_id'], $orderId,$address);
                break;
            }
        }
    }

    if (!empty($data['orders']['lineItems'])) {
        saveOrderItems($mysqli, $orderId, $data['orders']['lineItems']);
    }

    $pillarsSuccess = sendToPillars($data);
    if (!$pillarsSuccess) {
        logMessage("Failed to send data to Pillars API for order ID {$data['id']}");
    }

    http_response_code(200);
    echo json_encode(['status' => 'success']);
}
handleWebhook();
