<?php

/**
 * MediCare Pharmacy
 * Unified Login API
 *
 * File:
 * backend/auth/login.php
 *
 * Supports:
 * - Admin Login
 * - Customer/User Login
 */

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';


// =========================================================
// ONLY POST REQUESTS
// =========================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    header('Allow: POST');

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Please use POST.'
    ]);

    exit;
}


// =========================================================
// READ JSON INPUT
// =========================================================

$rawInput = file_get_contents('php://input');

$data = json_decode($rawInput, true);


// =========================================================
// VALIDATE JSON
// =========================================================

if (!is_array($data)) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request data.'
    ]);

    exit;
}


// =========================================================
// GET LOGIN DETAILS
// =========================================================

$email = strtolower(
    trim(
        (string)($data['email'] ?? '')
    )
);

$password = (string)(
    $data['password'] ?? ''
);


// =========================================================
// VALIDATE EMAIL
// =========================================================

if ($email === '') {

    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'Email address is required.'
    ]);

    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid email address.'
    ]);

    exit;
}


// =========================================================
// VALIDATE PASSWORD
// =========================================================

if ($password === '') {

    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'Password is required.'
    ]);

    exit;
}


$invalidMessage = 'Invalid email or password.';


// =========================================================
// STEP 1: CHECK ADMIN ACCOUNT
// =========================================================

$adminSql = "
    SELECT
        admin_id,
        full_name,
        email,
        password,
        phone,
        status
    FROM admin
    WHERE email = ?
    LIMIT 1
";

$adminStmt = $conn->prepare($adminSql);


if ($adminStmt) {

    $adminStmt->bind_param(
        's',
        $email
    );

    if ($adminStmt->execute()) {

        $adminResult = $adminStmt->get_result();

        if ($adminResult->num_rows === 1) {

            $admin = $adminResult->fetch_assoc();

            $adminStmt->close();


            // -------------------------------------------------
            // Check Admin Status
            // -------------------------------------------------

            if (
                strtolower(
                    (string)$admin['status']
                ) !== 'active'
            ) {

                http_response_code(403);

                echo json_encode([
                    'success' => false,
                    'message' => 'Your admin account is inactive.'
                ]);

                exit;
            }


            // -------------------------------------------------
            // Verify Admin Password
            // -------------------------------------------------

            if (
                !password_verify(
                    $password,
                    (string)$admin['password']
                )
            ) {

                http_response_code(401);

                echo json_encode([
                    'success' => false,
                    'message' => $invalidMessage
                ]);

                exit;
            }


            // -------------------------------------------------
            // Rehash Admin Password If Necessary
            // -------------------------------------------------

            if (
                password_needs_rehash(
                    (string)$admin['password'],
                    PASSWORD_DEFAULT
                )
            ) {

                $newPasswordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                if ($newPasswordHash !== false) {

                    $rehashSql = "
                        UPDATE admin
                        SET password = ?
                        WHERE admin_id = ?
                    ";

                    $rehashStmt = $conn->prepare($rehashSql);

                    if ($rehashStmt) {

                        $adminId = (int)$admin['admin_id'];

                        $rehashStmt->bind_param(
                            'si',
                            $newPasswordHash,
                            $adminId
                        );

                        $rehashStmt->execute();

                        $rehashStmt->close();
                    }
                }
            }


            // -------------------------------------------------
            // Clear Previous User Session
            // -------------------------------------------------

            unset(
                $_SESSION['user_id'],
                $_SESSION['user_name'],
                $_SESSION['user_email'],
                $_SESSION['user_phone'],
                $_SESSION['user_logged_in']
            );


            session_regenerate_id(true);


            // -------------------------------------------------
            // Create Admin Session
            // -------------------------------------------------

            $_SESSION['admin_id'] =
                (int)$admin['admin_id'];

            $_SESSION['admin_name'] =
                (string)$admin['full_name'];

            $_SESSION['admin_email'] =
                (string)$admin['email'];

            $_SESSION['admin_phone'] =
                (string)($admin['phone'] ?? '');

            $_SESSION['admin_logged_in'] =
                true;

            $_SESSION['login_type'] =
                'admin';


            // -------------------------------------------------
            // Admin Login Success
            // -------------------------------------------------

            http_response_code(200);

            echo json_encode([
                'success' => true,
                'message' => 'Admin login successful.',
                'user_type' => 'admin',
                'redirect' => 'admin/dashboard.php',

                'admin' => [
                    'id' => (int)$admin['admin_id'],
                    'full_name' => $admin['full_name'],
                    'email' => $admin['email'],
                    'phone' => $admin['phone'] ?? null
                ]
            ]);

            exit;
        }
    }

    $adminStmt->close();
}


// =========================================================
// STEP 2: CHECK NORMAL USER ACCOUNT
// =========================================================

$userSql = "
    SELECT
        user_id,
        full_name,
        email,
        phone,
        password,
        status
    FROM users
    WHERE email = ?
    LIMIT 1
";

$userStmt = $conn->prepare($userSql);


if (!$userStmt) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to process login request.'
    ]);

    exit;
}


$userStmt->bind_param(
    's',
    $email
);


if (!$userStmt->execute()) {

    $userStmt->close();

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to process login request.'
    ]);

    exit;
}


$userResult = $userStmt->get_result();


// =========================================================
// USER NOT FOUND
// =========================================================

if ($userResult->num_rows !== 1) {

    $userStmt->close();

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => $invalidMessage
    ]);

    exit;
}


$user = $userResult->fetch_assoc();

$userStmt->close();


// =========================================================
// CHECK USER STATUS
// =========================================================

if (
    strtolower(
        (string)$user['status']
    ) !== 'active'
) {

    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' =>
        'Your account is inactive. Please contact support.'
    ]);

    exit;
}


// =========================================================
// VERIFY USER PASSWORD
// =========================================================

if (
    !password_verify(
        $password,
        (string)$user['password']
    )
) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => $invalidMessage
    ]);

    exit;
}


// =========================================================
// REHASH USER PASSWORD IF NECESSARY
// =========================================================

if (
    password_needs_rehash(
        (string)$user['password'],
        PASSWORD_DEFAULT
    )
) {

    $newPasswordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    if ($newPasswordHash !== false) {

        $rehashSql = "
            UPDATE users
            SET password = ?
            WHERE user_id = ?
        ";

        $rehashStmt = $conn->prepare($rehashSql);

        if ($rehashStmt) {

            $userId = (int)$user['user_id'];

            $rehashStmt->bind_param(
                'si',
                $newPasswordHash,
                $userId
            );

            $rehashStmt->execute();

            $rehashStmt->close();
        }
    }
}


// =========================================================
// CLEAR PREVIOUS ADMIN SESSION
// =========================================================

unset(
    $_SESSION['admin_id'],
    $_SESSION['admin_name'],
    $_SESSION['admin_email'],
    $_SESSION['admin_phone'],
    $_SESSION['admin_logged_in']
);


session_regenerate_id(true);


// =========================================================
// CREATE USER SESSION
// =========================================================

$_SESSION['user_id'] =
    (int)$user['user_id'];

$_SESSION['user_name'] =
    (string)$user['full_name'];

$_SESSION['user_email'] =
    (string)$user['email'];

$_SESSION['user_phone'] =
    (string)($user['phone'] ?? '');

$_SESSION['user_logged_in'] =
    true;

$_SESSION['login_type'] =
    'user';


// =========================================================
// USER LOGIN SUCCESS
// =========================================================

http_response_code(200);

echo json_encode([
    'success' => true,
    'message' => 'Login successful.',
    'user_type' => 'user',
    'redirect' => 'index.php',

    'user' => [
        'id' => (int)$user['user_id'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'phone' => $user['phone'] ?? null
    ]
]);

exit;
?>
