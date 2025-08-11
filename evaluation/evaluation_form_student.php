<?php
// evaluation_form_student.php

// Enable error reporting for debugging (REMOVE OR SET TO 0 IN PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include your database connection (config.php should provide $conn PDO object)
require 'config.php';
// Include your session handling function (session.php provides checkUserSession)
require_once __DIR__ . '/session.php'; // Path to your session.php

// Start the session on this page.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check: Use the consistent checkUserSession function for students
$user = checkUserSession('student');
if ($user === null) {
    // If not authenticated or not a student, redirect to login
    header("Location: login.php?error=auth_required_student_eval_form");
    exit();
}

// User is authenticated as a student. Get their ID from the $user array.
$student_id = $user['user_id'];
$student_fullName = $user['fullName']; // For display if needed

$instructors_for_dropdown = []; // Array to hold instructors for dropdown if selection is needed
$evaluatee_id_to_evaluate = null;
$evaluatee_name_for_display = "";
$show_instructor_selection = true; // Flag to control display of instructor dropdown/cards

// Check if evaluatee_id is present and numeric in the URL
if (isset($_GET['evaluatee_id']) && is_numeric($_GET['evaluatee_id'])) {
    $temp_evaluatee_id = (int)$_GET['evaluatee_id']; // Cast to int for safety

    // Attempt to fetch the name of the specific faculty member being evaluated
    // and verify their role is 'faculty'
    try {
        $stmt = $conn->prepare("SELECT fullName FROM users WHERE id = ? AND role = 'faculty'");
        $stmt->execute([$temp_evaluatee_id]);
        $faculty_data = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch one row

        if ($faculty_data) { // If a row was found
            $evaluatee_id_to_evaluate = $temp_evaluatee_id; // Valid ID found and confirmed as faculty
            $evaluatee_name_for_display = htmlspecialchars($faculty_data['fullName']);
            $show_instructor_selection = false; // Valid faculty found, so DO NOT show selection
        } else {
            // ID was provided but invalid/not faculty, fall back to showing selection
            // $evaluatee_id_to_evaluate remains null, $show_instructor_selection remains true
        }
    } catch (PDOException $e) {
        error_log("DB Error fetching faculty by ID in student eval form: " . $e->getMessage());
        // $evaluatee_id_to_evaluate remains null, $show_instructor_selection remains true
    }
}

// If instructor selection is to be shown (either no ID or invalid ID from URL),
// fetch all faculty members to populate the dropdown/cards.
if ($show_instructor_selection) {
    try {
        $stmt = $conn->prepare("SELECT id, fullName FROM users WHERE role = 'faculty' ORDER BY fullName ASC");
        $stmt->execute();
        $instructors_for_dropdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("DB Error fetching faculty for student evaluation form dropdown: " . $e->getMessage());
        echo "<p style='color:red;'>Error loading instructors. Please try again later.</p>";
        $instructors_for_dropdown = []; // Ensure empty array on error
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Student Evaluation Form</title>
<style>
    /* Define CSS Variables from Faculty Form */
    :root {
        --primary-green: #4CAF50; /* A pleasant, vibrant green */
        --darker-green: #2E7D32; /* A deeper green for accents/header */
        --light-green-bg: #E8F5E9; /* Very light green for subtle backgrounds */
        --text-color: #333333; /* Dark gray for main text */
        --border-color: #CCCCCC; /* Light gray for borders */
        --shadow-color: rgba(0,0,0,0.08); /* Softer shadow */
    }

    body {
        margin: 0;
        padding: 0;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* Font from faculty form */
        background-color: #F8F8F8; /* Solid background from faculty form */
        color: var(--text-color);
    }

    /* Main form container styling (now using .card class name) */
    .card {
        background: white;
        padding: 30px; /* Increased padding inside card */
        border-radius: 8px; /* Slightly more rounded corners */
        box-shadow: 0 4px 15px var(--shadow-color); /* More prominent but soft shadow */
        border: 1px solid #ececec; /* Subtle border */
        width: 100%; /* Max width property below will control overall size */
        max-width: 600px; /* Preserved from original student form */
        box-sizing: border-box; /* Include padding in element's total width */
    }

    /* Page title (similar to faculty form's page-title) */
    .page-title {
        font-size: 24px; /* Larger title */
        font-weight: 700; /* Bolder */
        color: var(--darker-green); /* Use darker green for emphasis */
        margin-bottom: 25px;
        text-align: center; /* Centered for this standalone form */
    }

    /* Highlight section (Academic Year) */
    .highlight {
        border-left: 5px solid var(--primary-green); /* Use primary green for highlight */
        padding-left: 15px; /* More padding */
        margin-bottom: 25px;
        background-color: var(--light-green-bg); /* Light green background for highlight */
        padding-top: 10px;
        padding-bottom: 10px;
        border-radius: 4px;
    }
    .highlight p {
        margin: 0;
        font-size: 15px;
        color: var(--darker-green); /* Darker text for highlight */
    }

    /* Form labels */
    label {
        font-weight: 600; /* Semi-bold */
        display: block;
        margin-top: 25px; /* More space above labels */
        margin-bottom: 8px; /* Space between label and input */
        font-size: 15px;
    }

    /* Input fields and select dropdown */
    select,
    input[type="number"],
    textarea {
        width: 100%;
        padding: 12px; /* More padding for input fields */
        margin-top: 5px;
        border: 1px solid var(--border-color); /* Use variable border color */
        border-radius: 6px;
        font-size: 15px;
        box-sizing: border-box; /* Include padding in element's total width/height */
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }
    select:focus,
    input[type="number"]:focus,
    textarea:focus {
        outline: none;
        border-color: var(--primary-green); /* Green border on focus */
        box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2); /* Soft green glow on focus */
    }

    /* Rating Section Specifics - adapted from faculty form */
    .rating-section {
        margin-top: 25px;
    }
    .question {
        margin-bottom: 25px; /* More space between questions */
        border-bottom: 1px dashed #E0E0E0; /* Subtle separator */
        padding-bottom: 20px;
    }
    .question:last-of-type {
        border-bottom: none; /* No border for the last question */
        padding-bottom: 0;
    }
    .question label {
        margin-top: 0; /* Override general label margin-top */
        margin-bottom: 15px; /* Space between question label and options */
        font-size: 16px; /* Slightly larger question text */
        line-height: 1.5; /* Improve readability */
    }
    .rating-options {
        display: flex;
        gap: 20px; /* Space out radio options */
        align-items: center;
        flex-wrap: wrap; /* Allow wrapping on smaller screens */
    }
    .rating-options input[type="radio"] {
        -webkit-appearance: none; /* Hide default radio button */
        -moz-appearance: none;
        appearance: none;
        width: 20px; /* Custom radio button size */
        height: 20px;
        border: 2px solid var(--primary-green); /* Green border */
        border-radius: 50%; /* Make it circular */
        position: relative;
        cursor: pointer;
        outline: none;
        transition: all 0.3s ease;
        margin-right: 5px; /* Space between radio and label text */
        flex-shrink: 0; /* Prevent shrinking */
    }
    .rating-options input[type="radio"]:checked {
        background-color: var(--primary-green); /* Green fill when checked */
        border-color: var(--darker-green); /* Darker border when checked */
    }
    .rating-options input[type="radio"]:checked::before {
        content: '';
        display: block;
        width: 10px; /* Inner dot */
        height: 10px;
        background-color: white;
        border-radius: 50%;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
    .rating-options label { /* Styling for the text next to radio buttons */
        font-weight: normal;
        display: inline-block; /* Keep label and radio on same line */
        margin-top: 0;
        margin-bottom: 0;
        cursor: pointer;
        font-size: 16px;
        color: var(--text-color);
    }
    .rating-options input[type="radio"] + label {
         margin-right: 15px; /* Space between each rating option */
    }
    .rating-options input[type="radio"]:hover {
        box-shadow: 0 0 0 4px rgba(76, 175, 80, 0.1); /* Subtle hover effect */
    }

    /* Submit Button Styling */
    button {
        margin-top: 30px; /* More space above the button */
        background: var(--primary-green);
        color: #fff;
        border: none;
        padding: 14px 25px; /* Larger padding for button */
        border-radius: 8px; /* More rounded button */
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        letter-spacing: 0.5px;
        transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1); /* Soft shadow */
        width: 100%; /* Make button full width */
    }
    button:hover {
        background: var(--darker-green); /* Darker green on hover */
        transform: translateY(-2px); /* Slight lift effect */
        box-shadow: 0 6px 15px rgba(0,0,0,0.15); /* Enhanced shadow on hover */
    }
    button:active {
        transform: translateY(0); /* Press down effect */
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    /* Back Link Styling */
    .back-link {
        display: block;
        text-align: center;
        margin-top: 20px;
        color: var(--primary-green);
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }
    .back-link:hover {
        color: var(--darker-green);
        text-decoration: underline;
    }
</style>
</head>
<body>

<div class="card"> <!-- Changed form-container to card -->
  <div class="page-title">Evaluate Your Instructor</div> <!-- Replaced h2 with page-title -->
  <div class="highlight">
    <p><b>Academic Year:</b> 2022-2023 2nd Semester</p> <!-- Hardcoded, adapt as needed -->
  </div>

  <form action="submit_evaluation_student.php" method="POST">
    <?php if ($show_instructor_selection): ?>
    <label for="evaluatee_id">Select Instructor:</label>
    <select name="evaluatee_id" id="evaluatee_id" required>
      <option value="">-- Select Instructor --</option>
      <?php foreach ($instructors_for_dropdown as $instructor): ?>
        <option value="<?php echo htmlspecialchars($instructor['id']); ?>">
          <?php echo htmlspecialchars($instructor['fullName']); ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php else: ?>
    <!-- Display only the selected instructor's name, disable selection -->
    <label for="evaluatee_id">Evaluating Instructor:</label>
    <input type="text" id="evaluatee_display_name" value="<?php echo $evaluatee_name_for_display; ?>" readonly style="background-color:#f0f0f0;">
    <!-- Hidden input to still pass the ID to the form action -->
    <input type="hidden" name="evaluatee_id" value="<?php echo htmlspecialchars($evaluatee_id_to_evaluate); ?>">
    <?php endif; ?>

    <!-- Questions adapted to use radio buttons like the faculty form -->
    <div class="rating-section">
        <div class="question">
            <label for="q1_5">1. Instructor's communication skills:</label>
            <div class="rating-options">
                <input type="radio" id="q1_5" name="question1" value="5" required><label for="q1_5">5</label>
                <input type="radio" id="q1_4" name="question1" value="4"><label for="q1_4">4</label>
                <input type="radio" id="q1_3" name="question1" value="3"><label for="q1_3">3</label>
                <input type="radio" id="q1_2" name="question1" value="2"><label for="q1_2">2</label>
                <input type="radio" id="q1_1" name="question1" value="1"><label for="q1_1">1</label>
            </div>
        </div>

        <div class="question">
            <label for="q2_5">2. Instructor's knowledge of the subject matter:</label>
            <div class="rating-options">
                <input type="radio" id="q2_5" name="question2" value="5" required><label for="q2_5">5</label>
                <input type="radio" id="q2_4" name="question2" value="4"><label for="q2_4">4</label>
                <input type="radio" id="q2_3" name="question2" value="3"><label for="q2_3">3</label>
                <input type="radio" id="q2_2" name="question2" value="2"><label for="q2_2">2</label>
                <input type="radio" id="q2_1" name="question2" value="1"><label for="q2_1">1</label>
            </div>
        </div>

        <div class="question">
            <label for="q3_5">3. Instructor's approachability and willingness to help:</label>
            <div class="rating-options">
                <input type="radio" id="q3_5" name="question3" value="5" required><label for="q3_5">5</label>
                <input type="radio" id="q3_4" name="question3" value="4"><label for="q3_4">4</label>
                <input type="radio" id="q3_3" name="question3" value="3"><label for="q3_3">3</label>
                <input type="radio" id="q3_2" name="question3" value="2"><label for="q3_2">2</label>
                <input type="radio" id="q3_1" name="question3" value="1"><label for="q3_1">1</label>
            </div>
        </div>

        <div class="question">
            <label for="q4_5">4. Clarity of instructor's explanations:</label>
            <div class="rating-options">
                <input type="radio" id="q4_5" name="question4" value="5" required><label for="q4_5">5</label>
                <input type="radio" id="q4_4" name="question4" value="4"><label for="q4_4">4</label>
                <input type="radio" id="q4_3" name="question4" value="3"><label for="q4_3">3</label>
                <input type="radio" id="q4_2" name="question4" value="2"><label for="q4_2">2</label>
                <input type="radio" id="q4_1" name="question4" value="1"><label for="q4_1">1</label>
            </div>
        </div>

        <div class="question">
            <label for="q5_5">5. Instructor's punctuality and professionalism:</label>
            <div class="rating-options">
                <input type="radio" id="q5_5" name="question5" value="5" required><label for="q5_5">5</label>
                <input type="radio" id="q5_4" name="question5" value="4"><label for="q5_4">4</label>
                <input type="radio" id="q5_3" name="question5" value="3"><label for="q5_3">3</label>
                <input type="radio" id="q5_2" name="question5" value="2"><label for="q5_2">2</label>
                <input type="radio" id="q5_1" name="question5" value="1"><label for="q5_1">1</label>
            </div>
        </div>
    </div>
    <!-- End of questions adaptation -->

    <label for="comments">Additional Comments (Optional):</label>
    <textarea id="comments" name="comments" rows="4" placeholder="Write your feedback here..."></textarea>

    <!-- Hidden inputs for evaluator details (student side) -->
    <input type="hidden" name="evaluator_id" value="<?php echo htmlspecialchars($student_id); ?>">
    <input type="hidden" name="evaluator_role" value="student">
    <input type="hidden" name="evaluatee_role" value="faculty">
    <input type="hidden" name="category" value="student-to-faculty">

    <button type="submit">Submit Evaluation</button>
  </form>

  <a href="student_dashboard_new.php" class="back-link">← Back to Dashboard</a>
</div>

</body>
</html>