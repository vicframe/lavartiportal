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
