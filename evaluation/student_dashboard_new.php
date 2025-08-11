<?php
// student_dashboard_new.php - Student Dashboard Page

// Enable error reporting for debugging (REMOVE OR SET TO 0 IN PRODUCTION)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the session handling function definition
require_once __DIR__ . '/session.php'; // Ensures checkUserSession is available
// Include your database connection
require_once __DIR__ . '/config.php';

// Start the session on this page.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Call the checkUserSession function to validate the user and their role.
$user = checkUserSession('student');

// CRITICAL REDIRECTION LOGIC: If checkUserSession returns null, redirect.
if ($user === null) {
    header("Location: login.php?error=auth_required_or_invalid_session");
    exit(); // IMPORTANT: Always exit after a header redirect to prevent further code execution
}

// If we reach here, the user is authenticated and authorized as a 'student'.
// The $user array contains their validated data. Assign to variables for easy use in HTML.
$user_id = htmlspecialchars($user['user_id']);
$username = htmlspecialchars($user['username']);
$fullName = htmlspecialchars($user['fullName']);
$avatar = htmlspecialchars($user['avatar']);

// --- Dynamic Data for Student Dashboard ---
$currentAcademicYear = "2025-2026"; // Or fetch dynamically from DB/settings
$currentSemester = $currentAcademicYear . ' 1st Semester'; // Or fetch dynamically

$instructorsAvailable = []; // Array to hold instructors available for evaluation
$db_error_message = ""; // Initialize for database errors

try {
    // Fetch all users with the 'faculty' role (these are the instructors students evaluate)
    $all_faculty_stmt = $conn->prepare("SELECT id, fullName, username FROM users WHERE role = 'faculty' ORDER BY fullName ASC");
    $all_faculty_stmt->execute();
    $all_faculty_members = $all_faculty_stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($all_faculty_members) {
        foreach ($all_faculty_members as $faculty_row) {
            $facultyId = $faculty_row['id'];
            $facultyName = htmlspecialchars($faculty_row['fullName']);
            $facultyUsername = htmlspecialchars($faculty_row['username']);

            // Check if this student ($user_id) has already evaluated this faculty member ($facultyId)
            $evaluation_check_stmt = $conn->prepare("SELECT COUNT(*) AS count FROM evaluations_student WHERE evaluator_id = ? AND evaluatee_id = ?");
            $evaluation_check_stmt->execute([$user_id, $facultyId]);
            $status_row = $evaluation_check_stmt->fetch(PDO::FETCH_ASSOC);
            $has_evaluated = ($status_row['count'] > 0);

            $instructorsAvailable[] = [
                'id' => $facultyId,
                'name' => $facultyName,
                'username' => $facultyUsername,
                'status' => $has_evaluated ? 'completed' : 'pending',
                'course' => 'Assigned Course', // Placeholder: You'd fetch student's assigned courses to faculty
                'semester' => $currentSemester,
                'schedule' => 'TBD' // Placeholder: You'd fetch schedule if available
            ];
        }
    }
} catch (PDOException $e) {
    error_log("Student Dashboard DB Error: " . $e->getMessage());
    $instructorsAvailable = []; // Ensure empty array on error
    $db_error_message = "Could not load instructor data due to a database error.";
}

// Calculate summary counts
$totalInstructorsCount = count($instructorsAvailable);
$evaluatedInstructorsCount = 0;
$pendingEvaluationsCount = 0;

foreach ($instructorsAvailable as $instructor) {
    if ($instructor['status'] == 'completed') {
        $evaluatedInstructorsCount++;
    } else {
        $pendingEvaluationsCount++;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Faculty and Staff Evaluation Dashboard</title>
  <style>
    /* Your CSS remains largely the same, but I'll include it for completeness.
       Pay attention to the `.header-user-icon` and related styles. */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --primary: #f4f6fa;
      --primary-foreground: #000000;
      --secondary: #f4f6fa;
      --secondary-foreground: #6e7687;
      --muted: #f4f6fa;
      --muted-foreground: #6e7687;
      --accent: #f4f6fa;
      --accent-foreground: #6e7687;
      --destructive: #ef4444;
      --destructive-foreground: #f1f5f9;
      --border: #e5e7eb;
      --input: #e5e7eb;
      --ring: #7c3aed;
      --background: #ffffff;
      --background-foreground: #0a0a0a;
      --card: #ffffff;
      --card-foreground: #0a0a0a;
      --popover: #ffffff;
      --popover-foreground: #1e624a;
      --academic-primary: #10b981;         
      --academic-primary-dark: #059669;   
      --academic-secondary: #34d399;      
      --status-pending: #ffc107;           
      --status-completed: #10b981;        
      --status-available: #10b981;       
      --card-blue: #CCE5FF; /* Changed to more common blue tint */
      --card-green: #D4EDDA; /* Changed to more common green tint */
      --card-yellow: #FFF8DC; /* Changed to more common yellow tint */
    }

    html, body {
      height: 100%;
      min-height: 100vh;
      width: 100vw;
      background: #ffffffff; /* Set to full green as per previous request */
      font-size: 16px;
    }

    body {
      min-height: 100vh;
      width: 100vw;
      margin: 0;
      padding: 0;
      display: flex;
      flex-direction: column;
    }

    .header {
      background-color: var(--background);
      border-bottom: 1px solid var(--border);
      padding: 1rem 0;
    }

    .header-content {
      position: relative;
      z-index: 2;
      display: flex;
      align-items: center;
      justify-content: flex-start;
      gap: 2rem;
      width: 100%;
      max-width: 100vw;
      min-height: 110px;
      padding-left: 2rem;
      padding-right: 2rem;
      box-sizing: border-box;
    }

    .logo-section {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .logo {
      width: 2rem;
      height: 2rem;
      background-color: var(--academic-primary);
      border-radius: 0.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
    }

    .logo-text {
      display: flex;
      flex-direction: column;
    }

    .logo-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--primary-foreground);
    }

    .user-profile {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .user-avatar {
      width: 2rem;
      height: 2rem;
      border-radius: 50%;
      background-color: var(--academic-primary);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 500;
    }

    .user-info {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      color: #fff;
    }

    .user-name {
      font-weight: 600;
      font-size: 1.05rem;
    }

    .user-id {
      font-size: 0.98rem;
      opacity: 0.9;
    }
    /* Avatar image styling */
    .user-avatar-img {
      width: 50px; /* Adjusted size */
      height: 50px; /* Adjusted size */
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #fff;
      background: #fff;
      margin-right: 0.5rem;
    }

    /* Common style for the small header icons (profile and logout) */
    .header-user-icon {
      width: 28px;
      height: 28px;
      object-fit: contain;
    }

    /* Logout button specific styles */
    .logout-btn {
      background: transparent;
      border: none;
      cursor: pointer;
      margin-left: 0.5rem;
      display: flex;
      align-items: center;
      padding: 0;
      transition: opacity 0.2s;
    }
    .logout-btn:hover {
      opacity: 0.7;
    }

    /* Profile dropdown container */
    .profile-dropdown-container {
      position: relative;
    }
    .profile-dropdown-btn {
      background: transparent;
      border: none;
      cursor: pointer;
      padding: 0;
    }
    /* Profile dropdown menu styles */
    .profile-dropdown-menu {
      display: none;
      position: absolute;
      right: 0;
      top: 120%;
      background: #8d1010ff; /* Dark red background */
      min-width: 160px;
      border-radius: 0.5rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.12);
      z-index: 10;
    }
    .profile-dropdown-menu a {
      display: block;
      padding: 0.75rem 1rem;
      text-decoration: none;
      color: #ffffff; /* Changed to white for readability */
      transition: background-color 0.2s;
    }
    .profile-dropdown-menu a:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }


    .header-title,
    .header-subtitle,
    .header-user,
    .user-info,
    .user-name,
    .user-id {
      color: #fff !important;
      opacity: 1 !important;
      text-shadow: 0 2px 8px rgba(0,0,0,0.7), 0 1px 0 #000;
    }


    main.container {
      flex: 1 1 auto;
      width: 100vw;
      max-width: 100vw;
      min-height: calc(100vh - 110px);
      margin: 0;
      padding: 2rem 2vw;
      background: #ffffffff; /* Set to full green as per previous request */
      border-radius: 0;
      box-sizing: border-box;
    }

    .dashboard-header {
      margin-bottom: 2rem;
    }

    .dashboard-title {
      font-size: 1.25rem;
      font-weight: bold;
      color: var(--primary-foreground);
    }

    .dashboard-subtitle {
      font-size: 1rem;
      color: var(--muted-foreground);
    }

    .stats.grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .stats-card {
      background-color: var(--card);
      border: 1px solid var(--border);
      border-radius: 0.5rem;
      padding: 1.5rem;
      box-shadow: 0 2px 4px rgba(16, 185, 129, 0.05);
    }

    .card-blue {
      background-color: var(--card-blue);
      color: #004085;
    }
    .card-green {
      background-color: var(--card-green);
      color: #155724;
    }
    .card-yellow {
      background-color: var(--card-yellow);
      color: #8B8000;
    }

    .stats-title {
      font-size: 1rem;
      font-weight: 500;
      margin-bottom: 0.5rem;
    }

    .stats-value {
      font-size: 2rem;
      font-weight: bold;
      margin-bottom: 0.5rem;
    }

    .stats-subtitle {
      font-size: 0.98rem;
      color: var(--muted-foreground);
    }

    .instructors-section {
      padding: 2rem 1rem;
      border-radius: 0.75rem;
      background: #004030 !important;
      margin-bottom: 0;
    }

    .instructors-title {
      font-size: 2rem;
      font-weight: bold;
      color: white;
      margin-bottom: 2rem;
    }

    .instructors-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 1.25rem;
    }

    .instructors-card {
      min-height: 180px;
      padding: 1.25rem 1rem;
      background-color: #fff;
      border-radius: 0.5rem;
      box-shadow: 0 2px 4px rgba(16, 185, 129, 0.05);
    }

    .instructor-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }

    .intructor-name {
      font-size: 1.5rem;
      font-weight: 600;
      color: var(--primary-foreground);
    }

    .status-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 500;
    }

    .status-badge.pending {
      background-color: var(--status-pending);
      color: #fff;
    }

    .status-badge.completed {
      background-color: var(--status-completed);
      color: #fff;
    }

    .intructor-course {
      font-size: 0.98rem;
      color: var(--academic-primary);
      margin-bottom: 1rem;
    }

    .intructor-details {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      margin-bottom: 1rem;
    }

    .details-row {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.97rem;
      color: var(--muted-foreground);
    }

    .detailed-icon {
      width: 1rem;
      height: 1rem;
    }

    .evaluate-btn {
      width: 100%;
      padding: 0.7rem;
      border-radius: 0.5rem;
      font-weight: 600;
      font-size: 1rem;
      border: none;
      cursor: pointer;
      background-color: var(--academic-primary);
      color: #fff;
      box-shadow: 0 2px 8px rgba(16,185,129,0.08);
      transition: background 0.2s, box-shadow 0.2s, transform 0.1s;
      letter-spacing: 0.5px;
    }

    .evaluate-btn:hover,
    .evaluate-btn:focus {
      background-color: var(--academic-primary-dark);
      box-shadow: 0 4px 16px rgba(16,185,129,0.15);
      transform: translateY(-2px) scale(1.02);
    }

    .evaluate-btn:active {
      background-color: var(--academic-primary);
      transform: scale(0.98);
    }

    /* Header background and overlay */
    .custom-header {
      position: relative;
      width: 100vw;
      min-height: 110px;
      background: url('ICONS/CM HEADER.jpg') center/cover no-repeat;
      display: flex;
      align-items: center;
      z-index: 1;
      overflow: hidden;
    }
    /* Overlay only the image, not the text */
    .header-overlay {
      position: absolute;
      inset: 0;
      background: rgba(16, 185, 129, 0.85); /* emerald overlay */
      pointer-events: none;
    }

    .header-content {
      position: relative;
      z-index: 2;
      display: flex;
      align-items: center;
      justify-content: flex-start;
      gap: 2rem;
      width: 100%;
      max-width: 100vw;
      min-height: 110px;
      padding-left: 2rem;
      padding-right: 2rem;
      box-sizing: border-box;
    }

    .header-titles {
      display: flex;
      flex-direction: column;
      justify-content: center;
      min-width: 260px;
    }
    .header-title {
      color: #fff;
      font-size: 2rem;
      font-weight: 600;
      margin-bottom: 0.2rem;
      letter-spacing: 0.5px;
    }
    .header-subtitle {
      color: #e0f7ef;
      font-size: 1.1rem;
      font-weight: 300;
    }
    .header-user {
      display: flex;
      align-items: center;
      gap: 1.2rem;
    }
    .user-info {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      color: #fff;
    }
    .user-name {
      font-weight: 600;
      font-size: 1.05rem;
    }
    .user-id {
      font-size: 0.98rem;
      opacity: 0.9;
    }

    /* Media Queries */
    @media (max-width: 768px) {
      .header-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
        padding: 1.2rem;
      }
      .header-user {
        align-items: flex-start;
        gap: 0.5rem;
      }

      .logo-text, .user-info {
        display: none;
      }

      .stats.grid, .instructors-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 900px) {
      main.container {
        padding: 1rem 0.5rem;
      }
      .instructors-section {
        padding: 1rem 0.5rem;
      }
      .instructors-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <header class="custom-header">
    <div class="header-overlay"></div>
    <div class="header-content">
      <!-- School Logo -->
      <img src="tcm logo.jfif" alt="School Logo" style="height:56px; margin-right:1.5rem; margin-left:0;">
      <!-- Titles -->
      <div class="header-titles">
        <h1 class="header-title">Faculty and Staff Evaluation</h1>
        <span class="header-subtitle">Academic Year 2025-2026</span>
      </div>
      <!-- User Info & Actions -->
      <div class="header-user">
          <div class="user-info">
              <img src="<?php echo $avatar; ?>"
                  alt="Student Avatar" class="user-avatar-img">
              <!-- Display user's full name and ID -->
              <span class="user-name"><?php echo $fullName; ?></span>
              <span class="user-id">Student ID: <?php echo $user_id; ?></span>
          </div>
        <!-- Profile dropdown -->
        <div class="profile-dropdown-container">
          <button id="profile-dropdown-btn" class="profile-dropdown-btn">
            <img src="student.png" alt="Profile Dropdown" class="header-user-icon" />
          </button>
          <div id="profile-dropdown-menu" class="profile-dropdown-menu">
            <a href="profile.php">View Profile</a>
            <a href="change_password.php">Change Password</a>
          </div>
        </div>
        <!-- Logout button -->
        <button id="logout-btn" class="logout-btn" title="Log Out">
          <img src="logout.jpeg" alt="Log Out" class="header-user-icon" />
        </button>
      </div>
    </div>
  </header>

  <main class="container">
    <div class="dashboard-header">
      <h1 class="dashboard-title">Welcome to the Faculty and Staff Evaluation Dashboard</h1>
      <p class="dashboard-subtitle">Complete your instructor evaluation for the current semester</p>
    </div>

    <div class="stats grid">
      <div class="stats-card card-blue">
        <h2 class="stats-title">Total Instructors</h2>
        <p class="stats-value"><?php echo $totalInstructorsCount; ?></p>
        <p class="stats-subtitle">Instructors available for evaluation</p>
      </div>
      <div class="stats-card card-green">
        <h2 class="stats-title">Evaluated Instructors</h2>
        <p class="stats-value"><?php echo $evaluatedInstructorsCount; ?></p>
        <p class="stats-subtitle">Instructors you have evaluated</p>
      </div>
      <div class="stats-card card-yellow">
        <h2 class="stats-title">Pending Evaluations</h2>
        <p class="stats-value"><?php echo $pendingEvaluationsCount; ?></p>
        <p class="stats-subtitle">Instructors awaiting your evaluation</p>
      </div>
    </div>

    <div class="instructors-section">
      <h2 class="instructors-title">Instructors Available for Evaluation</h2>
      <div class="instructors-grid" id="instructor-list">
        <?php if (!empty($instructorsAvailable)): ?>
            <?php foreach ($instructorsAvailable as $instructor): ?>
                <div class="instructors-card">
                    <div class="instructor-header">
                        <span class="intructor-name"><?php echo $instructor['name']; ?></span>
                        <span class="status-badge <?php echo $instructor['status']; ?>">
                            <?php echo ucfirst($instructor['status']); ?>
                        </span>
                    </div>
                    <p class="intructor-course">Course: <?php echo $instructor['course']; ?></p>
                    <div class="intructor-details">
                        <div class="details-row">
                            <img src="ICONS/icons8-semester-64.png" alt="Semester Icon" class="detailed-icon" />
                            <span><?php echo $instructor['semester']; ?></span>
                        </div>
                        <div class="details-row">
                            <img src="ICONS/icons8-schedule-50.png" alt="Schedule Icon" class="detailed-icon" />
                            <span><?php echo $instructor['schedule']; ?></span>
                        </div>
                    </div>
                    <?php if ($instructor['status'] == 'pending'): ?>
                        <button class="evaluate-btn pending" data-id="<?php echo $instructor['id']; ?>">Evaluate Now</button>
                    <?php else: ?>
                        <button class="evaluate-btn completed" data-id="<?php echo $instructor['id']; ?>">View Evaluation</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color:white;">No instructors available for evaluation at this time.</p>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <script>
    document.querySelectorAll('.evaluate-btn').forEach(function(button) {
      button.addEventListener('click', function () {
        const status = this.classList.contains('pending') ? 'pending' : 'completed';
        const instructorId = this.dataset.id; // Get the ID from the data-id attribute

        alert(`You clicked to ${status === 'pending' ? 'start' : 'view'} the evaluation for instructor ID: ${instructorId}.`);

        if (status === 'pending') {
          // Redirect to evaluation_form_student.php, passing the instructorId
          window.location.href = `evaluation_form_student.php?evaluatee_id=${instructorId}`;
        } else {
          // Assuming view_evaluation.php also needs the ID
          window.location.href = `view_evaluation.php?evaluatee_id=${instructorId}`;
        }
      });
    });

    document.getElementById('logout-btn').addEventListener('click', function() {
      window.location.href = 'logout.php';
    });

    // Profile dropdown toggle
    const profileBtn = document.getElementById('profile-dropdown-btn');
    const profileMenu = document.getElementById('profile-dropdown-menu');
    document.addEventListener('click', function(e) {
      if (profileBtn && profileMenu) {
        if (profileBtn.contains(e.target)) {
          profileMenu.style.display = profileMenu.style.display === 'block' ? 'none' : 'block';
        } else if (!profileMenu.contains(e.target)) {
          profileMenu.style.display = 'none';
        }
      }
    });
  </script>
</body>
</html>
