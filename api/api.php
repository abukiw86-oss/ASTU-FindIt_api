<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ini_set('log_errors', 1);
ini_set('error_log', 'api_errors.log');

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
    out(['success' => false, 'message' => $msg], $code);
}

function debug_log($msg) {
    error_log("[LOST&FOUND] " . $msg);
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

debug_log("Action: $action, Method: {$_SERVER['REQUEST_METHOD']}");

if ($action === 'register') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Use POST', 405);

    $email     = cl($input['email'] ?? '');
    $password  = $input['password'] ?? '';
    $full_name = cl($input['full_name'] ?? '');
    $phone     = cl($input['phone'] ?? '');

    if (!$email || !$password || !$full_name || !$phone) {
        err('email, password, full_name and phone are required');
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
        out(['success' => true, 'message' => 'Registered successfully'], 201);
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

    out([
        'success' => true,
        'message' => 'Logged in successfully',
        'user' => [
            'id'         => $user['id'],
            'email'      => $user['email'],
            'full_name'  => $user['full_name'],
            'phone'      => $user['phone'] ?? null,
            'role'       => $user['role']
        ]
    ]);

}
else if ($action === 'logout') {

    out(['success' => true, 'message' => 'Logged out successfully']);

}

else if ($action === 'whoami') {

    err('Not supported without authentication', 403);

}

else if ($action === 'report-item') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Use POST', 405);

    debug_log("=== REPORT ITEM START ===");
    debug_log("POST data: " . print_r($_POST, true));
    debug_log("FILES data: " . print_r($_FILES, true));
    debug_log("INPUT data: " . print_r($input, true));

    try {
        $type           = isset($_POST['type']) ? cl($_POST['type']) : (isset($input['type']) ? cl($input['type']) : '');
        $title          = isset($_POST['title']) ? cl($_POST['title']) : (isset($input['title']) ? cl($input['title']) : '');
        $description    = isset($_POST['description']) ? cl($_POST['description']) : (isset($input['description']) ? cl($input['description']) : '');
        $location       = isset($_POST['location']) ? cl($_POST['location']) : (isset($input['location']) ? cl($input['location']) : '');
        $category       = isset($_POST['category']) ? cl($_POST['category']) : (isset($input['category']) ? cl($input['category']) : 'other');
        $reporter_name  = isset($_POST['reporter_name']) ? cl($_POST['reporter_name']) : (isset($input['reporter_name']) ? cl($input['reporter_name']) : '');
        $reporter_phone = isset($_POST['reporter_phone']) ? cl($_POST['reporter_phone']) : (isset($input['reporter_phone']) ? cl($input['reporter_phone']) : '');

        debug_log("Extracted - Type: '$type', Title: '$title', Reporter: '$reporter_name', Phone: '$reporter_phone'");

        if (!in_array($type, ['lost', 'found'])) {
            debug_log("ERROR: Invalid type: '$type'");
            err("Invalid type: '$type'. Must be 'lost' or 'found'");
        }

        if (!$title || strlen(trim($title)) === 0) {
            debug_log("ERROR: Title is empty");
            err('title is required');
        }
        
        if (!$description || strlen(trim($description)) === 0) {
            debug_log("ERROR: Description is empty");
            err('description is required');
        }
        
        if (!$reporter_name || strlen(trim($reporter_name)) === 0) {
            debug_log("ERROR: Reporter name is empty");
            err('reporter_name is required');
        }
        
        if (!$reporter_phone || strlen(trim($reporter_phone)) === 0) {
            debug_log("ERROR: Reporter phone is empty");
            err('reporter_phone is required');
        }

        // ─── Image upload handling ───────────────────────────────────────
        $image_path = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            debug_log("Processing image upload");
            
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    debug_log("ERROR: Failed to create upload directory");
                    err('Failed to create upload directory', 500);
                }
            }
            if (!is_writable($upload_dir)) {
                debug_log("ERROR: Upload directory not writable");
                err('Upload directory not writable', 500);
            }

            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($ext, $allowed)) {
                debug_log("ERROR: Invalid file extension: $ext");
                err('Only jpg, jpeg, png, gif allowed');
            }
            
            if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                debug_log("ERROR: File too large: " . $_FILES['image']['size']);
                err('Image too large (max 5MB)');
            }

            $filename = 'item_' . time() . '_' . uniqid() . '.' . $ext;
            $target = $upload_dir . $filename;

            debug_log("Attempting to move uploaded file to: $target");

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $image_path = $target;
                debug_log("Image uploaded successfully: $target");
            } else {
                debug_log("ERROR: Failed to move uploaded file. Error: " . $_FILES['image']['error']);
                err('Failed to upload image', 500);
            }
        }
        $image_path_sql = $image_path ? "'$image_path'" : 'NULL';
        
        $sql = "INSERT INTO items 
                (type, title, description, location, category, image_path, 
                 reporter_name, reporter_phone, status, created_at)
                VALUES 
                ('$type', '$title', '$description', '$location', '$category', 
                 $image_path_sql, '$reporter_name', '$reporter_phone', 'open', NOW())";

        debug_log("SQL: $sql");

        if (mysqli_query($conn, $sql)) {
            $new_id = mysqli_insert_id($conn);
            debug_log("Item saved successfully with ID: $new_id");
            out([
                'success' => true,
                'message' => 'Item reported successfully',
                'id' => $new_id
            ], 201);
        } else {
            debug_log("Database error: " . mysqli_error($conn));
            err('Database error: ' . mysqli_error($conn), 500);
        }
    } catch (Exception $e) {
        debug_log("EXCEPTION: " . $e->getMessage());
        err('Server error: ' . $e->getMessage(), 500);
    }

}
else if ($action === 'list-items') {

    $type_filter = '';
    $type = cl($_GET['type'] ?? '');
    if ($type === 'lost' || $type === 'found') {
        $type_filter = " AND type = '$type'";
    }

    $sql = "SELECT id, type, title, description, location, category, image_path,
                   reporter_name, reporter_phone, status, created_at 
            FROM items 
            WHERE status = 'open' $type_filter 
            ORDER BY created_at DESC 
            LIMIT 30";

    $result = mysqli_query($conn, $sql);
    $items = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }

    out(['success' => true, 'items' => $items]);

}

else {
    err('Unknown action', 404);
}

mysqli_close($conn);
?>