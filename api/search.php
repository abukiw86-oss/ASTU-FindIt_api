<?php
if ($action === 'simple-search') {
    header('Content-Type: application/json; charset=utf-8');
    
    $query = isset($_GET['query']) ? trim($_GET['query']) : '';
    $type = isset($_GET['type']) ? trim($_GET['type']) : '';
    $user_id = isset($_GET['user_id']) ? mysqli_real_escape_string($conn, $_GET['user_id']) : '';
    
    if (empty($query)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Search query is required'
        ]);
        exit;
    }
    
    $query = mysqli_real_escape_string($conn, $query);
    $type = mysqli_real_escape_string($conn, $type);
    
    $sql = "SELECT i.*, 
            u.full_name as owner_name,
            u.student_id,
            u.phone as owner_phone
            FROM items i
            LEFT JOIN users u ON i.user_string_id = u.user_string_id
            WHERE (i.title LIKE '%$query%' 
                   OR i.description LIKE '%$query%' 
                   OR i.location LIKE '%$query%'
                   OR i.reporter_name LIKE '%$query%')";
    
    if (!empty($type) && $type !== 'all') {
        $sql .= " AND i.type = '$type'";
    }
    
    $sql .= " AND i.status NOT IN ('rejected')";
    
    $sql .= " ORDER BY i.created_at DESC LIMIT 50";
    
    error_log("Simple Search SQL: $sql");
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        error_log("Search error: " . mysqli_error($conn));
        echo json_encode([
            'success' => false, 
            'message' => 'Database error occurred'
        ]);
        exit;
    }
    
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $image_list = [];
        $first_image = null;
        
        if (!empty($row['image_path']) && $row['image_path'] != 'NULL') {
            // Remove quotes if present
            $image_path = $row['image_path'];
            if (strpos($image_path, "'") === 0) {
                $image_path = trim($image_path, "'");
            }
            $raw_images = explode('|', $image_path);
            foreach ($raw_images as $img) {
                $img = trim($img);
                if (!empty($img) && $img != 'NULL') {
                    $image_list[] = $img;
                }
            }
            $first_image = !empty($image_list) ? $image_list[0] : null;
        }
        
        $row['image_path'] = $first_image; 
        $row['image_list'] = $image_list;  
        $row['image_count'] = count($image_list);
        
        $row['date_formatted'] = date('M d, Y', strtotime($row['created_at']));
        
        $items[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'count' => count($items),
        'query' => $query
    ]);
    exit;
}

if ($action === 'simple-item-details') {
    header('Content-Type: application/json; charset=utf-8');
    
    $item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($item_id <= 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Item ID required'
        ]);
        exit;
    }
    
    $sql = "SELECT i.*, 
            u.full_name as owner_name,
            u.student_id,
            u.phone as owner_phone
            FROM items i
            LEFT JOIN users u ON i.user_string_id = u.user_string_id
            WHERE i.id = $item_id";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Item not found'
        ]);
        exit;
    }
    
    $item = mysqli_fetch_assoc($result);
    $image_list = [];
    $first_image = null;
    
    if (!empty($item['image_path']) && $item['image_path'] != 'NULL') {
        $image_path = $item['image_path'];
        if (strpos($image_path, "'") === 0) {
            $image_path = trim($image_path, "'");
        }
        $raw_images = explode('|', $image_path);
        foreach ($raw_images as $img) {
            $img = trim($img);
            if (!empty($img) && $img != 'NULL') {
                $image_list[] = $img;
            }
        }
        
        $first_image = !empty($image_list) ? $image_list[0] : null;
    }
    
    $item['image_path'] = $first_image; 
    $item['image_list'] = $image_list; 
    $item['image_count'] = count($image_list); 
    
    echo json_encode([
        'success' => true,
        'item' => $item
    ]);
    exit;
}
?>