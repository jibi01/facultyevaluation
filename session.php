<?php
// session.php - Reusable Session Checking Function

/**
 * Checks the user's session for authentication and role-based access.
 * This function assumes session_start() has already been called by the calling script (e.g., login.php or dashboard pages).
 *
 * @param string $requiredRole The specific role needed to access the page (e.g., 'student', 'faculty').
 * @return array|null An associative array containing 'user_id', 'username', 'fullName', 'avatar', and 'user_role' if the session is valid and role matches. Otherwise, returns null.
 */
function checkUserSession($requiredRole) {
    // 1. Check if the essential session variables are set.
    // These must match exactly what your login.php sets.
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || !isset($_SESSION['user_role'])) {
        return null; // Not logged in or essential session data missing.
    }

    // 2. Check if the user's role matches the required role for the current page.
    // Ensure the role string (e.g., 'faculty') matches the exact case and value in your DB.
    if ($_SESSION['user_role'] !== $requiredRole) {
        return null; // User's role does not match the required role for this page.
    }

    // 3. If both checks pass, return the necessary user data from the session.
    // Use null coalescing (??) to provide default values if these session variables might not always be set
    // This helps prevent "Undefined variable" warnings if, for example, 'avatar' isn't always set during login.
    return [
        'user_id'   => $_SESSION['user_id'],
        'username'  => $_SESSION['username'],
        'user_role' => $_SESSION['user_role'],
        'fullName'  => $_SESSION['fullName'] ?? 'Guest User', // Assuming 'fullName' is set by login.php
        'avatar'    => $_SESSION['avatar'] ?? 'ICONS/default_avatar.png' // Assuming 'avatar' is set by login.php
    ];
}
?>