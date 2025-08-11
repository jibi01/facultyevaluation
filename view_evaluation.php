<?php
session_start();
require 'config.php'; // Make sure this path is correct for your database connection

// Security check: Ensure a faculty member is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'faculty') {
    header("Location: login.php");
    exit();
}

$faculty_id = $_SESSION['user_id']; // The ID of the logged-in faculty member

// Check if evaluatee_id is provided in the URL
if (isset($_GET['evaluatee_id'])) {
    $evaluatee_id = $_GET['evaluatee_id'];

    // In a real application, you would now:
    // 1. Fetch the details of the evaluated staff member ($evaluatee_id) from the 'users' table.
    // 2. Fetch the evaluation details (scores, comments) from the 'evaluations' table
    //    where evaluatee_id matches and evaluator_id matches $faculty_id.
    // 3. Display all this information in a formatted way.

    $evaluatee_name = "Staff Member " . htmlspecialchars($evaluatee_id); // Placeholder for now
    $evaluation_details = "No evaluation found yet for this staff member by you."; // Placeholder for now

    // Example of how you might fetch an evaluation
    // (This part needs proper implementation to fetch actual data from your 'evaluations' table)
    /*
    $stmt = $conn->prepare("SELECT q1, q2, q3, q4, q5, comments FROM evaluations WHERE evaluatee_id = ? AND evaluator_id = ? LIMIT 1");
    $stmt->bind_param("ii", $evaluatee_id, $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $eval_data = $result->fetch_assoc();
        $evaluation_details = "Q1: " . $eval_data['q1'] . ", Q2: " . $eval_data['q2'] . ", Comments: " . $eval_data['comments'];
        // ... and so on for all questions
    }
    $stmt->close();
    */

} else {
    // If no evaluatee_id is provided, redirect or show an error
    header("Location: faculty_dashboard_new.php"); // Or wherever your dashboard is
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Evaluation</title>
    <link rel="stylesheet" href="style.css"> <!-- Your main stylesheet -->
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background-color: #F8F8F8; color: #333; }
        .header { background-color: #2E7D32; color: white; padding: 18px 40px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 5px rgba(0,0,0,0.08); }
        .header .title { font-weight: 600; font-size: 17px; }
        .sidebar { width: 220px; background: #fff; height: 100vh; position: fixed; top: 0; left: 0; border-right: 1px solid #e0e0e0; padding-top: 70px; text-align: center; box-shadow: 2px 0 5px rgba(0,0,0,0.08); z-index: 1000; }
        .sidebar img { width: 140px; margin-bottom: 30px; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar ul li a { display: block; padding: 14px 20px; color: #333; text-decoration: none; text-align: left; font-size: 15px; }
        .sidebar ul li a.active { color: #4CAF50; font-weight: bold; background-color: #E8F5E9; border-left: 5px solid #4CAF50; }
        .sidebar ul li a:hover { background: #f0f0f0; }
        .main-content { margin-left: 220px; padding: 30px; }
        .page-title { font-size: 24px; font-weight: 700; color: #2E7D32; margin-bottom: 25px; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 30px; border: 1px solid #ececec; }
        .back-button { display: inline-block; background-color: #6c757d; color: white; padding: 10px 15px; border-radius: 5px; text-decoration: none; margin-top: 20px; }
        .back-button:hover { background-color: #5a6268; }
    </style>
</head>
<body>

    <div class="header">
        <div class="title">TCM FACULTY AND STAFF EVALUATION</div>
        <div class="academic-year-display">Viewing Evaluation</div>
    </div>

    <div class="sidebar">
        <img src="tcm logo.jfif" alt="TCM Logo">
        <ul>
            <li><a href="faculty_dashboard_new.php">Dashboard</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="page-title">Evaluation Details for <?php echo $evaluatee_name; ?></div>

        <div class="card">
            <p><strong>Evaluatee ID:</strong> <?php echo htmlspecialchars($evaluatee_id); ?></p>
            <p><strong>Evaluation Summary:</strong></p>
            <p><?php echo htmlspecialchars($evaluation_details); ?></p>
            
            <a href="faculty_dashboard_new.php" class="back-button">Back to Dashboard</a>
        </div>
    </div>

</body>
</html>