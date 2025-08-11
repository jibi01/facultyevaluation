<?php
// config.php - Database Connection Setup

$host = 'localhost';
$db   = 'evaluation_db'; // <<< CHANGE THIS to your actual database name
$user = 'root';         // <<< CHANGE THIS to your actual database username
$pass = '';             // <<< CHANGE THIS to your actual database password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch results as associative arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                  // For better security and performance
];

try {
    $conn = new PDO($dsn, $user, $pass, $options); // Using $conn for consistency across your project
} catch (\PDOException $e) {
    // Log the error message (important for debugging in production)
    error_log("Database connection failed: " . $e->getMessage());
    // Display a user-friendly error message (do NOT display $e->getMessage() in production)
    die("Database connection failed. Please try again later.");
}
?>