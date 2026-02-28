<?php
if ($action === 'update-profile') {
    if (ob_get_level()) ob_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }

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

    $check_user = mysqli_query($conn, "SELECT id, email, user_string_id FROM users WHERE id = $user_id");
    if (!$check_user || mysqli_num_rows($check_user) == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $user = mysqli_fetch_assoc($check_user);
    $updates = [];
    if (isset($input['full_name']) && !empty(trim($input['full_name']))) {
        $full_name = mysqli_real_escape_string($conn, trim($input['full_name']));
        $updates[] = "full_name = '$full_name'";
    }
    if (isset($input['phone'])) {
        $phone = mysqli_real_escape_string($conn, trim($input['phone']));
        $updates[] = "phone = '$phone'";
    }
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
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = $user_id";
    
    error_log("Update SQL: " . $sql);

    if (mysqli_query($conn, $sql)) {
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
else if ($action === 'get-user-history') {
    
    header('Content-Type: application/json; charset=utf-8');
    $user_string_id = isset($_GET['user_string_id']) ? mysqli_real_escape_string($conn, cl($_GET['user_string_id'])) : '';
    
    if (empty($user_string_id)) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit;
    }
    
    $user_query = mysqli_query($conn, "SELECT id, full_name FROM users WHERE user_string_id = '$user_string_id'");
    if (!$user_query || mysqli_num_rows($user_query) == 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $user_data = mysqli_fetch_assoc($user_query);
    $user_id = $user_data['id'];
    $user_name = $user_data['full_name'];
    
    $reported_sql = "SELECT *, 'reported' as history_type 
                    FROM items 
                    WHERE user_string_id = '$user_string_id' OR reporter_name = '$user_name'
                    ORDER BY created_at DESC";
    
    $reported_result = mysqli_query($conn, $reported_sql);
    $matches_sql = "SELECT 
                        m.*, 
                        lost.item_string_id as lost_item_string_id,
                        lost.title as lost_title,
                        lost.description as lost_description,
                        lost.image_path as lost_image,
                        found.item_string_id as found_item_string_id,
                        found.title as found_title,
                        found.description as found_description,
                        found.image_path as found_image,
                        'match' as history_type
                    FROM matches m
                    LEFT JOIN items lost ON m.lost_item_id = lost.id
                    LEFT JOIN items found ON m.found_item_id = found.id
                    WHERE lost.user_string_id = '$user_string_id' 
                       OR found.user_string_id = '$user_string_id'
                    ORDER BY m.created_at DESC";
    
    $matches_result = mysqli_query($conn, $matches_sql);
    
    $history = [];
    if ($reported_result && mysqli_num_rows($reported_result) > 0) {
        while ($row = mysqli_fetch_assoc($reported_result)) {
            if (!empty($row['image_path']) && $row['image_path'] != 'NULL') {
                $row['image_list'] = explode('|', $row['image_path']);
            }
            $row['sort_date'] = $row['created_at'];
            $history[] = $row;
        }
    }
    if ($claimed_result && mysqli_num_rows($claimed_result) > 0) {
        while ($row = mysqli_fetch_assoc($claimed_result)) {
            if (!empty($row['image_path']) && $row['image_path'] != 'NULL') {
                $row['image_list'] = explode('|', $row['image_path']);
            }
            $row['sort_date'] = $row['claim_date'] ?? $row['created_at'];
            $history[] = $row;
        }
    }
    
    usort($history, function($a, $b) {
        $dateA = strtotime($a['sort_date'] ?? 'now');
        $dateB = strtotime($b['sort_date'] ?? 'now');
        return $dateB - $dateA;
    });
    
    echo json_encode([
        'success' => true, 
        'history' => $history,
        'user_info' => [
            'id' => $user_id,
            'name' => $user_name,
            'string_id' => $user_string_id
        ],
        'counts' => [
            'reported' => $reported_result ? mysqli_num_rows($reported_result) : 0,
            'claimed' => $claimed_result ? mysqli_num_rows($claimed_result) : 0,
            'matches' => $matches_result ? mysqli_num_rows($matches_result) : 0,
            'total' => count($history)
        ]
    ]);
    
    exit;
}
?>