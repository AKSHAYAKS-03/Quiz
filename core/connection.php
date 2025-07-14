<?php
    session_start();

    date_default_timezone_set('Asia/Kolkata');

    $host = "localhost:3390";
    $username = "root";
    $password = "";
    $dbname = "quiz2";

    $conn = new mysqli($host, $username, $password, $dbname);

    mysqli_set_charset($conn, "utf8");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
?>
