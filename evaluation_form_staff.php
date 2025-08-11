<?php
session_start();
require 'config.php';

if ($_SESSION['role'] != 'staff') {
    header("Location: login.php");
    exit();
}

$staff_id = $_SESSION['user_id'];

// Get list of other staff (exclude self)
$sql = "SELECT * FROM users WHERE role = 'staff' AND id != $staff_id";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html>
<head><title>Staff Peer Evaluation</title></head>
<body>
<h2>Evaluate a Peer</h2>

<form action="submit_evaluation.php" method="POST">
    <label for="peer">Select Staff Peer:</label>
    <select name="evaluatee_id" required>
        <?php while ($row = $result->fetch_assoc()): ?>
            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?> (<?= htmlspecialchars($row['username']) ?>)</option>
        <?php endwhile; ?>
    </select><br><br>

    <label for="score">Score (1-5):</label>
    <input type="number" name="score" min="1" max="5" required><br><br>

    <label for="comments">Comments:</label><br>
    <textarea name="comments" rows="4" cols="50"></textarea><br><br>

    <input type="hidden" name="evaluator_id" value="<?= $staff_id ?>">
    <input type="hidden" name="evaluator_role" value="staff">
    <input type="hidden" name="evaluatee_role" value="staff">
    <input type="hidden" name="category" value="staff-peer">

    <input type="submit" value="Submit Evaluation">
</form>

<p><a href="staff_dashboard.php">Back to Dashboard</a></p>
</body>
</html>
