<?php
if ($action === 'register') {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Use POST', 405);

    $email     = cl($input['email'] ?? '');
    $password  = $input['password'] ?? '';
    $full_name = cl($input['full_name'] ?? '');
    $phone     = cl($input['phone'] ?? '');

    if (!$email || !$password || !$full_name || !$phone) {
        err('email, password, full_name and phone are required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        err('Invalid email format');
    }

    if (strlen($password) < 6) {
        err('Password must be at least 6 characters');
    }

    $r = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($r) > 0) {
        err('Email already registered', 409);
    }
    $user_string_id = generateUniqueStringId($conn);
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (user_string_id, email, password_hash, full_name, phone, role, created_at)
            VALUES ('$user_string_id', '$email', '$hash', '$full_name', '$phone', 'student', NOW())";

    if (mysqli_query($conn, $sql)) {
        $new_id = mysqli_insert_id($conn);
        out([
            'success' => true, 
            'message' => 'Registered successfully',
            'user' => [
                'id' => $new_id,
                'user_string_id' => $user_string_id,
                'email' => $email,
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

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Use POST', 405);

    $email    = cl($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (!$email || !$password) err('email and password required');

    $r = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
    $user = mysqli_fetch_assoc($r);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        err('Incorrect email or password', 401);
    }

    out([
        'success' => true,
        'message' => 'Logged in successfully',
        'user' => [
            'id'              => $user['id'],
            'user_string_id'  => $user['user_string_id'],
            'email'           => $user['email'],
            'full_name'       => $user['full_name'],
            'phone'           => $user['phone'] ?? null,
            'role'            => $user['role']
        ]
    ]);

}
else if ($action === 'logout') {

    out(['success' => true, 'message' => 'Logged out successfully']);

}
else if ($action === 'whoami') {

    err('Not supported without authentication', 403);

}
?>