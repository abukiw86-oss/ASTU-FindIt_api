<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');           
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
include 'db.php'; 

function cl($v) {
    global $conn;
    return mysqli_real_escape_string($conn, trim(strip_tags($v ?? '')));
}

function out($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function err($msg, $code = 400) {
    out(['error' => $msg], $code);
}

function is_logged_in() {
    return !empty($_SESSION['uid']);
}

$action = $_GET['action'] ?? '';

$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) {
        $input = $json;
    }
}
if ($action === 'register') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Use POST', 405);

    $email     = cl($input['email'] ?? '');
    $password  = $input['password'] ?? '';
    $full_name = cl($input['full_name'] ?? '');
    $phone     = cl($input['phone'] ?? '');

    if (!$email || !$password || !$full_name) {
        err('email, password, full_name required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        err('Invalid email format');
    }

    if (strlen($password) < 6) {
        err('Password must be at least 6 characters');
    }

    $r = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($r) > 0) {
        err('Email already registered', 409);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (email, password_hash, full_name, phone, role, created_at)
            VALUES ('$email', '$hash', '$full_name', '$phone', 'student', NOW())";

    if (mysqli_query($conn, $sql)) {
        out(['message' => 'Registered successfully'], 201);
    } else {
        err('Database error: ' . mysqli_error($conn), 500);
    }

}

else if ($action === 'login') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Use POST', 405);

    $email    = cl($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (!$email || !$password) err('email and password required');

    $r = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
    $user = mysqli_fetch_assoc($r);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        err('Incorrect email or password', 401);
    }

    session_regenerate_id(true);
    $_SESSION['uid']   = $user['id'];
    $_SESSION['role']  = $user['role'];
    $_SESSION['name']  = $user['full_name'];

    out([
        'message' => 'Logged in successfully',
        'user' => [
            'id'   => $user['id'],
            'name' => $user['full_name'],
            'role' => $user['role']
        ]
    ]);

}
else if ($action === 'logout') {

    $_SESSION = [];
    session_destroy();
    out(['message' => 'Logged out successfully']);

}
else if ($action === 'whoami') {

    if (!is_logged_in()) {
        err('Not logged in', 401);
    }

    out([
        'logged_in' => true,
        'id'   => $_SESSION['uid'],
        'name' => $_SESSION['name'],
        'role' => $_SESSION['role']
    ]);

}
else if ($action === 'report-item') {

    if (!is_logged_in()) err('Please login first', 401);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Use POST', 405);

    $type        = cl($_POST['type'] ?? $input['type'] ?? '');
    $title       = cl($_POST['title'] ?? $input['title'] ?? '');
    $description = cl($_POST['description'] ?? $input['description'] ?? '');
    $location    = cl($_POST['location'] ?? $input['location'] ?? '');
    $category    = cl($_POST['category'] ?? $input['category'] ?? 'other');

    if (!in_array($type, ['lost', 'found'])) err('type must be "lost" or "found"');
    if (!$title || !$description) err('title and description are required');

    // ─── Image upload handling ───────────────────────────────────────
    $image_path = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $allowed)) {
            err('Only jpg, jpeg, png, gif allowed');
        }

        if ($_FILES['image']['size'] > 5 * 1024 * 1024) { // 5 MB limit
            err('Image file too large (max 5MB)');
        }

        $filename = 'item_' . time() . '_' . uniqid() . '.' . $ext;
        $target = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image_path = $target; 
        } else {
            err('Failed to upload image', 500);
        }
    }

    $uid = $_SESSION['uid'];

    $sql = "INSERT INTO items 
            (user_id, type, title, description, location, category, image_path, status, created_at)
            VALUES 
            ($uid, '$type', '$title', '$description', '$location', '$category', " .
            ($image_path ? "'$image_path'" : 'NULL') . ", 'open', NOW())";

    if (mysqli_query($conn, $sql)) {
        $new_id = mysqli_insert_id($conn);
        out([
            'message' => 'Item reported successfully',
            'id' => $new_id
        ]);
    } else {
        err('Database error: ' . mysqli_error($conn), 500);
    }

}
else if ($action === 'list-items') {

    $type_filter = '';
    $type = cl($_GET['type'] ?? '');
    if ($type === 'lost' || $type === 'found') {
        $type_filter = " AND type = '$type'";
    }

    $sql = "SELECT id, type, title, description, location, category, image_path, status, created_at 
            FROM items 
            WHERE status = 'open' $type_filter 
            ORDER BY created_at DESC 
            LIMIT 30";

    $result = mysqli_query($conn, $sql);
    $items = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }

    out(['items' => $items]);

}
else {
    err('Unknown action', 404);
}

mysqli_close($conn);
