<?php

/**
 * MediCare Pharmacy
 * Add Medicine to Cart API
 *
 * File:
 * backend/cart/add-to-cart.php
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
        'message' => 'Please login to add medicines to your cart.',
        'login_required' => true
    ]);

    exit;
}

$userId = (int)$_SESSION['user_id'];


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
// Get Medicine ID
// ---------------------------------------------------------

$medicineId = filter_var(
    $data['medicine_id'] ?? $data['id'] ?? null,
    FILTER_VALIDATE_INT
);


// ---------------------------------------------------------
// Get Quantity
// ---------------------------------------------------------

$quantity = filter_var(
    $data['quantity'] ?? 1,
    FILTER_VALIDATE_INT
);


// ---------------------------------------------------------
// Validate Medicine ID
// ---------------------------------------------------------

if (
    $medicineId === false ||
    $medicineId === null ||
    $medicineId <= 0
) {

    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'A valid medicine ID is required.'
    ]);

    exit;
}


// ---------------------------------------------------------
// Validate Quantity
// ---------------------------------------------------------

if (
    $quantity === false ||
    $quantity === null ||
    $quantity <= 0
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
        'message' => 'Maximum quantity per request is 100.'
    ]);

    exit;
}


// ---------------------------------------------------------
// Start Transaction
// ---------------------------------------------------------

$conn->begin_transaction();

try {

    // -----------------------------------------------------
    // Get Medicine and Lock Its Row
    // -----------------------------------------------------

    $medicineSql = "
        SELECT
            id,
            name,
            price,
            stock,
            image
        FROM medicines
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ";

    $medicineStmt = $conn->prepare($medicineSql);

    if (!$medicineStmt) {
        throw new Exception(
            'Unable to prepare medicine query.'
        );
    }

    $medicineStmt->bind_param(
        'i',
        $medicineId
    );

    if (!$medicineStmt->execute()) {

        $medicineStmt->close();

        throw new Exception(
            'Unable to check medicine.'
        );
    }

    $medicineResult = $medicineStmt->get_result();


    // -----------------------------------------------------
    // Medicine Not Found
    // -----------------------------------------------------

    if ($medicineResult->num_rows !== 1) {

        $medicineStmt->close();

        $conn->rollback();

        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Medicine not found.'
        ]);

        exit;
    }

    $medicine = $medicineResult->fetch_assoc();

    $medicineStmt->close();


    // -----------------------------------------------------
    // Check Stock
    // -----------------------------------------------------

    $availableStock = (int)$medicine['stock'];

    if ($availableStock <= 0) {

        $conn->rollback();

        http_response_code(409);

        echo json_encode([
            'success' => false,
            'message' =>
                'This medicine is currently out of stock.'
        ]);

        exit;
    }


    // -----------------------------------------------------
    // Check Existing Cart Item
    // -----------------------------------------------------

    $cartSql = "
        SELECT
            id,
            quantity
        FROM cart
        WHERE user_id = ?
          AND medicine_id = ?
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
        $userId,
        $medicineId
    );

    if (!$cartStmt->execute()) {

        $cartStmt->close();

        throw new Exception(
            'Unable to check cart.'
        );
    }

    $cartResult = $cartStmt->get_result();


    // -----------------------------------------------------
    // Existing Cart Item
    // -----------------------------------------------------

    if ($cartResult->num_rows === 1) {

        $cartItem = $cartResult->fetch_assoc();

        $cartId = (int)$cartItem['id'];

        $existingQuantity =
            (int)$cartItem['quantity'];

        $newQuantity =
            $existingQuantity + $quantity;


        // -------------------------------------------------
        // Check Combined Quantity Against Stock
        // -------------------------------------------------

        if ($newQuantity > $availableStock) {

            $cartStmt->close();

            $conn->rollback();

            http_response_code(409);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Only ' . $availableStock .
                    ' item(s) are available in stock.',
                'available_stock' => $availableStock,
                'current_quantity' => $existingQuantity
            ]);

            exit;
        }


        // -------------------------------------------------
        // Update Existing Cart Item
        // -------------------------------------------------

        $updateSql = "
            UPDATE cart
            SET quantity = ?
            WHERE id = ?
              AND user_id = ?
            LIMIT 1
        ";

        $updateStmt =
            $conn->prepare($updateSql);

        if (!$updateStmt) {

            $cartStmt->close();

            throw new Exception(
                'Unable to prepare cart update.'
            );
        }

        $updateStmt->bind_param(
            'iii',
            $newQuantity,
            $cartId,
            $userId
        );

        if (!$updateStmt->execute()) {

            $updateStmt->close();
            $cartStmt->close();

            throw new Exception(
                'Unable to update cart.'
            );
        }

        $updateStmt->close();
        $cartStmt->close();


        // -------------------------------------------------
        // Commit
        // -------------------------------------------------

        $conn->commit();


        http_response_code(200);

        echo json_encode([
            'success' => true,
            'message' =>
                'Medicine quantity updated in your cart.',

            'cart_item' => [
                'cart_id' => $cartId,
                'medicine_id' => $medicineId,
                'quantity' => $newQuantity,
                'price' => (float)$medicine['price'],
                'name' => $medicine['name']
            ]
        ]);

        exit;
    }


    // -----------------------------------------------------
    // New Cart Item
    // -----------------------------------------------------

    $cartStmt->close();


    // -----------------------------------------------------
    // Check Requested Quantity Against Stock
    // -----------------------------------------------------

    if ($quantity > $availableStock) {

        $conn->rollback();

        http_response_code(409);

        echo json_encode([
            'success' => false,
            'message' =>
                'Only ' . $availableStock .
                ' item(s) are available in stock.',
            'available_stock' => $availableStock
        ]);

        exit;
    }


    // -----------------------------------------------------
    // Insert Cart Item
    // -----------------------------------------------------

    $insertSql = "
        INSERT INTO cart
        (
            user_id,
            medicine_id,
            quantity
        )
        VALUES
        (
            ?,
            ?,
            ?
        )
    ";

    $insertStmt =
        $conn->prepare($insertSql);

    if (!$insertStmt) {

        throw new Exception(
            'Unable to prepare cart insertion.'
        );
    }

    $insertStmt->bind_param(
        'iii',
        $userId,
        $medicineId,
        $quantity
    );

    if (!$insertStmt->execute()) {

        $insertStmt->close();

        throw new Exception(
            'Unable to add medicine to cart.'
        );
    }

    $cartId =
        (int)$conn->insert_id;

    $insertStmt->close();


    // -----------------------------------------------------
    // Commit Transaction
    // -----------------------------------------------------

    $conn->commit();


    // -----------------------------------------------------
    // Successful Response
    // -----------------------------------------------------

    http_response_code(201);

    echo json_encode([
        'success' => true,
        'message' =>
            'Medicine added to cart successfully.',

        'cart_item' => [
            'cart_id' => $cartId,
            'medicine_id' => $medicineId,
            'quantity' => $quantity,
            'price' => (float)$medicine['price'],
            'name' => $medicine['name']
        ]
    ]);

    exit;

} catch (Throwable $e) {

    $conn->rollback();

    error_log(
        'MediCare Add Cart Error: ' .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
            'Unable to add medicine to cart.'
    ]);

    exit;
}
?>
