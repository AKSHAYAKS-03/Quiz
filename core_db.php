<?php

$servername = "localhost:3390";
$username = "root";
$password = "";
$dbname = "quizz";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
