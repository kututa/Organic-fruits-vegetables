<?php
// installer to run the SQL schema file for organic_fruits_and_vegetables
$sqlFile = __DIR__ . '/sql/organic_fruits_and_vegetables.sql';
if (!file_exists($sqlFile)) {
    die('SQL file not found: ' . $sqlFile);
}

$dbHost = '127.0.0.1';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $sql = file_get_contents($sqlFile);

    // split statements by ; but naive split is ok for our file
    $pdo->exec($sql);
    echo "Database created and sample data inserted.\n";
    echo "You may remove this script after running.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
