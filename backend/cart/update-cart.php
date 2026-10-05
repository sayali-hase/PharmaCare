<?php

/**
 * MediCare Pharmacy
 * Update Cart Quantity API
 *
 * File:
 * backend/cart/update-cart.php
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
        'message' => 'Please login to update your cart.',
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
// Get Cart ID / Medicine ID
// ---------------------------------------------------------

$cartId = filter_var(
    $data['cart_id'] ?? null,
    FILTER_VALIDATE_INT
);

$medicineId = filter_var(
    $data['medicine_id'] ?? null,
    FILTER_VALIDATE_INT
);


// ---------------------------------------------------------
// Get Quantity
// ---------------------------------------------------------

$quantity = filter_var(
    $data['quantity'] ?? null,
    FILTER_VALIDATE_INT
);


// ---------------------------------------------------------
// Validate Quantity
// ---------------------------------------------------------

if (
    $quantity === false ||
    $quantity === null ||
    $quantity < 1
) {

    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'Quantity must be at least 1.'
    ]);

    exit;
}


if ($quantity > 100) {

    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'Maximum quantity is 100.'
    ]);

    exit;
}


// ---------------------------------------------------------
// At Least One Identifier Required
// ---------------------------------------------------------

if (
    ($cartId === false || $cartId === null || $cartId <= 0) &&
    ($medicineId === false || $medicineId === null || $medicineId <= 0)
) {

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
    // Find Cart Item
    // -----------------------------------------------------

    if (
        $cartId !== false &&
        $cartId !== null &&
        $cartId > 0
    ) {

        $cartSql = "
            SELECT
                c.cart_id,
                c.user_id,
                c.medicine_id,
                c.quantity,
                m.name,
                m.price,
                m.stock,
                m.image
            FROM cart c
            INNER JOIN medicines m
                ON m.medicine_id = c.medicine_id
            WHERE c.cart_id = ?
              AND c.user_id = ?
            LIMIT 1
            FOR UPDATE
        ";

        $cartStmt = $conn->prepare($cartSql);

        if (!$cartStmt) {

            throw new Exception(
                'Unable to prepare cart query.'
            );
        }

        $cartStmt->bind_param(
            'ii',
            $cartId,
            $userId
        );

    } else {

        $cartSql = "
            SELECT
                c.cart_id,
                c.user_id,
                c.medicine_id,
                c.quantity,
                m.name,
                m.price,
                m.stock,
                m.image
            FROM cart c
            INNER JOIN medicines m
                ON m.medicine_id = c.medicine_id
            WHERE c.medicine_id = ?
              AND c.user_id = ?
            LIMIT 1
            FOR UPDATE
        ";

        $cartStmt = $conn->prepare($cartSql);

        if (!$cartStmt) {

            throw new Exception(
                'Unable to prepare cart query.'
            );
        }

        $cartStmt->bind_param(
            'ii',
            $medicineId,
            $userId
        );
    }


    // -----------------------------------------------------
    // Execute Cart Query
    // -----------------------------------------------------

    if (!$cartStmt->execute()) {

        $cartStmt->close();

        throw new Exception(
            'Unable to find cart item.'
        );
    }


    $cartResult = $cartStmt->get_result();


    // -----------------------------------------------------
    // Cart Item Not Found
    // -----------------------------------------------------

    if ($cartResult->num_rows !== 1) {

        $cartStmt->close();

        $conn->rollback();

        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Cart item not found.'
        ]);

        exit;
    }


    $cartItem = $cartResult->fetch_assoc();

    $cartStmt->close();


    // -----------------------------------------------------
    // Get Current Values
    // -----------------------------------------------------

    $actualCartId = (int) $cartItem['cart_id'];

    $actualMedicineId = (int) $cartItem['medicine_id'];

    $availableStock = (int) $cartItem['stock'];


    // -----------------------------------------------------
    // Check Stock
    // -----------------------------------------------------

    if ($availableStock <= 0) {

        $conn->rollback();

        http_response_code(409);

        echo json_encode([
            'success' => false,
            'message' => 'This medicine is currently out of stock.',
            'available_stock' => 0
        ]);

        exit;
    }


    if ($quantity > $availableStock) {

        $conn->rollback();

        http_response_code(409);

        echo json_encode([
            'success' => false,
            'message' => 'Only ' . $availableStock .
                ' item(s) are available in stock.',
            'available_stock' => $availableStock,
            'requested_quantity' => $quantity
        ]);

        exit;
    }


    // -----------------------------------------------------
    // Update Cart
    // -----------------------------------------------------

    $updateSql = "
        UPDATE cart
        SET quantity = ?
        WHERE cart_id = ?
          AND user_id = ?
        LIMIT 1
    ";

    $updateStmt = $conn->prepare($updateSql);

    if (!$updateStmt) {

        throw new Exception(
            'Unable to prepare cart update.'
        );
    }


    $updateStmt->bind_param(
        'iii',
        $quantity,
        $actualCartId,
        $userId
    );


    if (!$updateStmt->execute()) {

        $updateStmt->close();

        throw new Exception(
            'Unable to update cart quantity.'
        );
    }


    $updateStmt->close();


    // -----------------------------------------------------
    // Commit Transaction
    // -----------------------------------------------------

    $conn->commit();


    // -----------------------------------------------------
    // Calculate Item Total
    // -----------------------------------------------------

    $price = (float) $cartItem['price'];

    $itemTotal = round(
        $price * $quantity,
        2
    );


    // -----------------------------------------------------
    // Successful Response
    // -----------------------------------------------------

    http_response_code(200);

    echo json_encode([
        'success' => true,
        'message' => 'Cart quantity updated successfully.',
        'cart_item' => [
            'cart_id' => $actualCartId,
            'medicine_id' => $actualMedicineId,
            'name' => $cartItem['name'],
            'price' => $price,
            'quantity' => $quantity,
            'stock' => $availableStock,
            'image' => $cartItem['image'] ?? '',
            'item_total' => $itemTotal
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
        'message' => 'Unable to update cart.'
    ]);

    exit;
}
