<?php

/**
 * PharmaCare Pharmacy Order Tracking System
 * Get Orders API
 *
 * File:
 * backend/orders/get-orders.php
 */

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';


// =========================================================
// ONLY GET REQUESTS
// =========================================================

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    http_response_code(405);

    header('Allow: GET');

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Please use GET.'
    ]);

    exit;
}


// =========================================================
// CHECK LOGIN
// =========================================================

$isAdmin =
    isset($_SESSION['admin_id']) &&
    ($_SESSION['admin_logged_in'] ?? false) === true;

$isCustomer =
    isset($_SESSION['user_id']) &&
    (int) $_SESSION['user_id'] > 0;


if (!$isAdmin && !$isCustomer) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Login required.',
        'login_required' => true
    ]);

    exit;
}


// =========================================================
// USER ID
// =========================================================

$userId = $isCustomer
    ? (int) $_SESSION['user_id']
    : 0;


// =========================================================
// PAGINATION
// =========================================================

$page = filter_input(
    INPUT_GET,
    'page',
    FILTER_VALIDATE_INT
);

$limit = filter_input(
    INPUT_GET,
    'limit',
    FILTER_VALIDATE_INT
);


if ($page === false || $page === null || $page < 1) {
    $page = 1;
}


if ($limit === false || $limit === null || $limit < 1) {
    $limit = 20;
}


if ($limit > 50) {
    $limit = 50;
}


$offset = ($page - 1) * $limit;


// =========================================================
// SEARCH
// =========================================================

$search = trim(
    (string) ($_GET['search'] ?? '')
);


// =========================================================
// STATUS
// =========================================================

$status = trim(
    (string) ($_GET['status'] ?? '')
);


// =========================================================
// BUILD WHERE CONDITION
// =========================================================

$where = [];

$params = [];

$types = '';


// ---------------------------------------------------------
// CUSTOMER CAN SEE ONLY THEIR OWN ORDERS
// ---------------------------------------------------------

if ($isCustomer) {

    $where[] = 'o.user_id = ?';

    $params[] = $userId;

    $types .= 'i';
}


// ---------------------------------------------------------
// ADMIN SEARCH
// Documentation-supported fields only
// ---------------------------------------------------------

if ($isAdmin && $search !== '') {

    $where[] = "
        (
            CAST(o.order_id AS CHAR) LIKE ?
            OR u.full_name LIKE ?
            OR u.email LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= 'sss';
}


// ---------------------------------------------------------
// STATUS FILTER
// ---------------------------------------------------------

if ($status !== '' && strtolower($status) !== 'all') {

    $where[] = 'o.order_status = ?';

    $params[] = $status;

    $types .= 's';
}


// =========================================================
// WHERE SQL
// =========================================================

$whereSql = '';

if (!empty($where)) {

    $whereSql =
        'WHERE ' .
        implode(' AND ', $where);
}


// =========================================================
// COUNT ORDERS
// =========================================================

$countSql = "
    SELECT COUNT(*) AS total

    FROM orders o

    LEFT JOIN users u
        ON u.user_id = o.user_id

    $whereSql
";


$countStmt = $conn->prepare($countSql);


if (!$countStmt) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to prepare order count query.'
    ]);

    exit;
}


if (!empty($params)) {

    $countStmt->bind_param(
        $types,
        ...$params
    );
}


if (!$countStmt->execute()) {

    $countStmt->close();

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to count orders.'
    ]);

    exit;
}


$countResult = $countStmt->get_result();

$countRow = $countResult->fetch_assoc();

$totalOrders = (int) ($countRow['total'] ?? 0);

$countStmt->close();


// =========================================================
// GET ORDERS
// =========================================================

$sql = "
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
        u.phone,

        COUNT(oi.order_item_id) AS unique_items,

        COALESCE(
            SUM(oi.quantity),
            0
        ) AS total_items

    FROM orders o

    LEFT JOIN users u
        ON u.user_id = o.user_id

    LEFT JOIN order_items oi
        ON oi.order_id = o.order_id

    $whereSql

    GROUP BY
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

    ORDER BY o.order_id DESC

    LIMIT ? OFFSET ?
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to prepare order list.'
    ]);

    exit;
}


$queryTypes = $types . 'ii';

$queryParams = $params;

$queryParams[] = $limit;

$queryParams[] = $offset;


$stmt->bind_param(
    $queryTypes,
    ...$queryParams
);


if (!$stmt->execute()) {

    $stmt->close();

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to load orders.'
    ]);

    exit;
}


$result = $stmt->get_result();

$orders = [];


// =========================================================
// BUILD ORDERS
// =========================================================

while ($row = $result->fetch_assoc()) {

    $orderId = (int) $row['order_id'];

    $orders[] = [

        'order_id' =>
        $orderId,

        'user_id' =>
        (int) $row['user_id'],

        'full_name' =>
        $row['full_name'] ?? 'Customer',

        'email' =>
        $row['email'] ?? '',

        'phone' =>
        $row['phone'] ?? '',

        'order_date' =>
        $row['order_date'] ?? null,

        'total_amount' =>
        (float) $row['total_amount'],

        'payment_method' =>
        $row['payment_method'] ?? '',

        'payment_status' =>
        $row['payment_status'] ?? '',

        'order_status' =>
        $row['order_status'] ?? '',

        'delivery_address' =>
        $row['delivery_address'] ?? '',

        'unique_items' =>
        (int) $row['unique_items'],

        'total_items' =>
        (int) $row['total_items'],

        'created_at' =>
        $row['created_at'] ?? null,

        'items' => []
    ];
}


$stmt->close();


// =========================================================
// LOAD ORDER ITEMS
// =========================================================

if (!empty($orders)) {

    foreach ($orders as &$order) {

        $orderId = (int) $order['order_id'];


        $itemSql = "
            SELECT

                oi.order_item_id,
                oi.order_id,
                oi.medicine_id,
                oi.quantity,
                oi.unit_price,
                oi.subtotal,

                m.name,
                m.image

            FROM order_items oi

            LEFT JOIN medicines m
                ON m.medicine_id = oi.medicine_id

            WHERE oi.order_id = ?

            ORDER BY oi.order_item_id ASC
        ";


        $itemStmt = $conn->prepare($itemSql);


        if (!$itemStmt) {
            continue;
        }


        $itemStmt->bind_param(
            'i',
            $orderId
        );


        if (!$itemStmt->execute()) {

            $itemStmt->close();

            continue;
        }


        $itemResult = $itemStmt->get_result();

        $items = [];


        while ($itemRow = $itemResult->fetch_assoc()) {

            $items[] = [

                'order_item_id' =>
                (int) $itemRow['order_item_id'],

                'order_id' =>
                (int) $itemRow['order_id'],

                'medicine_id' =>
                (int) $itemRow['medicine_id'],

                'name' =>
                $itemRow['name'] ?? 'Medicine',

                'medicine_name' =>
                $itemRow['name'] ?? 'Medicine',

                'image' =>
                $itemRow['image'] ?? '',

                'quantity' =>
                (int) $itemRow['quantity'],

                'unit_price' =>
                (float) $itemRow['unit_price'],

                'subtotal' =>
                (float) $itemRow['subtotal']
            ];
        }


        $itemStmt->close();


        $order['items'] = $items;
    }

    unset($order);
}


// =========================================================
// PAGINATION
// =========================================================

$totalPages =
    $totalOrders > 0
    ? (int) ceil(
        $totalOrders / $limit
    )
    : 0;


// =========================================================
// RESPONSE
// =========================================================

http_response_code(200);


echo json_encode([

    'success' => true,

    'message' =>
    empty($orders)
        ? 'No orders found.'
        : 'Orders loaded successfully.',

    'role' =>
    $isAdmin
        ? 'admin'
        : 'customer',

    'orders' =>
    $orders,

    'pagination' => [

        'page' =>
        $page,

        'current_page' =>
        $page,

        'limit' =>
        $limit,

        'total' =>
        $totalOrders,

        'total_pages' =>
        $totalPages,

        'has_next' =>
        $page < $totalPages,

        'has_previous' =>
        $page > 1
    ]

]);


exit;
