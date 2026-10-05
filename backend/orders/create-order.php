<?php

/**
 * PharmaCare Pharmacy
 * Create Order API
 *
 * File:
 * backend/orders/create-order.php
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
        'message' => 'Please login before placing your order.',
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
// Get Delivery Information
// ---------------------------------------------------------

$fullName = trim(
    (string) ($data['full_name'] ?? '')
);

$phone = trim(
    (string) ($data['phone'] ?? '')
);

$email = strtolower(
    trim((string) ($data['email'] ?? ''))
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

$paymentMethod = strtolower(
    trim(
        (string) (
            $data['payment_method']
            ?? $data['paymentMethod']
            ?? 'cod'
        )
    )
);


// ---------------------------------------------------------
// Validate Delivery Information
// ---------------------------------------------------------

$errors = [];


// ---------------------------------------------------------
// Full Name
// ---------------------------------------------------------

if ($fullName === '') {

    $errors['full_name'] =
        'Full name is required.';

} elseif (mb_strlen($fullName) < 2) {

    $errors['full_name'] =
        'Please enter a valid full name.';

} elseif (mb_strlen($fullName) > 100) {

    $errors['full_name'] =
        'Full name cannot exceed 100 characters.';
}


// ---------------------------------------------------------
// Phone
// ---------------------------------------------------------

if ($phone === '') {

    $errors['phone'] =
        'Phone number is required.';

} else {

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

    } else {

        $phone = $phoneDigits;
    }
}


// ---------------------------------------------------------
// Email
// ---------------------------------------------------------

if ($email === '') {

    $errors['email'] =
        'Email address is required.';

} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    $errors['email'] =
        'Please enter a valid email address.';
}


// ---------------------------------------------------------
// Address
// ---------------------------------------------------------

if ($address === '') {

    $errors['address'] =
        'Delivery address is required.';

} elseif (mb_strlen($address) > 500) {

    $errors['address'] =
        'Address cannot exceed 500 characters.';
}


// ---------------------------------------------------------
// City
// ---------------------------------------------------------

if ($city === '') {

    $errors['city'] =
        'City is required.';

} elseif (mb_strlen($city) > 100) {

    $errors['city'] =
        'City cannot exceed 100 characters.';
}


// ---------------------------------------------------------
// State
// ---------------------------------------------------------

if ($state === '') {

    $errors['state'] =
        'State is required.';

} elseif (mb_strlen($state) > 100) {

    $errors['state'] =
        'State cannot exceed 100 characters.';
}


// ---------------------------------------------------------
// Pincode
// ---------------------------------------------------------

if ($pincode === '') {

    $errors['pincode'] =
        'Pincode is required.';

} else {

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

    } else {

        $pincode = $pincodeDigits;
    }
}


// ---------------------------------------------------------
// Landmark
// ---------------------------------------------------------

if (
    $landmark !== '' &&
    mb_strlen($landmark) > 200
) {

    $errors['landmark'] =
        'Landmark cannot exceed 200 characters.';
}


// ---------------------------------------------------------
// Validate Payment Method
// ---------------------------------------------------------

$allowedPaymentMethods = [
    'cod'
];

if (!in_array(
    $paymentMethod,
    $allowedPaymentMethods,
    true
)) {

    $errors['payment_method'] =
        'Selected payment method is not available.';
}


// ---------------------------------------------------------
// Return Validation Errors
// ---------------------------------------------------------

if (!empty($errors)) {

    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'Please correct the checkout information.',
        'errors' => $errors
    ]);

    exit;
}


// ---------------------------------------------------------
// Start Database Transaction
// ---------------------------------------------------------

$conn->begin_transaction();

try {

    // -----------------------------------------------------
    // Get User
    // -----------------------------------------------------

    $userSql = "
        SELECT
            id,
            full_name,
            email,
            phone,
            status
        FROM users
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ";

    $userStmt = $conn->prepare($userSql);

    if (!$userStmt) {

        throw new Exception(
            'Unable to prepare user query.'
        );
    }

    $userStmt->bind_param(
        'i',
        $userId
    );

    if (!$userStmt->execute()) {

        $userStmt->close();

        throw new Exception(
            'Unable to verify user.'
        );
    }

    $userResult = $userStmt->get_result();

    if ($userResult->num_rows !== 1) {

        $userStmt->close();

        throw new Exception(
            'User account was not found.'
        );
    }

    $user = $userResult->fetch_assoc();

    $userStmt->close();


    // -----------------------------------------------------
    // Check User Status
    // -----------------------------------------------------

    if (
        isset($user['status']) &&
        $user['status'] !== 'active'
    ) {

        throw new Exception(
            'User account is not active.'
        );
    }


    // -----------------------------------------------------
    // Get Cart Items
    // -----------------------------------------------------

    $cartSql = "
        SELECT
            c.id AS cart_id,
            c.medicine_id,
            c.quantity,
            m.name,
            m.price,
            m.stock,
            m.image,
            m.status
        FROM cart c
        INNER JOIN medicines m
            ON m.id = c.medicine_id
        WHERE c.user_id = ?
        FOR UPDATE
    ";

    $cartStmt = $conn->prepare($cartSql);

    if (!$cartStmt) {

        throw new Exception(
            'Unable to prepare cart query.'
        );
    }

    $cartStmt->bind_param(
        'i',
        $userId
    );

    if (!$cartStmt->execute()) {

        $cartStmt->close();

        throw new Exception(
            'Unable to load cart.'
        );
    }

    $cartResult = $cartStmt->get_result();


    // -----------------------------------------------------
    // Empty Cart
    // -----------------------------------------------------

    if ($cartResult->num_rows === 0) {

        $cartStmt->close();

        throw new Exception(
            'Your cart is empty.'
        );
    }


    $cartItems = [];

    $subtotal = 0.00;


    // -----------------------------------------------------
    // Validate Every Cart Item
    // -----------------------------------------------------

    while ($row = $cartResult->fetch_assoc()) {

        $medicineId = (int) $row['medicine_id'];

        $quantity = (int) $row['quantity'];

        $price = (float) $row['price'];

        $stock = (int) $row['stock'];

        $medicineStatus = (string) $row['status'];


        // -------------------------------------------------
        // Check Medicine Status
        // -------------------------------------------------

        if ($medicineStatus !== 'active') {

            $cartStmt->close();

            throw new Exception(
                $row['name'] .
                ' is currently unavailable.'
            );
        }


        // -------------------------------------------------
        // Quantity Validation
        // -------------------------------------------------

        if ($quantity <= 0) {

            $cartStmt->close();

            throw new Exception(
                'Invalid quantity for ' .
                $row['name'] . '.'
            );
        }


        // -------------------------------------------------
        // Stock Validation
        // -------------------------------------------------

        if ($stock <= 0) {

            $cartStmt->close();

            throw new Exception(
                $row['name'] .
                ' is currently out of stock.'
            );
        }


        if ($quantity > $stock) {

            $cartStmt->close();

            throw new Exception(
                'Only ' .
                $stock .
                ' item(s) of ' .
                $row['name'] .
                ' are available.'
            );
        }


        // -------------------------------------------------
        // Calculate Item Total
        // -------------------------------------------------

        $itemTotal = round(
            $price * $quantity,
            2
        );

        $subtotal += $itemTotal;


        // -------------------------------------------------
        // Store Cart Item
        // -------------------------------------------------

        $cartItems[] = [

            'cart_id' =>
                (int) $row['cart_id'],

            'medicine_id' =>
                $medicineId,

            'name' =>
                $row['name'],

            'price' =>
                $price,

            'quantity' =>
                $quantity,

            'stock' =>
                $stock,

            'image' =>
                $row['image'] ?? '',

            'item_total' =>
                $itemTotal
        ];
    }

    $cartStmt->close();


    // -----------------------------------------------------
    // Delivery Charge
    // -----------------------------------------------------

    /*
     * Free delivery for orders above ₹500.
     * ₹40 delivery charge for orders below ₹500.
     */

    $deliveryCharge =
        $subtotal >= 500
        ? 0.00
        : 40.00;


    // -----------------------------------------------------
    // Calculate Final Total
    // -----------------------------------------------------

    $total = round(
        $subtotal + $deliveryCharge,
        2
    );


    // -----------------------------------------------------
    // Generate Order Number
    // -----------------------------------------------------

    $orderNumber =
        'MED-' .
        date('YmdHis') .
        '-' .
        strtoupper(
            substr(
                bin2hex(random_bytes(3)),
                0,
                6
            )
        );


    // -----------------------------------------------------
    // Create Order
    // -----------------------------------------------------

    /*
     * Expected orders columns:
     *
     * id
     * user_id
     * order_number
     * total_amount
     * status
     * payment_method
     * payment_status
     * full_name
     * phone
     * email
     * address
     * city
     * state
     * pincode
     * landmark
     */

    $orderSql = "
        INSERT INTO orders
        (
            user_id,
            order_number,
            total_amount,
            status,
            payment_method,
            payment_status,
            full_name,
            phone,
            email,
            address,
            city,
            state,
            pincode,
            landmark
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ";


    // IMPORTANT:
    // Order status starts with "Order Placed"

    $orderStatus = 'Order Placed';

    $paymentStatus = 'Pending';


    $orderStmt = $conn->prepare($orderSql);

    if (!$orderStmt) {

        throw new Exception(
            'Unable to prepare order creation.'
        );
    }


    $orderStmt->bind_param(
        'isdsssssssssss',
        $userId,
        $orderNumber,
        $total,
        $orderStatus,
        $paymentMethod,
        $paymentStatus,
        $fullName,
        $phone,
        $email,
        $address,
        $city,
        $state,
        $pincode,
        $landmark
    );


    if (!$orderStmt->execute()) {

        $orderStmt->close();

        throw new Exception(
            'Unable to create order.'
        );
    }


    $orderId = (int) $conn->insert_id;

    $orderStmt->close();


    // -----------------------------------------------------
    // Insert Order Items
    // -----------------------------------------------------

    $itemSql = "
        INSERT INTO order_items
        (
            order_id,
            medicine_id,
            quantity,
            price
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?
        )
    ";


    $itemStmt = $conn->prepare($itemSql);

    if (!$itemStmt) {

        throw new Exception(
            'Unable to prepare order item creation.'
        );
    }


    // -----------------------------------------------------
    // Update Stock Statement
    // -----------------------------------------------------

    $stockSql = "
        UPDATE medicines
        SET stock = stock - ?
        WHERE id = ?
          AND status = 'active'
          AND stock >= ?
        LIMIT 1
    ";


    $stockStmt = $conn->prepare($stockSql);

    if (!$stockStmt) {

        $itemStmt->close();

        throw new Exception(
            'Unable to prepare stock update.'
        );
    }


    // -----------------------------------------------------
    // Process Order Items
    // -----------------------------------------------------

    foreach ($cartItems as $item) {

        $medicineId =
            $item['medicine_id'];

        $quantity =
            $item['quantity'];

        $price =
            $item['price'];


        // -------------------------------------------------
        // Insert Order Item
        // -------------------------------------------------

        $itemStmt->bind_param(
            'iiid',
            $orderId,
            $medicineId,
            $quantity,
            $price
        );


        if (!$itemStmt->execute()) {

            $itemStmt->close();

            $stockStmt->close();

            throw new Exception(
                'Unable to create order items.'
            );
        }


        // -------------------------------------------------
        // Decrease Stock
        // -------------------------------------------------

        $stockStmt->bind_param(
            'iii',
            $quantity,
            $medicineId,
            $quantity
        );


        if (!$stockStmt->execute()) {

            $itemStmt->close();

            $stockStmt->close();

            throw new Exception(
                'Unable to update medicine stock.'
            );
        }


        if ($stockStmt->affected_rows !== 1) {

            $itemStmt->close();

            $stockStmt->close();

            throw new Exception(
                'Medicine stock changed while processing the order.'
            );
        }
    }


    $itemStmt->close();

    $stockStmt->close();


    // -----------------------------------------------------
    // Create Initial Tracking Record
    // -----------------------------------------------------

    /*
     * Expected order_tracking columns:
     *
     * id
     * order_id
     * status
     * description
     * created_at
     */

    $trackingSql = "
        INSERT INTO order_tracking
        (
            order_id,
            status,
            description
        )
        VALUES
        (
            ?,
            ?,
            ?
        )
    ";


    $trackingDescription =
        'Your order has been placed successfully.';


    $trackingStmt =
        $conn->prepare($trackingSql);


    if (!$trackingStmt) {

        throw new Exception(
            'Unable to prepare order tracking.'
        );
    }


    $trackingStmt->bind_param(
        'iss',
        $orderId,
        $orderStatus,
        $trackingDescription
    );


    if (!$trackingStmt->execute()) {

        $trackingStmt->close();

        throw new Exception(
            'Unable to create order tracking record.'
        );
    }


    $trackingStmt->close();


    // -----------------------------------------------------
    // Clear User Cart
    // -----------------------------------------------------

    $clearCartSql = "
        DELETE FROM cart
        WHERE user_id = ?
    ";


    $clearCartStmt =
        $conn->prepare(
            $clearCartSql
        );


    if (!$clearCartStmt) {

        throw new Exception(
            'Unable to prepare cart cleanup.'
        );
    }


    $clearCartStmt->bind_param(
        'i',
        $userId
    );


    if (!$clearCartStmt->execute()) {

        $clearCartStmt->close();

        throw new Exception(
            'Unable to clear cart.'
        );
    }


    $clearCartStmt->close();


    // -----------------------------------------------------
    // Commit Transaction
    // -----------------------------------------------------

    $conn->commit();


    // -----------------------------------------------------
    // Prepare Order Items Response
    // -----------------------------------------------------

    $responseItems = [];


    foreach ($cartItems as $item) {

        $responseItems[] = [

            'medicine_id' =>
                $item['medicine_id'],

            'name' =>
                $item['name'],

            'price' =>
                $item['price'],

            'quantity' =>
                $item['quantity'],

            'item_total' =>
                $item['item_total']
        ];
    }


    // -----------------------------------------------------
    // Successful Response
    // -----------------------------------------------------

    http_response_code(201);


    echo json_encode([

        'success' => true,

        'message' =>
            'Order placed successfully.',

        'order' => [

            'id' =>
                $orderId,

            'order_number' =>
                $orderNumber,

            'status' =>
                $orderStatus,

            'payment_method' =>
                $paymentMethod,

            'payment_status' =>
                $paymentStatus,

            'subtotal' =>
                round($subtotal, 2),

            'delivery' =>
                $deliveryCharge,

            'total' =>
                $total,

            'items' =>
                $responseItems,

            'delivery_address' => [

                'full_name' =>
                    $fullName,

                'phone' =>
                    $phone,

                'email' =>
                    $email,

                'address' =>
                    $address,

                'city' =>
                    $city,

                'state' =>
                    $state,

                'pincode' =>
                    $pincode,

                'landmark' =>
                    $landmark
            ]
        ]
    ]);

    exit;


} catch (Throwable $e) {

    // -----------------------------------------------------
    // Rollback Everything
    // -----------------------------------------------------

    $conn->rollback();


    // -----------------------------------------------------
    // Error Response
    // -----------------------------------------------------

    http_response_code(500);


    echo json_encode([

        'success' => false,

        'message' =>
            'Unable to place your order. Please try again.'
    ]);

    exit;
}