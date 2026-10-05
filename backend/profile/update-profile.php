<?php

/**
 * MediCare Pharmacy
 * Update User Profile API
 *
 * File:
 * backend/profile/update-profile.php
 */

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';


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
// Check Login Session
// ---------------------------------------------------------

if (!isset($_SESSION['user_id'])) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Please login to update your profile.',
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
// Read JSON Request
// ---------------------------------------------------------

$rawInput = file_get_contents('php://input');

$data = json_decode($rawInput, true);

if (!is_array($data)) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request data.'
    ]);

    exit;
}


// ---------------------------------------------------------
// Get Input
// ---------------------------------------------------------

$fullName = trim(
    (string) ($data['full_name'] ?? '')
);

$phone = trim(
    (string) ($data['phone'] ?? '')
);

$address = trim(
    (string) ($data['address'] ?? '')
);

$city = trim(
    (string) ($data['city'] ?? '')
);

$state = trim(
    (string) ($data['state'] ?? '')
);

$pincode = trim(
    (string) ($data['pincode'] ?? '')
);

$landmark = trim(
    (string) ($data['landmark'] ?? '')
);


// ---------------------------------------------------------
// Validation
// ---------------------------------------------------------

$errors = [];


// Full Name

if ($fullName === '') {

    $errors['full_name'] = 'Full name is required.';

} elseif (mb_strlen($fullName) < 2) {

    $errors['full_name'] =
        'Full name must contain at least 2 characters.';

} elseif (mb_strlen($fullName) > 100) {

    $errors['full_name'] =
        'Full name cannot exceed 100 characters.';
}


// Phone

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


// Address

if ($address !== '' && mb_strlen($address) > 255) {

    $errors['address'] =
        'Address cannot exceed 255 characters.';
}


// City

if ($city !== '' && mb_strlen($city) > 100) {

    $errors['city'] =
        'City cannot exceed 100 characters.';
}


// State

if ($state !== '' && mb_strlen($state) > 100) {

    $errors['state'] =
        'State cannot exceed 100 characters.';
}


// Pincode

if ($pincode !== '') {

    $pincodeDigits = preg_replace(
        '/\D/',
        '',
        $pincode
    );

    if (
        $pincodeDigits === null ||
        strlen($pincodeDigits) !== 6
    ) {

        $errors['pincode'] =
            'Please enter a valid 6-digit pincode.';
    }
}


// Landmark

if ($landmark !== '' && mb_strlen($landmark) > 150) {

    $errors['landmark'] =
        'Landmark cannot exceed 150 characters.';
}


// ---------------------------------------------------------
// Return Validation Errors
// ---------------------------------------------------------

if (!empty($errors)) {

    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'Please correct the highlighted fields.',
        'errors' => $errors
    ]);

    exit;
}


// ---------------------------------------------------------
// Normalize Phone and Pincode
// ---------------------------------------------------------

if ($phone !== '') {

    $phoneDigits = preg_replace(
        '/\D/',
        '',
        $phone
    );

    $phone = $phoneDigits ?? $phone;
}

if ($pincode !== '') {

    $pincodeDigits = preg_replace(
        '/\D/',
        '',
        $pincode
    );

    $pincode = $pincodeDigits ?? $pincode;
}


// ---------------------------------------------------------
// Start Transaction
// ---------------------------------------------------------

$conn->begin_transaction();

try {


    // -----------------------------------------------------
    // Update Users Table
    // -----------------------------------------------------

    $userSql = "
        UPDATE users
        SET
            full_name = ?,
            phone = ?
        WHERE user_id = ?
        LIMIT 1
    ";

    $userStmt = $conn->prepare($userSql);

    if (!$userStmt) {

        throw new Exception(
            'Unable to prepare user profile update.'
        );
    }

    $userStmt->bind_param(
        'ssi',
        $fullName,
        $phone,
        $userId
    );

    if (!$userStmt->execute()) {

        $userStmt->close();

        throw new Exception(
            'Unable to update user profile.'
        );
    }

    $userStmt->close();


    // -----------------------------------------------------
    // Check Existing Address
    // -----------------------------------------------------

    $addressSelectSql = "
        SELECT
            address_id
        FROM addresses
        WHERE user_id = ?
        ORDER BY address_id ASC
        LIMIT 1
        FOR UPDATE
    ";

    $addressSelectStmt = $conn->prepare(
        $addressSelectSql
    );

    if (!$addressSelectStmt) {

        throw new Exception(
            'Unable to prepare address query.'
        );
    }

    $addressSelectStmt->bind_param(
        'i',
        $userId
    );

    if (!$addressSelectStmt->execute()) {

        $addressSelectStmt->close();

        throw new Exception(
            'Unable to check existing address.'
        );
    }

    $addressResult =
        $addressSelectStmt->get_result();

    $existingAddress =
        $addressResult->fetch_assoc();

    $addressSelectStmt->close();


    // -----------------------------------------------------
    // Update Existing Address
    // -----------------------------------------------------

    if ($existingAddress) {

        $addressId =
            (int) $existingAddress['address_id'];

        $addressSql = "
            UPDATE addresses
            SET
                full_name = ?,
                phone = ?,
                address = ?,
                city = ?,
                state = ?,
                pincode = ?,
                landmark = ?
            WHERE address_id = ?
              AND user_id = ?
            LIMIT 1
        ";

        $addressStmt =
            $conn->prepare($addressSql);

        if (!$addressStmt) {

            throw new Exception(
                'Unable to prepare address update.'
            );
        }

        $addressStmt->bind_param(
            'ssssss sii',
            $fullName,
            $phone,
            $address,
            $city,
            $state,
            $pincode,
            $landmark,
            $addressId,
            $userId
        );

        if (!$addressStmt->execute()) {

            $addressStmt->close();

            throw new Exception(
                'Unable to update address.'
            );
        }

        $addressStmt->close();

    } else {

        // -------------------------------------------------
        // Create New Address
        // -------------------------------------------------

        $addressType = 'Home';

        $addressSql = "
            INSERT INTO addresses
            (
                user_id,
                full_name,
                phone,
                address,
                city,
                state,
                pincode,
                landmark,
                address_type
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $addressStmt =
            $conn->prepare($addressSql);

        if (!$addressStmt) {

            throw new Exception(
                'Unable to prepare address insert.'
            );
        }

        $addressStmt->bind_param(
            'issssssss',
            $userId,
            $fullName,
            $phone,
            $address,
            $city,
            $state,
            $pincode,
            $landmark,
            $addressType
        );

        if (!$addressStmt->execute()) {

            $addressStmt->close();

            throw new Exception(
                'Unable to save address.'
            );
        }

        $addressStmt->close();
    }


    // -----------------------------------------------------
    // Commit Transaction
    // -----------------------------------------------------

    $conn->commit();


} catch (Throwable $e) {

    $conn->rollback();

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to update your profile.'
    ]);

    exit;
}


// ---------------------------------------------------------
// Update Session
// ---------------------------------------------------------

$_SESSION['user_id'] = $userId;

$_SESSION['user_name'] = $fullName;

$_SESSION['user_phone'] = $phone;


// ---------------------------------------------------------
// Return Updated Profile
// ---------------------------------------------------------

http_response_code(200);

echo json_encode([
    'success' => true,
    'message' => 'Profile updated successfully.',
    'profile' => [
        'user_id' => $userId,
        'full_name' => $fullName,
        'phone' => $phone,
        'address' => $address,
        'city' => $city,
        'state' => $state,
        'pincode' => $pincode,
        'landmark' => $landmark
    ]
]);

exit;
?>
