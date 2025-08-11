<?php
session_start();
if ($_SESSION['role'] != 'faculty') {
    header("Location: login.php");
    exit();
}

?>
<li><a href="evaluation_form_staff.php">Evaluate Your Peers</a></li>


<h2>Welcome, <?= $_SESSION['name'] ?> (Faculty)</h2>
<p>This is your faculty dashboard.</p>