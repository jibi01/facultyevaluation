<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'faculty') {
    // Grab POST data safely
    $faculty_id = $_POST['evaluator_id'];
    $staff_id = $_POST['evaluatee_id'];
    $score = $_POST['score'];
    $comments = $_POST['comments'];
    $evaluator_role = $_POST['evaluator_role'];
    $evaluatee_role = $_POST['evaluatee_role'];
    $category = $_POST['category'];

    // Basic validation
    if (empty($faculty_id) || empty($staff_id) || empty($score)) {
        die("Please fill in all required fields.");
    }

    // Prepare your SQL query - adjust table/fields if needed
    $stmt = $conn->prepare("INSERT INTO evaluations_faculty (faculty_id, staff_id, score, comments, evaluator_role, evaluatee_role, category) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("iiissss", $faculty_id, $staff_id, $score, $comments, $evaluator_role, $evaluatee_role, $category);

    if ($stmt->execute()) {
        echo "<script>alert('Evaluation submitted successfully!'); window.location.href='faculty_dashboard.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: login.php");
    exit();
}
