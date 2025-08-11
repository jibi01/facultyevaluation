<?php
// submit_evaluation.php - Handles Evaluation Submission

// Enable error reporting for debugging (REMOVE OR SET TO 0 IN PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!-- Script submit_evaluation.php started. -->"; // Debugging marker

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
echo "<!-- DEBUG: submit_evaluation.php - Session Status: " . session_status() . " -->"; // Expected: 2 (PHP_SESSION_ACTIVE)
echo "<!-- DEBUG: submit_evaluation.php - SESSION Array Contents: ";
print_r($_SESSION); // What's actually in $_SESSION?
echo " -->";
echo "<!-- DEBUG: submit_evaluation.php - POST Array Contents: ";
print_r($_POST); // What data was sent from the form?
echo " -->";
// ======================================================

// Security check: Use the consistent checkUserSession function
$user = checkUserSession('faculty');

// ======================================================
echo "<!-- DEBUG: submit_evaluation.php - Result of checkUserSession('faculty'): ";
var_dump($user); // Is it null or an array?
echo " -->";
echo "<!-- DEBUG: submit_evaluation.php - End of debug before redirect logic. -->";
// ======================================================

if ($user === null) {
    echo "<!-- DEBUG: submit_evaluation.php - Redirecting to login because checkUserSession returned null. -->";
    header("Location: login.php?error=auth_required_submit_eval");
    exit();
}

// User is authenticated, get their ID from the $user array (security: use session data, not POST for evaluator_id)
$evaluator_id = $user['user_id'];
$evaluator_role = $user['user_role']; // Get role from session for consistency and security

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data (sanitize and validate evaluatee_id)
    $evaluatee_id = filter_var($_POST['evaluatee_id'] ?? '', FILTER_VALIDATE_INT);
    $evaluatee_role = $_POST['evaluatee_role'] ?? ''; // This should be 'staff'
    $category = $_POST['category'] ?? ''; // This should be 'faculty-to-staff'
    $comments = htmlspecialchars($_POST['comments'] ?? '');

    // Collect question scores (validate as integers)
    // Use filter_var to ensure they are integers or null if not set/invalid
    $q1 = filter_var($_POST['question1'] ?? null, FILTER_VALIDATE_INT);
    $q2 = filter_var($_POST['question2'] ?? null, FILTER_VALIDATE_INT);
    $q3 = filter_var($_POST['question3'] ?? null, FILTER_VALIDATE_INT);
    $q4 = filter_var($_POST['question4'] ?? null, FILTER_VALIDATE_INT);
    $q5 = filter_var($_POST['question5'] ?? null, FILTER_VALIDATE_INT);

    // Basic validation for essential fields (all questions should be answered if required in HTML)
    if ($evaluatee_id === false || $evaluatee_id === null || empty($evaluatee_role) || empty($category) ||
        $q1 === false || $q2 === false || $q3 === false || $q4 === false || $q5 === false) {
        // Redirect back with an error if essential data is missing or invalid
        header("Location: faculty_dashboard_new.php?status=error&message=Missing or invalid evaluation data.");
        exit();
    }


    // --- DATABASE INSERTION (Using PDO consistently with $conn) ---
    // Ensure your 'evaluations_faculty' table exists with the specified columns.
    try {
        $stmt = $conn->prepare("INSERT INTO evaluations_faculty (evaluator_id, evaluatee_id, q1, q2, q3, q4, q5, comments, evaluator_role, evaluatee_role, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Execute the statement with an array of parameters (PDO syntax)
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
            // Success message or redirect
            header("Location: faculty_dashboard_new.php?status=success");
            exit();
        } else {
            // This else block might not be reached if execute throws an exception
            error_log("PDO Execute failed for evaluation: " . implode(" ", $stmt->errorInfo())); // Log detailed error
            header("Location: faculty_dashboard_new.php?status=error&message=DB_Execute_Error");
            exit();
        }
    } catch (PDOException $e) {
        // Catch PDO exceptions (database errors)
        error_log("PDO Evaluation Insertion Error: " . $e->getMessage()); // Log detailed error
        header("Location: faculty_dashboard_new.php?status=error&message=DB_Error");
        exit();
    }


} else {
    // If accessed directly without POST data
    echo "<h1>Access Denied</h1>";
    echo "<p>This page should only be accessed via form submission.</p>";
}

// In a typical web application, you would not have content after header(Location) and exit()
// unless it's for direct access without POST.
// The echo "<br><a href='faculty_dashboard_new.php'>Back to Dashboard</a>"; below
// will only be reached if the script doesn't exit, which it should if POST or direct access is handled.
?>