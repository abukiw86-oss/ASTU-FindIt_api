<?php
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
function generateUniqueItemStringId($conn) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789bfkerfbeo94ry3048y2304y340823ywoajfihfaobf';
    $id = '';
    $maxAttempts = 100;
    $attempts = 0;
    
    do {
        $id = '';
        for ($i = 0; $i < 12; $i++) {
            $id .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        $check = mysqli_query($conn, "SELECT id FROM items WHERE item_string_id = '$id'");
        $attempts++;
        
        if ($attempts > $maxAttempts) {
            $id = strtoupper(substr(md5(uniqid()), 0, 12));
            break;
        }
    } while (mysqli_num_rows($check) > 0);
    
    return $id;
}

function getItemIdFromString($conn, $item_string_id) {
    if (empty($item_string_id)) return 0;
    $query = mysqli_query($conn, "SELECT id FROM items WHERE item_string_id = '" . mysqli_real_escape_string($conn, $item_string_id) . "'");
    if ($query && mysqli_num_rows($query) > 0) {
        $item = mysqli_fetch_assoc($query);
        return $item['id'];
    }
    return 0;
}
function generateUniqueStringId($conn) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789befowufboewfwoe3y904923y04fkdfjhbjfisdvfivf';
    $id = '';
    $maxAttempts = 100;
    $attempts = 0;
    
    do {
        $id = '';
        for ($i = 0; $i < 8; $i++) {
            $id .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        $check = mysqli_query($conn, "SELECT id FROM users WHERE user_string_id = '$id'");
        $attempts++;
        
        if ($attempts > $maxAttempts) {
            $id = strtoupper(substr(md5(uniqid()), 0, 8));
            break;
        }
    } while (mysqli_num_rows($check) > 0);
    
    return $id;
}

function getUserIdFromString($conn, $user_string_id) {
    if (empty($user_string_id)) return 0;
    $query = mysqli_query($conn, "SELECT id FROM users WHERE user_string_id = '" . mysqli_real_escape_string($conn, $user_string_id) . "'");
    if ($query && mysqli_num_rows($query) > 0) {
        $user = mysqli_fetch_assoc($query);
        return $user['id'];
    }
    return 0;
}
?>