<?php
include_once '../core/header.php';

if (!isset($_SESSION['RegNo']) || empty($_SESSION['RegNo'])) {
    header('Location: ../index.php');
    exit;
}

// logKey.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keyCode = $_POST['key'];
    $regNo = $_POST['regNo'];
    $quizId = $_POST['quizId'];

    error_log("Received Key: $keyCode, RegNo: $regNo, QuizId: $quizId");

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Insert into the database
    $stmt = $conn->prepare("INSERT INTO logEvent (QuizId, RegNo, Event, Time) VALUES (?, ?,?, NOW())");
    $stmt->bind_param('iss', $quizId, $regNo, $keyCode);

    if ($stmt->execute()) {
        echo "Key logged successfully.";
    } else {
        echo "Error: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request method.";
}
?>