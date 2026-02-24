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

// ────────────────────────────────────────────────
else if ($action === 'report-item') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Use POST', 405);

    $type           = isset($_POST['type']) ? cl($_POST['type']) : '';
    $title          = isset($_POST['title']) ? cl($_POST['title']) : '';
    $description    = isset($_POST['description']) ? cl($_POST['description']) : '';
    $location       = isset($_POST['location']) ? cl($_POST['location']) : '';
    $category       = isset($_POST['category']) ? cl($_POST['category']) : 'other';
    $reporter_name  = isset($_POST['reporter_name']) ? cl($_POST['reporter_name']) : '';
    $reporter_phone = isset($_POST['reporter_phone']) ? cl($_POST['reporter_phone']) : '';
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

    if (!in_array($type, ['lost', 'found'])) {
        err("Invalid type: '$type'. Must be 'lost' or 'found'");
    }

    if (!$title || !$description) err('title and description are required');
    if (!$reporter_name || !$reporter_phone) err('reporter_name and reporter_phone are required');

    // For found items, image is required
    if ($type === 'found' && (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK)) {
        err('Image is required for found items');
    }

    $image_path = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $allowed)) err('Only jpg, jpeg, png, gif allowed');
        if ($_FILES['image']['size'] > 5 * 1024 * 1024) err('Image too large (max 5MB)');

        $filename = 'item_' . time() . '_' . uniqid() . '.' . $ext;
        $target = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image_path = $target;
        } else {
            err('Failed to upload image', 500);
        }
    }

    $user_id_sql = $user_id > 0 ? $user_id : 'NULL';
    
    $sql = "INSERT INTO items 
            (type, title, description, location, category, image_path, 
             reporter_name, reporter_phone, user_id, status, created_at)
            VALUES 
            ('$type', '$title', '$description', '$location', '$category', " .
            ($image_path ? "'$image_path'" : 'NULL') . ", 
            '$reporter_name', '$reporter_phone', $user_id_sql, 'pending', NOW())";

    if (mysqli_query($conn, $sql)) {
        $new_id = mysqli_insert_id($conn);
        out([
            'success' => true,
            'message' => 'Item reported successfully',
            'id' => $new_id
        ], 201);
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
else if ($action === 'admin-pending-items') {
    
    header('Content-Type: application/json; charset=utf-8');
    
    $admin_email = isset($_GET['admin_email']) ? $_GET['admin_email'] : '';
    if (empty($admin_email)) {
        echo json_encode(['success' => false, 'message' => 'Admin email required']);
        exit;
    }
    
    $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE email = '$admin_email'");
    if (!$check_admin || mysqli_num_rows($check_admin) == 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $admin = mysqli_fetch_assoc($check_admin);
    if ($admin['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin access only']);
        exit;
    }
    
    $sql = "SELECT * FROM items WHERE status = 'pending' ORDER BY created_at DESC";
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        exit;
    }
    
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
    
    echo json_encode(['success' => true, 'items' => $items]);
    exit;
}
else if ($action === 'admin-review-item') {
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    
    header('Content-Type: application/json; charset=utf-8');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $admin_email = $input['admin_email'] ?? '';
    $item_id = intval($input['item_id'] ?? 0);
    $review_action = $input['review_action'] ?? '';
    $admin_notes = mysqli_real_escape_string($conn, $input['admin_notes'] ?? '');
    
    if (empty($admin_email) || !$item_id || !$review_action) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE email = '$admin_email'");
    if (!$check_admin || mysqli_num_rows($check_admin) == 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $admin = mysqli_fetch_assoc($check_admin);
    if ($admin['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin access only']);
        exit;
    }
    
    $new_status = ($review_action === 'approve') ? 'open' : 'rejected';
    
    $sql = "UPDATE items SET status = '$new_status', admin_notes = '$admin_notes' WHERE id = $item_id";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Item ' . $review_action . 'd successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit;
}

else if ($action === 'admin-get-claims') {
    
    header('Content-Type: application/json; charset=utf-8');
    
    $admin_email = isset($_GET['admin_email']) ? $_GET['admin_email'] : '';
    if (empty($admin_email)) {
        echo json_encode(['success' => false, 'message' => 'Admin email required']);
        exit;
    }
    
    $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE email = '$admin_email'");
    if (!$check_admin || mysqli_num_rows($check_admin) == 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $admin = mysqli_fetch_assoc($check_admin);
    if ($admin['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin access only']);
        exit;
    }
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'item_claims'");
    if (mysqli_num_rows($table_check) == 0) {
        echo json_encode(['success' => true, 'claims' => []]);
        exit;
    }
    $sql = "SELECT c.*, 
            i.title as item_title, i.type as item_type, i.description as item_description,
            u.full_name as claimant_name, u.email as claimant_email, u.phone as claimant_phone
            FROM item_claims c
            LEFT JOIN items i ON c.item_id = i.id
            LEFT JOIN users u ON c.user_id = u.id
            ORDER BY c.created_at DESC";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        exit;
    }
    
    $claims = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $claims[] = $row;
    }
    
    echo json_encode(['success' => true, 'claims' => $claims]);
    exit;
}

else if ($action === 'admin-review-claim') {
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    
    header('Content-Type: application/json; charset=utf-8');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $admin_email = $input['admin_email'] ?? '';
    $claim_id = intval($input['claim_id'] ?? 0);
    $claim_action = $input['claim_action'] ?? '';
    $admin_notes = mysqli_real_escape_string($conn, $input['admin_notes'] ?? '');
    
    if (empty($admin_email) || !$claim_id || !$claim_action) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE email = '$admin_email'");
    if (!$check_admin || mysqli_num_rows($check_admin) == 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $admin = mysqli_fetch_assoc($check_admin);
    if ($admin['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin access only']);
        exit;
    }
    $new_status = ($claim_action === 'approve') ? 'approved' : 'rejected';
    
    $sql = "UPDATE item_claims SET status = '$new_status', admin_notes = '$admin_notes', reviewed_at = NOW() WHERE id = $claim_id";
    
    if (mysqli_query($conn, $sql)) {
        if ($claim_action === 'approve') {
            $get_item = mysqli_query($conn, "SELECT item_id FROM item_claims WHERE id = $claim_id");
            if ($get_item && mysqli_num_rows($get_item) > 0) {
                $claim_data = mysqli_fetch_assoc($get_item);
                $item_id = $claim_data['item_id'];
                mysqli_query($conn, "UPDATE items SET status = 'claimed' WHERE id = $item_id");
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Claim ' . $claim_action . 'd successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit;
}
else if ($action === 'admin-get-matches') {
    
    header('Content-Type: application/json; charset=utf-8');
    
    $admin_email = isset($_GET['admin_email']) ? $_GET['admin_email'] : '';
    if (empty($admin_email)) {
        echo json_encode(['success' => false, 'message' => 'Admin email required']);
        exit;
    }
    
    $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE email = '$admin_email'");
    if (!$check_admin || mysqli_num_rows($check_admin) == 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $admin = mysqli_fetch_assoc($check_admin);
    if ($admin['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin access only']);
        exit;
    }
    
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'matches'");
    if (mysqli_num_rows($table_check) == 0) {
        echo json_encode(['success' => true, 'matches' => []]);
        exit;
    }
    
    $sql = "SELECT m.*, 
            l.title as lost_title, l.reporter_name as lost_reporter, l.reporter_phone as lost_phone,
            f.title as found_title, f.reporter_name as found_reporter, f.reporter_phone as found_phone
            FROM matches m
            LEFT JOIN items l ON m.lost_item_id = l.id
            LEFT JOIN items f ON m.found_item_id = f.id
            ORDER BY m.created_at DESC";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        exit;
    }
    
    $matches = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $matches[] = $row;
    }
    
    echo json_encode(['success' => true, 'matches' => $matches]);
    exit;
}

else if ($action === 'admin-update-match') {
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    
    header('Content-Type: application/json; charset=utf-8');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $admin_email = $input['admin_email'] ?? '';
    $match_id = intval($input['match_id'] ?? 0);
    $status = $input['status'] ?? '';
    
    if (empty($admin_email) || !$match_id || !$status) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Verify admin role
    $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE email = '$admin_email'");
    if (!$check_admin || mysqli_num_rows($check_admin) == 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $admin = mysqli_fetch_assoc($check_admin);
    if ($admin['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin access only']);
        exit;
    }
    
    $sql = "UPDATE matches SET status = '$status' WHERE id = $match_id";
    
    if (mysqli_query($conn, $sql)) {
        if ($status === 'confirmed') {
            $get_items = mysqli_query($conn, "SELECT lost_item_id, found_item_id FROM matches WHERE id = $match_id");
            if ($get_items && mysqli_num_rows($get_items) > 0) {
                $items = mysqli_fetch_assoc($get_items);
                mysqli_query($conn, "UPDATE items SET status = 'resolved' WHERE id IN ({$items['lost_item_id']}, {$items['found_item_id']})");
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Match ' . $status]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit;
}
else if ($action === 'admin-get-stats') {
    
    header('Content-Type: application/json; charset=utf-8');
    
    $admin_email = isset($_GET['admin_email']) ? $_GET['admin_email'] : '';
    if (empty($admin_email)) {
        echo json_encode(['success' => false, 'message' => 'Admin email required']);
        exit;
    }
    
    $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE email = '$admin_email'");
    if (!$check_admin || mysqli_num_rows($check_admin) == 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $admin = mysqli_fetch_assoc($check_admin);
    if ($admin['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin access only']);
        exit;
    }
    
    $stats = [];
    
    $pending = mysqli_query($conn, "SELECT COUNT(*) as count FROM items WHERE status = 'pending'");
    $stats['pending_items'] = $pending ? mysqli_fetch_assoc($pending)['count'] : 0;
    
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'item_claims'");
    if (mysqli_num_rows($table_check) > 0) {
        $claims = mysqli_query($conn, "SELECT COUNT(*) as count FROM item_claims WHERE status = 'pending'");
        $stats['pending_claims'] = $claims ? mysqli_fetch_assoc($claims)['count'] : 0;
    } else {
        $stats['pending_claims'] = 0;
    }
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'matches'");
    if (mysqli_num_rows($table_check) > 0) {
        $matches = mysqli_query($conn, "SELECT COUNT(*) as count FROM matches WHERE status = 'pending'");
        $stats['pending_matches'] = $matches ? mysqli_fetch_assoc($matches)['count'] : 0;
    } else {
        $stats['pending_matches'] = 0;
    }
    
    $resolved = mysqli_query($conn, "SELECT COUNT(*) as count FROM items WHERE status = 'resolved'");
    $stats['resolved_items'] = $resolved ? mysqli_fetch_assoc($resolved)['count'] : 0;
    
    echo json_encode(['success' => true, 'stats' => $stats]);
    exit;
}
else if ($action === 'request-item') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Use POST', 405);

    $input = json_decode(file_get_contents('php://input'), true);
    
    $user_id = intval($input['user_id'] ?? 0);
    $item_id = intval($input['item_id'] ?? 0);
    $message = $conn->real_escape_string($input['message'] ?? '');
    $proof_description = $conn->real_escape_string($input['proof_description'] ?? '');

    if (!$user_id || !$item_id || !$message) {
        err('User ID, item ID and message are required');
    }

    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'item_claims'");
    if (mysqli_num_rows($table_check) == 0) {
        // Create the table if it doesn't exist
        $create_table = "CREATE TABLE IF NOT EXISTS item_claims (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            user_id INT NOT NULL,
            message TEXT,
            proof_description TEXT,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            admin_notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            reviewed_at TIMESTAMP NULL,
            INDEX idx_item (item_id),
            INDEX idx_user (user_id),
            INDEX idx_status (status)
        )";
        
        if (!mysqli_query($conn, $create_table)) {
            err('Failed to create claims table: ' . mysqli_error($conn), 500);
        }
    }

    $check = mysqli_query($conn, "SELECT id FROM item_claims WHERE item_id = $item_id AND user_id = $user_id");
    if (mysqli_num_rows($check) > 0) {
        err('You have already requested this item', 409);
    }

    $sql = "INSERT INTO item_claims (item_id, user_id, message, proof_description, status, created_at)
            VALUES ($item_id, $user_id, '$message', '$proof_description', 'pending', NOW())";

    if (mysqli_query($conn, $sql)) {
        out(['success' => true, 'message' => 'Claim request submitted successfully']);
    } else {
        err('Database error: ' . mysqli_error($conn), 500);
    }
}

else if ($action === 'get-found-items') {

    $user_id = intval($_GET['user_id'] ?? 0);
    
    $sql = "SELECT f.*, 
            CASE 
                WHEN f.status = 'claimed' THEN 'claimed'
                WHEN c.id IS NOT NULL AND c.status = 'approved' THEN 'accessible'
                WHEN c.id IS NOT NULL AND c.status = 'pending' THEN 'pending'
                ELSE 'restricted'
            END as access_level,
            c.status as claim_status,
            c.id as claim_id
            FROM items f
            LEFT JOIN item_claims c ON f.id = c.item_id AND c.user_id = $user_id
            WHERE f.type = 'found' 
            ORDER BY f.created_at DESC";

    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        err('Database error: ' . mysqli_error($conn), 500);
    }
    
    $items = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $is_founder = false;
        
        if (isset($row['user_id']) && $row['user_id'] == $user_id) {
            $is_founder = true;
        }
        if ($row['access_level'] == 'restricted' && !$is_founder) {
            $row['description'] = '[HIDDEN - Request access to view details]';
            $row['image_path'] = null; 
            $row['reporter_name'] = 'Hidden';
            $row['reporter_phone'] = 'Hidden';
            $row['is_restricted'] = true;
        } else {
            $row['is_restricted'] = false;
            if ($is_founder) {
                $row['access_level'] = 'accessible'; 
            }
        }
        
        $row['is_founder'] = $is_founder;
        
        $items[] = $row;
    }

    out(['success' => true, 'items' => $items]);
}

else if ($action === 'get-lost-items') {

    $sql = "SELECT * FROM items 
            WHERE type = 'lost' AND status = 'open'
            ORDER BY created_at DESC";

    $result = mysqli_query($conn, $sql);
    $items = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }

    out(['success' => true, 'items' => $items]);
}
else if ($action === 'admin-get-claims') {

    $is_admin = false;
    if (isset($_GET['admin_key']) && $_GET['admin_key'] === 'your-secret-admin-key') {
        $is_admin = true;
    }

    if (!$is_admin) {
        err('Unauthorized', 401);
    }

    $sql = "SELECT c.*, 
            i.title as item_title, i.type as item_type, i.description as item_description,
            u.full_name as claimant_name, u.email as claimant_email, u.phone as claimant_phone
            FROM item_claims c
            JOIN items i ON c.item_id = i.id
            JOIN users u ON c.user_id = u.id
            ORDER BY c.created_at DESC";

    $result = mysqli_query($conn, $sql);
    $claims = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $claims[] = $row;
    }

    out(['success' => true, 'claims' => $claims]);
}

else if ($action === 'admin-review-claim') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Use POST', 405);

    $is_admin = false;
    if (isset($input['admin_key']) && $input['admin_key'] === 'your-secret-admin-key') {
        $is_admin = true;
    }

    if (!$is_admin) {
        err('Unauthorized', 401);
    }

    $claim_id = intval($input['claim_id'] ?? 0);
    $action = $input['claim_action'] ?? ''; 
    $admin_notes = cl($input['admin_notes'] ?? '');

    if (!$claim_id || !in_array($action, ['approve', 'reject'])) {
        err('Invalid request');
    }

    $new_status = ($action === 'approve') ? 'approved' : 'rejected';

    mysqli_query($conn, "UPDATE item_claims SET status = '$new_status', admin_notes = '$admin_notes', reviewed_at = NOW() WHERE id = $claim_id");

    if ($action === 'approve') {
        $claim_result = mysqli_query($conn, "SELECT item_id, user_id FROM item_claims WHERE id = $claim_id");
        $claim = mysqli_fetch_assoc($claim_result);
        
        mysqli_query($conn, "UPDATE items SET status = 'claimed' WHERE id = {$claim['item_id']}");
    }

    out(['success' => true, 'message' => 'Claim ' . $action . 'd successfully']);
}
else if ($action === 'report-found-match') {

    if (ob_get_level()) ob_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Use POST']);
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }

    error_log("Report found match input: " . print_r($input, true));

    $lost_item_id = intval($input['lost_item_id'] ?? 0);
    $finder_name = trim($input['finder_name'] ?? '');
    $finder_phone = trim($input['finder_phone'] ?? '');
    $finder_message = trim($input['finder_message'] ?? '');
    $user_id = intval($input['user_id'] ?? 0);

    if (!$lost_item_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Lost item ID is required']);
        exit;
    }

    if (empty($finder_name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Finder name is required']);
        exit;
    }

    if (empty($finder_phone)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Finder phone is required']);
        exit;
    }
    $lost_item_query = mysqli_query($conn, "SELECT * FROM items WHERE id = $lost_item_id AND type = 'lost'");
    if (!$lost_item_query) {
        error_log("Database error: " . mysqli_error($conn));
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error checking lost item']);
        exit;
    }

    $lost = mysqli_fetch_assoc($lost_item_query);
    if (!$lost) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Lost item not found']);
        exit;
    }

    $column_check = mysqli_query($conn, "SHOW COLUMNS FROM items LIKE 'lost_item_id'");
    if (mysqli_num_rows($column_check) == 0) {
        $alter_table = "ALTER TABLE items ADD COLUMN lost_item_id INT DEFAULT NULL AFTER user_id";
        if (!mysqli_query($conn, $alter_table)) {
            error_log("Failed to add column: " . mysqli_error($conn));
        }
    }
    $sql = "INSERT INTO items 
            (type, title, description, location, category, 
             reporter_name, reporter_phone, status, lost_item_id, user_id, created_at)
            VALUES 
            ('found', ?, ?, ?, ?, ?, ?, 'pending_match', ?, ?, NOW())";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("Prepare failed: " . mysqli_error($conn));
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database prepare error']);
        exit;
    }

    $title = "Found: " . $lost['title'];
    $location = $lost['location'] ?? 'Unknown';
    $category = $lost['category'] ?? 'other';

    mysqli_stmt_bind_param(
        $stmt, 
        "sssssiii", 
        $title,
        $finder_message,
        $location,
        $category,
        $finder_name,
        $finder_phone,
        $lost_item_id,
        $user_id
    );

    if (mysqli_stmt_execute($stmt)) {
        $new_id = mysqli_insert_id($conn);
        $match_table_check = mysqli_query($conn, "SHOW TABLES LIKE 'matches'");
        if (mysqli_num_rows($match_table_check) > 0) {
            $match_sql = "INSERT INTO matches (lost_item_id, found_item_id, match_confidence, status, created_by, created_at)
                         VALUES (?, ?, 90, 'pending', ?, NOW())";
            $match_stmt = mysqli_prepare($conn, $match_sql);
            if ($match_stmt) {
                mysqli_stmt_bind_param($match_stmt, "iii", $lost_item_id, $new_id, $user_id);
                mysqli_stmt_execute($match_stmt);
                mysqli_stmt_close($match_stmt);
            }
        }
        
        mysqli_stmt_close($stmt);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Thank you for reporting! Admin will verify and connect you with the owner.',
            'id' => $new_id
        ]);
    } else {
        error_log("Execute failed: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit;
}
else if ($action === 'request-item-access') {

    // Clear any previous output
    if (ob_get_level()) ob_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Use POST']);
        exit;
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }

    error_log("Request item access input: " . print_r($input, true));

    $user_id = intval($input['user_id'] ?? 0);
    $item_id = intval($input['item_id'] ?? 0);
    $message = trim($input['message'] ?? '');

    // Validate required fields
    if (!$user_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }

    if (!$item_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Item ID is required']);
        exit;
    }

    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message is required']);
        exit;
    }

    $create_table = "CREATE TABLE IF NOT EXISTS item_claims (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        user_id INT NOT NULL,
        message TEXT,
        proof_description TEXT,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        admin_notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        reviewed_at TIMESTAMP NULL,
        INDEX idx_item (item_id),
        INDEX idx_user (user_id),
        INDEX idx_status (status)
    )";
    
    if (!mysqli_query($conn, $create_table)) {
        error_log("Failed to create item_claims table: " . mysqli_error($conn));
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database setup error']);
        exit;
    }

    // Check if the found item exists
    $item_check = mysqli_query($conn, "SELECT id, status FROM items WHERE id = $item_id AND type = 'found'");
    if (!$item_check || mysqli_num_rows($item_check) == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Found item not found']);
        exit;
    }

    $item = mysqli_fetch_assoc($item_check);

    // Check if user has already requested this item
    $check = mysqli_query($conn, "SELECT id, status FROM item_claims WHERE item_id = $item_id AND user_id = $user_id");
    if (mysqli_num_rows($check) > 0) {
        $existing = mysqli_fetch_assoc($check);
        http_response_code(409);
        echo json_encode([
            'success' => false, 
            'message' => 'You have already requested this item. Status: ' . $existing['status']
        ]);
        exit;
    }

    // Insert the claim request
    $escaped_message = mysqli_real_escape_string($conn, $message);
    $sql = "INSERT INTO item_claims (item_id, user_id, message, status, created_at)
            VALUES ($item_id, $user_id, '$escaped_message', 'pending', NOW())";

    if (mysqli_query($conn, $sql)) {
        $claim_id = mysqli_insert_id($conn);
        mysqli_query($conn, "UPDATE items SET status = 'pending' WHERE id = $item_id");
        
        error_log("Access request created with ID: $claim_id for item: $item_id by user: $user_id");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Access request submitted successfully',
            'claim_id' => $claim_id
        ]);
    } else {
        error_log("Database error: " . mysqli_error($conn));
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit;
}
else if ($action === 'get-user-requests') {

    $user_id = intval($_GET['user_id'] ?? 0);
    
    if (!$user_id) {
        err('User ID is required');
    }

    $sql = "SELECT c.*, i.title, i.location, i.type 
            FROM item_claims c
            JOIN items i ON c.item_id = i.id
            WHERE c.user_id = $user_id
            ORDER BY c.created_at DESC";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        err('Database error: ' . mysqli_error($conn), 500);
    }
    
    $requests = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $requests[] = $row;
    }
    
    out(['success' => true, 'requests' => $requests]);
}
else if ($action === 'admin-review-claim') {
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    
    header('Content-Type: application/json; charset=utf-8');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $admin_email = $input['admin_email'] ?? '';
    $claim_id = intval($input['claim_id'] ?? 0);
    $claim_action = $input['claim_action'] ?? '';
    $admin_notes = mysqli_real_escape_string($conn, $input['admin_notes'] ?? '');
    
    if (empty($admin_email) || !$claim_id || !$claim_action) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE email = '$admin_email'");
    if (!$check_admin || mysqli_num_rows($check_admin) == 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $admin = mysqli_fetch_assoc($check_admin);
    if ($admin['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin access only']);
        exit;
    }
    $get_claim = mysqli_query($conn, "SELECT c.*, i.title as item_title, u.full_name 
                                      FROM item_claims c
                                      JOIN items i ON c.item_id = i.id
                                      JOIN users u ON c.user_id = u.id
                                      WHERE c.id = $claim_id");
    $claim_data = mysqli_fetch_assoc($get_claim);
    
    if (!$claim_data) {
        echo json_encode(['success' => false, 'message' => 'Claim not found']);
        exit;
    }
    
    $new_status = ($claim_action === 'approve') ? 'approved' : 'rejected';
    $sql = "UPDATE item_claims SET status = '$new_status', admin_notes = '$admin_notes', reviewed_at = NOW() WHERE id = $claim_id";
    
    if (mysqli_query($conn, $sql)) {
        $user_id = $claim_data['user_id'];
        $item_title = $claim_data['item_title'];
        
        if ($claim_action === 'approve') {
            $notif_title = "Claim Approved! ✅";
            $notif_message = "Your claim for '$item_title' has been approved. You can now view the item details.";
            $notif_type = "claim_approved";
            
            $item_id = $claim_data['item_id'];
            mysqli_query($conn, "UPDATE items SET status = 'claimed' WHERE id = $item_id");
        } else {
            $notif_title = "Claim Rejected ";
            $notif_message = "Your claim for '$item_title' has been rejected. Reason: " . ($admin_notes ?: 'No specific reason provided');
            $notif_type = "claim_rejected";
        }
        
        $notif_sql = "INSERT INTO notifications (user_id, title, message, type, item_id, created_at) 
                      VALUES ($user_id, '$notif_title', '$notif_message', '$notif_type', {$claim_data['item_id']}, NOW())";
        mysqli_query($conn, $notif_sql);
        
        echo json_encode(['success' => true, 'message' => 'Claim ' . $claim_action . 'd successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit;
}

else if ($action === 'get-notifications') {
    
    header('Content-Type: application/json; charset=utf-8');
    
    $user_id = intval($_GET['user_id'] ?? 0);
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit;
    }
    
    $sql = "SELECT * FROM notifications 
            WHERE user_id = $user_id 
            ORDER BY created_at DESC 
            LIMIT 50";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
    
    $notifications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }
    
    // Get unread count
    $unread = mysqli_query($conn, "SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = FALSE");
    $unread_count = mysqli_fetch_assoc($unread)['count'];
    
    echo json_encode([
        'success' => true, 
        'notifications' => $notifications,
        'unread_count' => intval($unread_count)
    ]);
    exit;
}

else if ($action === 'mark-notification-read') {
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    
    header('Content-Type: application/json; charset=utf-8');
    
    $input = json_decode(file_get_contents('php://input'), true);
    $notification_id = intval($input['notification_id'] ?? 0);
    $user_id = intval($input['user_id'] ?? 0);
    
    if (!$notification_id || !$user_id) {
        echo json_encode(['success' => false, 'message' => 'Notification ID and User ID required']);
        exit;
    }
    
    $sql = "UPDATE notifications SET is_read = TRUE WHERE id = $notification_id AND user_id = $user_id";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Marked as read']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}
else if ($action === 'mark-all-notifications-read') {
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    
    header('Content-Type: application/json; charset=utf-8');
    
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = intval($input['user_id'] ?? 0);
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit;
    }
    
    $sql = "UPDATE notifications SET is_read = TRUE WHERE user_id = $user_id";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'message' => 'All marked as read']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

else if ($action === 'get-user-history') {
    
    header('Content-Type: application/json; charset=utf-8');
    
    $user_id = intval($_GET['user_id'] ?? 0);
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit;
    }
    
    // Get items reported by user
    $reported_sql = "SELECT *, 'reported' as history_type FROM items 
                     WHERE reporter_name = (SELECT full_name FROM users WHERE id = $user_id)
                     ORDER BY created_at DESC";
    
    // Get items user has claimed
    $claimed_sql = "SELECT i.*, 'claimed' as history_type, c.status as claim_status, c.created_at as claim_date
                    FROM item_claims c
                    JOIN items i ON c.item_id = i.id
                    WHERE c.user_id = $user_id
                    ORDER BY c.created_at DESC";
    
    $reported_result = mysqli_query($conn, $reported_sql);
    $claimed_result = mysqli_query($conn, $claimed_sql);
    
    $history = [];
    
    if ($reported_result) {
        while ($row = mysqli_fetch_assoc($reported_result)) {
            $history[] = $row;
        }
    }
    
    if ($claimed_result) {
        while ($row = mysqli_fetch_assoc($claimed_result)) {
            $history[] = $row;
        }
    }
    
    usort($history, function($a, $b) {
        $dateA = strtotime($a['created_at'] ?? $a['claim_date'] ?? 'now');
        $dateB = strtotime($b['created_at'] ?? $b['claim_date'] ?? 'now');
        return $dateB - $dateA;
    });
    
    echo json_encode(['success' => true, 'history' => $history]);
    exit;
}

// ────────────────────────────────────────────────
else {
    err('Unknown action', 404);
}

mysqli_close($conn);
?>