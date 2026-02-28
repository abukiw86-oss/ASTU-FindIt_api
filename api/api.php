<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
include 'db.php';
include 'helpers.php';

$action = $_GET['action'] ?? '';

$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) {
        $input = $json;
    }
}


if (strpos($action, 'admin-') === 0) {
    include 'admin.php';
    exit;
}
elseif (in_array($action, ['get-profile', 'update-profile', 'get-user-history', 'get-user-requests'])) {
    include 'profile.php';
}
elseif (in_array($action, ['register', 'login', 'logout'])) {
    include 'auth.php';
}
elseif (in_array($action, ['get-notifications','mark-notification-read','mark-all-notifications-read','get-notification-details','admin-send-message','get-admin-messages','reply-to-admin'])) {
    include 'notify.php';
    exit;
}
else if (in_array($action, ['simple-search', 'simple-item-details'])) {
    include 'search.php';
    exit;
}
else {
    include 'items.php';
}

mysqli_close($conn);
?>