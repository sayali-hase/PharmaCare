<?php

/**
 * MediCare Pharmacy
 * Get User Profile API
 *
 * File:
 * backend/profile/get-profile.php
 */

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';


// ---------------------------------------------------------
// Only GET requests are allowed
// ---------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    http_response_code(405);

    header('Allow: GET');

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Please use GET.'
    ]);

    exit;
}


// ---------------------------------------------------------
// Check Login Session
// ---------------------------------------------------------

if (!isset($_SESSION['user_id'])) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Please login to view your profile.',
        'login_required' => true
    ]);

    exit;
}


$userId = (int) $_SESSION['user_id'];


// ---------------------------------------------------------
// Validate Session User ID
// ---------------------------------------------------------

if ($userId <= 0) {

    session_unset();
    session_destroy();

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid user session.',
        'login_required' => true
    ]);

    exit;
}


// ---------------------------------------------------------
// Get User Profile
// ---------------------------------------------------------

$sql = "
    SELECT
        user_id,
        full_name,
        email,
        phone,
        status
    FROM users
    WHERE user_id = ?
    LIMIT 1
";


$stmt = $conn->prepare($sql);

if (!$stmt) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to prepare profile query.'
    ]);

    exit;
}


// ---------------------------------------------------------
// Bind User ID
// ---------------------------------------------------------

$stmt->bind_param(
    'i',
    $userId
);


// ---------------------------------------------------------
// Execute Query
// ---------------------------------------------------------

if (!$stmt->execute()) {

    $stmt->close();

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to load profile.'
    ]);

    exit;
}


// ---------------------------------------------------------
// Get Result
// ---------------------------------------------------------

$result = $stmt->get_result();


// ---------------------------------------------------------
// User Not Found
// ---------------------------------------------------------

if ($result->num_rows !== 1) {

    $stmt->close();

    session_unset();
    session_destroy();

    http_response_code(404);

    echo json_encode([
        'success' => false,
        'message' => 'User account was not found.',
        'login_required' => true
    ]);

    exit;
}


// ---------------------------------------------------------
// Fetch User
// ---------------------------------------------------------

$user = $result->fetch_assoc();

$stmt->close();


// ---------------------------------------------------------
// Format Profile
// ---------------------------------------------------------

$profile = [
    'user_id' => (int) $user['user_id'],

    'full_name' => $user['full_name'] ?? '',

    'email' => $user['email'] ?? '',

    'phone' => $user['phone'] ?? '',

    'status' => $user['status'] ?? ''
];


// ---------------------------------------------------------
// Update Session Information
// ---------------------------------------------------------

$_SESSION['user_id'] = $profile['user_id'];

$_SESSION['user_name'] = $profile['full_name'];

$_SESSION['user_email'] = $profile['email'];


// ---------------------------------------------------------
// Successful Response
// ---------------------------------------------------------

http_response_code(200);

echo json_encode([
    'success' => true,
    'message' => 'Profile loaded successfully.',
    'profile' => $profile
]);

exit;
?>
