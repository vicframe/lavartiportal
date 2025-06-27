<?php
require 'vendor/autoload.php'; // Run: composer require firebase/php-jwt
use Firebase\JWT\JWT;

define('RSI_SECRET_KEY', '%#c@r#vRS022'); // For MD5 SSO hash
define('RSI_JWT_SECRET', '%#c@r#vRS022'); // For JWT

// 🎯 Org IDs and Redirect URLs
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

// 🧾 Sample input from CRM or system
$input = [
    'id' => 'M1697768',
    'firstName' => 'Abbiii',
    'lastName' => 'Allisonnnn',
    'email' => 'abbiallison20036738@gmail.com',
    'phone1' => '6417773678',
    'address1' => '414 Hamilton Sttt',
    'city' => 'Ottumwa',
    'state' => 'IA',
    'postalCode' => '52501',
    'country' => 'USA',
    'productCategory' => 'Passport Lite'
];

// 🔐 Generate JWT from member data
function generateJwtPayload(array $data): string
{
    $payload = $data;
    unset($payload['productCategory']); // remove extra field not needed in JWT
    return JWT::encode($payload, RSI_JWT_SECRET, 'HS256');
}

// 🔄 Send POST to RSI Member Create/Update
function sendToRsiApi(array $data, array $categoryMap): array
{
    $productCategory = $data['productCategory'];
    if (!isset($categoryMap[$productCategory])) {
        throw new Exception("Invalid product category: $productCategory");
    }

    $orgId = $categoryMap[$productCategory]['orgId'];
    $url = "https://middleware.accessrsi.com/api/members/createupdate/$orgId/jwt";

    $jwtPayload = generateJwtPayload($data);
    $postData = http_build_query(['value' => $jwtPayload]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
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

// 🚀 Run the whole process
try {
    $apiResult = sendToRsiApi($input, $categoryMap);

    if (in_array($apiResult['status'], [200, 201, 202, 409])) {
        echo "✅ RSI API Response:\n";
        print_r($apiResult['response']);

        $redirectUrl = generateRsiUrl($input['id'], $input['productCategory'], $categoryMap);
        echo "\n🔗 RSI Login URL: $redirectUrl\n";
    } else {
        echo "❌ API Error [{$apiResult['status']}]: ";
        print_r($apiResult['response']);
        if ($apiResult['error']) {
            echo "\nCurl Error: {$apiResult['error']}\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage();
}
