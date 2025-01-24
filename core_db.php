
<?php

$servername = "localhost:3307";
$username = "root";
$password = "";
$dbname = "quizz";

$conn = new mysqli($servername, $username, $password, $dbname);
mysqli_set_charset($conn, "utf8");

// $current_charset = mysqli_character_set_name($conn);
// echo "Current character set: " . $current_charset;


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

