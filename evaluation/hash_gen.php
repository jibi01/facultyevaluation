<?php
$plain_text_password = 'faculty';
$hashed_password = password_hash($plain_text_password, PASSWORD_DEFAULT);
echo "Your Hashed Password: " . $hashed_password;

$plain_text_password = 'staff';
$hashed_password = password_hash($plain_text_password, PASSWORD_DEFAULT);
echo "Your Hashed Password: " . $hashed_password;
?>