<?php
session_start();
require 'config.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Delete user if "delete" link is clicked
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM users WHERE id=$id");
    header("Location: user_management.php");
    exit();
}


$result = $conn->query("SELECT * FROM users ORDER BY role, name");
?>

<!DOCTYPE html>
<html>
<head><title>User Management</title></head>
<body>
<h2>User Management</h2>

<a href="admin_dashboard.php">⬅ Back to Dashboard</a> |
<a href="add_user.php">➕ Add New User</a>
<br><br>

<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Name</th>
        <th>Role</th>
        <th>Actions</th>
    </tr>
    <?php while($user = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $user['id'] ?></td>
        <td><?= htmlspecialchars($user['username']) ?></td>
        <td><?= htmlspecialchars($user['name']) ?></td>
        <td><?= $user['role'] ?></td>
        <td>
            <a href="edit_user.php?id=<?= $user['id'] ?>">✏️ Edit</a> |
            <a href="user_management.php?delete=<?= $user['id'] ?>" onclick="return confirm('Are you sure?')">🗑️ Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

</body>
</html>
