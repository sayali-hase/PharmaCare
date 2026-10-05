<?php

/**
 * MediCare Pharmacy
 * User Registration API
 *
 * File:
 * backend/auth/signup.php
 */

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';


// =========================================================
// HELPER FUNCTION
// =========================================================

function sendResponse(
    bool $success,
    string $message,
    int $statusCode = 200,
    array $extra = []
): never {

    http_response_code($statusCode);

    echo json_encode(
        array_merge(
            [
                'success' => $success,
                'message' => $message
            ],
            $extra
        ),
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


// =========================================================
// ONLY POST REQUESTS
// =========================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    sendResponse(
        false,
        'Method not allowed. Please use POST.',
        405
    );
}


// =========================================================
// DATABASE CONNECTION CHECK
// =========================================================

if (!isset($conn) || !($conn instanceof mysqli)) {

    error_log(
        'MediCare Signup: Database connection unavailable.'
    );

    sendResponse(
        false,
        'Database connection is unavailable.',
        500
    );
}

$conn->set_charset('utf8mb4');


// =========================================================
// READ JSON REQUEST
// =========================================================

$rawInput = file_get_contents('php://input');

if ($rawInput === false || trim($rawInput) === '') {

    sendResponse(
        false,
        'Request data is missing.',
        400
    );
}


// =========================================================
// DECODE JSON
// =========================================================

$data = json_decode($rawInput, true);

if (
    json_last_error() !== JSON_ERROR_NONE ||
    !is_array($data)
) {

    sendResponse(
        false,
        'Invalid request data.',
        400
    );
}


// =========================================================
// GET INPUT VALUES
// =========================================================

$fullName = trim(
    (string)(
        $data['name']
        ?? $data['full_name']
        ?? ''
    )
);

$email = strtolower(
    trim(
        (string)(
            $data['email']
            ?? ''
        )
    )
);

$password = (string)(
    $data['password']
    ?? ''
);

$confirmPassword = (string)(
    $data['confirmPassword']
    ?? $data['confirm_password']
    ?? ''
);

$phone = trim(
    (string)(
        $data['phone']
        ?? ''
    )
);


// =========================================================
// VALIDATION
// =========================================================

$errors = [];


// ---------------------------------------------------------
// FULL NAME
// ---------------------------------------------------------

if ($fullName === '') {

    $errors['name'] =
        'Full name is required.';

} elseif (mb_strlen($fullName) < 2) {

    $errors['name'] =
        'Full name must contain at least 2 characters.';

} elseif (mb_strlen($fullName) > 100) {

    $errors['name'] =
        'Full name cannot exceed 100 characters.';
}


// ---------------------------------------------------------
// EMAIL
// ---------------------------------------------------------

if ($email === '') {

    $errors['email'] =
        'Email address is required.';

} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    $errors['email'] =
        'Please enter a valid email address.';

} elseif (strlen($email) > 150) {

    $errors['email'] =
        'Email address cannot exceed 150 characters.';
}


// ---------------------------------------------------------
// PASSWORD
// ---------------------------------------------------------

if ($password === '') {

    $errors['password'] =
        'Password is required.';

} elseif (strlen($password) < 8) {

    $errors['password'] =
        'Password must contain at least 8 characters.';

} elseif (strlen($password) > 72) {

    $errors['password'] =
        'Password cannot exceed 72 characters.';
}


// ---------------------------------------------------------
// CONFIRM PASSWORD
// ---------------------------------------------------------

if ($confirmPassword === '') {

    $errors['confirm_password'] =
        'Please confirm your password.';

} elseif ($password !== $confirmPassword) {

    $errors['confirm_password'] =
        'Passwords do not match.';
}


// ---------------------------------------------------------
// PHONE
// ---------------------------------------------------------

if ($phone !== '') {

    $phoneDigits = preg_replace(
        '/\D/',
        '',
        $phone
    );

    if (
        $phoneDigits === null ||
        strlen($phoneDigits) !== 10
    ) {

        $errors['phone'] =
            'Please enter a valid 10-digit phone number.';
    }
}


// =========================================================
// RETURN VALIDATION ERRORS
// =========================================================

if (!empty($errors)) {

    sendResponse(
        false,
        'Please correct the highlighted fields.',
        422,
        [
            'errors' => $errors
        ]
    );
}


// =========================================================
// CHECK DUPLICATE EMAIL
// =========================================================

$checkSql = "
    SELECT user_id
    FROM users
    WHERE email = ?
    LIMIT 1
";

$checkStmt = $conn->prepare($checkSql);

if ($checkStmt === false) {

    error_log(
        'MediCare Signup: Email check failed: ' .
        $conn->error
    );

    sendResponse(
        false,
        'Unable to process registration.',
        500
    );
}

$checkStmt->bind_param(
    's',
    $email
);

if (!$checkStmt->execute()) {

    error_log(
        'MediCare Signup: Email query failed: ' .
        $checkStmt->error
    );

    $checkStmt->close();

    sendResponse(
        false,
        'Unable to process registration.',
        500
    );
}

$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {

    $checkStmt->close();

    sendResponse(
        false,
        'An account with this email address already exists.',
        409,
        [
            'errors' => [
                'email' =>
                'Email address is already registered.'
            ]
        ]
    );
}

$checkStmt->close();


// =========================================================
// HASH PASSWORD
// =========================================================

$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);

if ($passwordHash === false) {

    error_log(
        'MediCare Signup: Password hashing failed.'
    );

    sendResponse(
        false,
        'Unable to secure your password.',
        500
    );
}


// =========================================================
// USER STATUS
// =========================================================

$status = 'active';


// =========================================================
// INSERT USER
// =========================================================

$insertSql = "
    INSERT INTO users
    (
        full_name,
        email,
        password,
        phone,
        status
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?
    )
";

$insertStmt = $conn->prepare($insertSql);

if ($insertStmt === false) {

    error_log(
        'MediCare Signup: Insert prepare failed: ' .
        $conn->error
    );

    sendResponse(
        false,
        'Unable to create your account.',
        500
    );
}

$insertStmt->bind_param(
    'sssss',
    $fullName,
    $email,
    $passwordHash,
    $phone,
    $status
);


// =========================================================
// EXECUTE INSERT
// =========================================================

if (!$insertStmt->execute()) {

    $errorCode = $insertStmt->errno;
    $errorMessage = $insertStmt->error;

    error_log(
        'MediCare Signup: Insert failed [' .
        $errorCode .
        ']: ' .
        $errorMessage
    );

    $insertStmt->close();

    if ($errorCode === 1062) {

        sendResponse(
            false,
            'An account with this email address already exists.',
            409,
            [
                'errors' => [
                    'email' =>
                    'Email address is already registered.'
                ]
            ]
        );
    }

    sendResponse(
        false,
        'Registration failed. Please try again.',
        500
    );
}


// =========================================================
// GET NEW USER ID
// =========================================================

$userId = (int)$conn->insert_id;

$insertStmt->close();


// =========================================================
// VERIFY USER ID
// =========================================================

if ($userId <= 0) {

    error_log(
        'MediCare Signup: Invalid user ID after insert.'
    );

    sendResponse(
        false,
        'Account creation could not be completed.',
        500
    );
}


// =========================================================
// CREATE LOGIN SESSION
// =========================================================

session_regenerate_id(true);

$_SESSION['user_id'] =
    $userId;

$_SESSION['user_name'] =
    $fullName;

$_SESSION['user_email'] =
    $email;

$_SESSION['user_phone'] =
    $phone;

$_SESSION['user_logged_in'] =
    true;

$_SESSION['login_type'] =
    'user';


// =========================================================
// SUCCESS RESPONSE
// =========================================================

sendResponse(
    true,
    'Account created successfully.',
    201,
    [
        'user' => [
            'id' => $userId,
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'status' => $status
        ]
    ]
);
?>
