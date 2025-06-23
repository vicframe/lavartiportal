<?php

/**
 * Dashboard Home
 */
echo "✅ You hit rsi_form.php<br>";
print_r($_POST);
exit;
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

function getRsiRedirectInfo($order) {
    $rsi_secret = '%#c@r#vRS022';
    $uid = $order['user_id'] ?? null;
    $sku = $order['item_sku'] ?? null;

    if (!$uid || !$sku) {
        return null;
    }
 
    // Determine correct RSI environment
    if (str_starts_with($sku, 'PASSLITE-')) {
        $orgId = 803;
        $label = 'Passport Lite';
        $baseUrl = 'https://passportlite.thedash.life/index.php';
    } elseif (str_starts_with($sku, 'PASS-')) {
        $orgId = 793;
        $label = 'Passport (Dashlife)';
        $baseUrl = 'https://sso.thedash.life/index.php';
    } elseif (str_starts_with($sku, 'PASSTA-')) {
        $orgId = 826;
        $label = 'Passport Travel Agent';
        $baseUrl = 'https://travelagent.thedash.life/index.php';
    } else {
        return null; // Invalid SKU
    }

    // Generate secure login token
    $sk = md5($rsi_secret . $uid);
    $loginUrl = $baseUrl . '?uid=' . urlencode($uid) . '&sk=' . $sk;
    // header("Location: " . 'https://sso.thedash.life/index.php');
    header("Location: " . $baseUrl);
    exit;
    return [
        'product' => $label,
        'org_id' => $orgId,
        'url' => $loginUrl
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = trim($_POST['user_id'] ?? '');
    $item_sku = trim($_POST['item_sku'] ?? '');

    if (empty($user_id) || empty($item_sku)) {
        echo "❌ Missing user_id or item_sku.";
        exit;
    }

    $order = [
        'user_id' => $user_id,
        'item_sku' => $item_sku
    ];

    $redirectData = getRsiRedirectInfo($order);
    if ($redirectData && isset($redirectData['url'])) {
        // Uncomment this line in production:
        header("Location: " . $redirectData['url']);
        exit;

        // For debugging only (comment out above redirect to test this)
        // echo "✅ Redirecting to: <a href='{$redirectData['url']}'>{$redirectData['url']}</a>";
    } else {
        echo "❌ Invalid SKU or user not found in RSI.";
    }
} else {
    echo "❌ Invalid request method.";
}
