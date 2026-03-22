<?php
require_once __DIR__ . '/../config.php';

try {
    $dsn  = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $conn = new PDO($dsn, DB_USER, DB_PASS);
    $conn->exec("set names utf8mb4");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    global $conn;
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
