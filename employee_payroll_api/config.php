<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration Constants
define('DB_SERVER', 'localhost'); //Or '127.0.0.1'
define('DB_USERNAME', 'root');   
define('DB_PASSWORD', '');       
define('DB_NAME', 'employee_payroll_db'); //database name

//connect sa MySQL database
$db_connection = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

//Check connection
if ($db_connection->connect_error) {
    die("Connection failed: " . $db_connection->connect_error);
}
if (!$db_connection->set_charset("utf8mb4")) {
}
?>