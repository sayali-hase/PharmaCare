<?php

/**
 * MediCare Pharmacy
 * Add Medicine API
 *
 * File:
 * backend/medicines/add-medicine.php
 *
 * Supports:
 * - Medicine details
 * - Medicine image upload
 * - Supplier and category
 * - Admin authentication
 */

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';


// =========================================================
// CONFIGURATION
// =========================================================

$maxImageSize = 2 * 1024 * 1024; // 2 MB

$allowedMimeTypes = [
    'image/jpeg',
    'image/png',
    'image/webp'
];

$allowedExtensions = [
    'jpg',
    'jpeg',
    'png',
    'webp'
];


// =========================================================
// ONLY POST REQUESTS
// =========================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    header('Allow: POST');

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Please use POST.'
    ]);

    exit;
}


// =========================================================
// ADMIN AUTHENTICATION
// =========================================================

if (
    !isset($_SESSION['admin_id']) ||
    ($_SESSION['admin_logged_in'] ?? false) !== true
) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Admin authentication required.'
    ]);

    exit;
}


// =========================================================
// READ FORM DATA
// =========================================================

$name = trim(
    (string)($_POST['name'] ?? '')
);

$description = trim(
    (string)($_POST['description'] ?? '')
);

$supplierId = filter_var(
    $_POST['supplier_id'] ?? null,
    FILTER_VALIDATE_INT
);

$categoryId = filter_var(
    $_POST['category_id'] ?? null,
    FILTER_VALIDATE_INT
);

$priceInput = $_POST['price'] ?? null;

$stockInput = $_POST['stock'] ?? null;


// =========================================================
// VALIDATION
// =========================================================

$errors = [];


// ---------------------------------------------------------
// Medicine Name
// ---------------------------------------------------------

if ($name === '') {

    $errors['name'] =
        'Medicine name is required.';

} elseif (mb_strlen($name) < 2) {

    $errors['name'] =
        'Medicine name must contain at least 2 characters.';

} elseif (mb_strlen($name) > 150) {

    $errors['name'] =
        'Medicine name cannot exceed 150 characters.';
}


// ---------------------------------------------------------
// Description
// ---------------------------------------------------------

if ($description === '') {

    $errors['description'] =
        'Medicine description is required.';

} elseif (mb_strlen($description) > 2000) {

    $errors['description'] =
        'Description cannot exceed 2000 characters.';
}


// ---------------------------------------------------------
// Supplier
// ---------------------------------------------------------

if (
    $supplierId === false ||
    $supplierId === null ||
    $supplierId <= 0
) {

    $errors['supplier_id'] =
        'A valid supplier is required.';
}


// ---------------------------------------------------------
// Category
// ---------------------------------------------------------

if (
    $categoryId === false ||
    $categoryId === null ||
    $categoryId <= 0
) {

    $errors['category_id'] =
        'A valid category is required.';
}


// ---------------------------------------------------------
// Price
// ---------------------------------------------------------

if (
    $priceInput === null ||
    $priceInput === '' ||
    !is_numeric((string)$priceInput)
) {

    $errors['price'] =
        'A valid price is required.';

} else {

    $price = (float)$priceInput;

    if ($price <= 0) {

        $errors['price'] =
            'Price must be greater than zero.';

    } elseif ($price > 1000000) {

        $errors['price'] =
            'Price is too high.';
    }
}


// ---------------------------------------------------------
// Stock
// ---------------------------------------------------------

if (
    $stockInput === null ||
    $stockInput === '' ||
    filter_var(
        $stockInput,
        FILTER_VALIDATE_INT
    ) === false
) {

    $errors['stock'] =
        'A valid stock quantity is required.';

} else {

    $stock = (int)$stockInput;

    if ($stock < 0) {

        $errors['stock'] =
            'Stock cannot be negative.';

    } elseif ($stock > 10000000) {

        $errors['stock'] =
            'Stock quantity is too high.';
    }
}


// =========================================================
// IMAGE VALIDATION
// =========================================================

$imageFile = $_FILES['image'] ?? null;


// Image is required for this admin form.
if (
    $imageFile === null ||
    !isset($imageFile['error'])
) {

    $errors['image'] =
        'Medicine image is required.';

} elseif (
    $imageFile['error'] !== UPLOAD_ERR_OK
) {

    switch ($imageFile['error']) {

        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:

            $errors['image'] =
                'Image size exceeds the allowed limit.';

            break;

        case UPLOAD_ERR_NO_FILE:

            $errors['image'] =
                'Please upload a medicine image.';

            break;

        default:

            $errors['image'] =
                'Unable to upload the medicine image.';
    }

} else {

    // -----------------------------------------------------
    // File Size
    // -----------------------------------------------------

    $imageSize =
        (int)$imageFile['size'];

    if ($imageSize <= 0) {

        $errors['image'] =
            'The uploaded image is empty.';

    } elseif ($imageSize > $maxImageSize) {

        $errors['image'] =
            'Image size must be 2 MB or less.';
    }


    // -----------------------------------------------------
    // Temporary File
    // -----------------------------------------------------

    $tmpName =
        (string)$imageFile['tmp_name'];

    if (
        !isset($errors['image']) &&
        !is_uploaded_file($tmpName)
    ) {

        $errors['image'] =
            'Invalid image upload.';
    }


    // -----------------------------------------------------
    // MIME Type
    // -----------------------------------------------------

    if (
        !isset($errors['image']) &&
        function_exists('finfo_open')
    ) {

        $finfo =
            finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {

            $errors['image'] =
                'Unable to verify image type.';

        } else {

            $mimeType =
                finfo_file(
                    $finfo,
                    $tmpName
                );

            finfo_close($finfo);

            if (
                !in_array(
                    $mimeType,
                    $allowedMimeTypes,
                    true
                )
            ) {

                $errors['image'] =
                    'Only JPG, JPEG, PNG and WEBP images are allowed.';
            }
        }
    }


    // -----------------------------------------------------
    // Verify Actual Image
    // -----------------------------------------------------

    if (
        !isset($errors['image']) &&
        @getimagesize($tmpName) === false
    ) {

        $errors['image'] =
            'The uploaded file is not a valid image.';
    }


    // -----------------------------------------------------
    // Extension
    // -----------------------------------------------------

    if (!isset($errors['image'])) {

        $originalName =
            (string)$imageFile['name'];

        $extension =
            strtolower(
                pathinfo(
                    $originalName,
                    PATHINFO_EXTENSION
                )
            );

        if (
            !in_array(
                $extension,
                $allowedExtensions,
                true
            )
        ) {

            $errors['image'] =
                'Invalid image file extension.';
        }
    }
}


// =========================================================
// RETURN VALIDATION ERRORS
// =========================================================

if (!empty($errors)) {

    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' =>
        'Please correct the highlighted fields.',
        'errors' => $errors
    ]);

    exit;
}


// =========================================================
// NORMALIZE VALUES
// =========================================================

$supplierId =
    (int)$supplierId;

$categoryId =
    (int)$categoryId;

$price =
    round(
        (float)$priceInput,
        2
    );

$stock =
    (int)$stockInput;


// =========================================================
// CHECK SUPPLIER
// =========================================================

$supplierSql = "
    SELECT id
    FROM suppliers
    WHERE id = ?
    AND status = 'active'
    LIMIT 1
";

$supplierStmt =
    $conn->prepare($supplierSql);

if (!$supplierStmt) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
        'Unable to verify medicine supplier.'
    ]);

    exit;
}

$supplierStmt->bind_param(
    'i',
    $supplierId
);

if (!$supplierStmt->execute()) {

    $supplierStmt->close();

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
        'Unable to verify medicine supplier.'
    ]);

    exit;
}

$supplierResult =
    $supplierStmt->get_result();

if ($supplierResult->num_rows !== 1) {

    $supplierStmt->close();

    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' =>
        'Selected medicine supplier does not exist or is inactive.',
        'errors' => [
            'supplier_id' =>
            'Invalid supplier.'
        ]
    ]);

    exit;
}

$supplierStmt->close();


// =========================================================
// CHECK CATEGORY
// =========================================================

$categorySql = "
    SELECT id
    FROM categories
    WHERE id = ?
    LIMIT 1
";

$categoryStmt =
    $conn->prepare($categorySql);

if (!$categoryStmt) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
        'Unable to verify medicine category.'
    ]);

    exit;
}

$categoryStmt->bind_param(
    'i',
    $categoryId
);

if (!$categoryStmt->execute()) {

    $categoryStmt->close();

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
        'Unable to verify medicine category.'
    ]);

    exit;
}

$categoryResult =
    $categoryStmt->get_result();

if ($categoryResult->num_rows !== 1) {

    $categoryStmt->close();

    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' =>
        'Selected medicine category does not exist.',
        'errors' => [
            'category_id' =>
            'Invalid category.'
        ]
    ]);

    exit;
}

$categoryStmt->close();


// =========================================================
// CHECK DUPLICATE MEDICINE
// =========================================================

$duplicateSql = "
    SELECT id
    FROM medicines
    WHERE name = ?
    LIMIT 1
";

$duplicateStmt =
    $conn->prepare($duplicateSql);

if (!$duplicateStmt) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
        'Unable to validate medicine name.'
    ]);

    exit;
}

$duplicateStmt->bind_param(
    's',
    $name
);

if (!$duplicateStmt->execute()) {

    $duplicateStmt->close();

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
        'Unable to validate medicine name.'
    ]);

    exit;
}

$duplicateResult =
    $duplicateStmt->get_result();

if ($duplicateResult->num_rows > 0) {

    $duplicateStmt->close();

    http_response_code(409);

    echo json_encode([
        'success' => false,
        'message' =>
        'A medicine with this name already exists.'
    ]);

    exit;
}

$duplicateStmt->close();


// =========================================================
// CREATE IMAGE DIRECTORY
// =========================================================

$imageDirectory =
    dirname(__DIR__, 2)
    . DIRECTORY_SEPARATOR
    . 'assets'
    . DIRECTORY_SEPARATOR
    . 'images'
    . DIRECTORY_SEPARATOR
    . 'medicines';


if (!is_dir($imageDirectory)) {

    if (
        !mkdir(
            $imageDirectory,
            0755,
            true
        )
    ) {

        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' =>
            'Unable to create medicine image directory.'
        ]);

        exit;
    }
}


// =========================================================
// GENERATE SAFE IMAGE NAME
// =========================================================

$originalName =
    (string)$imageFile['name'];

$extension =
    strtolower(
        pathinfo(
            $originalName,
            PATHINFO_EXTENSION
        )
    );


// Generate unique filename.
$uniqueFilename =
    'medicine_' .
    date('Ymd_His') .
    '_' .
    bin2hex(
        random_bytes(8)
    ) .
    '.' .
    $extension;


// Full physical path.
$imagePhysicalPath =
    $imageDirectory .
    DIRECTORY_SEPARATOR .
    $uniqueFilename;


// Database / browser path.
$imageDatabasePath =
    'assets/images/medicines/' .
    $uniqueFilename;


// =========================================================
// MOVE IMAGE
// =========================================================

if (
    !move_uploaded_file(
        $imageFile['tmp_name'],
        $imagePhysicalPath
    )
) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
        'Unable to save medicine image.'
    ]);

    exit;
}


// =========================================================
// INSERT MEDICINE
// =========================================================

$insertSql = "
    INSERT INTO medicines
    (
        supplier_id,
        category_id,
        name,
        description,
        price,
        stock,
        image,
        status
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
        'active'
    )
";

$stmt =
    $conn->prepare($insertSql);


// ---------------------------------------------------------
// Check Prepare
// ---------------------------------------------------------

if (!$stmt) {

    // Delete uploaded image because
    // database insertion cannot continue.
    if (is_file($imagePhysicalPath)) {
        @unlink($imagePhysicalPath);
    }

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
        'Unable to prepare medicine creation.'
    ]);

    exit;
}


// =========================================================
// BIND VALUES
// =========================================================

$stmt->bind_param(
    'iissdiss',
    $supplierId,
    $categoryId,
    $name,
    $description,
    $price,
    $stock,
    $imageDatabasePath
);


// =========================================================
// EXECUTE INSERT
// =========================================================

if (!$stmt->execute()) {

    // Delete image if database insert fails.
    if (is_file($imagePhysicalPath)) {
        @unlink($imagePhysicalPath);
    }

    $stmt->close();

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' =>
        'Unable to add medicine.'
    ]);

    exit;
}


// =========================================================
// GET NEW MEDICINE ID
// =========================================================

$medicineId =
    (int)$conn->insert_id;

$stmt->close();


// =========================================================
// SUCCESS RESPONSE
// =========================================================

http_response_code(201);

echo json_encode([
    'success' => true,

    'message' =>
    'Medicine added successfully.',

    'medicine' => [
        'id' =>
        $medicineId,

        'supplier_id' =>
        $supplierId,

        'category_id' =>
        $categoryId,

        'name' =>
        $name,

        'description' =>
        $description,

        'price' =>
        $price,

        'stock' =>
        $stock,

        'image' =>
        $imageDatabasePath,

        'status' =>
        'active'
    ]
]);

exit;