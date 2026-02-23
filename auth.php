<?php
// auth.php

require_once 'db.php';
require_once 'response.php';

function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function require_login() {
    if (!is_logged_in()) {
        json_error('Please login first', 401);
    }
}

function require_role($role) {
    require_login();
    if ($_SESSION['role'] !== $role) {
        json_error('Access denied – insufficient permissions', 403);
    }
}

function handle_register($input) {
    $email  = trim($input['email']  ?? '');
    $pass   = $input['password']    ?? '';
    $name   = trim($input['full_name'] ?? '');
    $phone  = trim($input['phone']  ?? '');

    if (!$email || !$pass || !$name) {
        json_error('email, password and full_name are required', 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Invalid email format', 400);
    }

    if (strlen($pass) < 6) {
        json_error('Password must be at least 6 characters', 400);
    }

    $db = get_db();
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        json_error('Email already registered', 409);
    }

    $hash = password_hash($pass, PASSWORD_DEFAULT);

    $stmt = $db->prepare("
        INSERT INTO users 
        (email, password_hash, full_name, phone, role)
        VALUES (?, ?, ?, ?, 'student')
    ");

    $ok = $stmt->execute([$email, $hash, $name, $phone ?: null]);

    if (!$ok) {
        json_error('Registration failed (database error)', 500);
    }

    json_ok(['message' => 'Account created successfully'], 201);
}

function handle_login($input) {
    $email = trim($input['email'] ?? '');
    $pass  = $input['password'] ?? '';

    if (!$email || !$pass) {
        json_error('email and password required', 400);
    }

    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user['password_hash'])) {
        json_error('Incorrect email or password', 401);
    }

    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email']   = $user['email'];
    $_SESSION['role']    = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];

    json_ok([
        'message' => 'Login successful',
        'user' => [
            'id'        => $user['id'],
            'email'     => $user['email'],
            'full_name' => $user['full_name'],
            'role'      => $user['role']
        ]
    ]);
}

function handle_logout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    json_ok(['message' => 'Logged out']);
}

function handle_whoami() {
    if (!is_logged_in()) {
        json_error('Not authenticated', 401);
    }
    json_ok([
        'user' => [
            'id'        => $_SESSION['user_id'],
            'email'     => $_SESSION['email'],
            'full_name' => $_SESSION['full_name'],
            'role'      => $_SESSION['role']
        ]
    ]);
}