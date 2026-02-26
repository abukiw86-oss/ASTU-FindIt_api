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

debug_log("Main router - Action: $action, Method: {$_SERVER['REQUEST_METHOD']}");

if (strpos($action, 'admin-') === 0) {
    include 'admin.php';
} 
elseif (in_array($action, ['get-profile', 'update-profile', 'change-password', 'get-user-history', 'get-user-requests', 'get-notifications', 'mark-notification-read', 'mark-all-notifications-read'])) {
    include 'profile.php';
}
elseif (in_array($action, ['register', 'login', 'logout', 'whoami'])) {
    include 'auth.php';
}
else {
    include 'items.php';
}

mysqli_close($conn);
?>