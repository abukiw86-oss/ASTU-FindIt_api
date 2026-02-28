<?php
if ($action === 'get-notifications') {
    header('Content-Type: application/json; charset=utf-8');
    
    $user_id = isset($_GET['user_id']) ? mysqli_real_escape_string($conn, $_GET['user_id']) : '';
    
    if (empty($user_id)) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit;
    }
    
    // Check if user exists
    $check_user = mysqli_query($conn, "SELECT id FROM users WHERE user_string_id = '$user_id'");
    if (!$check_user || mysqli_num_rows($check_user) == 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Simple query first - get all notifications for this user
    $sql = "SELECT * FROM notifications 
            WHERE user_string_id = '$user_id' 
            ORDER BY created_at DESC";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        exit;
    }
    
    $notifications = [];
    $unread_count = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Get additional details based on notification type and reference_id
        $item_details = null;
        $claim_details = null;
        $match_details = null;
        
        // For item-related notifications
        if (in_array($row['type'], ['item_review', 'item_claimed']) && $row['reference_id']) {
            $item_query = mysqli_query($conn, "SELECT id, title, item_string_id, type, status, image_path 
                                              FROM items WHERE id = " . intval($row['reference_id']));
            if ($item_query && mysqli_num_rows($item_query) > 0) {
                $item_details = mysqli_fetch_assoc($item_query);
            }
        }
        
        // For claim-related notifications
        if (in_array($row['type'], ['claim_approved', 'claim_rejected']) && $row['reference_id']) {
            $claim_query = mysqli_query($conn, "SELECT c.*, i.title as item_title 
                                               FROM item_claims c
                                               LEFT JOIN items i ON c.item_id = i.id
                                               WHERE c.id = " . intval($row['reference_id']));
            if ($claim_query && mysqli_num_rows($claim_query) > 0) {
                $claim_details = mysqli_fetch_assoc($claim_query);
            }
        }
        
        // For match-related notifications
        if ($row['type'] == 'match_found' && $row['reference_id']) {
            $match_query = mysqli_query($conn, "SELECT m.*, 
                                               l.title as lost_title, 
                                               f.title as found_title
                                               FROM matches m
                                               LEFT JOIN items l ON m.lost_item_id = l.item_string_id
                                               LEFT JOIN items f ON m.found_item_id = f.item_string_id
                                               WHERE m.id = " . intval($row['reference_id']));
            if ($match_query && mysqli_num_rows($match_query) > 0) {
                $match_details = mysqli_fetch_assoc($match_query);
            }
        }
        
        // Build notification with all available details
        $notification = [
            'id' => $row['id'],
            'user_string_id' => $row['user_string_id'],
            'type' => $row['type'],
            'title' => $row['title'], // Use the title directly from the table
            'message' => $row['message'],
            'is_read' => (bool)$row['is_read'],
            'created_at' => $row['created_at'],
            'reference_id' => $row['reference_id'],
            'admin_notes' => $row['admin_notes'] ?? null,
            'item_details' => $item_details,
            'claim_details' => $claim_details,
            'match_details' => $match_details
        ];
        
        $notifications[] = $notification;
        
        if (!$row['is_read']) {
            $unread_count++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unread_count,
        'total' => count($notifications)
    ]);
    
    exit;
}

function createNotification($conn, $user_string_id, $type, $title, $message, $reference_id = null, $admin_notes = null) {
    $user_string_id = mysqli_real_escape_string($conn, $user_string_id);
    $type = mysqli_real_escape_string($conn, $type);
    $title = mysqli_real_escape_string($conn, $title);
    $message = mysqli_real_escape_string($conn, $message);
    $admin_notes = $admin_notes ? mysqli_real_escape_string($conn, $admin_notes) : null;
    $reference_id = $reference_id ? intval($reference_id) : 'NULL';
    
    $sql = "INSERT INTO notifications 
            (user_string_id, type, title, message, reference_id, admin_notes, created_at, is_read) 
            VALUES ('$user_string_id', '$type', '$title', '$message', $reference_id, " . 
            ($admin_notes ? "'$admin_notes'" : "NULL") . ", NOW(), 0)";
    
    return mysqli_query($conn, $sql);
}
function _getNotificationTitle($row) {
    $type = $row['type'];
    
    switch ($type) {
        case 'claim_approved':
            return 'Claim Approved ✅';
        case 'claim_rejected':
            return 'Claim Rejected ❌';
        case 'match_found':
            return 'Match Found! 🔗';
        case 'item_claimed':
            return 'Your Item Has Been Claimed 📦';
        case 'item_review':
            return 'Item Review Update 📋';
        case 'admin_message':
            return 'Message from Admin 👤';
        default:
            return 'Notification';
    }
}
function _getItemDetails($row) {
    $details = [];
    
    if ($row['item_title']) {
        $details['title'] = $row['item_title'];
    }
    if ($row['item_type']) {
        $details['type'] = $row['item_type'];
    }
    if ($row['item_status']) {
        $details['status'] = $row['item_status'];
    }
    if ($row['item_image']) {
        $details['image'] = $row['item_image'];
    }
    
    return !empty($details) ? $details : null;
}