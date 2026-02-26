<?php
// Profile and user-related endpoints

if ($action === 'get-profile') {

    header('Content-Type: application/json; charset=utf-8');
    
    // Accept either user_id or user_string_id
    $user_id = 0;
    if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
    } elseif (isset($_GET['user_string_id']) && !empty($_GET['user_string_id'])) {
        $user_string_id = cl($_GET['user_string_id']);
        $user_id = getUserIdFromString($conn, $user_string_id);
    }
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit;
    }

    $result = mysqli_query($conn, "SELECT id, user_string_id, email, full_name, phone, role FROM users WHERE id = $user_id");
    
    if (!$result || mysqli_num_rows($result) == 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $user = mysqli_fetch_assoc($result);
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => intval($user['id']),
            'user_string_id' => $user['user_string_id'],
            'email' => $user['email'],
            'full_name' => $user['full_name'] ?? '',
            'phone' => $user['phone'] ?? '',
            'role' => $user['role'] ?? 'student'
        ]
    ]);
    exit;
}
else if ($action === 'update-profile') {

    // Clear any previous output
    if (ob_get_level()) ob_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
        exit;
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }

    error_log("Update profile input: " . print_r($input, true));

    // Accept either user_id or user_string_id
    $user_id = 0;
    if (isset($input['user_id']) && !empty($input['user_id'])) {
        $user_id = intval($input['user_id']);
    } elseif (isset($input['user_string_id']) && !empty($input['user_string_id'])) {
        $user_string_id = cl($input['user_string_id']);
        $user_id = getUserIdFromString($conn, $user_string_id);
    }
    
    if (!$user_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }

    // Check if user exists
    $check_user = mysqli_query($conn, "SELECT id, email, user_string_id FROM users WHERE id = $user_id");
    if (!$check_user || mysqli_num_rows($check_user) == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $user = mysqli_fetch_assoc($check_user);
    
    // Build update query dynamically (only update fields that are provided)
    $updates = [];

    // Update full_name if provided
    if (isset($input['full_name']) && !empty(trim($input['full_name']))) {
        $full_name = mysqli_real_escape_string($conn, trim($input['full_name']));
        $updates[] = "full_name = '$full_name'";
    }

    // Update phone if provided (can be empty)
    if (isset($input['phone'])) {
        $phone = mysqli_real_escape_string($conn, trim($input['phone']));
        $updates[] = "phone = '$phone'";
    }

    // Check if there's anything to update
    if (empty($updates)) {
        echo json_encode([
            'success' => true, 
            'message' => 'No changes to update',
            'user' => [
                'id' => $user['id'],
                'user_string_id' => $user['user_string_id'],
                'email' => $user['email'],
                'full_name' => $user['full_name'] ?? '',
                'phone' => $user['phone'] ?? '',
                'role' => $user['role'] ?? 'student'
            ]
        ]);
        exit;
    }

    // Build and execute update query
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = $user_id";
    
    error_log("Update SQL: " . $sql);

    if (mysqli_query($conn, $sql)) {
        // Fetch updated user data
        $result = mysqli_query($conn, "SELECT id, user_string_id, email, full_name, phone, role FROM users WHERE id = $user_id");
        $updated_user = mysqli_fetch_assoc($result);
        
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => intval($updated_user['id']),
                'user_string_id' => $updated_user['user_string_id'],
                'email' => $updated_user['email'],
                'full_name' => $updated_user['full_name'] ?? '',
                'phone' => $updated_user['phone'] ?? '',
                'role' => $updated_user['role'] ?? 'student'
            ]
        ]);
    } else {
        error_log("Database error: " . mysqli_error($conn));
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit;
}
else if ($action === 'change-password') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Accept either user_id or user_string_id
    $user_id = 0;
    if (isset($input['user_id']) && !empty($input['user_id'])) {
        $user_id = intval($input['user_id']);
    } elseif (isset($input['user_string_id']) && !empty($input['user_string_id'])) {
        $user_string_id = cl($input['user_string_id']);
        $user_id = getUserIdFromString($conn, $user_string_id);
    }
    
    $current_password = $input['current_password'] ?? '';
    $new_password = $input['new_password'] ?? '';

    if (!$user_id || !$current_password || !$new_password) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    if (strlen($new_password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        exit;
    }

    // Get current password hash
    $result = mysqli_query($conn, "SELECT password_hash FROM users WHERE id = $user_id");
    $user = mysqli_fetch_assoc($result);

    if (!$user || !password_verify($current_password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit;
    }

    // Update password
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET password_hash = '$new_hash' WHERE id = $user_id";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}
else if ($action === 'get-user-history') {
    
    header('Content-Type: application/json; charset=utf-8');
    
    // Accept either user_id or user_string_id
    $user_id = 0;
    if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
    } elseif (isset($_GET['user_string_id']) && !empty($_GET['user_string_id'])) {
        $user_string_id = cl($_GET['user_string_id']);
        $user_id = getUserIdFromString($conn, $user_string_id);
    }
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit;
    }
    
    // Get user's name
    $user_query = mysqli_query($conn, "SELECT full_name FROM users WHERE id = $user_id");
    $user_data = mysqli_fetch_assoc($user_query);
    $user_name = $user_data['full_name'];
    
    // Get items reported by user
    $reported_sql = "SELECT *, 'reported' as history_type FROM items 
                     WHERE reporter_name = '$user_name' OR user_id = $user_id
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
else if ($action === 'get-user-requests') {

    // Accept either user_id or user_string_id
    $user_id = 0;
    if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
    } elseif (isset($_GET['user_string_id']) && !empty($_GET['user_string_id'])) {
        $user_string_id = cl($_GET['user_string_id']);
        $user_id = getUserIdFromString($conn, $user_string_id);
    }
    
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
else if ($action === 'get-notifications') {
    
    header('Content-Type: application/json; charset=utf-8');
    
    // Accept either user_id or user_string_id
    $user_id = 0;
    if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
    } elseif (isset($_GET['user_string_id']) && !empty($_GET['user_string_id'])) {
        $user_string_id = cl($_GET['user_string_id']);
        $user_id = getUserIdFromString($conn, $user_string_id);
    }
    
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
    
    $user_id = 0;
    if (isset($input['user_id']) && !empty($input['user_id'])) {
        $user_id = intval($input['user_id']);
    } elseif (isset($input['user_string_id']) && !empty($input['user_string_id'])) {
        $user_string_id = cl($input['user_string_id']);
        $user_id = getUserIdFromString($conn, $user_string_id);
    }
    
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
    
    // Accept either user_id or user_string_id
    $user_id = 0;
    if (isset($input['user_id']) && !empty($input['user_id'])) {
        $user_id = intval($input['user_id']);
    } elseif (isset($input['user_string_id']) && !empty($input['user_string_id'])) {
        $user_string_id = cl($input['user_string_id']);
        $user_id = getUserIdFromString($conn, $user_string_id);
    }
    
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
?>