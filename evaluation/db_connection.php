<?php
// db_connection.php

$host = 'localhost';
$db   = 'evaluation_db';     // <--- Verify this is EXACTLY your database name
$user = 'root';             // <--- Common default for XAMPP (no password)
$pass = '';                 // <--- Common default for XAMPP (no password)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// In db_connection.php, add error reporting:
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage()); // Shows exact error
}
?>