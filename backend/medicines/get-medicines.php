<?php

/**
 * MediCare Pharmacy
 * Get Medicines API
 *
 * File:
 * backend/medicines/get-medicines.php
 */

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';


// ---------------------------------------------------------
// Only GET requests
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
// Safe Integer
// ---------------------------------------------------------

function getIntParam(
    string $key,
    int $default,
    int $min,
    int $max
): int {

    if (
        !isset($_GET[$key]) ||
        $_GET[$key] === ''
    ) {
        return $default;
    }

    $value = filter_var(
        $_GET[$key],
        FILTER_VALIDATE_INT
    );

    if ($value === false) {
        return $default;
    }

    return max(
        $min,
        min($max, (int)$value)
    );
}


// ---------------------------------------------------------
// Parameters
// ---------------------------------------------------------

$search = trim(
    (string)($_GET['search'] ?? '')
);

$category = trim(
    (string)($_GET['category'] ?? '')
);

$sort = strtolower(
    trim((string)($_GET['sort'] ?? ''))
);

$page = getIntParam(
    'page',
    1,
    1,
    100000
);

$limit = getIntParam(
    'limit',
    20,
    1,
    100
);

$offset =
    ($page - 1) * $limit;

$inStock = strtolower(
    trim((string)($_GET['in_stock'] ?? ''))
);


// ---------------------------------------------------------
// WHERE
// ---------------------------------------------------------

$where = [
    "m.status = 'active'"
];

$params = [];

$types = '';


// Search
if ($search !== '') {

    $where[] = "
        (
            m.name LIKE ?
            OR m.description LIKE ?
            OR c.name LIKE ?
        )
    ";

    $searchValue =
        '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= 'sss';
}


// Category
if ($category !== '') {

    $where[] =
        "c.name = ?";

    $params[] =
        $category;

    $types .= 's';
}


// In stock
if (
    $inStock === '1' ||
    $inStock === 'true' ||
    $inStock === 'yes'
) {

    $where[] =
        "m.stock > 0";
}


$whereSql = '';

if (!empty($where)) {

    $whereSql =
        ' WHERE ' .
        implode(
            ' AND ',
            $where
        );
}


// ---------------------------------------------------------
// Sorting
// ---------------------------------------------------------

switch ($sort) {

    case 'oldest':
        $orderSql =
            'm.id ASC';
        break;

    case 'price_low':
    case 'price_asc':
        $orderSql =
            'm.price ASC, m.id DESC';
        break;

    case 'price_high':
    case 'price_desc':
        $orderSql =
            'm.price DESC, m.id DESC';
        break;

    case 'name_az':
    case 'name_asc':
        $orderSql =
            'm.name ASC, m.id DESC';
        break;

    case 'name_za':
    case 'name_desc':
        $orderSql =
            'm.name DESC, m.id DESC';
        break;

    case 'stock':
    case 'stock_desc':
        $orderSql =
            'm.stock DESC, m.id DESC';
        break;

    case 'stock_asc':
        $orderSql =
            'm.stock ASC, m.id DESC';
        break;

    case 'newest':
    default:
        $orderSql =
            'm.id DESC';
        break;
}


// ---------------------------------------------------------
// Count Medicines
// ---------------------------------------------------------

$countSql = "
    SELECT COUNT(m.id) AS total
    FROM medicines m
    LEFT JOIN categories c
        ON c.id = m.category_id
    $whereSql
";

$countStmt =
    $conn->prepare($countSql);


if (!$countStmt) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
        'Unable to prepare count query.',
        'error' =>
        $conn->error
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

    $error =
        $countStmt->error;

    $countStmt->close();

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
        'Unable to count medicines.',
        'error' =>
        $error
    ]);

    exit;
}


$countResult =
    $countStmt->get_result();


if (!$countResult) {

    $countStmt->close();

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
        'Unable to read medicine count.'
    ]);

    exit;
}


$countRow =
    $countResult->fetch_assoc();


$totalMedicines =
    (int)($countRow['total'] ?? 0);


$countStmt->close();


// ---------------------------------------------------------
// Get Medicines
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
    $whereSql
    ORDER BY $orderSql
    LIMIT ? OFFSET ?
";


$stmt =
    $conn->prepare($sql);


if (!$stmt) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
        'Unable to prepare medicine list.',
        'error' =>
        $conn->error
    ]);

    exit;
}


// ---------------------------------------------------------
// Bind
// ---------------------------------------------------------

$queryParams =
    $params;

$queryParams[] =
    $limit;

$queryParams[] =
    $offset;

$queryTypes =
    $types . 'ii';


$stmt->bind_param(
    $queryTypes,
    ...$queryParams
);


// ---------------------------------------------------------
// Execute
// ---------------------------------------------------------

if (!$stmt->execute()) {

    $error =
        $stmt->error;

    $stmt->close();

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
        'Unable to load medicines.',
        'error' =>
        $error
    ]);

    exit;
}


$result =
    $stmt->get_result();


if (!$result) {

    $stmt->close();

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
        'Unable to read medicine data.'
    ]);

    exit;
}


// ---------------------------------------------------------
// Format Medicines
// ---------------------------------------------------------

$medicines = [];

$totalStock = 0;

$lowStock = 0;

$outStock = 0;


while (
    $row =
    $result->fetch_assoc()
) {

    $stock =
        (int)$row['stock'];

    $totalStock +=
        $stock;


    if ($stock <= 0) {

        $outStock++;
    } elseif ($stock <= 10) {

        $lowStock++;
    }


    $medicines[] = [

        'id' =>
        (int)$row['id'],

        'name' =>
        $row['name'],

        'description' =>
        $row['description'] ?? '',

        'category_id' =>
        isset($row['category_id'])
            ? (int)$row['category_id']
            : null,

        'category' =>
        $row['category'] ?? 'General',

        'price' =>
        (float)$row['price'],

        'stock' =>
        $stock,

        'image' =>
        $row['image'] ?? ''
    ];
}


$stmt->close();


// ---------------------------------------------------------
// Pagination
// ---------------------------------------------------------

$totalPages =
    $totalMedicines > 0
    ? (int)ceil(
        $totalMedicines / $limit
    )
    : 0;


// ---------------------------------------------------------
// Response
// ---------------------------------------------------------

http_response_code(200);

echo json_encode([

    'success' =>
    true,

    'message' =>
    'Medicines loaded successfully.',

    'medicines' =>
    $medicines,

    'pagination' => [

        // Your existing API name
        'page' =>
        $page,

        // Compatible with admin/medicines.php
        'current_page' =>
        $page,

        'limit' =>
        $limit,

        'total' =>
        $totalMedicines,

        'total_pages' =>
        $totalPages,

        'has_next' =>
        $page < $totalPages,

        'has_previous' =>
        $page > 1
    ],

    'summary' => [

        'total_medicines' =>
        $totalMedicines,

        'total_stock' =>
        $totalStock,

        'low_stock' =>
        $lowStock,

        'out_of_stock' =>
        $outStock
    ]

], JSON_UNESCAPED_UNICODE);

exit;