<?php

/**
 * PharmaCare Pharmacy Order Tracking System
 * Track Order API
 *
 * File:
 * backend/orders/track-order.php
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
        'message' => 'Please login to track your order.',
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
// Get Order ID
// ---------------------------------------------------------

$orderId = filter_input(
    INPUT_GET,
    'order_id',
    FILTER_VALIDATE_INT
);


// Support ?id=123 as well

if (
    $orderId === false ||
    $orderId === null ||
    $orderId <= 0
) {

    $orderId = filter_input(
        INPUT_GET,
        'id',
        FILTER_VALIDATE_INT
    );
}


// ---------------------------------------------------------
// Validate Order ID
// ---------------------------------------------------------

if (
    $orderId === false ||
    $orderId === null ||
    $orderId <= 0
) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'A valid order ID is required.'
    ]);

    exit;
}

$orderId = (int) $orderId;


// ---------------------------------------------------------
// Verify Order Belongs to User
// ---------------------------------------------------------

$orderSql = "
    SELECT
        order_id,
        user_id,
        order_date,
        total_amount,
        payment_method,
        payment_status,
        order_status,
        delivery_address,
        created_at
    FROM orders
    WHERE order_id = ?
      AND user_id = ?
    LIMIT 1
";

$orderStmt = $conn->prepare($orderSql);

if (!$orderStmt) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to prepare order query.'
    ]);

    exit;
}

$orderStmt->bind_param(
    'ii',
    $orderId,
    $userId
);

if (!$orderStmt->execute()) {

    $orderStmt->close();

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to verify order.'
    ]);

    exit;
}

$orderResult = $orderStmt->get_result();


// ---------------------------------------------------------
// Order Not Found
// ---------------------------------------------------------

if ($orderResult->num_rows !== 1) {

    $orderStmt->close();

    http_response_code(404);

    echo json_encode([
        'success' => false,
        'message' => 'Order not found.'
    ]);

    exit;
}

$order = $orderResult->fetch_assoc();

$orderStmt->close();


// ---------------------------------------------------------
// Get Current Order Status
// ---------------------------------------------------------

$currentStatus = trim(
    (string) ($order['order_status'] ?? 'Pending')
);


// ---------------------------------------------------------
// Get Tracking Records
// ---------------------------------------------------------

$tracking = [];

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

$trackingStmt = $conn->prepare($trackingSql);

if ($trackingStmt) {

    $trackingStmt->bind_param(
        'i',
        $orderId
    );

    if ($trackingStmt->execute()) {

        $trackingResult =
            $trackingStmt->get_result();

        while (
            $row =
            $trackingResult->fetch_assoc()
        ) {

            $tracking[] = [
                'tracking_id' =>
                    (int) $row['tracking_id'],

                'status' =>
                    $row['status'],

                'status_time' =>
                    $row['status_time'],

                'location' =>
                    $row['location']
            ];
        }
    }

    $trackingStmt->close();
}


// ---------------------------------------------------------
// Status Definitions
// ---------------------------------------------------------

$statuses = [
    'Pending',
    'Accepted',
    'Processing',
    'Shipped',
    'Delivered'
];


// ---------------------------------------------------------
// Handle Cancelled Order
// ---------------------------------------------------------

if (
    strtolower($currentStatus) === 'cancelled' ||
    strtolower($currentStatus) === 'canceled'
) {

    http_response_code(200);

    echo json_encode([
        'success' => true,

        'message' =>
            'Order tracking loaded successfully.',

        'order' => [
            'order_id' =>
                (int) $order['order_id'],

            'status' =>
                'Cancelled',

            'order_date' =>
                $order['order_date'] ?? null,

            'delivery_address' =>
                $order['delivery_address'] ?? null,

            'created_at' =>
                $order['created_at'] ?? null
        ],

        'tracking' => $tracking,

        'progress' => [
            'current_step' => 0,
            'total_steps' => count($statuses),
            'percentage' => 0
        ]
    ]);

    exit;
}


// ---------------------------------------------------------
// Find Current Status Position
// ---------------------------------------------------------

$currentIndex = array_search(
    $currentStatus,
    $statuses,
    true
);


// ---------------------------------------------------------
// Handle Unknown Status
// ---------------------------------------------------------

if ($currentIndex === false) {

    $currentIndex = 0;
}


// ---------------------------------------------------------
// Create Timeline
// ---------------------------------------------------------

$timeline = [];


// ---------------------------------------------------------
// Build Timeline
// ---------------------------------------------------------

foreach ($statuses as $index => $status) {

    $completed =
        $index < $currentIndex;

    $current =
        $index === $currentIndex;

    $statusTime = null;

    $location = null;


    // -----------------------------------------------------
    // Find Actual Tracking Record
    // -----------------------------------------------------

    foreach ($tracking as $record) {

        if (
            strtolower(
                trim(
                    (string) $record['status']
                )
            ) === strtolower($status)
        ) {

            $statusTime =
                $record['status_time'];

            $location =
                $record['location'];

            break;
        }
    }


    // -----------------------------------------------------
    // Default Location
    // -----------------------------------------------------

    if ($location === null) {

        $location = 'Pharmacy';
    }


    // -----------------------------------------------------
    // Timeline
    // -----------------------------------------------------

    $timeline[] = [

        'tracking_id' => null,

        'status' =>
            $status,

        'status_time' =>
            $statusTime,

        'location' =>
            $location,

        'completed' =>
            $completed,

        'current' =>
            $current,

        'step' =>
            $index + 1
    ];
}


// ---------------------------------------------------------
// Add Actual Tracking Records Not in Standard List
// ---------------------------------------------------------

$knownStatuses = array_map(
    'strtolower',
    $statuses
);

foreach ($tracking as $record) {

    $recordStatus = trim(
        (string) $record['status']
    );

    if (
        !in_array(
            strtolower($recordStatus),
            $knownStatuses,
            true
        )
    ) {

        $timeline[] = [

            'tracking_id' =>
                $record['tracking_id'],

            'status' =>
                $recordStatus,

            'status_time' =>
                $record['status_time'],

            'location' =>
                $record['location'],

            'completed' =>
                true,

            'current' =>
                strtolower($recordStatus)
                === strtolower($currentStatus),

            'step' =>
                count($timeline) + 1
        ];
    }
}


// ---------------------------------------------------------
// Calculate Progress
// ---------------------------------------------------------

$totalSteps = count($statuses);

$progressPercent = round(
    (($currentIndex + 1) / $totalSteps) * 100
);


// ---------------------------------------------------------
// Successful Response
// ---------------------------------------------------------

http_response_code(200);

echo json_encode([

    'success' => true,

    'message' =>
        'Order tracking loaded successfully.',

    'order' => [

        'order_id' =>
            (int) $order['order_id'],

        'user_id' =>
            (int) $order['user_id'],

        'order_date' =>
            $order['order_date'] ?? null,

        'total_amount' =>
            $order['total_amount'] ?? null,

        'payment_method' =>
            $order['payment_method'] ?? null,

        'payment_status' =>
            $order['payment_status'] ?? null,

        'status' =>
            $currentStatus,

        'delivery_address' =>
            $order['delivery_address'] ?? null,

        'created_at' =>
            $order['created_at'] ?? null
    ],

    'tracking' =>
        $timeline,

    'progress' => [

        'current_step' =>
            $currentIndex + 1,

        'total_steps' =>
            $totalSteps,

        'percentage' =>
            $progressPercent
    ]

]);

exit;
