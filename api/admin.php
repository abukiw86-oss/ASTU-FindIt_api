<?php

function verifyAdminAccess($conn, $admin_identifier = '') {
    if (empty($admin_identifier)) {
        return ['success' => false, 'message' => 'Admin identifier required'];
    }
    
    $admin_identifier = mysqli_real_escape_string($conn, $admin_identifier);
    
    $query = "SELECT id, full_name, role, user_string_id FROM users 
              WHERE (student_id = '$admin_identifier' OR user_string_id = '$admin_identifier') 
              AND role = 'admin'";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        return ['success' => false, 'message' => 'Admin not found'];
    }
    
    $admin = mysqli_fetch_assoc($result);
    
    return ['success' => true, 'admin' => $admin];
}

function logAdminAction($conn, $admin_name, $action, $details, $target_user = '') {
    $admin_name = mysqli_real_escape_string($conn, $admin_name);
    $details = mysqli_real_escape_string($conn, $details);
    $target_user = mysqli_real_escape_string($conn, $target_user);
    $admin_message = mysqli_real_escape_string($conn, $action . ': ' . $details);
    
    $sql = "INSERT INTO admin_logs (user_string_id, details, created_at, user_name, admin_name, admin_mssage, user_message) 
            VALUES ('$target_user', '$details', NOW(), '$target_user', '$admin_name', '$admin_message', '')";
    
    return mysqli_query($conn, $sql);
}

function processImagePath($image_path) {
    if (empty($image_path) || $image_path == 'NULL') {
        return ['image_list' => [], 'image_count' => 0, 'first_image' => null];
    }
    
    if (strpos($image_path, "'") === 0) {
        $image_path = trim($image_path, "'");
    }
    
    $images = explode('|', $image_path);
    $images = array_filter($images, function($img) {
        return !empty($img) && $img != 'NULL';
    });
    $images = array_values($images); 
    
    return [
        'image_list' => $images,
        'image_count' => count($images),
        'first_image' => $images[0] ?? null
    ];
}
if ($action === 'admin-dashboard-stats') {
    header('Content-Type: application/json; charset=utf-8');
    
    $admin_identifier = $_GET['admin_id'] ?? $_GET['admin_email'] ?? $_GET['admin_string_id'] ?? '';
    

    
    $stats = [];
    $items_result = mysqli_query($conn, "SELECT 
        COUNT(*) as total_items,
        SUM(CASE WHEN type = 'lost' THEN 1 ELSE 0 END) as total_lost,
        SUM(CASE WHEN type = 'found' THEN 1 ELSE 0 END) as total_found,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_items,
        SUM(CASE WHEN status = 'admin_approval' THEN 1 ELSE 0 END) as admin_approval_items,
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_items,
        SUM(CASE WHEN status = 'claimed' THEN 1 ELSE 0 END) as claimed_items,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_items,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_items
        FROM items");
    
    $stats['items'] = $items_result ? mysqli_fetch_assoc($items_result) : [];
    
    $claims_result = mysqli_query($conn, "SELECT 
        COUNT(*) as total_claims,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_claims,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_claims,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_claims
        FROM item_claims");
    
    $stats['claims'] = $claims_result ? mysqli_fetch_assoc($claims_result) : [];
    
    $matches_result = mysqli_query($conn, "SELECT 
        COUNT(*) as total_matches,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_matches,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_matches,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_matches,
        AVG(match_confidence) as avg_confidence
        FROM matches");
    
    $stats['matches'] = $matches_result ? mysqli_fetch_assoc($matches_result) : [];
    
    $users_result = mysqli_query($conn, "SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
        SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as students
        FROM users");
    
    $stats['users'] = $users_result ? mysqli_fetch_assoc($users_result) : [];
    
    $recent_items = mysqli_query($conn, "SELECT id, title, type, status, created_at 
                                         FROM items ORDER BY created_at DESC LIMIT 5");
    $stats['recent_items'] = [];
    if ($recent_items) {
        while ($row = mysqli_fetch_assoc($recent_items)) {
            $stats['recent_items'][] = $row;
        }
    }
    
    $recent_claims = mysqli_query($conn, "SELECT c.id, i.title, c.status, c.created_at 
                                          FROM item_claims c 
                                          LEFT JOIN items i ON c.item_id = i.id 
                                          ORDER BY c.created_at DESC LIMIT 5");
    $stats['recent_claims'] = [];
    if ($recent_claims) {
        while ($row = mysqli_fetch_assoc($recent_claims)) {
            $stats['recent_claims'][] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'stats' => $stats]);
    exit;
}

else if ($action === 'admin-get-items') {
    header('Content-Type: application/json; charset=utf-8');
    
    $admin_identifier = $_GET['admin_id'] ?? $_GET['admin_email'] ?? $_GET['admin_string_id'] ?? '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    
    $where = ["1=1"];
    
    if (!empty($status)) {
        $status = mysqli_real_escape_string($conn, $status);
        $where[] = "i.status = '$status'";
    }
    
    if (!empty($type)) {
        $type = mysqli_real_escape_string($conn, $type);
        $where[] = "i.type = '$type'";
    }
    
    if (!empty($search)) {
        $search = mysqli_real_escape_string($conn, $search);
        $where[] = "(i.title LIKE '%$search%' OR i.description LIKE '%$search%' OR i.reporter_name LIKE '%$search%')";
    }
    
    $where_clause = implode(' AND ', $where);
    
    $sql = "SELECT i.*, 
            u.full_name as user_full_name, 
            u.student_id,
            u.phone as user_phone
            FROM items i 
            LEFT JOIN users u ON i.user_string_id = u.user_string_id 
            WHERE $where_clause
            ORDER BY i.created_at DESC";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
    
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $image_data = processImagePath($row['image_path']);
        $row['image_list'] = $image_data['image_list'];
        $row['image_count'] = $image_data['image_count'];
        $row['first_image'] = $image_data['first_image'];
        unset($row['image_path']);
        $items[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'total' => count($items)
    ]);
    exit;
}

else if ($action === 'admin-get-pending-items') {
    header('Content-Type: application/json; charset=utf-8');
    
    $admin_identifier = $_GET['admin_id'] ?? $_GET['admin_email'] ?? $_GET['admin_string_id'] ?? '';
    

    
    $sql = "SELECT i.*, 
            u.full_name as user_full_name,
            u.student_id,
            u.phone as user_phone
            FROM items i
            LEFT JOIN users u ON i.user_string_id = u.user_string_id
            WHERE i.status IN ('pending', 'admin_approval')
            ORDER BY i.created_at ASC";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
    
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $image_data = processImagePath($row['image_path']);
        $row['image_list'] = $image_data['image_list'];
        $row['image_count'] = $image_data['image_count'];
        $row['first_image'] = $image_data['first_image'];
        $items[] = $row;
    }
    
    echo json_encode(['success' => true, 'items' => $items]);
    exit;
}

else if ($action === 'admin-review-item') {
    // Enable error logging but don't display
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    
    try {
        error_log("=== ADMIN REVIEW ITEM STARTED ===");
        
        if (ob_get_level()) ob_clean();
        
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("Method not allowed: " . $_SERVER['REQUEST_METHOD']);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        $raw_input = file_get_contents('php://input');
        error_log("Raw input: " . $raw_input);
        
        $input = json_decode($raw_input, true);
        error_log("Decoded input: " . print_r($input, true));
        
        if (!$input) {
            error_log("Invalid JSON input");
            echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
            exit;
        }
        
        // Extract parameters
        $item_id = isset($input['item_id']) ? trim($input['item_id']) : '';
        $review_action = isset($input['review_action']) ? trim($input['review_action']) : '';
        $admin_notes = isset($input['admin_notes']) ? trim($input['admin_notes']) : '';
        $notify_user = isset($input['notify_user']) ? filter_var($input['notify_user'], FILTER_VALIDATE_BOOLEAN) : true;
        
        error_log("item_id: $item_id");
        error_log("review_action: $review_action");
        error_log("admin_notes: $admin_notes");
        error_log("notify_user: " . ($notify_user ? 'true' : 'false'));
        
        if (empty($item_id)) {
            error_log("Missing item_id");
            echo json_encode(['success' => false, 'message' => 'Item ID is required']);
            exit;
        }
        
        if (!in_array($review_action, ['approve', 'reject'])) {
            error_log("Invalid review_action: $review_action");
            echo json_encode(['success' => false, 'message' => 'Review action must be "approve" or "reject"']);
            exit;
        }
        
        if (!$conn) {
            error_log("Database connection failed");
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit;
        }
        
        
        $new_status = $review_action === 'approve' ? 'open' : 'rejected';
        
        $escaped_item_id = mysqli_real_escape_string($conn, $item_id);
        error_log("Escaped item_id: $escaped_item_id");
        
        mysqli_begin_transaction($conn);
        
        try {
            $check_sql = "SELECT * FROM items WHERE item_string_id = '$escaped_item_id'";
            error_log("Check SQL: " . $check_sql);
            
            $check_result = mysqli_query($conn, $check_sql);
            if (!$check_result) {
                throw new Exception("Check query failed: " . mysqli_error($conn));
            }
            
            if (mysqli_num_rows($check_result) == 0) {
                throw new Exception("Item not found with ID: $escaped_item_id");
            }
            
            $item = mysqli_fetch_assoc($check_result);
            error_log("Found item: " . print_r($item, true));
            
            // Update the item
            $update_sql = "UPDATE items SET status = '$new_status', admin_notes = ? WHERE item_string_id = '$escaped_item_id'";
            error_log("Update SQL: " . $update_sql);
            
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "s", $admin_notes);
            
            if (!mysqli_stmt_execute($update_stmt)) {
                throw new Exception("Update failed: " . mysqli_stmt_error($update_stmt));
            }
            
            mysqli_stmt_close($update_stmt);
            error_log("Update successful");
            if ($notify_user && !empty($item['user_string_id'])) {
                $notif_type = 'item_review';
                $notif_title = $review_action === 'approve' ? '✅ Item Approved' : '❌ Item Rejected';
                $notif_message = "Your {$item['type']} item '{$item['title']}' has been " . strtoupper($new_status) . ".";
                
                if (!empty($admin_notes)) {
                    $notif_message .= " Admin notes: $admin_notes";
                }
                
                error_log("Creating notification for user: " . $item['user_string_id']);
                
                // Use prepared statement for notification to avoid SQL injection
                $notif_sql = "INSERT INTO notifications 
                    (user_string_id, type, title, message, reference_id, admin_notes, created_at, is_read) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)";
                
                $notif_stmt = mysqli_prepare($conn, $notif_sql);
                mysqli_stmt_bind_param(
                    $notif_stmt, 
                    "ssssss", 
                    $item['user_string_id'],
                    $notif_type,
                    $notif_title,
                    $notif_message,
                    $escaped_item_id,
                    $admin_notes
                );
                
                if (!mysqli_stmt_execute($notif_stmt)) {
                    error_log("Notification insert failed: " . mysqli_stmt_error($notif_stmt));
                } else {
                    error_log("Notification created successfully");
                }
                
                mysqli_stmt_close($notif_stmt);
            }
            
            // Commit transaction
            mysqli_commit($conn);
            error_log("Transaction committed successfully");
            
            $response = [
                'success' => true, 
                'message' => 'Item ' . $review_action . 'd successfully',
                'new_status' => $new_status
            ];
            
            error_log("Sending response: " . json_encode($response));
            echo json_encode($response);
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            error_log("Exception: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
        error_log("=== ADMIN REVIEW ITEM ENDED ===");
        exit;
        
    } catch (Exception $e) {
        error_log("Fatal exception: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        if (isset($conn) && $conn) {
            mysqli_rollback($conn);
        }
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        exit;
    }
}


else if ($action === 'admin-delete-item') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    
    header('Content-Type: application/json; charset=utf-8');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $admin_identifier = $input['admin_id'] ?? $input['admin_email'] ?? $input['admin_string_id'] ?? '';
    $item_id = intval($input['item_id'] ?? 0);
    

    
    if (!$item_id) {
        echo json_encode(['success' => false, 'message' => 'Item ID required']);
        exit;
    }
    
    mysqli_begin_transaction($conn);
    $get_item = mysqli_query($conn, "SELECT user_string_id, title, item_string_id FROM items WHERE id = $item_id");
    $item = mysqli_fetch_assoc($get_item);
    
    mysqli_query($conn, "DELETE FROM item_claims WHERE item_id = $item_id");
    mysqli_query($conn, "DELETE FROM matches WHERE lost_item_id = '{$item['item_string_id']}' OR found_item_id = '{$item['item_string_id']}'");
    mysqli_query($conn, "DELETE FROM items WHERE id = $item_id");
    
    logAdminAction($conn, $verify['admin']['full_name'], "delete_item", 
                  "Deleted item #$item_id: {$item['title']}", $item['user_string_id']);
    
    mysqli_commit($conn);
    
    echo json_encode(['success' => true, 'message' => 'Item deleted']);
    exit;
}

else if ($action === 'admin-get-claims') {
    header('Content-Type: application/json; charset=utf-8');
    
    $admin_identifier = $_GET['admin_id'] ?? $_GET['admin_email'] ?? $_GET['admin_string_id'] ?? '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    

    
    $where = ["1=1"];
    if (!empty($status)) {
        $status = mysqli_real_escape_string($conn, $status);
        $where[] = "c.status = '$status'";
    }
    
    $where_clause = implode(' AND ', $where);
    
    $sql = "SELECT c.*, 
            i.title as item_title, 
            i.type as item_type,
            u_owner.full_name as owner_name,
            u_claimant.full_name as claimant_name,
            u_claimant.student_id as claimant_student_id,
            u_claimant.phone as claimant_phone
            FROM item_claims c
            LEFT JOIN items i ON c.item_id = i.id
            LEFT JOIN users u_owner ON i.user_string_id = u_owner.user_string_id
            LEFT JOIN users u_claimant ON c.claimant_string_id = u_claimant.user_string_id
            WHERE $where_clause
            ORDER BY c.created_at DESC";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
    
    $claims = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $attachments = mysqli_query($conn, "SELECT * FROM item_claim_attachments WHERE claim_id = {$row['id']}");
        $row['attachments'] = [];
        if ($attachments) {
            while ($att = mysqli_fetch_assoc($attachments)) {
                $row['attachments'][] = $att;
            }
        }
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
    
    $admin_identifier = $input['admin_id'] ?? $input['admin_email'] ?? $input['admin_string_id'] ?? '';
    $claim_id = intval($input['claim_id'] ?? 0);
    $claim_action = $input['claim_action'] ?? '';
    $admin_notes = mysqli_real_escape_string($conn, $input['admin_notes'] ?? '');
    
    
    if (!$claim_id || !in_array($claim_action, ['approve', 'reject'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    
    $new_status = $claim_action === 'approve' ? 'approved' : 'rejected';
    
    mysqli_begin_transaction($conn);
    
    try {
        $get_claim = mysqli_query($conn, "SELECT c.*, i.title, i.id as item_id, i.item_string_id, 
                                          i.user_string_id as owner_id, i.status as item_status
                                         FROM item_claims c 
                                         LEFT JOIN items i ON c.item_id = i.id 
                                         WHERE c.id = $claim_id");
        
        if (!$get_claim || mysqli_num_rows($get_claim) == 0) {
            throw new Exception('Claim not found');
        }
        
        $claim = mysqli_fetch_assoc($get_claim);
        
        if (!mysqli_query($conn, $update_claim)) {
            throw new Exception('Failed to update claim: ' . mysqli_error($conn));
        }
        
        if ($claim_action === 'approve' && $claim['item_id']) {
            if (!empty($claim['item_string_id'])) {
                $update_item = "UPDATE items SET status = 'open' WHERE item_string_id = '{$claim['item_string_id']}'";
            } else {
                $update_item = "UPDATE items SET status = 'open' WHERE id = {$claim['item_id']}";
            }
            
            if (!mysqli_query($conn, $update_item)) {
                throw new Exception('Failed to update item status: ' . mysqli_error($conn));
            }
        }
        
        $notif_title = $claim_action === 'approve' ? 'Claim Approved ✅' : 'Claim Rejected ❌';
        $notif_message = "Your claim for '{$claim['title']}' has been $new_status.";
        if (!empty($admin_notes)) {
            $notif_message .= " Notes: $admin_notes";
        }
        
        $notif_title_escaped = mysqli_real_escape_string($conn, $notif_title);
        $notif_message_escaped = mysqli_real_escape_string($conn, $notif_message);
        $admin_notes_escaped = mysqli_real_escape_string($conn, $admin_notes);
        
        $insert_notif = "INSERT INTO notifications 
                        (user_string_id, type, title, message, reference_id, admin_notes, created_at, is_read) 
                        VALUES ('{$claim['claimant_string_id']}', 'claim_$new_status', '$notif_title_escaped', 
                                '$notif_message_escaped', $claim_id, '$admin_notes_escaped', NOW(), 0)";
        
        if ($claim_action === 'approve' && !empty($claim['owner_id'])) {
            $owner_notif = "Your item '{$claim['title']}' has been claimed and approved.";
            $owner_notif_escaped = mysqli_real_escape_string($conn, $owner_notif);
            
            $insert_owner_notif = "INSERT INTO notifications 
                                  (user_string_id, type, title, message, reference_id, admin_notes, created_at, is_read) 
                                  VALUES ('{$claim['owner_id']}', 'item_claimed', 'Item Claimed', 
                                          '$owner_notif_escaped', $claim_id, '$admin_notes_escaped', NOW(), 0)";
            mysqli_query($conn, $insert_owner_notif);
        }
        
        logAdminAction($conn, $verify['admin']['full_name'], "review_claim", 
                      "Claim #$claim_id $new_status for item: {$claim['title']}", 
                      $claim['claimant_string_id'], $claim['item_id']);
        
        mysqli_commit($conn);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Claim ' . $claim_action . 'd successfully',
            'new_status' => $new_status
        ]);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

else if ($action === 'admin-get-matches') {
    header('Content-Type: application/json; charset=utf-8');
    
    $admin_identifier = $_GET['admin_id'] ?? $_GET['admin_email'] ?? $_GET['admin_string_id'] ?? '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    

    
    $where = ["1=1"];
    if (!empty($status)) {
        $status = mysqli_real_escape_string($conn, $status);
        $where[] = "m.status = '$status'";
    }
    
    $where_clause = implode(' AND ', $where);
    
    $sql = "SELECT m.*, 
            l.title as lost_title, 
            l.description as lost_description,
            l.reporter_name as lost_reporter,
            l.reporter_phone as lost_phone,
            u_lost.full_name as lost_owner_name,
            f.title as found_title,
            f.description as found_description,
            f.reporter_name as found_reporter,
            f.reporter_phone as found_phone,
            u_found.full_name as found_owner_name
            FROM matches m
            LEFT JOIN items l ON m.lost_item_id = l.item_string_id
            LEFT JOIN items f ON m.found_item_id = f.item_string_id
            LEFT JOIN users u_lost ON l.user_string_id = u_lost.user_string_id
            LEFT JOIN users u_found ON f.user_string_id = u_found.user_string_id
            WHERE $where_clause
            ORDER BY m.created_at DESC";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
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
    
    // Enable error logging
    error_log("=== ADMIN UPDATE MATCH STARTED ===");
    
    $input = json_decode(file_get_contents('php://input'), true);
    error_log("Input: " . print_r($input, true));
    
    $match_id = intval($input['match_id'] ?? 0);
    $status = $input['status'] ?? '';
    $admin_notes = mysqli_real_escape_string($conn, $input['admin_notes'] ?? '');
    
    error_log("match_id: $match_id, status: $status");
    
    if (!$match_id || !in_array($status, ['confirmed', 'rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    
    mysqli_begin_transaction($conn);
    
    try {
        // FIXED: Correct SQL query with proper joins using item_string_id
        $get_match = mysqli_query($conn, "SELECT m.*, 
                                         l.user_string_id as lost_owner, 
                                         f.user_string_id as found_owner,
                                         l.title as lost_title, 
                                         f.title as found_title,
                                         l.item_string_id as lost_string, 
                                         f.item_string_id as found_string
                                         FROM matches m
                                         LEFT JOIN items l ON m.lost_item_id = l.id
                                         LEFT JOIN items f ON m.found_item_id = f.id
                                         WHERE m.id = $match_id");
        
        if (!$get_match) {
            throw new Exception("Match query failed: " . mysqli_error($conn));
        }
        
        $match = mysqli_fetch_assoc($get_match);
        error_log("Match data: " . print_r($match, true));
        
        if (!$match) {
            throw new Exception("Match not found");
        }
        
        // FIXED: Correct array access syntax - removed quotes around array key
        $get_item = mysqli_query($conn, "SELECT * FROM items WHERE item_string_id = '" . mysqli_real_escape_string($conn, $match['lost_item_id']) . "'");
        
        if (!$get_item) {
            throw new Exception("Item query failed: " . mysqli_error($conn));
        }
        
        $item = mysqli_fetch_assoc($get_item);
        
        // Update match status
        $update_match = "UPDATE matches SET status = '$status' WHERE id = $match_id";
        if (!mysqli_query($conn, $update_match)) {
            throw new Exception("Match update failed: " . mysqli_error($conn));
        }
        $escaped_lost_item_id = mysqli_real_escape_string($conn, $match['lost_item_id']);
        $escaped_found_item_id = mysqli_real_escape_string($conn, $match['found_item_id']);
        
        if ($status === 'confirmed') {
            // Update both items status
            $update_lost = "UPDATE items SET status = 'matching' WHERE item_string_id = '$escaped_lost_item_id'";
            $update_found = "UPDATE items SET status = 'matching' WHERE item_string_id = '$escaped_found_item_id'";
            
            if (!mysqli_query($conn, $update_lost) || !mysqli_query($conn, $update_found)) {
                throw new Exception("Item update failed: " . mysqli_error($conn));
            }

            $notif_title = "Match Confirmed! ✅";
            $notif_message = "Your item '{$item['title']}' has been matched with a " . 
                            ($match['lost_owner'] == $item['user_string_id'] ? 'found' : 'lost') . 
                            " item. Please check your matches.";
            
            // Notify lost item owner
            if (!empty($match['owner_of_item'])) {
                $notif_lost = "INSERT INTO notifications 
                              (user_string_id, type, title, message, reference_id, created_at, is_read) 
                              VALUES (
                                  '" . mysqli_real_escape_string($conn, $match['lost_owner']) . "', 
                                  'match_confirmed', 
                                  '$notif_title', 
                                  '" . mysqli_real_escape_string($conn, $notif_message) . "', 
                                  '$match_id', 
                                  NOW(), 
                                  0
                              )";
                if (!mysqli_query($conn, $notif_lost)) {
                    error_log("Lost owner notification failed: " . mysqli_error($conn));
                }
            }
            
            // Notify found item owner
            if (!empty($match['owner_of_item'])) {
                $notif_found = "INSERT INTO notifications 
                               (user_string_id, type, title, message, reference_id, created_at, is_read) 
                               VALUES (
                                   '" . mysqli_real_escape_string($conn, $match['found_owner']) . "', 
                                   'match_confirmed', 
                                   '$notif_title', 
                                   '" . mysqli_real_escape_string($conn, $notif_message) . "', 
                                   '$match_id', 
                                   NOW(), 
                                   0
                               )";
                if (!mysqli_query($conn, $notif_found)) {
                    error_log("Found owner notification failed: " . mysqli_error($conn));
                }
            }
        }
        if (function_exists('logAdminAction') && isset($input['admin_full_name'])) {
            logAdminAction($conn, $input['admin_full_name'], "update_match", 
                          "Match #$match_id $status", $admin_identifier);
        }
        
        mysqli_commit($conn);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Match ' . $status . ' successfully',
            'match_id' => $match_id,
            'new_status' => $status
        ]);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Match update error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    error_log("=== ADMIN UPDATE MATCH ENDED ===");
    exit;
}

else if ($action === 'admin-get-users') {
    header('Content-Type: application/json; charset=utf-8');
    
    $admin_identifier = $_GET['admin_id'] ?? $_GET['admin_email'] ?? $_GET['admin_string_id'] ?? '';
    $role = isset($_GET['role']) ? $_GET['role'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    

    
    $where = ["1=1"];
    
    if (!empty($role)) {
        $role = mysqli_real_escape_string($conn, $role);
        $where[] = "role = '$role'";
    }
    
    if (!empty($search)) {
        $search = mysqli_real_escape_string($conn, $search);
        $where[] = "(full_name LIKE '%$search%' OR student_id LIKE '%$search%' OR phone LIKE '%$search%')";
    }
    
    $where_clause = implode(' AND ', $where);
    
    $sql = "SELECT u.*, 
            (SELECT COUNT(*) FROM items WHERE user_string_id = u.user_string_id) as items_count,
            (SELECT COUNT(*) FROM item_claims WHERE claimant_string_id = u.user_string_id) as claims_count
            FROM users u
            WHERE $where_clause
            ORDER BY u.created_at DESC";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
    
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        unset($row['password_hash']);
        $users[] = $row;
    }
    
    echo json_encode(['success' => true, 'users' => $users]);
    exit;
}

else if ($action === 'admin-update-user') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    
    header('Content-Type: application/json; charset=utf-8');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $admin_identifier = $input['admin_id'] ?? $input['admin_email'] ?? $input['admin_string_id'] ?? '';
    $user_id = intval($input['user_id'] ?? 0);
    $full_name = mysqli_real_escape_string($conn, $input['full_name'] ?? '');
    $student_id = mysqli_real_escape_string($conn, $input['student_id'] ?? '');
    $phone = mysqli_real_escape_string($conn, $input['phone'] ?? '');
    $role = mysqli_real_escape_string($conn, $input['role'] ?? '');
    $password = $input['password'] ?? '';

    
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit;
    }
    
    $updates = [];
    if (!empty($full_name)) $updates[] = "full_name = '$full_name'";
    if (!empty($student_id)) $updates[] = "student_id = '$student_id'";
    if (!empty($phone)) $updates[] = "phone = '$phone'";
    if (!empty($role)) $updates[] = "role = '$role'";
    if (!empty($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $updates[] = "password_hash = '$password_hash'";
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'No updates']);
        exit;
    }
    
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = $user_id";
    
    if (mysqli_query($conn, $sql)) {
        logAdminAction($conn, $verify['admin']['full_name'], "update_user", "Updated user #$user_id");
        echo json_encode(['success' => true, 'message' => 'User updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
    exit;
}

else if ($action === 'admin-delete-user') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    
    header('Content-Type: application/json; charset=utf-8');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $admin_identifier = $input['admin_id'] ?? $input['admin_email'] ?? $input['admin_string_id'] ?? '';
    $user_id = intval($input['user_id'] ?? 0);
    

    
    if (!$user_id || $verify['admin']['id'] == $user_id) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete yourself']);
        exit;
    }
    
    mysqli_begin_transaction($conn);
    $get_user = mysqli_query($conn, "SELECT user_string_id, full_name FROM users WHERE id = $user_id");
    $user = mysqli_fetch_assoc($get_user);
    mysqli_query($conn, "DELETE FROM notifications WHERE user_string_id = '{$user['user_string_id']}'");
    mysqli_query($conn, "DELETE FROM item_claims WHERE claimant_string_id = '{$user['user_string_id']}'");
    
    $items = mysqli_query($conn, "SELECT item_string_id FROM items WHERE user_string_id = '{$user['user_string_id']}'");
    while ($item = mysqli_fetch_assoc($items)) {
        mysqli_query($conn, "DELETE FROM matches WHERE lost_item_id = '{$item['item_string_id']}' OR found_item_id = '{$item['item_string_id']}'");
    }
    mysqli_query($conn, "DELETE FROM items WHERE user_string_id = '{$user['user_string_id']}'");
    
    mysqli_query($conn, "DELETE FROM users WHERE id = $user_id");
    
    logAdminAction($conn, $verify['admin']['full_name'], "delete_user", "Deleted user #$user_id");
    
    mysqli_commit($conn);
    
    echo json_encode(['success' => true, 'message' => 'User deleted']);
    exit;
}
else if ($action === 'admin-send-message') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    
    header('Content-Type: application/json; charset=utf-8');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $admin_identifier = $input['admin_id'] ?? $input['admin_email'] ?? $input['admin_string_id'] ?? '';
    $user_id = mysqli_real_escape_string($conn, $input['user_id'] ?? '');
    $subject = mysqli_real_escape_string($conn, $input['subject'] ?? 'Message from Admin');
    $message = mysqli_real_escape_string($conn, $input['message'] ?? '');
    

    
    if (empty($user_id) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'User ID and message required']);
        exit;
    }
    $notif_sql = "INSERT INTO notifications 
                 (user_string_id, type, title, message, admin_notes, created_at, is_read) 
                 VALUES ('$user_id', 'admin_message', '$subject', '$message', 
                         'Sent by {$verify['admin']['full_name']}', NOW(), 0)";
    
    if (mysqli_query($conn, $notif_sql)) {
        logAdminAction($conn, $verify['admin']['full_name'], "send_message", 
                      "Sent message to $user_id", $user_id);
        
        echo json_encode(['success' => true, 'message' => 'Message sent']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send']);
    }
    exit;
}
else if ($action === 'admin-get-logs') {
    header('Content-Type: application/json; charset=utf-8');
    
    $admin_identifier = $_GET['admin_id'] ?? $_GET['admin_email'] ?? $_GET['admin_string_id'] ?? '';
    
    
    $result = mysqli_query($conn, "SELECT * FROM admin_logs ORDER BY created_at DESC LIMIT 100");
    
    $logs = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $logs[] = $row;
    }
    
    echo json_encode(['success' => true, 'logs' => $logs]);
    exit;
}
else if ($action === 'admin-login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    
    header('Content-Type: application/json; charset=utf-8');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $student_id = mysqli_real_escape_string($conn, $input['student_id'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($student_id) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Student ID and password required']);
        exit;
    }
    
    $result = mysqli_query($conn, "SELECT * FROM users WHERE student_id = '$student_id'");
    
    if (!$result || mysqli_num_rows($result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit;
    }
    
    $user = mysqli_fetch_assoc($result);
    
    if (password_verify($password, $user['password_hash'])) {
        if ($user['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Not an admin']);
            exit;
        }
        
        unset($user['password_hash']);
        
        logAdminAction($conn, $user['full_name'], "login", "Admin logged in", $user['user_string_id']);
        
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid admin action']);
exit;
?>