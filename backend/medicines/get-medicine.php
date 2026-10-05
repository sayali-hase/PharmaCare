<?php

/**
 * MediCare Pharmacy
 * Get Single Medicine API
 *
 * File:
 * backend/medicines/get-medicine.php
 */

declare(strict_types=1);

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
// Get Medicine ID
// ---------------------------------------------------------

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


// ---------------------------------------------------------
// Validate Medicine ID
// ---------------------------------------------------------

if ($id === false || $id === null || $id <= 0) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'A valid medicine ID is required.'
    ]);

    exit;
}


// ---------------------------------------------------------
// Get Medicine
// ---------------------------------------------------------

$sql = "
    SELECT
        m.id,
        m.name,
        m.description,
        m.price,
        m.stock,
        m.image,
        m.category_id,
        c.name AS category
    FROM medicines m
    LEFT JOIN categories c
        ON c.id = m.category_id
    WHERE m.id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to prepare medicine query.'
    ]);

    exit;
}


// ---------------------------------------------------------
// Bind Medicine ID
// ---------------------------------------------------------

$stmt->bind_param('i', $id);


// ---------------------------------------------------------
// Execute Query
// ---------------------------------------------------------

if (!$stmt->execute()) {

    $stmt->close();

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to load medicine.'
    ]);

    exit;
}


// ---------------------------------------------------------
// Get Result
// ---------------------------------------------------------

$result = $stmt->get_result();


// ---------------------------------------------------------
// Medicine Not Found
// ---------------------------------------------------------

if ($result->num_rows !== 1) {

    $stmt->close();

    http_response_code(404);

    echo json_encode([
        'success' => false,
        'message' => 'Medicine not found.'
    ]);

    exit;
}


// ---------------------------------------------------------
// Fetch Medicine
// ---------------------------------------------------------

$row = $result->fetch_assoc();

$stmt->close();


// ---------------------------------------------------------
// Format Response
// ---------------------------------------------------------

$medicine = [
    'id' => (int)$row['id'],

    'name' => $row['name'],

    'description' => $row['description'] ?? '',

    'category_id' => isset($row['category_id'])
        ? (int)$row['category_id']
        : null,

    'category' => $row['category'] ?? 'General',

    'price' => (float)$row['price'],

    'stock' => (int)$row['stock'],

    'image' => $row['image'] ?? ''
];


// ---------------------------------------------------------
// Successful Response
// ---------------------------------------------------------

http_response_code(200);

echo json_encode([
    'success' => true,
    'message' => 'Medicine loaded successfully.',
    'medicine' => $medicine
]);

exit;
