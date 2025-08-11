<?php
session_start();
require 'config.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'];
$user = $conn->query("SELECT * FROM users WHERE id=$id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $name = $_POST['name'];
    $role = $_POST['role'];
    $password = $_POST['password'];

    if (!empty($password)) {
        $hashed = hash('sha256', $password);
        $conn->query("UPDATE users SET username='$username', name='$name', role='$role', password='$hashed' WHERE id=$id");
    } else {
        $conn->query("UPDATE users SET username='$username', name='$name', role='$role' WHERE id=$id");
    }

    header("Location: user_management.php");
    exit();

   

}
?>

<!DOCTYPE html>
<html>
<head><title>Edit User</title></head>
<body>
<h2>Edit User</h2>
<a href="user_management.php">⬅ Back to User List</a>
<br><br>

<form method="POST" action="">
    Username: <input type="text" name="username" value="<?= $user['username'] ?>" required><br><br>
    Name: <input type="text" name="name" value="<?= $user['name'] ?>" required><br><br>
    Role:
    <select name="role" required>
        <option value="student" <?= $user['role'] == 'student' ? 'selected' : '' ?>>Student</option>
        <option value="faculty" <?= $user['role'] == 'faculty' ? 'selected' : '' ?>>Faculty</option>
        <option value="dept_head" <?= $user['role'] == 'dept_head' ? 'selected' : '' ?>>Department Head</option>
        <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
    </select><br><br>
    Password: <input type="password" name="password"> (Leave blank to keep old password)<br><br>
    <button type="submit">Update User</button>
</form>
</body>
</html>
