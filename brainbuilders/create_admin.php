<?php
$plain_password = 'emma5656'; // <--- CHANGE THIS to the password you want to use
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

echo "Plain password: " . $plain_password . "<br>";
echo "Hashed password: " . $hashed_password . "<br>";
echo "Copy this hashed password and update your database.";
?>