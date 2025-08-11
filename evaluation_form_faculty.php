<?php
// evaluation_form_faculty.php

// Enable error reporting for debugging (REMOVE OR SET TO 0 IN PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!-- Script evaluation_form_faculty.php started. -->"; // Debugging marker

// Include your database connection (config.php should provide $conn PDO object)
require 'config.php';
// Include your session handling function
require_once __DIR__ . '/session.php'; // Path to your session.php

// Start the session on this page.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Use checkUserSession for robust authentication and role checking
// 'faculty' MUST match the exact string in your users table's 'role' column
$user = checkUserSession('faculty');

// If checkUserSession returns null, it means authentication failed or role mismatch
if ($user === null) {
    header("Location: login.php?error=auth_required_for_evaluation");
    exit();
}

// User is authenticated and authorized as faculty. Extract their data.
$faculty_id = $user['user_id']; // This is already htmlspecialchars'd by checkUserSession if passed through it
// $faculty_fullName = $user['fullName']; // You can use this if needed

$evaluatee_id_from_url = null;
$evaluatee_name_for_display = "";
$show_staff_selection = true; // Assume we need to show staff selection by default

// Check if evaluatee_id is present and numeric in the URL
if (isset($_GET['evaluatee_id']) && is_numeric($_GET['evaluatee_id'])) {
    $temp_evaluatee_id = (int)$_GET['evaluatee_id']; // Cast to int for safety

    echo "<!-- evaluatee_id from URL: " . htmlspecialchars($temp_evaluatee_id) . " -->";

    // Attempt to fetch the name of the specific staff member being evaluated
    // and verify their role is 'staff' using PDO
    try {
        $stmt = $conn->prepare("SELECT fullName FROM users WHERE id = ? AND role = 'staff'"); // Use fullName
        $stmt->execute([$temp_evaluatee_id]);
        $staff_data = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch one row

        if ($staff_data) { // If a row was found
            $evaluatee_id_from_url = $temp_evaluatee_id; // Valid ID found and confirmed as staff
            $evaluatee_name_for_display = htmlspecialchars($staff_data['fullName']); // Use fullName
            $show_staff_selection = false; // Valid staff found, so DO NOT show staff selection cards
            echo "<!-- Staff found from URL: ID=" . $evaluatee_id_from_url . ", Name=" . $evaluatee_name_for_display . ". Hiding staff selection. -->";
        } else {
            echo "<!-- Staff NOT found or role is not 'staff' for ID: " . htmlspecialchars($temp_evaluatee_id) . ". Reverting to staff selection. -->";
            // evaluatee_id_from_url remains null, show_staff_selection remains true
        }
    } catch (PDOException $e) {
        error_log("DB Error fetching staff by ID in eval form: " . $e->getMessage());
        echo "<!-- DB Error fetching staff by ID. Reverting to staff selection. -->";
        // evaluatee_id_from_url remains null, show_staff_selection remains true
    }
} else {
    echo "<!-- No evaluatee_id in URL or it's not numeric. Showing staff selection. -->";
}

// Query to fetch all staff along with their average ratings
// This query is only run if the staff selection cards are going to be displayed
$staff_listing_results = []; // Initialize as an empty array for PDO fetchAll
if ($show_staff_selection) {
    // --- CORRECTED SQL QUERY HERE ---
    $sql = "
        SELECT
            u.id,
            u.fullName,
            u.username,
            AVG(CASE WHEN ef.q1 IS NOT NULL THEN (ef.q1 + ef.q2 + ef.q3 + ef.q4 + ef.q5) / 5 ELSE NULL END) AS average_rating
        FROM
            users u
        LEFT JOIN
            evaluations_faculty ef ON u.id = ef.evaluatee_id  -- Changed 'e' to 'ef' and 'evaluations' to 'evaluations_faculty'
        WHERE
            u.role = 'staff'
        GROUP BY
            u.id, u.fullName, u.username
        ORDER BY
            u.fullName ASC
    ";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $staff_listing_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to fetch staff list with ratings: " . $e->getMessage());
        echo "<!-- DB Error fetching staff list: " . htmlspecialchars($e->getMessage()) . " -->";
        $staff_listing_results = []; // Ensure empty array on error
    }
}

echo "<!-- show_staff_selection final state: " . ($show_staff_selection ? 'true' : 'false') . " -->";
echo "<!-- Script finished PHP processing. -->";

?>

<!DOCTYPE html>
<html>
<head>
    <title>Evaluate Staff</title>
    <!-- You might link to a central style.css or keep styles embedded -->
    <!-- <link rel="stylesheet" href="style.css"> -->
    <style>
        /* Define a CSS variable for the primary green color */
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* A more modern sans-serif font */
            background-color: #F8F8F8; /* A slightly warmer off-white background */
            color: var(--text-color);
        }

        /* Top Header */
        .header {
            background-color: var(--darker-green);
            color: white;
            padding: 18px 40px; /* Increased padding for more space */
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 5px var(--shadow-color); /* Subtle shadow */
        }
        .header .title {
            font-weight: 600; /* Slightly bolder */
            font-size: 17px;
            letter-spacing: 0.5px;
        }
        .header .menu-icon {
            font-size: 24px; /* Slightly larger icon */
            cursor: pointer;
        }

        /* Sidebar */
        .sidebar {
            width: 220px;
            background: #fff;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            border-right: 1px solid #e0e0e0; /* Softer border */
            padding-top: 70px; /* Adjust padding for header */
            text-align: center;
            box-shadow: 2px 0 5px var(--shadow-color); /* Shadow to make it pop */
            z-index: 1000; /* Ensure it's above other content */
        }
        .sidebar img {
            width: 140px;
            margin-bottom: 30px; /* More space below logo */
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar ul li {
            margin: 0;
        }
        .sidebar ul li a {
            display: block;
            padding: 14px 20px;
            color: var(--text-color);
            text-decoration: none;
            text-align: left;
            font-size: 15px;
            transition: background-color 0.3s ease, color 0.3s ease, border-left 0.3s ease; /* Smooth transitions */
        }
        .sidebar ul li a.active {
            color: var(--primary-green);
            font-weight: bold;
            background-color: var(--light-green-bg); /* Light green for active background */
            border-left: 5px solid var(--primary-green); /* Thicker, primary green border */
        }
        .sidebar ul li a:hover:not(.active) {
            background: #f0f0f0; /* Lighter hover for non-active links */
            color: var(--darker-green);
            border-left: 5px solid #d0d0d0; /* Subtle border on hover */
        }

        /* Main content */
        .main-content {
            margin-left: 220px;
            padding: 30px; /* Increased padding */
        }
        .page-title {
            font-size: 24px; /* Larger title */
            font-weight: 700; /* Bolder */
            color: var(--darker-green); /* Use darker green for emphasis */
            margin-bottom: 25px;
        }
        .card {
            background: white;
            padding: 30px; /* Increased padding inside card */
            border-radius: 8px; /* Slightly more rounded corners */
            box-shadow: 0 4px 15px var(--shadow-color); /* More prominent but soft shadow */
            margin-bottom: 30px;
            border: 1px solid #ececec; /* Subtle border */
        }
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
        label {
            font-weight: 600; /* Semi-bold */
            display: block;
            margin-top: 25px; /* More space above labels */
            margin-bottom: 8px; /* Space between label and input */
            font-size: 15px;
        }
        select,
        input[type="number"], /* Though not used for rating in this form, good to style */
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
        textarea:focus {
            outline: none;
            border-color: var(--primary-green); /* Green border on focus */
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2); /* Soft green glow on focus */
        }

        /* Rating Section Specifics */
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

        /* NEW STYLES for Staff Cards */
        .staff-selection-section {
            margin-top: 25px;
            padding-bottom: 25px; /* Space before the actual form */
        }

        .staff-cards-container {
            display: grid; /* Use CSS Grid for better control of layout */
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); /* Responsive columns */
            gap: 20px; /* Space between cards */
            margin-top: 15px;
        }

        .staff-card {
            background-color: #f9f9f9;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 120px; /* Ensure consistent card height */
        }

        .staff-card:hover {
            border-color: var(--primary-green);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }

        .staff-card.active {
            background-color: var(--light-green-bg);
            border-color: var(--primary-green);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-3px);
        }

        .staff-card-name {
            font-weight: 600;
            font-size: 18px;
            color: var(--darker-green);
            margin-bottom: 5px;
        }

        .staff-card-username {
            font-size: 14px;
            color: var(--text-color);
            margin-bottom: 10px; /* Space between username and rating */
        }

        .staff-card-rating {
            font-size: 24px; /* Larger rating */
            font-weight: bold;
            color: var(--primary-green); /* Green color for rating */
            margin-top: auto; /* Push rating to the bottom if content is dynamic */
            display: flex;
            align-items: center;
        }

        .staff-card-rating::before {
            content: '⭐'; /* Star icon */
            margin-right: 5px;
            font-size: 18px;
            color: orange; /* Color for the star */
        }

        /* Initial display state of evaluation form content */
        #evaluationFormContent {
            display: none; /* Hidden by default if no ID from URL */
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
            margin-top: 30px; /* Space between staff cards/name and form */
            padding-top: 30px; /* Visual separation within the card */
            border-top: 1px dashed #e0e0e0;
        }
        #evaluationFormContent.show {
            display: block;
            opacity: 1;
        }
    </style>
</head>
<body>

    <!-- Top Navbar -->
    <div class="header">
        <div class="title">TCM FACULTY AND STAFF PERFORMANCE EVALUATION SYSTEM</div>
        <div class="menu-icon">&#9776;</div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <img src="tcm logo.jfif" alt="TCM Logo">
        <ul>
            <li><a href="faculty_dashboard_new.php">Dashboard</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-title">Evaluate Staff Performance</div>

        <div class="card">
            <div class="highlight">
                <p><b>Academic Year:</b> 2022-2023 2nd Semester</p>
            </div>

            <?php if ($show_staff_selection): ?>
            <!-- Staff Selection Section - ONLY display if no evaluatee_id is in URL or if it's invalid -->
            <div class="staff-selection-section">
                <label>Select Staff to Evaluate:</label>
                <div class="staff-cards-container">
                    <?php
                    // Corrected: Using $staff_listing_results from PDO fetchAll
                    if (!empty($staff_listing_results)):
                        foreach ($staff_listing_results as $row):
                            $staffId = htmlspecialchars($row['id']);
                            $staffName = htmlspecialchars($row['fullName']); // Use fullName
                            $staffUsername = htmlspecialchars($row['username']);
                            // Format average rating to 2 decimal places, or show "N/A" if null
                            $averageRating = is_numeric($row['average_rating']) ? number_format($row['average_rating'], 2) : 'No ratings yet';

                            echo "<div class='staff-card' data-id='{$staffId}'>";
                            echo "<div class='staff-card-name'>{$staffName}</div>";
                            echo "<div class='staff-card-username'>({$staffUsername})</div>";
                            echo "<div class='staff-card-rating'>{$averageRating}</div>";
                            echo "</div>";
                        endforeach;
                    else:
                        echo "<p>No staff available for evaluation.</p>";
                    endif;
                    ?>
                </div>
            </div>
            <?php else: ?>
            <!-- Display Staff Name being evaluated if evaluatee_id is in URL and valid -->
            <h3 style="margin-top: 20px; color: var(--darker-green);">Evaluating: <?php echo $evaluatee_name_for_display; ?></h3>
            <?php endif; ?>

            <form action="submit_evaluation.php" method="POST">
                <!-- Hidden input for selected staff ID -->
                <input type="hidden" name="evaluatee_id" id="selectedStaffId" required value="<?= $evaluatee_id_from_url !== null ? htmlspecialchars($evaluatee_id_from_url) : '' ?>">

                <!-- Evaluation Form Content - Conditionally shown/hidden -->
                <div id="evaluationFormContent" class="<?= $evaluatee_id_from_url !== null ? 'show' : '' ?>">
                    <div class="rating-section">
                        <div class="question">
                            <label for="q1_5">1. Demonstrates sensitivity to students' ability to attend and absorb content information.</label>
                            <div class="rating-options">
                                <input type="radio" id="q1_5" name="question1" value="5" required><label for="q1_5">5</label>
                                <input type="radio" id="q1_4" name="question1" value="4"><label for="q1_4">4</label>
                                <input type="radio" id="q1_3" name="question1" value="3"><label for="q1_3">3</label>
                                <input type="radio" id="q1_2" name="question1" value="2"><label for="q1_2">2</label>
                                <input type="radio" id="q1_1" name="question1" value="1"><label for="q1_1">1</label>
                            </div>
                        </div>

                        <div class="question">
                            <label for="q2_5">2. Integrates sensitively his/her learning objectives with those of the students in a collaborative process.</label>
                            <div class="rating-options">
                                <input type="radio" id="q2_5" name="question2" value="5" required><label for="q2_5">5</label>
                                <input type="radio" id="q2_4" name="question2" value="4"><label for="q2_4">4</label>
                                <input type="radio" id="q2_3" name="question2" value="3"><label for="q2_3">3</label>
                                <input type="radio" id="q2_2" name="question2" value="2"><label for="q2_2">2</label>
                                <input type="radio" id="q2_1" name="question2" value="1"><label for="q2_1">1</label>
                            </div>
                        </div>

                        <div class="question">
                            <label for="q3_5">3. Makes self-available to students beyond official time.</label>
                            <div class="rating-options">
                                <input type="radio" id="q3_5" name="question3" value="5" required><label for="q3_5">5</label>
                                <input type="radio" id="q3_4" name="question3" value="4"><label for="q3_4">4</label>
                                <input type="radio" id="q3_3" name="question3" value="3"><label for="q3_3">3</label>
                                <input type="radio" id="q3_2" name="question3" value="2"><label for="q3_2">2</label>
                                <input type="radio" id="q3_1" name="question3" value="1"><label for="q3_1">1</label>
                            </div>
                        </div>

                        <div class="question">
                            <label for="q4_5">4. Regularly comes to class on time, well-groomed, and well-prepared to complete assigned responsibilities.</label>
                            <div class="rating-options">
                                <input type="radio" id="q4_5" name="question4" value="5" required><label for="q4_5">5</label>
                                <input type="radio" id="q4_4" name="question4" value="4"><label for="q4_4">4</label>
                                <input type="radio" id="q4_3" name="question4" value="3"><label for="q4_3">3</label>
                                <input type="radio" id="q4_2" name="question4" value="2"><label for="q4_2">2</label>
                                <input type="radio" id="q4_1" name="question4" value="1"><label for="q4_1">1</label>
                            </div>
                        </div>

                        <div class="question">
                            <label for="q5_5">5. Keeps accurate records of students' performance and prompt submission of the same.</label>
                            <div class="rating-options">
                                <input type="radio" id="q5_5" name="question5" value="5" required><label for="q5_5">5</label>
                                <input type="radio" id="q5_4" name="question5" value="4"><label for="q5_4">4</label>
                                <input type="radio" id="q5_3" name="question5" value="3"><label for="q5_3">3</label>
                                <input type="radio" id="q5_2" name="question5" value="2"><label for="q5_2">2</label>
                                <input type="radio" id="q5_1" name="question5" value="1"><label for="q5_1">1</label>
                            </div>
                        </div>
                    </div>

                    <label for="comments">Comments:</label>
                    <textarea name="comments" id="comments" rows="5" placeholder="Optional comments..."></textarea>

                    <input type="hidden" name="evaluator_id" value="<?= htmlspecialchars($faculty_id) ?>">
                    <input type="hidden" name="evaluator_role" value="faculty">
                    <input type="hidden" name="evaluatee_role" value="staff">
                    <input type="hidden" name="category" value="faculty-to-staff">

                    <button type="submit">Submit Evaluation</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const staffCards = document.querySelectorAll('.staff-card');
            const evaluationFormContent = document.getElementById('evaluationFormContent');
            const selectedStaffIdInput = document.getElementById('selectedStaffId');
            const evaluationForm = document.querySelector('form'); // Get the form element

            // Only attach click listeners if staff selection cards are visible on load
            // This is determined by PHP's $show_staff_selection variable
            if (<?php echo json_encode($show_staff_selection); ?>) {
                staffCards.forEach(card => {
                    card.addEventListener('click', function() {
                        // Remove 'active' class from all cards
                        staffCards.forEach(c => c.classList.remove('active'));

                        // Add 'active' class to the clicked card
                        this.classList.add('active');

                        // Set the value of the hidden input
                        selectedStaffIdInput.value = this.dataset.id;

                        // Show the evaluation form content with a fade-in effect
                        evaluationFormContent.classList.add('show');

                        // Optional: Scroll to the evaluation form for better UX
                        evaluationFormContent.scrollIntoView({ behavior: 'smooth', block: 'start' });

                        // Reset radio buttons and comments field for new evaluation
                        evaluationForm.reset(); // Resets all form fields
                        // Ensure custom radio buttons are truly unchecked
                        document.querySelectorAll('input[type="radio"]').forEach(radio => radio.checked = false);
                    });
                });
            }
        });
    </script>

</body>
</html>