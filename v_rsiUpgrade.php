<?php
//Hardcoded demo values — replace with real values or pull from DB
$customerId = 'abc1323543';  // From GHL order
$currentOrgId = 803; // Starting from Passport Lite
$newPackageId = 1677; // Upgrade to Travel Agent

// 🔐 Auth details for token
$client_id = 'levarti';
$client_secret = '!l@cebVTk#';
$username = 'levarti@login.com';
$password = '$b@e001$!!#';

// STEP 1: Get access token
$token_url = "https://authorize.accessrsi.com/connect/token";

$token_data = http_build_query([
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'grant_type' => 'password',
    'username' => $username,
    'password' => $password
]);

$token_opts = [
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded",
        'content' => $token_data
    ]
];

$token_context = stream_context_create($token_opts);
$token_response = file_get_contents($token_url, false, $token_context);

if ($token_response === false) {
    die("❌ Failed to get token.\n");
}

$token_json = json_decode($token_response, true);
$access_token = $token_json['access_token'] ?? null;

if (!$access_token) {
    die("❌ No access_token in response: " . print_r($token_json, true));
}

// STEP 2: PUT to upgrade package
$upgrade_url = "https://svc.accessrsi.com/membermanagerapi/memberchangepackage/{$currentOrgId}";
$upgrade_body = json_encode([
    "id" => $customerId,
    "packageId" => $newPackageId
]);

$upgrade_opts = [
    'http' => [
        'method' => 'PUT',
        'header' => "Content-Type: application/json\r\nAuthorization: Bearer $access_token",
        'content' => $upgrade_body
    ]
];

$upgrade_context = stream_context_create($upgrade_opts);
$upgrade_response = file_get_contents($upgrade_url, false, $upgrade_context);

if ($upgrade_response === false) {
    die("❌ Failed to send upgrade request.");
}

echo "<pre>✅ Upgrade Response:\n" . $upgrade_response . "</pre>";
?>



<?php
// 🔐 Config: Replace with your real values
$rsiSecretKey = '%#c@r#vRS022'; // For MD5
$rsiJwtSecret = '%#c@r#vRS022'; // For JWT signing

// 🧾 Input from order/customer (normally dynamic)
$input = [
    'id' => 'M1697778',
    'firstName' => 'Abbiii',
    'lastName' => 'Allisonnnn',
    'email' => 'abbiallison20033338@gmail.com',
    'phone1' => '6417773558',
    'address1' => '414 Hamilton Sttt',
    'city' => 'Ottumwa',
    'state' => 'IA',
    'postalCode' => '52501',
    'country' => 'USA',
    'productCategory' => 'Passport Travel Agent'
];

// 🎯 Category Map
$categoryMap = [
    'Passport Lite' => ['orgId' => 803, 'url' => 'https://passportlite.thedash.life/index.php'],
    'Passport' => ['orgId' => 793, 'url' => 'https://sso.thedash.life/index.php'],
    'Passport Travel Agent' => ['orgId' => 826, 'url' => 'https://travelagent.thedash.life/index.php']
];

// 🔐 Encode JWT (HMAC SHA256 manually)
function generateJwt($payload, $secret) {
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $segments = [
        rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '='),
        rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=')
    ];
    $signingInput = implode('.', $segments);
    $signature = hash_hmac('sha256', $signingInput, $secret, true);
    $segments[] = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    return implode('.', $segments);
}

// 🔗 Create SSO login link
function generateLoginUrl($id, $category, $map, $md5Key) {
    if (!isset($map[$category])) throw new Exception("Invalid product category: $category");
    $hash = md5($md5Key . $id);
    return $map[$category]['url'] . "?uid=" . urlencode($id) . "&sk=" . $hash;
}

// 🚀 Send to RSI API
function sendToRsi($data, $jwtSecret, $map) {
    $category = $data['productCategory'];
    if (!isset($map[$category])) throw new Exception("Invalid category: $category");

    $orgId = $map[$category]['orgId'];
    $jwtPayload = $data;
    unset($jwtPayload['productCategory']);
    $jwt = generateJwt($jwtPayload, $jwtSecret);

    $url = "https://middleware.accessrsi.com/api/members/createupdate/$orgId/jwt";
    $body = http_build_query(['value' => $jwt]);

    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded",
            'content' => $body
        ]
    ];
    $context = stream_context_create($opts);
    $response = file_get_contents($url, false, $context);

    if ($response === false) {
        echo "❌ RSI API Error\n";
    } else {
        echo "✅ RSI API Response:\n$response\n";
    }
}

// 🧪 RUN
try {
    sendToRsi($input, $rsiJwtSecret, $categoryMap);
    $loginUrl = generateLoginUrl($input['id'], $input['productCategory'], $categoryMap, $rsiSecretKey);
    echo "🔗 Redirect Login URL: $loginUrl\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
