<?php

// 🔐 RSI API Auth Config
define('RSI_CLIENT_ID', 'levarti');
define('RSI_CLIENT_SECRET', '!l@cebVTk#');
define('RSI_USERNAME', 'levarti@login.com');
define('RSI_PASSWORD', '$b@e001$!!#');

// 🧠 Upgrade Matrix
$upgradeMatrix = [
    'Passport Lite' => [
        'to' => [
            'Passport' => ['packageId' => 1477, 'currentOrgId' => 803],
            'Travel Agent' => ['packageId' => 1677, 'currentOrgId' => 803],
        ]
    ],
    'Passport' => [
        'to' => [
            'Passport Lite' => ['packageId' => 1621, 'currentOrgId' => 793],
            'Travel Agent' => ['packageId' => 1677, 'currentOrgId' => 793],
        ]
    ],
    'Travel Agent' => [
        'to' => [
            'Passport' => ['packageId' => 1477, 'currentOrgId' => 826],
            'Passport Lite' => ['packageId' => 1621, 'currentOrgId' => 826],
        ]
    ]
];

// 🔄 Function: Get access token
function getAccessToken() {
    $url = 'https://authorize.accessrsi.com/connect/token';
    $postData = http_build_query([
        'client_id' => RSI_CLIENT_ID,
        'client_secret' => RSI_CLIENT_SECRET,
        'grant_type' => 'password',
        'username' => RSI_USERNAME,
        'password' => RSI_PASSWORD
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) throw new Exception("Token Error: $error");

    $data = json_decode($response, true);
    return $data['access_token'] ?? throw new Exception("Access token missing from response.");
}

// 🚀 Function: Perform upgrade
function upgradePackage($input, $upgradeMatrix) {
    $from = $input['fromProduct'];
    $to = $input['toProduct'];
    $memberId = $input['id'];

    if (!isset($upgradeMatrix[$from]['to'][$to])) {
        throw new Exception("Invalid upgrade path from $from to $to.");
    }

    $upgradeData = $upgradeMatrix[$from]['to'][$to];
    $accessToken = getAccessToken();

    $upgradeUrl = "https://svc.accessrsi.com/membermanagerapi/memberchangepackage/{$upgradeData['currentOrgId']}";
    $payload = json_encode([
        'id' => $memberId,
        'packageId' => $upgradeData['packageId']
    ]);

    $ch = curl_init($upgradeUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'status' => $httpCode,
        'response' => json_decode($response, true),
        'error' => $error
    ];
}

// 🧾 Input: Accept via POST or set manually
/*$input = [
    'id' => $_POST['id'] ?? 'M1697768',                 // RSI member ID
    'fromProduct' => $_POST['fromProduct'] ?? 'Passport Lite',
    'toProduct' => $_POST['toProduct'] ?? 'Travel Agent'
];
*/

$input = [
    'id' => $_POST['user_id'] ?? null,
    'fromProduct' => $_POST['from_product'] ?? null,
    'toProduct' => $_POST['to_product'] ?? null
];
if (!$input['id'] || !$input['fromProduct'] || !$input['toProduct']) {
    echo "❌ Missing input data.";
    exit;
}


try {
    $result = upgradePackage($input, $upgradeMatrix);

    if (in_array($result['status'], [200, 202])) {
        echo "✅ Upgrade Success:<br>";
        print_r($result['response']);
    } else {
        echo "❌ Upgrade Failed [{$result['status']}]:<br>";
        print_r($result['response']);
        if ($result['error']) echo "<br>Curl Error: {$result['error']}";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage();
}
