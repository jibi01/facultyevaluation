<?php
// faculty_dashboard_new.php

// Enable error reporting for debugging (REMOVE OR SET TO 0 IN PRODUCTION)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include your database connection (config.php should provide $conn PDO object)
require_once __DIR__ . '/config.php';
// Include your session handling function
require_once __DIR__ . '/session.php';

// Start the session on this page.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Use checkUserSession for robust authentication and role checking
// 'faculty' MUST match the exact string in your users table's 'role' column
$user = checkUserSession('faculty');

// If checkUserSession returns null, it means authentication failed or role mismatch
if ($user === null) {
    header("Location: login.php?error=auth_required_for_faculty");
    exit();
}

// User is authenticated and authorized as faculty. Extract their data.
$faculty_id = htmlspecialchars($user['user_id']); // Sanitize for display if directly used
$faculty_fullName = htmlspecialchars($user['fullName']);
$faculty_avatar = htmlspecialchars($user['avatar']);


// --- Academic Year & Semester (can be dynamic, currently fixed) ---
$currentAcademicYear = "2025-2026";
$currentSemester = $currentAcademicYear . ' 2nd Semester';

// --- Dynamically fetch staff members and their evaluation status ---
$instructorEvaluations = [];
$db_error_message = ""; // Initialize error message for database operations

try {
    // Step 1: Get all staff members (using PDO with $conn)
    // Assuming staff members are also in the 'users' table with role 'staff'
    $staff_stmt = $conn->prepare("SELECT id, fullName, username FROM users WHERE role = 'staff' ORDER BY fullName ASC");
    $staff_stmt->execute();
    $staff_members = $staff_stmt->fetchAll(); // Fetch all results as associative array

    if ($staff_members) {
        foreach ($staff_members as $staff_row) {
            $staffId = $staff_row['id'];
            $staffName = htmlspecialchars($staff_row['fullName']); // Use fullName as per your DB
            $staffUsername = htmlspecialchars($staff_row['username']);

            // Step 2: Check if the current faculty member has evaluated this staff (using PDO with $conn)
            // Assuming evaluations are stored in 'evaluations_faculty' table
            $evaluation_status_stmt = $conn->prepare("SELECT COUNT(*) AS count FROM evaluations_faculty WHERE evaluator_id = ? AND evaluatee_id = ?");
            $evaluation_status_stmt->execute([$faculty_id, $staffId]);
            $status_row = $evaluation_status_stmt->fetch(); // Fetch single row
            $has_evaluated = ($status_row['count'] > 0);

            // Add to our dynamic list
            $instructorEvaluations[] = [
                'name' => $staffName,
                'course' => 'Programming', // <<< Placeholder: Fetch dynamically if staff are linked to courses
                'semester' => $currentSemester, // Using dynamic semester from above
                'schedule' => 'Flexible', // <<< Placeholder: Fetch dynamically if staff have schedules
                'status' => $has_evaluated ? 'completed' : 'pending',
                'evaluatee_id' => $staffId
            ];
        }
    }
} catch (PDOException $e) {
    error_log("Faculty Dashboard DB Error: " . $e->getMessage()); // Log detailed error
    $instructorEvaluations = []; // Ensure array is empty on error
    $db_error_message = "Could not load instructor data due to a database error.";
}

// --- Dynamic calculation for summary statistics ---
$evaluatedInstructorsCount = 0;
$pendingEvaluationsCount = 0; // Renamed to clarify count is for evaluations
foreach ($instructorEvaluations as $evaluation) {
    if ($evaluation['status'] == 'completed') {
        $evaluatedInstructorsCount++;
    } else {
        $pendingEvaluationsCount++;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard</title>
    <!-- You might have a separate style.css or your styles embedded below -->
    <!-- <link rel="stylesheet" href="style.css"> -->
    <style>
        /* General Styles & Variables */
        :root {
            --primary-green: #4CAF50; /* Calming green */
            --darker-green: #2E7D32; /* Deeper green for accents */
            --light-green-bg: #E8F5E9; /* Very light green for subtle backgrounds */
            --soft-yellow-orange: #FFC107; /* Soft yellow/orange for attention */
            --text-color-dark: #333333;
            --text-color-light: #FFFFFF;
            --border-color-light: #e0e0e0;
            --shadow-light: rgba(0,0,0,0.08);
            --shadow-medium: rgba(0,0,0,0.15);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background-color: #F8F8F8;
            color: var(--text-color-dark);
        }

        /* Top Header */
        .header {
            background-color: var(--darker-green);
            color: white;
            padding: 18px 200px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 5px var(--shadow-light);
            /* Ensure the header does not overflow the sidebar if you have padding */
            z-index: 1001; /* Above sidebar */
            position: relative; /* To position correctly over main-content's margin */
            width: calc(100% - 220px); /* Adjust width to fit beside sidebar */
            margin-left: 220px; /* Align with main content */
        }
        .header .title {
            font-weight: 600;
            font-size: 20px;
            letter-spacing: 0.5px;
            /* Adjust padding if you want it left-aligned with other content */
            /* padding: 12px 0px; */
        }
        .academic-year-display {
            font-size: 16px;
            font-weight: 500;
            color: white;
        }


        /* Sidebar */
        .sidebar {
            width: 220px;
            background: #fff;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            border-right: 1px solid #e0e0e0;
            padding-top: 20px; /* Adjusted padding to accommodate user profile */
            text-align: center;
            box-shadow: 2px 0 5px var(--shadow-light);
            z-index: 1000;
        }

        /* New: User Profile in Sidebar */
        .sidebar-user-profile {
            padding: 20px 0;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color-light);
        }
        .sidebar-avatar {
            width: 80px; /* Larger avatar */
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
            border: 2px solid var(--primary-green);
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        .sidebar-user-name {
            font-size: 1.1em;
            font-weight: bold;
            color: var(--text-color-dark);
            margin-bottom: 5px;
        }
        .sidebar-user-role {
            font-size: 0.9em;
            color: #666;
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
            color: var(--text-color-dark);
            text-decoration: none;
            text-align: left;
            font-size: 15px;
            transition: background-color 0.3s ease, color 0.3s ease, border-left 0.3s ease;
        }
        .sidebar ul li a.active {
            color: var(--primary-green);
            font-weight: bold;
            background-color: var(--light-green-bg);
            border-left: 5px solid var(--primary-green);
        }
        .sidebar ul li a:hover:not(.active) {
            background: #f0f0f0;
            color: var(--darker-green);
            border-left: 5px solid #d0d0d0;
        }

        /* Main Content Area */
        .main-content {
            margin-left: 220px; /* To accommodate sidebar */
            padding: 30px;
            background-color: #F8F8F8;
            min-height: calc(100vh - 66px); /* Adjust based on header height if fixed */
        }

        /* Summary Statistics Section */
        .summary-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .stat-box {
            flex: 1;
            min-width: 250px;
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 10px var(--shadow-light);
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .stat-box.evaluated {
            border-left: 5px solid var(--primary-green);
        }
        .stat-box.pending {
            border-left: 5px solid var(--soft-yellow-orange);
        }
        .stat-label {
            font-size: 16px;
            color: #666;
            margin-bottom: 10px;
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
        }
        .stat-box.evaluated .stat-number {
            color: var(--primary-green);
        }
        .stat-box.pending .stat-number {
            color: var(--soft-yellow-orange);
        }

        /* Available for Evaluation Section */
        .section-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--darker-green);
            margin-bottom: 20px;
            border-bottom: 2px solid var(--light-green-bg);
            padding-bottom: 10px;
        }
        .instructor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        .instructor-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px var(--shadow-light);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .instructor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px var(--shadow-medium);
        }
        .instructor-info {
            margin-bottom: 20px;
        }
        .instructor-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--darker-green);
        }
        .course-details, .schedule {
            font-size: 14px;
            color: #555;
            margin-bottom: 3px;
        }
        .card-button {
            display: block;
            width: 100%;
            padding: 12px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            transition: background-color 0.2s ease, transform 0.1s ease;
        }
        .button-evaluate-now {
            background-color: var(--primary-green);
            color: var(--text-color-light);
        }
        .button-evaluate-now:hover {
            background-color: var(--darker-green);
            transform: translateY(-1px);
        }
        .button-view-evaluation {
            background-color: var(--soft-yellow-orange);
            color: var(--text-color-dark);
        }
        .button-view-evaluation:hover {
            background-color: #FFD54F;
            transform: translateY(-1px);
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
                width: 100%; /* Full width on mobile */
                margin-left: 0;
            }
            .summary-stats {
                flex-direction: column;
                align-items: center;
            }
            .stat-box {
                width: 90%;
                min-width: unset;
            }
            .instructor-grid {
                grid-template-columns: 1fr;
            }
            .main-content {
                padding: 20px;
                margin-left: 0;
            }
            .sidebar {
                display: none; /* Hide sidebar on small screens, implement toggle if needed */
            }
        }
    </style>
</head>
<body>

    <!-- Top Header (adjusted for dashboard info) -->
    <div class="header">
        <div class="title">TCM FACULTY AND STAFF EVALUATION</div>
        <div class="academic-year-display">Academic Year: <?php echo $currentAcademicYear; ?></div>
        <!-- Removed menu-icon as it was display:none; and not part of the core request -->
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- New: Faculty Profile Display -->
        <div class="sidebar-user-profile">
            <img src="<?php echo $faculty_avatar; ?>" alt="<?php echo $faculty_fullName; ?>" class="sidebar-avatar">
            <div class="sidebar-user-name"><?php echo $faculty_fullName; ?></div>
            <div class="sidebar-user-role">Faculty</div>
        </div>
        <ul>
            <li><a href="faculty_dashboard_new.php" class="active">Dashboard</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <?php if (!empty($db_error_message)): ?>
            <p style="color: red; font-weight: bold;"><?php echo htmlspecialchars($db_error_message); ?></p>
        <?php endif; ?>

        <div class="summary-stats">
            <div class="stat-box evaluated">
                <div class="stat-label">Evaluated Instructors</div>
                <div class="stat-number"><?php echo $evaluatedInstructorsCount; ?></div>
            </div>
            <div class="stat-box pending">
                <div class="stat-label">Pending Evaluations</div>
                <div class="stat-number"><?php echo $pendingEvaluationsCount; ?></div>
            </div>
        </div>

        <div class="section-title">Available for Evaluation</div>
        <div class="instructor-grid">
            <?php if (empty($instructorEvaluations)): ?>
                <p>No staff members available for evaluation or an error occurred.</p>
            <?php else: ?>
                <?php foreach ($instructorEvaluations as $evaluation): ?>
                    <div class="instructor-card">
                        <div class="instructor-info">
                            <div class="instructor-name"><?php echo htmlspecialchars($evaluation['name']); ?></div>
                            <div class="course-details">Course: <?php echo htmlspecialchars($evaluation['course']); ?></div>
                            <div class="course-details"><?php echo htmlspecialchars($evaluation['semester']); ?></div>
                            <div class="schedule"><?php echo htmlspecialchars($evaluation['schedule']); ?></div>
                        </div>
                        <?php if ($evaluation['status'] == 'pending'): ?>
                            <a href="evaluation_form_faculty.php?evaluatee_id=<?php echo htmlspecialchars($evaluation['evaluatee_id']); ?>" class="card-button button-evaluate-now">Evaluate Now</a>
                        <?php else: ?>
                            <a href="view_evaluation.php?evaluatee_id=<?php echo htmlspecialchars($evaluation['evaluatee_id']); ?>" class="class-button button-view-evaluation">View Evaluation</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>