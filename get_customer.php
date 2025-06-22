<?php

$baseUrl = 'https://api.pillarshub.com/api/v1'; // Replace with your actual base URL
$customerId = 'AmIOw1Nj8B1NewLhlBQT'; // Replace with actual customer ID
$token = 'ZFge8sWV3T8JLB0sH9N5oQg89IJl40pjSLcx7Zhsu2mv'; // Replace with actual token

$url = $baseUrl . '/Customers/' . $customerId;

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo '❌ cURL Error: ' . curl_error($ch);
} elseif ($httpCode >= 200 && $httpCode < 300) {
    echo "✅ Customer retrieved successfully:\n";
    echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT);
} else {
    echo "❌ Error fetching customer (HTTP $httpCode):\n";
    echo $response;
}

curl_close($ch);