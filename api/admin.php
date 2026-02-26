<?php
// Admin-only endpoints

if ($action === 'admin-pending-items') {
    
    header('Content-Type: application/json; charset=utf-8');
    
    // Accept either admin_email or admin_string_id
    $admin_email = isset($_GET['admin_email']) ? $_GET['admin_email'] : '';
    $admin_string_id = isset($_GET['admin_string_id']) ? $_GET['admin_string_id'] : '';
    
    if (empty($admin_email) && empty($admin_string_id)) {
        echo json_encode(['success' => false, 'message' => 'Admin email or string ID required']);
        exit;
    }
    
    $admin_id = 0;
    if (!empty($admin_string_id)) {
        $admin_id = getUserIdFromString($conn, $admin_string_id);
    }
    
    $check_admin = null;
    if (!empty($admin_email)) {
        $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE email = '$admin_email'");
    } elseif ($admin_id > 0) {
        $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE id = $admin_id");
    }
    
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
    
    // Accept either admin_email or admin_string_id
    $admin_email = $input['admin_email'] ?? '';
    $admin_string_id = $input['admin_string_id'] ?? '';
    $item_id = intval($input['item_id'] ?? 0);
    $review_action = $input['review_action'] ?? '';
    $admin_notes = mysqli_real_escape_string($conn, $input['admin_notes'] ?? '');
    
    if ((empty($admin_email) && empty($admin_string_id)) || !$item_id || !$review_action) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $admin_id = 0;
    if (!empty($admin_string_id)) {
        $admin_id = getUserIdFromString($conn, $admin_string_id);
    }
    
    $check_admin = null;
    if (!empty($admin_email)) {
        $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE email = '$admin_email'");
    } elseif ($admin_id > 0) {
        $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE id = $admin_id");
    }
    
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
    
    // Accept either admin_email or admin_string_id
    $admin_email = isset($_GET['admin_email']) ? $_GET['admin_email'] : '';
    $admin_string_id = isset($_GET['admin_string_id']) ? $_GET['admin_string_id'] : '';
    
    if (empty($admin_email) && empty($admin_string_id)) {
        echo json_encode(['success' => false, 'message' => 'Admin email or string ID required']);
        exit;
    }
    
    $admin_id = 0;
    if (!empty($admin_string_id)) {
        $admin_id = getUserIdFromString($conn, $admin_string_id);
    }
    
    $check_admin = null;
    if (!empty($admin_email)) {
        $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE email = '$admin_email'");
    } elseif ($admin_id > 0) {
        $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE id = $admin_id");
    }
    
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
            u.full_name as claimant_name, u.email as claimant_email, u.phone as claimant_phone,
            u.user_string_id as claimant_string_id
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
    
    // Accept either admin_email or admin_string_id
    $admin_email = $input['admin_email'] ?? '';
    $admin_string_id = $input['admin_string_id'] ?? '';
    $claim_id = intval($input['claim_id'] ?? 0);
    $claim_action = $input['claim_action'] ?? '';
    $admin_notes = mysqli_real_escape_string($conn, $input['admin_notes'] ?? '');
    
    if ((empty($admin_email) && empty($admin_string_id)) || !$claim_id || !$claim_action) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $admin_id = 0;
    if (!empty($admin_string_id)) {
        $admin_id = getUserIdFromString($conn, $admin_string_id);
    }
    
    $check_admin = null;
    if (!empty($admin_email)) {
        $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE email = '$admin_email'");
    } elseif ($admin_id > 0) {
        $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE id = $admin_id");
    }
    
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
    
    // Accept either admin_email or admin_string_id
    $admin_email = isset($_GET['admin_email']) ? $_GET['admin_email'] : '';
    $admin_string_id = isset($_GET['admin_string_id']) ? $_GET['admin_string_id'] : '';
    
    if (empty($admin_email) && empty($admin_string_id)) {
        echo json_encode(['success' => false, 'message' => 'Admin email or string ID required']);
        exit;
    }
    
    $admin_id = 0;
    if (!empty($admin_string_id)) {
        $admin_id = getUserIdFromString($conn, $admin_string_id);
    }
    
    $check_admin = null;
    if (!empty($admin_email)) {
        $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE email = '$admin_email'");
    } elseif ($admin_id > 0) {
        $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE id = $admin_id");
    }
    
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
    
    // Accept either admin_email or admin_string_id
    $admin_email = $input['admin_email'] ?? '';
    $admin_string_id = $input['admin_string_id'] ?? '';
    $match_id = intval($input['match_id'] ?? 0);
    $status = $input['status'] ?? '';
    
    if ((empty($admin_email) && empty($admin_string_id)) || !$match_id || !$status) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $admin_id = 0;
    if (!empty($admin_string_id)) {
        $admin_id = getUserIdFromString($conn, $admin_string_id);
    }
    
    $check_admin = null;
    if (!empty($admin_email)) {
        $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE email = '$admin_email'");
    } elseif ($admin_id > 0) {
        $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE id = $admin_id");
    }
    
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
    
    // Accept either admin_email or admin_string_id
    $admin_email = isset($_GET['admin_email']) ? $_GET['admin_email'] : '';
    $admin_string_id = isset($_GET['admin_string_id']) ? $_GET['admin_string_id'] : '';
    
    if (empty($admin_email) && empty($admin_string_id)) {
        echo json_encode(['success' => false, 'message' => 'Admin email or string ID required']);
        exit;
    }
    
    $admin_id = 0;
    if (!empty($admin_string_id)) {
        $admin_id = getUserIdFromString($conn, $admin_string_id);
    }
    
    $check_admin = null;
    if (!empty($admin_email)) {
        $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE email = '$admin_email'");
    } elseif ($admin_id > 0) {
        $check_admin = mysqli_query($conn, "SELECT role FROM users WHERE id = $admin_id");
    }
    
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
?>