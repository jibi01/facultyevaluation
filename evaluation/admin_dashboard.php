<?php
session_start();
require 'config.php';

// Only allow admin
if ($_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Get total number of evaluations
$total = $conn->query("SELECT COUNT(*) AS total FROM evaluations")->fetch_assoc()['total'];

// Get average scores for Q1–Q5 per faculty
$avg_query = "
SELECT 
    u.name AS faculty_name,
    ROUND(AVG(e.q1), 2) AS q1_avg,
    ROUND(AVG(e.q2), 2) AS q2_avg,
    ROUND(AVG(e.q3), 2) AS q3_avg,
    ROUND(AVG(e.q4), 2) AS q4_avg,
    ROUND(AVG(e.q5), 2) AS q5_avg
FROM evaluations e
JOIN users u ON e.evaluatee_id = u.id
WHERE u.role = 'faculty'
GROUP BY e.evaluatee_id
ORDER BY faculty_name ASC
";



$results = $conn->query($avg_query);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Admin Dashboard</title>
</head>
<body>
  <h2>Welcome, Admin!</h2>
  <a href="user_management.php">👤 Manage Users</a>
  <p>Total Evaluations Submitted: <strong><?= $total ?></strong></p>

  <h3>Faculty Evaluation Summary</h3>
  <table border="1" cellpadding="10">
    <tr>
      <th>Faculty Name</th>
      <th>Q1 Avg</th>
      <th>Q2 Avg</th>
      <th>Q3 Avg</th>
      <th>Q4 Avg</th>
      <th>Q5 Avg</th>
    </tr>
    <?php while ($row = $results->fetch_assoc()): ?>
    <tr>
      <td><?= htmlspecialchars($row['faculty_name']) ?></td>
      <td><?= $row['q1_avg'] ?></td>
      <td><?= $row['q2_avg'] ?></td>
      <td><?= $row['q3_avg'] ?></td>
      <td><?= $row['q4_avg'] ?></td>
      <td><?= $row['q5_avg'] ?></td>
    </tr>
    <?php endwhile; ?>
  </table>

  <br>
  <a href="login.php">Logout</a>
</body>
</html>
