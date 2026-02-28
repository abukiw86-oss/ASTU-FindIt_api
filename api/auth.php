<?php
if ($action === 'register') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Use POST', 405);

    $student_id     = cl($input['student_id'] ?? '');
    $password  = $input['password'] ?? '';
    $full_name = cl($input['full_name'] ?? '');
    $phone     = cl($input['phone'] ?? '');

    if (!$student_id || !$password || !$full_name || !$phone) {

    }

    if (strlen($password) < 6) {
        err('Password must be at least 6 characters');
    }

    $r = mysqli_query($conn, "SELECT id FROM users WHERE student_id = '$student_id'");
    if (mysqli_num_rows($r) > 0) {
        err('Email already registered', 409);
    }

    $user_string_id = generateUniqueStringId($conn);
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (user_string_id, student_id, password_hash, full_name, phone, role, created_at)
            VALUES ('$user_string_id', '$student_id', '$hash', '$full_name', '$phone', 'student', NOW())";

    if (mysqli_query($conn, $sql)) {
        $new_id = mysqli_insert_id($conn);
        out([
            'success' => true, 
            'message' => 'Registered successfully',
            'user' => [
                'id' => $new_id,
                'user_string_id' => $user_string_id,
                'student_id' => $student_id,
                'full_name' => $full_name,
                'phone' => $phone,
                'role' => 'student'
            ]
        ], 201);
    } else {
        err('Database error: ' . mysqli_error($conn), 500);
    }

}
else if ($action === 'login') {

    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed – Use POST']);
        exit;
    }

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }

    $student_id = trim($input['student_id'] ?? '');
    $password   = $input['password'] ?? '';

    if (empty($student_id) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Student ID and password are required']);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE student_id = ? LIMIT 1");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Incorrect student ID or password']);
        $stmt->close();
        exit;
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    if (!password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Incorrect student ID or password']);
        exit;
    }

    $safe_user = [
        'user_string_id' => $user['user_string_id'],
        'student_id'     => $user['student_id'],
        'full_name'      => $user['full_name'],
        'email'          => $user['email'],
        'phone'          => $user['phone'] ?? null,
        'role'           => $user['role'],
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Logged in successfully',
        'user'    => $safe_user
    ]);

    exit;
}

else if ($action === 'logout') {

    out(['success' => true, 'message' => 'Logged out successfully']);

}

else if ($action === 'whoami') {

    err('Not supported without authentication', 403);

}
?>