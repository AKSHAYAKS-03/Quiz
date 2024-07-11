<?php
session_start();

date_default_timezone_set('Asia/Kolkata');

$host = "localhost:3307";
$username = "root";
$password = "";
$dbname = "quizz";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
