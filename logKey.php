<?php
session_start();
include 'core_db.php';

if (!isset($_SESSION['RollNo']) || empty($_SESSION['RollNo'])) {
    header('Location: index.php');
    exit;
}

// logKey.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keyCode = $_POST['key'];
    $regNo = $_POST['regNo'];
    $quizId = $_POST['quizId'];

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Insert into the database
    $stmt = $conn->prepare("INSERT INTO logEvent (quizId, regNo, event, time) VALUES (?, ?,?, NOW())");
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
