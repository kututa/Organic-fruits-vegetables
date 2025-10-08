<?php
// PDO connection - adjust credentials as needed
$db_host = '127.0.0.1'; // use 127.0.0.1 or 'localhost' for XAMPP
$db_name = 'organic_fruits_and_vegetables';
$db_user = 'root';
$db_pass = '';

try {
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // show a clearer error for local dev
    die('DB Connection failed: ' . $e->getMessage());
}
