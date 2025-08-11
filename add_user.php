<?php
session_start();
require 'config.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $name = $_POST['name'];
    $role = $_POST['role'];
    $password = hash('sha256', $_POST['password']);

    $stmt = $conn->prepare("INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $password, $name, $role);
    $stmt->execute();

    header("Location: user_management.php");
    

    exit();
}
?>

<!DOCTYPE html>
<html>
<head><title>Add User</title></head>
<body>
<h2>Add New User</h2>
<a href="user_management.php">⬅ Back to User List</a>
<br><br>

<form method="POST" action="">
    Username: <input type="text" name="username" required><br><br>
    Name: <input type="text" name="name" required><br><br>
    Role:
    <select name="role" required>
        <option value="">-- Select Role --</option>
        <option value="student">Student</option>
        <option value="faculty">Faculty</option>
        <option value="dept_head">Department Head</option>
        <option value="admin">Admin</option>
    </select><br><br>
    Password: <input type="password" name="password" required><br><br>
    <button type="submit">Add User</button>
</form>
</body>
</html>
