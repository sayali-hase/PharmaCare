<?php

declare(strict_types=1);

$dbHost = 'localhost';
$dbUser = 'root';
$dbPassword = '';
$dbName = 'pharmacare';
$dbPort = 3306;

$conn = new mysqli(
    $dbHost,
    $dbUser,
    $dbPassword,
    $dbName,
    $dbPort
);

if ($conn->connect_error) {
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');

    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed.'
    ]);

    exit;
}

if (!$conn->set_charset('utf8mb4')) {
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');

    echo json_encode([
        'success' => false,
        'message' => 'Unable to configure database character set.'
    ]);

    exit;
}