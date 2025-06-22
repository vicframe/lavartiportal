<?php
/**
 * Update Account Details API
 * 
 * Allows a logged-in user to update profile info, password, or notification preferences.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json');

// Ensure user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$user = get_current_logged_user();
$user_id = (int) $user['id'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_profile':
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $src_url = $_POST['src_url'] ?? '';

            if ($first_name === '' || $last_name === '') {
                throw new Exception('First name and last name are required.');
            }

            db_query("UPDATE users SET first_name = ?, last_name = ?, phone = ?, src_url = ? WHERE id = ?", [
                $first_name, $last_name, $phone, $src_url, $user_id
            ]);

            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
            break;

        case 'update_password':
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';

            if (strlen($new_password) < 6) {
                throw new Exception('New password must be at least 6 characters long.');
            }

            if (!password_verify($current_password, $user['password'])) {
                throw new Exception('Current password is incorrect.');
            }

            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            db_query("UPDATE users SET password = ? WHERE id = ?", [
                $hashedPassword, $user_id
            ]);

            echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
            break;

        case 'update_notifications':
            $email_news = isset($_POST['email_news']) ? 1 : 0;
            $email_commission = isset($_POST['email_commission']) ? 1 : 0;
            $email_team = isset($_POST['email_team']) ? 1 : 0;
            $sms_commission = isset($_POST['sms_commission']) ? 1 : 0;

            db_query("UPDATE users SET 
                email_news = ?, 
                email_commission = ?, 
                email_team = ?, 
                sms_commission = ?
                WHERE id = ?", [
                $email_news, $email_commission, $email_team, $sms_commission, $user_id
            ]);

            echo json_encode(['success' => true, 'message' => 'Notification settings updated']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
