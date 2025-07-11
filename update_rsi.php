<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require 'vendor/autoload.php'; // Run: composer require firebase/php-jwt
use Firebase\JWT\JWT;



define('RSI_SECRET_KEY', '%#c@r#vRS022'); // For MD5 SSO hash

define('RSI_JWT_SECRET', '%#c@r#vRS022'); // For JWT

// ✅ Make sure your DB connection function exists
function getDBConnection() {

    // $host = 'localhost';
    // $user = 'lavartiportal_user';
    // $pass = 'I-]5d+pH.sgK';
    // $dbname = 'lavartiportal';

    $host = 'localhost';
    $user = 'testing_order_user';
    $pass = '4;i1ib!0XUAp';
    $dbname = 'testing_order';

    $mysqli = new mysqli($host, $user, $pass, $dbname);
    if ($mysqli->connect_error) {
        die("DB Connection failed: " . $mysqli->connect_error);
    }

    $mysqli->set_charset("utf8mb4");
    return $mysqli;
}

// ✅ Simple logger
function logMessage($msg) {
    file_put_contents('webhook.log', "[" . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

// ✅ Call this function directly to run the logic
function processAllUsersAndOrders() {
    $mysqli = getDBConnection();

    $query = "SELECT * FROM users";
    $result = $mysqli->query($query);

    if (!$result) {
        logMessage("❌ Failed to fetch users: " . $mysqli->error);
        return;
    }

    while ($user = $result->fetch_assoc()) {
        $userId = $user['id'];
        $orders = $mysqli->query("SELECT * FROM orders WHERE user_id = $userId");

        if (!$orders) {
            logMessage("❌ Failed to fetch orders for user $userId: " . $mysqli->error);
            continue;
        }

        while ($order = $orders->fetch_assoc()) {
            $orderId = $order['id'];
            $items = $mysqli->query("SELECT * FROM order_items WHERE order_id = $orderId");

            if (!$items) {
                logMessage("❌ Failed to fetch items for order $orderId: " . $mysqli->error);
                continue;
            }

            while ($item = $items->fetch_assoc()) {
                $title = strtolower($item['name']);
                $productCategory = null;

                if (strpos($title, 'passport lite') !== false) {
                    $productCategory = 'Passport Lite';
                } elseif (strpos($title, 'passport travel') !== false) {
                    $productCategory = 'Passport Travel Agent';
                } elseif (strpos($title, 'passport') !== false) {
                    $productCategory = 'Passport';
                }

                if ($productCategory) {
                    $customerData = [
                        'id'              => $user['id'],
                        'firstName'       => $user['first_name'],
                        'lastName'        => $user['last_name'],
                        'email'           => $user['email'],
                        'phone'           => $user['phone'],
                        'addressLine1'    => $user['address1'],
                        'city'            => $user['city'],
                        'state'           => $user['state'],
                        'postalCode'      => $user['postal_code'],
                        'country'         => $user['country'],
                        'productCategory' => $productCategory
                    ];

                    try {
                        sendToRsiApi($customerData, $userId);
                        logMessage("✅ Sent user $userId to RSI API with category $productCategory");
                    } catch (Exception $e) {
                        logMessage("❌ Failed for user $userId: " . $e->getMessage());
                        continue;
                    }
                }
            }
        }
    }

    $mysqli->close();
}
function generateTemporaryPassword() {

    return substr(md5(uniqid(rand(), true)), 0, 10);

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
// 🔁 Trigger the logic if running via CLI
if (php_sapi_name() === 'cli') {
    processAllUsersAndOrders();
}