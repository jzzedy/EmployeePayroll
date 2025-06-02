<?php
require_once 'config.php'; //test for hash

$username = 'admin';
$plainPassword = 'password'; // password for admin

// Hash the password
$hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

echo "Username: " . $username . "<br>";
echo "Plain Password (for reference only, DO NOT STORE): " . $plainPassword . "<br>";
echo "Hashed Password (store this in the database): " . $hashedPassword . "<br><br>";

$sql = "INSERT INTO users (username, password_hash, full_name) VALUES ('admin', '" . $hashedPassword . "', 'Administrator');";
echo "SQL to run in phpMyAdmin: <br>" . htmlspecialchars($sql);

?>
