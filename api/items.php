<?php

if ($action === 'report-lost-item') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        err('Use POST method only', 405);
    }


    $type           = cl($_POST['type']           ?? '');
    $title          = cl($_POST['title']          ?? '');
    $description    = cl($_POST['description']    ?? '');
    $location       = cl($_POST['location']       ?? '');
    $category       = cl($_POST['category']       ?? 'other');
    $reporter_name  = cl($_POST['reporter_name']  ?? '');
    $reporter_phone = cl($_POST['reporter_phone'] ?? '');
    $user_string_id = cl($_POST['user_string_id'] ?? '');

    if (empty($user_string_id)) {
        err('User string ID is required. Please login.', 400);
    }
    if (!in_array($type, ['lost', 'found'])) {
        err("Invalid type: '$type'. Must be 'lost' or 'found'", 400);
    }
    if (empty($title) || empty($description)) {
        err('Title and description are required', 400);
    }
    if (empty($reporter_name) || empty($reporter_phone)) {
        err('Reporter name and phone are required', 400);
    }
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE user_string_id = ?");
    mysqli_stmt_bind_param($stmt, "s", $user_string_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 0) {
        err('Invalid user ID - user not found', 400);
    }
    mysqli_stmt_close($stmt);

    $image_paths = [];
    $upload_dir = 'uploads/';

    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            err('Failed to create upload directory', 500);
        }
    }

    if (!is_writable($upload_dir)) {
        err('Upload directory is not writable', 500);
    }

    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
    $max_size_per_file = 5 * 1024 * 1024; 

    if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
        $files = $_FILES['image'];

        $names     = is_array($files['name'])     ? $files['name']     : [$files['name'] ?? ''];
        $tmp_names = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name'] ?? ''];
        $errors    = is_array($files['error'])    ? $files['error']    : [$files['error'] ?? UPLOAD_ERR_NO_FILE];
        $sizes     = is_array($files['size'])     ? $files['size']     : [$files['size'] ?? 0];

        $file_count = count($names);
        error_log("Detected $file_count file(s) in 'image' field");

        for ($i = 0; $i < $file_count; $i++) {
            $error_code = $errors[$i] ?? UPLOAD_ERR_NO_FILE;
            if ($error_code !== UPLOAD_ERR_OK) {
                error_log("File #$i upload error code: $error_code");
                continue;
            }

            $original_name = $names[$i] ?? "file_$i";
            $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed_ext)) {
                error_log("File #$i skipped - invalid extension: $ext ($original_name)");
                continue;
            }

            $file_size = $sizes[$i] ?? 0;
            if ($file_size > $max_size_per_file) {
                error_log("File #$i skipped - too large: $file_size bytes ($original_name)");
                continue;
            }

            $filename = 'lost_' . time() . '_' . uniqid() . '.' . $ext;
            $target_path = $upload_dir . $filename;
            $tmp_name = $tmp_names[$i] ?? '';

            if ($tmp_name && move_uploaded_file($tmp_name, $target_path)) {
                $image_paths[] = $target_path;
                error_log("SUCCESS - Uploaded file #$i: $target_path (" . round($file_size / 1024, 1) . " KB)");
            } else {
                error_log("FAILED - move_uploaded_file for file #$i: $original_name (tmp: $tmp_name)");
            }
        }
    } else {
        error_log("No 'image' files received");
    }

    if ($type === 'found' && empty($image_paths)) {
        err('At least one valid image is required for found items', 400);
    }

    $image_path_value = empty($image_paths)
        ? 'NULL'
        : "'" . mysqli_real_escape_string($conn, implode('|', $image_paths)) . "'";
    $item_string_id = generateUniqueItemStringId($conn);

    $sql = "INSERT INTO items 
            (item_string_id, type, title, description, location, category, image_path,
             reporter_name, reporter_phone, user_string_id, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
       
        err('Database prepare error', 500);
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ssssssssss",
        $item_string_id,
        $type,
        $title,
        $description,
        $location,
        $category,
        $image_path_value,
        $reporter_name,
        $reporter_phone,
        $user_string_id
    );

    if (mysqli_stmt_execute($stmt)) {
        $new_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // Success response
        out([
            'success'         => true,
            'message'         => 'Item reported successfully',
            'id'              => $new_id,
            'item_string_id'  => $item_string_id,
            'uploaded_images' => count($image_paths),
        ], 201);
    } else {
        $error = mysqli_stmt_error($stmt);
    }

    exit;
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
else if ($action === 'get-lost-items') {

    $sql = "SELECT *, item_string_id, user_string_id FROM items 
            WHERE type = 'lost' 
            ORDER BY created_at DESC";

    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        err('Database error: ' . mysqli_error($conn), 500);
    }
    
    $items = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }

    out(['success' => true, 'items' => $items]);
}
else if ($action === 'get-found-items') {

    $user_string_id = isset($_GET['user_string_id']) ? cl($_GET['user_string_id']) : '';
    
    $sql = "SELECT f.*, f.item_string_id, f.user_string_id,
            CASE 
                WHEN f.status = 'claimed' THEN 'claimed'
                WHEN c.id IS NOT NULL AND c.status = 'approved' THEN 'accessible'
                WHEN c.id IS NOT NULL AND c.status = 'pending' THEN 'pending'
                ELSE 'restricted'
            END as access_level,
            c.status as claim_status,
            c.id as claim_id
            FROM items f
            LEFT JOIN item_claims c ON f.id = c.item_id AND c.user_id = (SELECT id FROM users WHERE user_string_id = '$user_string_id')
            WHERE f.type = 'found' 
            ORDER BY f.created_at DESC";

    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        err('Database error: ' . mysqli_error($conn), 500);
    }
    
    $items = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $is_founder = false;
        
        if (!empty($user_string_id) && isset($row['user_string_id']) && $row['user_string_id'] == $user_string_id) {
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
else if ($action === 'request-item-access') {

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
    $user_string_id = isset($input['user_string_id']) ? cl($input['user_string_id']) : '';
    
    $user_id = 0;
    if (!empty($user_string_id)) {
        $user_id = getUserIdFromString($conn, $user_string_id);
    }
    
    $item_string_id = isset($input['item_string_id']) ? cl($input['item_string_id']) : '';
    
    $item_id = 0;
    if (!empty($item_string_id)) {
        $item_id = getItemIdFromString($conn, $item_string_id);
    }
    
    $message = trim($input['message'] ?? '');

    if (!$user_id || !$item_id || !$message) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID and Item ID are required']);
        exit;
    }

    $item_check = mysqli_query($conn, "SELECT id, status FROM items WHERE id = $item_id AND type = 'found'");
    if (!$item_check || mysqli_num_rows($item_check) == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Found item not found']);
        exit;
    }
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
else if ($action === 'report-found-match') {
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Use POST']);
        exit;
    }
    $input = [];
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';

    if (strpos($content_type, 'multipart/form-data') !== false) {
        $input['lost_item_string_id'] = $_POST['lost_item_string_id'] ?? '';
        $input['finder_name']         = $_POST['finder_name']         ?? '';
        $input['finder_phone']        = $_POST['finder_phone']        ?? '';
        $input['finder_message']      = $_POST['finder_message']      ?? '';
        $input['user_string_id']      = $_POST['user_string_id']      ?? '';
    } else {
        $json = file_get_contents('php://input');
        $input = json_decode($json, true) ?? [];
        if (empty($input)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }
    }

    // Required fields
    $lost_item_string_id = trim($input['lost_item_string_id'] ?? '');
    $finder_name         = trim($input['finder_name'] ?? '');
    $finder_phone        = trim($input['finder_phone'] ?? '');
    $finder_message      = trim($input['finder_message'] ?? '');
    $user_string_id      = trim($input['user_string_id'] ?? '');

    if (empty($lost_item_string_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Lost item string ID is required']);
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
    if (empty($finder_message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please describe where/when you found it']);
        exit;
    }
    if (empty($user_string_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please login to continue']);
        exit;
    }

    $lost_item_id = getItemIdFromString($conn, $lost_item_string_id);
    if (!$lost_item_id) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Lost item not found']);
        exit;
    }

    // Verify lost item exists
    $stmt = mysqli_prepare($conn, "SELECT id FROM items WHERE id = ? AND type = 'lost'");
    mysqli_stmt_bind_param($stmt, "i", $lost_item_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Lost item not found']);
        exit;
    }
    mysqli_stmt_close($stmt);
    $image_paths = [];
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $max_size = 5 * 1024 * 1024;

    if (!empty($_FILES['image']['name'])) {
        $files = $_FILES['image'];

        if (!is_array($files['name'])) {
            $files = [
                'name'     => [$files['name']],
                'tmp_name' => [$files['tmp_name']],
                'error'    => [$files['error']],
                'size'     => [$files['size']],
            ];
        }

        foreach ($files['name'] as $key => $name) {
            if ($files['error'][$key] !== UPLOAD_ERR_OK) continue;

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) continue;

            if ($files['size'][$key] > $max_size) continue;

            $filename = 'found_' . time() . '_' . uniqid() . '.' . $ext;
            $target   = $upload_dir . $filename;

            if (move_uploaded_file($files['tmp_name'][$key], $target)) {
                $image_paths[] = $target;
            }
        }
    }

    $image_path_json = !empty($image_paths) ? json_encode($image_paths) : null;

    $new_item_string_id = generateUniqueItemStringId($conn);

    $title    = "Found: " . ($lost['title'] ?? 'Item');
    $location = $lost['location'] ?? 'Unknown';
    $category = $lost['category'] ?? 'other';

    $sql = "INSERT INTO items 
            (item_string_id, type, title, description, location, category, image_path,
             reporter_name, reporter_phone, status, user_string_id, created_at)
            VALUES 
            (?, 'found', ?, ?, ?, ?, ?, ?, ?, 'pending_match', ?,  NOW())";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param(
        $stmt,
        "sssssssss",
        $new_item_string_id,
        $title,
        $finder_message,
        $location,
        $category,
        $image_path_json,
        $finder_name,
        $finder_phone,
        $user_string_id
    );

    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create found item: ' . mysqli_stmt_error($stmt)
        ]);
        mysqli_stmt_close($stmt);
        exit;
    }

    $new_found_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    $match_sql = "INSERT INTO matches 
                  (lost_item_id, found_item_id, match_confidence, status, created_by, created_at)
                  VALUES (?, ?, ?, 'pending', ?, NOW())";

    $match_stmt = mysqli_prepare($conn, $match_sql);
    mysqli_stmt_bind_param(
        $match_stmt,
        "iiis",
        $lost_item_id,
        $new_found_id,
        $match_confidence,
        $user_string_id  
    );

    if (!mysqli_stmt_execute($match_stmt)) {
        error_log("Failed to create match entry: " . mysqli_stmt_error($match_stmt));
    }

    mysqli_stmt_close($match_stmt);

    echo json_encode([
        'success'        => true,
        'message'        => 'Thank you for reporting! Admin will verify and connect you with the owner.',
        'id'             => $new_found_id,
        'item_string_id' => $new_item_string_id,
        'image_count'    => count($image_paths),
        'senderid'       => $user_string_id
    ]);

    exit;
}


else if ($action === 'update-item') {

    if (ob_get_level()) ob_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Use POST']);
        exit;
    }

    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    
    $user_id = 0;
    if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
    } elseif (isset($_POST['user_string_id']) && !empty($_POST['user_string_id'])) {
        $user_string_id = cl($_POST['user_string_id']);
        $user_id = getUserIdFromString($conn, $user_string_id);
    }
    
    $title = isset($_POST['title']) ? mysqli_real_escape_string($conn, trim($_POST['title'])) : '';
    $description = isset($_POST['description']) ? mysqli_real_escape_string($conn, trim($_POST['description'])) : '';
    $location = isset($_POST['location']) ? mysqli_real_escape_string($conn, trim($_POST['location'])) : '';
    $category = isset($_POST['category']) ? mysqli_real_escape_string($conn, trim($_POST['category'])) : 'other';

    if (!$item_id || !$user_id || !$title || !$description) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    $check_item = mysqli_query($conn, "SELECT * FROM items WHERE id = $item_id");
    if (!$check_item || mysqli_num_rows($check_item) == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        exit;
    }

    $item = mysqli_fetch_assoc($check_item);
    
    $is_owner = false;
    if ($item['user_id'] && $item['user_id'] == $user_id) {
        $is_owner = true;
    } else {
        // Check by name (fallback)
        $user_check = mysqli_query($conn, "SELECT full_name FROM users WHERE id = $user_id");
        $user = mysqli_fetch_assoc($user_check);
        if ($user && $user['full_name'] == $item['reporter_name']) {
            $is_owner = true;
        }
    }

    if (!$is_owner) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to edit this item']);
        exit;
    }

    if ($item['status'] != 'pending') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Only pending items can be edited']);
        exit;
    }
    $image_path = $item['image_path']; 

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $allowed)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Only jpg, jpeg, png, gif allowed']);
            exit;
        }

        if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Image too large (max 5MB)']);
            exit;
        }
        if ($image_path && file_exists($image_path)) {
            unlink($image_path);
        }

        $filename = 'item_' . time() . '_' . uniqid() . '.' . $ext;
        $target = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image_path = $target;
            error_log("New image uploaded: $target");
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
            exit;
        }
    }

    // Update the item
    $sql = "UPDATE items SET 
            title = '$title',
            description = '$description',
            location = '$location',
            category = '$category',
            image_path = '$image_path'
            WHERE id = $item_id";

    error_log("Update SQL: $sql");

    if (mysqli_query($conn, $sql)) {
        echo json_encode([
            'success' => true,
            'message' => 'Item updated successfully',
            'item_id' => $item_id
        ]);
    } else {
        error_log("Database error: " . mysqli_error($conn));
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit;
}
else if ($action === 'delete-item') {

    if (ob_get_level()) ob_clean();
    
    header('Content-Type: application/json; charset=utf-8');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Use POST']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    $item_id = isset($input['item_id']) ? intval($input['item_id']) : 0;
    
    $user_id = 0;
    if (isset($input['user_id']) && !empty($input['user_id'])) {
        $user_id = intval($input['user_id']);
    } elseif (isset($input['user_string_id']) && !empty($input['user_string_id'])) {
        $user_string_id = cl($input['user_string_id']);
        $user_id = getUserIdFromString($conn, $user_string_id);
    }

    if (!$item_id || !$user_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Item ID and User ID required']);
        exit;
    }
    $check_item = mysqli_query($conn, "SELECT * FROM items WHERE id = $item_id");
    if (!$check_item || mysqli_num_rows($check_item) == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        exit;
    }

    $item = mysqli_fetch_assoc($check_item);
    
    $is_owner = false;
    if ($item['user_id'] && $item['user_id'] == $user_id) {
        $is_owner = true;
    } else {
        $user_check = mysqli_query($conn, "SELECT full_name FROM users WHERE id = $user_id");
        $user = mysqli_fetch_assoc($user_check);
        if ($user && $user['full_name'] == $item['reporter_name']) {
            $is_owner = true;
        }
    }

    if (!$is_owner) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this item']);
        exit;
    }

    if ($item['status'] != 'pending') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Only pending items can be deleted']);
        exit;
    }

    if ($item['image_path'] && file_exists($item['image_path'])) {
        unlink($item['image_path']);
    }

    $sql = "DELETE FROM items WHERE id = $item_id";

    if (mysqli_query($conn, $sql)) {
        echo json_encode([
            'success' => true,
            'message' => 'Item deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit;
}
?>