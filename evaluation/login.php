<?php
// login.php

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'config.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"] ?? '');
    $raw_password = $_POST["password"] ?? '';
    $role = $_POST["role"] ?? '';

    if (empty($username) || empty($raw_password) || empty($role)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, username, password, fullName, avatar, role 
                                     FROM users WHERE username = ? AND role = ?");
            $stmt->execute([$username, $role]);
            $user = $stmt->fetch();

            if ($user && password_verify($raw_password, $user["password"])) {
                $_SESSION["user_id"]   = $user["id"];
                $_SESSION["username"]  = $user["username"];
                $_SESSION["user_role"] = $user["role"];
                $_SESSION["fullName"]  = $user["fullName"];
                $_SESSION["avatar"]    = $user["avatar"] ?? 'ICONS/default_avatar.png';

                switch ($user["role"]) {
                    case "admin":   header("Location: admin_dashboard_new.php");   exit;
                    case "faculty": header("Location: faculty_dashboard_new.php"); exit;
                    case "staff":   header("Location: staff_dashboard_new.php");   exit;
                    case "student": header("Location: student_dashboard_new.php"); exit;
                }
            } else {
                $error = "Invalid username, password, or role.";
            }
        } catch (Exception $e) {
            $error = "An error occurred. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>System Login</title>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');
    * { box-sizing: border-box; font-family: 'Poppins', sans-serif; }

    body {
        margin: 0;
        height: 100vh;
        background: url('tcm.jpg') no-repeat center center fixed;
        background-size: cover;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .login-container {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        padding: 40px 30px;
        width: 320px;
        text-align: center;
        box-shadow: 0 4px 30px rgba(0,0,0,0.1);
    }

    .login-container img {
        width: 70px;
        margin-bottom: 10px;
    }

    .login-container h2 {
        color: #fff;
        font-weight: 600;
        margin-bottom: 20px;
    }

    .form-group {
        position: relative;
        margin-bottom: 15px;
    }

    .form-group input, .form-group select {
        width: 100%;
        padding: 12px;
        border-radius: 6px;
        border: none;
        outline: none;
        font-size: 14px;
    }

    .form-group input {
        padding-right: 40px;
    }

    .toggle-password {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #555;
        font-size: 14px;
        user-select: none;
    }

    .login-btn {
        width: 100%;
        padding: 12px;
        background: linear-gradient(to right, #007BFF, #3399FF);
        border: none;
        border-radius: 6px;
        color: white;
        font-size: 16px;
        cursor: pointer;
        margin-top: 10px;
    }

    .error-message {
        background: rgba(255,0,0,0.1);
        color: #ffdddd;
        padding: 8px;
        border-radius: 5px;
        margin-bottom: 15px;
        font-size: 13px;
    }
</style>
</head>
<body>

<div class="login-container">
    <img src="tcm logo.jfif" alt="System Logo">
    <h2>Faculty And Staff Evaluation System</h2>

    <?php if (!empty($error)): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <input type="text" name="username" placeholder="Username/Email" required>
        </div>

        <div class="form-group">
            <input type="password" name="password" id="password" placeholder="Password" required>
            <span class="toggle-password" onclick="togglePassword()">👁</span>
        </div>

        <div class="form-group">
            <select name="role" required>
                <option value="">Select Role</option>
                <option value="student">Student</option>
                <option value="faculty">Faculty</option>
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
            </select>
        </div>

        <button type="submit" class="login-btn">Login</button>
    </form>
</div>

<script>
function togglePassword() {
    const pwd = document.getElementById("password");
    if (pwd.type === "password") {
        pwd.type = "text";
    } else {
        pwd.type = "password";
    }
}
</script>

</body>
</html>
