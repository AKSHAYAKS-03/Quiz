<?php
session_start();

date_default_timezone_set('Asia/Kolkata');

$host = "localhost:3307";
$username = "root";
$password = "";
$dbname = "quizz";

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
