<?php
// public/index.php

// Start session on EVERY request (must be first line - no output before)
session_start();

// Allow CORS for development (tighten in production!)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../response.php';
require_once __DIR__ . '/../auth.php';

// Parse URL
$uri   = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts = explode('/', $uri);

if (count($parts) < 2 || $parts[0] !== 'api' || $parts[1] !== 'v1') {
    json_error('Use /api/v1/ endpoint', 404);
}

$resource = $parts[2] ?? '';
$action   = $parts[3] ?? '';

// Read JSON body once
$input = [];
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: [];
}

switch ($resource) {
    case 'auth':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'register') {
                handle_register($input);
            } elseif ($action === 'login') {
                handle_login($input);
            } elseif ($action === 'logout') {
                handle_logout();
            } else {
                json_error('Invalid auth action', 400);
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'whoami') {
            handle_whoami();
        } else {
            json_error('Method not allowed for /auth', 405);
        }
        break;

    // Next files will be added here (items, claims...)
    default:
        json_error('Resource not found', 404);
}