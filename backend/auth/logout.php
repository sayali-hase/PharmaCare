<?php

/**
 * MediCare Pharmacy
 * User Logout API
 *
 * File:
 * backend/auth/logout.php
 */

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=UTF-8');


// ---------------------------------------------------------
// Only POST requests are allowed
// ---------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    header('Allow: POST');

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Please use POST.'
    ]);

    exit;
}


// ---------------------------------------------------------
// Check Whether User Is Logged In
// ---------------------------------------------------------

$isLoggedIn = isset($_SESSION['user_id']);


// ---------------------------------------------------------
// Clear All Session Variables
// ---------------------------------------------------------

$_SESSION = [];


// ---------------------------------------------------------
// Delete Session Cookie
// ---------------------------------------------------------

if (ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        (bool)$params['secure'],
        (bool)$params['httponly']
    );
}


// ---------------------------------------------------------
// Destroy Session
// ---------------------------------------------------------

session_destroy();


// ---------------------------------------------------------
// Successful Response
// ---------------------------------------------------------

http_response_code(200);

echo json_encode([
    'success' => true,
    'message' => $isLoggedIn
        ? 'You have been logged out successfully.'
        : 'You are already logged out.'
]);

exit;
