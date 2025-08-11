<?php
// submit_evaluation_student.php - Handles Student Evaluation Submission

// Enable error reporting for debugging (REMOVE OR SET TO 0 IN PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!-- Script submit_evaluation_student.php started. -->"; // Debugging marker

// Include your database connection (config.php should provide $conn PDO object)
require 'config.php';
// Include your session handling function (session.php provides checkUserSession)
require_once __DIR__ . '/session.php';

// Start the session on this page.
// This MUST be the first thing called related to sessions on any page.
// If your config.php or session.php contains any output (even a space) before <?php,
// it can cause 'headers already sent' issues which break session_start.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ======================================================
// --- CRITICAL DEBUGGING OUTPUT - PLEASE PROVIDE THIS ---
// ======================================================
echo "<!-- DEBUG: submit_evaluation_student.php - Session Status: " . session_status() . " -->"; // Expected: 2 (PHP_SESSION_ACTIVE)
echo "<!-- DEBUG: submit_evaluation_student.php - SESSION Array Contents: ";
print_r($_SESSION); // What's actually in $_SESSION?
echo " -->";
echo "<!-- DEBUG: submit_evaluation_student.php - POST Array Contents: ";
print_r($_POST); // What data was sent from the form?
echo " -->";
// ======================================================

// Security check: Use the consistent checkUserSession function
$user = checkUserSession('student'); // Check for 'student' role here

// ======================================================
echo "<!-- DEBUG: submit_evaluation_student.php - Result of checkUserSession('student'): ";
var_dump($user); // Is it null or an array?
echo " -->";
echo "<!-- DEBUG: submit_evaluation_student.php - End of debug before redirect logic. -->";
// ======================================================

if ($user === null) {
    echo "<!-- DEBUG: submit_evaluation_student.php - Redirecting to login because checkUserSession returned null. -->";
    header("Location: login.php?error=auth_required_submit_eval_student");
    exit();
}

// User is authenticated, get their ID from the $user array
$evaluator_id = $user['user_id'];
$evaluator_role = $user['user_role']; // Get role from session for consistency and security

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data (sanitize and validate evaluatee_id)
    $evaluatee_id = filter_var($_POST['evaluatee_id'] ?? '', FILTER_VALIDATE_INT);
    $evaluatee_role = $_POST['evaluatee_role'] ?? ''; // This should be 'faculty' as defined in hidden input
    $category = $_POST['category'] ?? ''; // This should be 'student-to-faculty' as defined in hidden input
    $comments = htmlspecialchars($_POST['comments'] ?? '');

    // Collect question scores (validate as integers)
    $q1 = filter_var($_POST['question1'] ?? null, FILTER_VALIDATE_INT);
    $q2 = filter_var($_POST['question2'] ?? null, FILTER_VALIDATE_INT);
    $q3 = filter_var($_POST['question3'] ?? null, FILTER_VALIDATE_INT);
    $q4 = filter_var($_POST['question4'] ?? null, FILTER_VALIDATE_INT);
    $q5 = filter_var($_POST['question5'] ?? null, FILTER_VALIDATE_INT);

    // Basic validation for essential fields (all questions should be answered if required in HTML)
    // Check for false (failed filter_var) or null (not set) for required fields.
    if ($evaluatee_id === false || $evaluatee_id === null || empty($evaluatee_role) || empty($category) ||
        $q1 === false || $q1 === null ||
        $q2 === false || $q2 === null ||
        $q3 === false || $q3 === null ||
        $q4 === false || $q4 === null ||
        $q5 === false || $q5 === null) {
        // Redirect back with an error if essential data is missing or invalid
        header("Location: student_dashboard_new.php?status=error&message=Missing_or_invalid_evaluation_data");
        exit();
    }

    // --- DATABASE INSERTION (Using PDO consistently with $conn) ---
    // Make sure your 'evaluations_student' table exists with columns:
    // evaluator_id, evaluatee_id, q1, q2, q3, q4, q5, comments, evaluator_role, evaluatee_role, category
    try {
        $stmt = $conn->prepare("INSERT INTO evaluations_student (evaluator_id, evaluatee_id, q1, q2, q3, q4, q5, comments, evaluator_role, evaluatee_role, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if ($stmt->execute([
            $evaluator_id,
            $evaluatee_id,
            $q1,
            $q2,
            $q3,
            $q4,
            $q5,
            $comments,
            $evaluator_role,
            $evaluatee_role,
            $category
        ])) {
            header("Location: student_dashboard_new.php?status=success");
            exit();
        } else {
            error_log("PDO Execute failed for student evaluation: " . implode(" ", $stmt->errorInfo()));
            header("Location: student_dashboard_new.php?status=error&message=DB_Execute_Error");
            exit();
        }
    } catch (PDOException $e) {
        error_log("PDO Student Evaluation Insertion Error: " . $e->getMessage());
        header("Location: student_dashboard_new.php?status=error&message=DB_Error");
        exit();
    }

} else {
    // If accessed directly without POST data
    echo "<h1>Access Denied</h1>";
    echo "<p>This page should only be accessed via form submission.</p>";
}
?>