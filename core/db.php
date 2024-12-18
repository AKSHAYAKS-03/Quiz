<?php
session_start();

date_default_timezone_set('Asia/Kolkata');

$host = "localhost:3390";
$username = "root";
$password = "";
$dbname = "quizz";

$conn = new mysqli($host, $username, $password, $dbname);

// Set the character set to utf8mb4
mysqli_set_charset($conn, "utf8");

// // Check and display the current character set
// $current_charset = mysqli_character_set_name($conn);

// echo "Current character set: " . $current_charset;

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
