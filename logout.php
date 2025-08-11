<?php
// logout.php - Handles User Logout

session_start(); // Start the session
$_SESSION = array(); // Unset all session variables
session_destroy(); // Destroy the session

// Clear the session cookie from the client's browser
// (This is important to completely end the session on the client side)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to the login page
header("Location: login.php?logged_out=true");
exit();
?>