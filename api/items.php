<?php
if ($action === 'report-lost-item') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        err('Use POST method only', 405);
    }
    try{
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

        for ($i = 0; $i < $file_count; $i++) {
            $error_code = $errors[$i] ?? UPLOAD_ERR_NO_FILE;
            if ($error_code !== UPLOAD_ERR_OK) {
                continue;
            }

            $original_name = $names[$i] ?? "file_$i";
            $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed_ext)) {
               continue;
            }

            $file_size = $sizes[$i] ?? 0;
            if ($file_size > $max_size_per_file) {
               continue;
            }
            $filename = 'lost_' . time() . '_' . uniqid() . '.' . $ext;
            $target_path = $upload_dir . $filename;
            $tmp_name = $tmp_names[$i] ?? '';

            if ($tmp_name && move_uploaded_file($tmp_name, $target_path)) {
                $image_paths[] = $target_path;
            }
        }
    }
    if ($type === 'found' && empty($image_paths)) {
        err('At least one valid image is required for found items', 400);
    }
    $image_path_value = empty($image_paths)
        ? 'NULL'
        : "'" . mysqli_real_escape_string($conn, implode('|', $image_paths)) . "'";

    $item_string_id = generateUniqueItemStringId($conn);
    $sql = "INSERT INTO items 
            (item_string_id, type, title, description, location,  image_path,
             reporter_name, reporter_phone, user_string_id, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?,  ?, ?, 'admin_approval', NOW())";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        err('Database prepare error', 500);
    }

    mysqli_stmt_bind_param(
        $stmt,
        "sssssssss",
        $item_string_id,
        $type,
        $title,
        $description,
        $location,
        $image_path_value,
        $reporter_name,
        $reporter_phone,
        $user_string_id
    );

    if (mysqli_stmt_execute($stmt)) {
        $new_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        out([
            'success'         => true,
            'message'         => 'Item reported successfully',
            'id'              => $new_id,
            'item_string_id'  => $item_string_id,
            'uploaded_images' => count($image_paths),
        ], 201);
    } else {
        $error = mysqli_stmt_error($stmt);
        err("Database insert failed: $error", 500);
    }
    } catch (Exception $e) {

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Server error - please try again later',
        ]);
    }
    exit;
}

else if ($action === 'update-item') {

    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed – Use POST']);
        exit;
    } 
    $item_id = trim($_POST['item_string_id'] ?? '');
    $user_string_id = trim($_POST['user_string_id'] ?? '');

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $category    = trim($_POST['category'] ?? 'other');

    $kept_images_json   = $_POST['kept_images']   ?? '[]';
    $removed_images_json = $_POST['removed_images'] ?? '[]';
 
    if (!$item_id || empty($item_id) || empty($user_string_id) || empty($title) || empty($description)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing or invalid required fields (item_id, user_string_id, title, description)',
            'received_item_id' => $item_id,
            'received_user' => $user_string_id
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT id, user_string_id, image_path, status 
        FROM items 
        WHERE item_string_id = ?
    ");
    $stmt->bind_param("s", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        $stmt->close();
        exit;
    }

    $item = $result->fetch_assoc();
    $stmt->close();

    if ($item['user_string_id'] !== $user_string_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not own this item']);
        exit;
    }

    if ($item['status'] == 'pending') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => ' pending items can/t be edited']);
        exit;
    }

    $kept_images   = json_decode($kept_images_json, true)   ?? [];
    $removed_images = json_decode($removed_images_json, true) ?? [];

    if (!is_array($kept_images))   $kept_images   = [];
    if (!is_array($removed_images)) $removed_images = [];
 
    $upload_dir = 'uploads/items/';
    $new_image_paths = [];

    if (!empty($_FILES['new_images']['name'][0]) && is_array($_FILES['new_images']['name'])) {

        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $max_size = 5 * 1024 * 1024;

        foreach ($_FILES['new_images']['tmp_name'] as $idx => $tmp_name) {
            if ($_FILES['new_images']['error'][$idx] !== UPLOAD_ERR_OK) continue;

            $file_name = $_FILES['new_images']['name'][$idx];
            $file_size = $_FILES['new_images']['size'][$idx];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed) || $file_size > $max_size) continue;

            $new_filename = 'item_' . $item_id . '_' . time() . '_' . uniqid() . '.' . $ext;
            $destination = $upload_dir . $new_filename;

            if (move_uploaded_file($tmp_name, $destination)) {
                $new_image_paths[] = $destination;
            }
        }
    }  
    $final_paths = array_merge($kept_images, $new_image_paths);
    $final_image_path_string = implode('|', array_filter($final_paths));
 
    foreach ($removed_images as $old_path) {
        $old_path = trim($old_path);
        if ($old_path && file_exists($old_path) && is_file($old_path)) {
            @unlink($old_path);
        }
    } 
    $stmt = $conn->prepare("
        UPDATE items SET 
            title       = ?,
            description = ?,
            location    = ?,
            category    = ?,
            image_path  = ?
        WHERE item_string_id = ?
    ");

    $stmt->bind_param("ssssss", $title, $description, $location, $category, $final_image_path_string, $item_id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Item updated successfully',
            'item_id' => $item_id,
            'image_count' => count($final_paths)
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error',
            'error' => $stmt->error
        ]);
    }

    $stmt->close();
    exit;
}

else if ($action === 'delete-item') {

    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed - Use POST']);
        exit;
    }

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON input',
        ]);
        exit;
    }
    $item_identifier = trim($input['item_string_id'] ?? $input['item_id'] ?? '');
    $user_string_id  = trim($input['user_string_id'] ?? '');


    if (empty($item_identifier) || empty($user_string_id)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Item identifier and User ID required',
            'received_item' => $item_identifier,
            'received_user' => $user_string_id
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT id, user_string_id, image_path, status 
        FROM items 
        WHERE item_string_id = ?
    ");
    $stmt->bind_param("s", $item_identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        exit;
    }

    $item = $result->fetch_assoc();
    $stmt->close();

    if ($item['user_string_id'] !== $user_string_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not own this item']);
        exit;
    }

    if ($item['status'] == 'pending') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'pending items can[t be deleted']);
        exit;
    }
    if (!empty($item['image_path'])) {
        $paths = explode('|', $item['image_path']);
        foreach ($paths as $p) {
            $p = trim($p);
            if ($p && file_exists($p)) {
                @unlink($p);
            }
        }
    }
    $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
    $stmt->bind_param("i", $item['id']);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Item deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error',
            'error' => $conn->error
        ]);
    }

    $stmt->close();
    exit;
}

else if ($action === 'get-found-items') {
    
    $user_string_id = isset($_GET['user_string_id']) ? cl($_GET['user_string_id']) : '';
    if(empty($user_string_id)){

    }
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

    out(['success' => true, 'items' => $items],201);
}

else if ($action === 'get-lost-items') {

    $sql = "SELECT *, item_string_id, user_string_id FROM items 
            WHERE type = 'lost' AND status = 'open'
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

else if ($action === 'submit-item-claim') {

    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        exit;
    }
    $item_id            = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
    $user_string_id     = trim($_POST['user_string_id'] ?? '');
    $description        = trim($_POST['description'] ?? '');
    $lost_location      = trim($_POST['lost_location'] ?? '');

    if (!$item_id || $item_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Valid item ID required']);
        exit;
    }

    if (empty($user_string_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit;
    }

    if (empty($description) || strlen($description) < 20) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Description too short (min 20 characters)']);
        exit;
    }

    $check = $conn->prepare("
        SELECT id FROM item_claims 
        WHERE item_id = ? AND claimant_string_id = ? AND status = 'pending'
        LIMIT 1
    ");
    $check->bind_param("is", $item_id, $user_string_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'You already have a pending claim for this item']);
        exit;
    }
    $check->close();

    $upload_dir = 'uploads/claims/';
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size = 5 * 1024 * 1024; 
    $image_paths = [];

    if (!empty($_FILES['images']['name'][0])) {
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $file_name = $_FILES['images']['name'][$key];
            $file_tmp  = $_FILES['images']['tmp_name'][$key];
            $file_size = $_FILES['images']['size'][$key];
            $file_type = $_FILES['images']['type'][$key];

            if ($file_size > $max_size) {
                continue;
            }

            if (!in_array($file_type, $allowed_types)) {
                continue;
            }

            $ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_name = 'claim_' . time() . '_' . uniqid() . '.' . $ext;
            $dest_path = $upload_dir . $new_name;

            if (move_uploaded_file($file_tmp, $dest_path)) {
                $image_paths[] = $dest_path;
            }
        }
    }
    $conn->begin_transaction();

    try { 
        $stmt = $conn->prepare("
            INSERT INTO item_claims 
            (item_id, claimant_string_id, description, lost_location, status, created_at)
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->bind_param("isss", $item_id, $user_string_id, $description, $lost_location);
        $stmt->execute();
        $claim_id = $conn->insert_id;
        $stmt->close();

        if (!empty($image_paths)) {
            $stmt = $conn->prepare("
                INSERT INTO item_claim_attachments 
                (claim_id, file_path, original_name, mime_type)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($image_paths as $path) {
                $original = basename($path);
                $mime = mime_content_type($path) ?: 'image/jpeg';
                $stmt->bind_param("isss", $claim_id, $path, $original, $mime);
                $stmt->execute();
            }
            $stmt->close();
        }

        $conn->commit(); 

        echo json_encode([
            'success' => true,
            'message' => 'Claim submitted successfully. It will be reviewed soon.',
            'claim_id' => $claim_id
        ]);

    } catch (Exception $e) {
        $conn->rollback();

        foreach ($image_paths as $path) {
            if (file_exists($path)) @unlink($path);
        }

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Server error while saving claim',
             
        ]);
    }

    exit;
}

else if ($action === 'report-found-match') {
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Use POST']);
        exit;
    }

    try { 
        $input = [];
        $content_type = $_SERVER['CONTENT_TYPE'] ?? '';

        if (strpos($content_type, 'multipart/form-data') !== false) {
            $input['lost_item_string_id'] = $_POST['lost_item_string_id'] ?? '';
            $input['finder_name']         = $_POST['finder_name']         ?? '';
            $input['finder_phone']        = $_POST['finder_phone']        ?? '';
            $input['finder_message']      = $_POST['finder_message']      ?? '';
            $input['user_string_id']      = $_POST['user_string_id']      ?? '';
            $input['title']                = $_POST['title']               ?? '';
            $input['property']             = $_POST['property']            ?? '';
            $input['date_and_time']        = $_POST['date_and_time']       ?? '';
            $input['location']              = $_POST['location']            ?? '';
        } else {
            $json = file_get_contents('php://input');
            $input = json_decode($json, true) ?? [];
            if (empty($input)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid input']);
                exit;
            }
        } 
        $lost_item_string_id = trim($input['lost_item_string_id'] ?? '');
        $finder_name         = trim($input['finder_name'] ?? '');
        $finder_phone        = trim($input['finder_phone'] ?? '');
        $finder_message      = trim($input['finder_message'] ?? '');
        $user_string_id      = trim($input['user_string_id'] ?? '');
        $title               = cl($input['title'] ?? '');
        $location            = cl($input['location'] ?? '');
        $property            = cl($input['property'] ?? '');
        $found_date          = cl($input['date_and_time'] ?? '');
 
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
        mysqli_begin_transaction($conn); 
        $stmt = mysqli_prepare($conn, "SELECT id, user_string_id as owner_id FROM items WHERE item_string_id = ? AND type = 'lost'");
        mysqli_stmt_bind_param($stmt, "s", $lost_item_string_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) === 0) {
            mysqli_rollback($conn);
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Lost item not found']);
            exit;
        }
        
        $lost_item = mysqli_fetch_assoc($result);
        $lost_man_id = getidforlost($conn , $lost_item_string_id); 
        mysqli_stmt_close($stmt); 
        $image_paths = [];
        $upload_dir = 'uploads/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 5 * 1024 * 1024;

        if (!empty($_FILES['image']['name'][0])) {
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
        if (empty($image_paths)) {
            mysqli_rollback($conn);
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'At least one image is required']);
            exit;
        }

        $image_path_value = implode('|', $image_paths);

        $new_item_string_id = generateUniqueItemStringId($conn);

        $sql = "INSERT INTO items 
                (item_string_id, found_item_property, type, title, description, location, image_path,
                 reporter_name, reporter_phone, status, when_lost, user_string_id, created_at)
                VALUES 
                (?, ?, 'found', ?, ?, ?, ?, ?, ?, 'admin_approval', ?, ?, NOW())";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . mysqli_error($conn));
        }

        mysqli_stmt_bind_param(
            $stmt,
            "ssssssssss", 
            $new_item_string_id,
            $property,
            $title,
            $finder_message,
            $location,
            $image_path_value,
            $finder_name,
            $finder_phone,
            $found_date,
            $user_string_id
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to create found item: ' . mysqli_stmt_error($stmt));
        }

        $new_found_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
 

        $match_confidence = 80;
 
        $match_sql = "INSERT INTO matches 
                      (lost_item_id, found_item_id, match_confidence, status, created_by, owner_of_item, created_at)
                      VALUES (?, ?, ?, 'pending', ?, ?, NOW())";

        $match_stmt = mysqli_prepare($conn, $match_sql);
        if (!$match_stmt) {
            throw new Exception('Failed to prepare match statement: ' . mysqli_error($conn));
        } 
        mysqli_stmt_bind_param(
            $match_stmt,
            "ssiss",
            $lost_item_string_id,
            $new_item_string_id,
            $match_confidence,
            $user_string_id,
            $lost_man_id
        );

        if (!mysqli_stmt_execute($match_stmt)) {
            throw new Exception('Failed to create match: ' . mysqli_stmt_error($match_stmt));
        }

        mysqli_stmt_close($match_stmt);
 
        mysqli_commit($conn);
 
        echo json_encode([
            'success'         => true,
            'message'         => 'Thank you for reporting! Admin will verify and connect you with the owner.',
            'id'              => $lost_item_string_id,
            'item_string_id'  => $new_item_string_id,
            'image_count'     => count($image_paths),
            'senderid'        => $user_string_id
        ]);

    } catch (Exception $e) { 
        mysqli_rollback($conn);
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage()
        ]);
    }

    exit;
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
    
    try { 
        $user_string_id = mysqli_real_escape_string($conn, trim($input['user_string_id'] ?? ''));
        $item_string_id = mysqli_real_escape_string($conn, trim($input['item_string_id'] ?? ''));
        $message = mysqli_real_escape_string($conn, trim($input['message'] ?? ''));
 
        if (empty($user_string_id) || empty($item_string_id) || empty($message)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID, Item ID, and message are required']);
            exit;
        } 
        if (strlen($message) < 20) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Message must be at least 20 characters']);
            exit;
        } 
        $user_query = "SELECT id FROM users WHERE user_string_id = ?";
        $stmt = mysqli_prepare($conn, $user_query);
        mysqli_stmt_bind_param($stmt, "s", $user_string_id);
        mysqli_stmt_execute($stmt);
        $user_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($user_result) === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        
        $user_data = mysqli_fetch_assoc($user_result);
        $user_id = $user_data['id']; 
        $item_query = "SELECT id, status, user_string_id as owner_string_id 
                      FROM items 
                      WHERE id = ? AND type = 'found'";
        $stmt = mysqli_prepare($conn, $item_query);
        mysqli_stmt_bind_param($stmt, "s", $item_string_id);
        mysqli_stmt_execute($stmt);
        $item_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($item_result) === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Found item not found']);
            exit;
        }
        
        $item_data = mysqli_fetch_assoc($item_result);
        $item_id = $item_data['id']; 
        if ($item_data['owner_string_id'] === $user_string_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'You cannot claim your own item']);
            exit;
        } 
        if ($item_data['status'] == 'pending') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'This item is not available for claiming']);
            exit;
        }
 
        $check_query = "SELECT id, status FROM item_claims 
                       WHERE item_id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "ss", $item_string_id, $user_string_id);
        mysqli_stmt_execute($stmt);
        $check_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $existing = mysqli_fetch_assoc($check_result);
            http_response_code(409);
            echo json_encode([
                'success' => false, 
                'message' => 'You have already requested this item. Status: ' . $existing['status']
            ]);
            exit;
        }
 
        mysqli_begin_transaction($conn);
        $insert_sql = "INSERT INTO item_claims (item_id, user_id, message, status, created_at)
                      VALUES (?, ?, ?, 'pending', NOW())";
        
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "sss", $item_string_id, $user_string_id, $message);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to create claim: ' . mysqli_stmt_error($stmt));
        }
        
        $claim_id = mysqli_insert_id($conn);
        $update_sql = "UPDATE items SET status = 'pending' WHERE item_string_id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "s", $item_string_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to update item status');
        }
        mysqli_commit($conn);
        echo json_encode([
            'success' => true, 
            'message' => 'Access request submitted successfully',
            'claim_id' => $claim_id,
            'status' => 'pending'
        ]);

    } catch (Exception $e) {
        mysqli_rollback($conn);

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Server error - please try again later'
        ]);
    }
    exit;
}

?>