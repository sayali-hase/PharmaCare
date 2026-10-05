<?php
/*
 * PharmaCare Pharmacy Order Tracking System
 * Get Order API
 * File: backend/orders/get-order.php
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../config/database.php";

$response = [
    "success" => false,
    "message" => "",
    "data" => null
];

try {

    // Check order_id
    if (!isset($_GET["order_id"]) || empty($_GET["order_id"])) {
        $response["message"] = "Order ID is required";
        echo json_encode($response);
        exit;
    }

    $order_id = intval($_GET["order_id"]);

    if ($order_id <= 0) {
        $response["message"] = "Invalid Order ID";
        echo json_encode($response);
        exit;
    }


    /* =========================================
       1. GET ORDER DETAILS
       ========================================= */

    $orderSql = "
        SELECT
            o.order_id,
            o.user_id,
            o.order_date,
            o.total_amount,
            o.payment_method,
            o.payment_status,
            o.order_status,
            o.delivery_address,
            o.created_at,

            u.full_name,
            u.email,
            u.phone

        FROM orders o

        INNER JOIN users u
            ON o.user_id = u.user_id

        WHERE o.order_id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($orderSql);

    if (!$stmt) {
        throw new Exception("Failed to prepare order query");
    }

    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    $orderResult = $stmt->get_result();

    if ($orderResult->num_rows === 0) {
        $response["message"] = "Order not found";
        echo json_encode($response);
        exit;
    }

    $order = $orderResult->fetch_assoc();

    $stmt->close();


    /* =========================================
       2. GET ORDER ITEMS
       ========================================= */

    $itemSql = "
        SELECT
            oi.order_item_id,
            oi.order_id,
            oi.medicine_id,
            oi.quantity,
            oi.unit_price,
            oi.subtotal,

            m.name AS medicine_name,
            m.description,
            m.image

        FROM order_items oi

        INNER JOIN medicines m
            ON oi.medicine_id = m.medicine_id

        WHERE oi.order_id = ?

        ORDER BY oi.order_item_id ASC
    ";

    $stmt = $conn->prepare($itemSql);

    if (!$stmt) {
        throw new Exception("Failed to prepare order items query");
    }

    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    $itemResult = $stmt->get_result();

    $items = [];

    while ($item = $itemResult->fetch_assoc()) {
        $items[] = $item;
    }

    $stmt->close();


    /* =========================================
       3. GET DELIVERY DETAILS
       ========================================= */

    $deliverySql = "
        SELECT
            delivery_id,
            order_id,
            delivery_address,
            city,
            state,
            pincode,
            delivery_status,
            expected_delivery_date,
            delivered_at,
            created_at

        FROM deliveries

        WHERE order_id = ?

        ORDER BY delivery_id DESC
        LIMIT 1
    ";

    $stmt = $conn->prepare($deliverySql);

    if (!$stmt) {
        throw new Exception("Failed to prepare delivery query");
    }

    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    $deliveryResult = $stmt->get_result();

    $delivery = null;

    if ($deliveryResult->num_rows > 0) {
        $delivery = $deliveryResult->fetch_assoc();
    }

    $stmt->close();


    /* =========================================
       4. GET PAYMENT DETAILS
       ========================================= */

    $paymentSql = "
        SELECT
            payment_id,
            order_id,
            payment_method,
            transaction_id,
            amount,
            payment_status,
            paid_at,
            created_at

        FROM payments

        WHERE order_id = ?

        ORDER BY payment_id DESC
        LIMIT 1
    ";

    $stmt = $conn->prepare($paymentSql);

    if (!$stmt) {
        throw new Exception("Failed to prepare payment query");
    }

    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    $paymentResult = $stmt->get_result();

    $payment = null;

    if ($paymentResult->num_rows > 0) {
        $payment = $paymentResult->fetch_assoc();
    }

    $stmt->close();


    /* =========================================
       5. GET ORDER TRACKING
       ========================================= */

    $trackingSql = "
        SELECT
            tracking_id,
            order_id,
            status,
            status_time,
            location

        FROM order_tracking

        WHERE order_id = ?

        ORDER BY status_time ASC
    ";

    $stmt = $conn->prepare($trackingSql);

    if (!$stmt) {
        throw new Exception("Failed to prepare tracking query");
    }

    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    $trackingResult = $stmt->get_result();

    $tracking = [];

    while ($track = $trackingResult->fetch_assoc()) {
        $tracking[] = $track;
    }

    $stmt->close();


    /* =========================================
       6. FINAL RESPONSE
       ========================================= */

    $response["success"] = true;
    $response["message"] = "Order details fetched successfully";

    $response["data"] = [
        "order" => $order,
        "items" => $items,
        "delivery" => $delivery,
        "payment" => $payment,
        "tracking" => $tracking
    ];

    echo json_encode($response);


} catch (Exception $e) {

    http_response_code(500);

    $response["success"] = false;
    $response["message"] = $e->getMessage();

    echo json_encode($response);
}
?>
