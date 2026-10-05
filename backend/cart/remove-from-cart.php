<?php

/**
 * MediCare Pharmacy
 * Remove Medicine from Cart API
 *
 * File:
 * backend/cart/remove-from-cart.php
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
        'message' => 'Please login to modify your cart.',
        'login_required' => true
    ]);

    exit;
}

$userId = (int) $_SESSION['user_id'];


// ---------------------------------------------------------
// Validate User ID
// ---------------------------------------------------------

if ($userId <= 0) {

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
// Get Cart ID
// ---------------------------------------------------------

$cartId = filter_var(
    $data['cart_id'] ?? null,
    FILTER_VALIDATE_INT
);


// ---------------------------------------------------------
// Get Medicine ID as Fallback
// ---------------------------------------------------------

$medicineId = filter_var(
    $data['medicine_id'] ?? null,
    FILTER_VALIDATE_INT
);


// ---------------------------------------------------------
// Validate Identifier
// ---------------------------------------------------------

$hasCartId = (
    $cartId !== false &&
    $cartId !== null &&
    $cartId > 0
);

$hasMedicineId = (
    $medicineId !== false &&
    $medicineId !== null &&
    $medicineId > 0
);


if (!$hasCartId && !$hasMedicineId) {

    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'A valid cart ID or medicine ID is required.'
    ]);

    exit;
}


// ---------------------------------------------------------
// Start Transaction
// ---------------------------------------------------------

$conn->begin_transaction();

try {

    // -----------------------------------------------------
    // Find Cart Item by Cart ID
    // -----------------------------------------------------

    if ($hasCartId) {

        $findSql = "
            SELECT
                c.cart_id,
                c.user_id,
                c.medicine_id,
                c.quantity,
                m.name
            FROM cart c
            INNER JOIN medicines m
                ON m.medicine_id = c.medicine_id
            WHERE c.cart_id = ?
              AND c.user_id = ?
            LIMIT 1
            FOR UPDATE
        ";

        $findStmt = $conn->prepare($findSql);

        if (!$findStmt) {

            throw new Exception(
                'Unable to prepare cart query.'
            );
        }

        $findStmt->bind_param(
            'ii',
            $cartId,
            $userId
        );

    } else {

        // -------------------------------------------------
        // Find Cart Item by Medicine ID
        // -------------------------------------------------

        $findSql = "
            SELECT
                c.cart_id,
                c.user_id,
                c.medicine_id,
                c.quantity,
                m.name
            FROM cart c
            INNER JOIN medicines m
                ON m.medicine_id = c.medicine_id
            WHERE c.medicine_id = ?
              AND c.user_id = ?
            LIMIT 1
            FOR UPDATE
        ";

        $findStmt = $conn->prepare($findSql);

        if (!$findStmt) {

            throw new Exception(
                'Unable to prepare cart query.'
            );
        }

        $findStmt->bind_param(
            'ii',
            $medicineId,
            $userId
        );
    }


    // -----------------------------------------------------
    // Execute Query
    // -----------------------------------------------------

    if (!$findStmt->execute()) {

        $findStmt->close();

        throw new Exception(
            'Unable to find cart item.'
        );
    }


    $result = $findStmt->get_result();


    // -----------------------------------------------------
    // Cart Item Not Found
    // -----------------------------------------------------

    if ($result->num_rows !== 1) {

        $findStmt->close();

        $conn->rollback();

        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Cart item not found.'
        ]);

        exit;
    }


    $cartItem = $result->fetch_assoc();

    $findStmt->close();


    // -----------------------------------------------------
    // Store Item Information
    // -----------------------------------------------------

    $actualCartId = (int) $cartItem['cart_id'];

    $actualMedicineId = (int) $cartItem['medicine_id'];

    $medicineName = $cartItem['name'];

    $removedQuantity = (int) $cartItem['quantity'];


    // -----------------------------------------------------
    // Delete Cart Item
    // -----------------------------------------------------

    $deleteSql = "
        DELETE FROM cart
        WHERE cart_id = ?
          AND user_id = ?
        LIMIT 1
    ";

    $deleteStmt = $conn->prepare($deleteSql);

    if (!$deleteStmt) {

        throw new Exception(
            'Unable to prepare cart removal.'
        );
    }


    $deleteStmt->bind_param(
        'ii',
        $actualCartId,
        $userId
    );


    if (!$deleteStmt->execute()) {

        $deleteStmt->close();

        throw new Exception(
            'Unable to remove medicine from cart.'
        );
    }


    // -----------------------------------------------------
    // Check Whether Item Was Actually Deleted
    // -----------------------------------------------------

    $deletedRows = $deleteStmt->affected_rows;

    $deleteStmt->close();


    if ($deletedRows !== 1) {

        throw new Exception(
            'Cart item could not be removed.'
        );
    }


    // -----------------------------------------------------
    // Commit Transaction
    // -----------------------------------------------------

    $conn->commit();


    // -----------------------------------------------------
    // Successful Response
    // -----------------------------------------------------

    http_response_code(200);

    echo json_encode([
        'success' => true,
        'message' => 'Medicine removed from cart.',
        'removed_item' => [
            'cart_id' => $actualCartId,
            'medicine_id' => $actualMedicineId,
            'name' => $medicineName,
            'quantity' => $removedQuantity
        ]
    ]);

    exit;

} catch (Throwable $e) {

    // -----------------------------------------------------
    // Rollback on Error
    // -----------------------------------------------------

    $conn->rollback();

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to remove medicine from cart.'
    ]);

    exit;
}
