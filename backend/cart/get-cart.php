<?php

/**
 * MediCare Pharmacy
 * Get Cart API
 *
 * File:
 * backend/cart/get-cart.php
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
        'message' => 'Please login to view your cart.',
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
// Get Cart Items
// ---------------------------------------------------------

$sql = "
    SELECT
        c.cart_id,
        c.user_id,
        c.medicine_id,
        c.quantity,

        m.name,
        m.description,
        m.price,
        m.stock,
        m.image,
        m.category_id,

        cat.name AS category

    FROM cart c

    INNER JOIN medicines m
        ON m.medicine_id = c.medicine_id

    LEFT JOIN categories cat
        ON cat.category_id = m.category_id

    WHERE c.user_id = ?

    ORDER BY c.cart_id DESC
";


$stmt = $conn->prepare($sql);

if (!$stmt) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to prepare cart query.'
    ]);

    exit;
}


// ---------------------------------------------------------
// Bind User ID
// ---------------------------------------------------------

$stmt->bind_param('i', $userId);


// ---------------------------------------------------------
// Execute Query
// ---------------------------------------------------------

if (!$stmt->execute()) {

    $stmt->close();

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to load cart.'
    ]);

    exit;
}


$result = $stmt->get_result();


// ---------------------------------------------------------
// Prepare Cart Data
// ---------------------------------------------------------

$items = [];

$itemCount = 0;

$subtotal = 0.00;

$hasStockIssue = false;


// ---------------------------------------------------------
// Process Cart Items
// ---------------------------------------------------------

while ($row = $result->fetch_assoc()) {

    $cartId = (int) $row['cart_id'];

    $medicineId = (int) $row['medicine_id'];

    $quantity = (int) $row['quantity'];

    $price = (float) $row['price'];

    $stock = (int) $row['stock'];


    // -----------------------------------------------------
    // Safety Check
    // -----------------------------------------------------

    if ($quantity < 1) {
        $quantity = 1;
    }


    // -----------------------------------------------------
    // Stock Status
    // -----------------------------------------------------

    if ($stock <= 0) {

        $stockStatus = 'out_of_stock';

        $hasStockIssue = true;

    } elseif ($quantity > $stock) {

        $stockStatus = 'insufficient_stock';

        $hasStockIssue = true;

    } else {

        $stockStatus = 'available';
    }


    // -----------------------------------------------------
    // Calculate Item Total
    // -----------------------------------------------------

    $itemTotal = round(
        $price * $quantity,
        2
    );


    // -----------------------------------------------------
    // Calculate Subtotal
    // -----------------------------------------------------

    $subtotal += $itemTotal;

    $itemCount += $quantity;


    // -----------------------------------------------------
    // Add Cart Item
    // -----------------------------------------------------

    $items[] = [

        'id' => $cartId,

        'cart_id' => $cartId,

        'user_id' => $userId,

        'medicine_id' => $medicineId,

        'name' => $row['name'],

        'medicine_name' => $row['name'],

        'description' => $row['description'] ?? '',

        'category_id' => isset($row['category_id'])
            ? (int) $row['category_id']
            : null,

        'category' => $row['category'] ?? 'General',

        'price' => $price,

        'quantity' => $quantity,

        'stock' => $stock,

        'image' => $row['image'] ?? '',

        'item_total' => $itemTotal,

        'stock_status' => $stockStatus,

        'can_checkout' => $stockStatus === 'available'
    ];
}


$stmt->close();


// ---------------------------------------------------------
// Final Calculations
// ---------------------------------------------------------

$subtotal = round($subtotal, 2);

$delivery = null;

$total = $subtotal;


// ---------------------------------------------------------
// Successful Response
// ---------------------------------------------------------

http_response_code(200);

echo json_encode([

    'success' => true,

    'message' => empty($items)
        ? 'Your cart is empty.'
        : 'Cart loaded successfully.',

    'items' => $items,

    'item_count' => $itemCount,

    'unique_items' => count($items),

    'subtotal' => $subtotal,

    'delivery' => $delivery,

    'total' => $total,

    'has_stock_issue' => $hasStockIssue,

    'cart' => [

        'items' => $items,

        'item_count' => $itemCount,

        'unique_items' => count($items),

        'subtotal' => $subtotal,

        'delivery' => $delivery,

        'total' => $total,

        'has_stock_issue' => $hasStockIssue
    ]

]);

exit;
