<?php
session_start();
require 'config.php';

// Only allow students
if ($_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $faculty_id = $_POST['faculty_id'];
    $q1 = $_POST['q1'];
$q2 = $_POST['q2'];
$q3 = $_POST['q3'];
$q4 = $_POST['q4'];
$q5 = $_POST['q5'];

    $evaluator_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO evaluations (evaluator_id, faculty_id, q1, q2, q3, q4, q5) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iiiiiii", $evaluator_id, $faculty_id, $q1, $q2, $q3, $q4, $q5);
$stmt->execute();

    $message = "Evaluation submitted successfully!";
}


// Get list of faculty to show in dropdown
$faculty_list = $conn->query("SELECT id, name FROM users WHERE role='faculty'");
?>

<!DOCTYPE html>
<html>
<head><title>Student Evaluation</title></head>
<body>
<h2>Student Evaluation Form</h2>

<?php if (isset($message)) echo "<p style='color:green;'>$message</p>"; ?>

<form method="POST" action="">
  <label>Select Faculty:</label><br>
  <select name="faculty_id" required>
    <option value="">-- Select Faculty --</option>
    <?php while($row = $faculty_list->fetch_assoc()): ?>
      <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
    <?php endwhile; ?>
  </select><br><br>

 <label>1. Knowledge of the Subject:</label><br>
<input type="radio" name="q1" value="4" required> Excellent<br>
<input type="radio" name="q1" value="3"> Good<br>
<input type="radio" name="q1" value="2"> Fair<br>
<input type="radio" name="q1" value="1"> Poor<br><br>

<label>2. Communication Skills:</label><br>
<input type="radio" name="q2" value="4" required> Excellent<br>
<input type="radio" name="q2" value="3"> Good<br>
<input type="radio" name="q2" value="2"> Fair<br>
<input type="radio" name="q2" value="1"> Poor<br><br>

<label>3. Classroom Management:</label><br>
<input type="radio" name="q3" value="4" required> Excellent<br>
<input type="radio" name="q3" value="3"> Good<br>
<input type="radio" name="q3" value="2"> Fair<br>
<input type="radio" name="q3" value="1"> Poor<br><br>

<label>4. Punctuality:</label><br>
<input type="radio" name="q4" value="4" required> Excellent<br>
<input type="radio" name="q4" value="3"> Good<br>
<input type="radio" name="q4" value="2"> Fair<br>
<input type="radio" name="q4" value="1"> Poor<br><br>

<label>5. Student Engagement:</label><br>
<input type="radio" name="q5" value="4" required> Excellent<br>
<input type="radio" name="q5" value="3"> Good<br>
<input type="radio" name="q5" value="2"> Fair<br>
<input type="radio" name="q5" value="1"> Poor<br><br>


  <button type="submit">Submit Evaluation</button>
</form>
</body>
</html>
