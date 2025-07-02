<?php
// File: api/upgrade-package.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$user = get_current_logged_user();
$userId = $user['id'];
$fromProduct = $data['currentProductId'] ?? null;
$toProductId = $data['toProductId'] ?? null;

if (!$fromProduct || !$toProductId) {
    echo json_encode(['status' => 'error', 'message' => 'Missing product selection.']);
    exit;
}

// $toProduct = $toProductId;
$toProduct = $toProductId;
if (!$toProduct) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid target product.']);
    exit;
}

$upgradeMatrix = [
    '803' => [
        '793' => ['packageId' => 1477, 'currentOrgId' => 803],
        '826' => ['packageId' => 1677, 'currentOrgId' => 803]
    ],
    '793' => [
        '803' => ['packageId' => 1621, 'currentOrgId' => 793],
        '826' => ['packageId' => 1677, 'currentOrgId' => 793]
    ],
    '826' => [
        '793' => ['packageId' => 1477, 'currentOrgId' => 826],
        '803' => ['packageId' => 1621, 'currentOrgId' => 826]
    ]
];

$authConfig = [
    'client_id' => 'levarti',
    'client_secret' => '!l@cebVTk#',
    'username' => 'levarti@login.com',
    'password' => '$b@e001$!!#'
];

try {
    $upgradeData = $upgradeMatrix[$fromProduct][$toProduct] ?? null;
    if (!$upgradeData) throw new Exception("Invalid upgrade path from $fromProduct to $toProduct.");

    // Step 1: Get Bearer Token using cURL
    $postFields = http_build_query([
        'grant_type' => 'password',
        'client_id' => $authConfig['client_id'],
        'client_secret' => $authConfig['client_secret'],
        'username' => $authConfig['username'],
        'password' => $authConfig['password'],
    ]);

    $ch = curl_init('https://authorize.accessrsi.com/connect/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $tokenResult = curl_exec($ch);
    curl_close($ch);

    $tokenData = json_decode($tokenResult, true);
    if (!isset($tokenData['access_token'])) {
        throw new Exception("Failed to retrieve access token.");
    }
    $accessToken = $tokenData['access_token'];

    // Step 2: Send upgrade request using cURL
    $upgradeUrl = "https://svc.accessrsi.com/membermanagerapi/memberchangepackage/{$upgradeData['currentOrgId']}";
    $upgradePayload = json_encode([
        'id' => $userId,
        'packageId' => $upgradeData['packageId']
    ]);

    $ch = curl_init($upgradeUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $upgradePayload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json',
        'Content-Length: ' . strlen($upgradePayload)
    ]);
    $upgradeResult = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception("Upgrade failed: HTTP $httpCode. Response: $upgradeResult");
    }
    db_query(
        "UPDATE users SET tier_id = ?, package_id = ? WHERE id = ?",
        [$toProductId, $toProductId, $userId]
    );
    echo json_encode(['status' => 'success', 'message' => 'Package upgraded successfully.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// function get_product_by_id($id)
// {
//     global $pdo;
//     $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
//     $stmt->execute([$id]);
//     return $stmt->fetch(PDO::FETCH_ASSOC);
// }
