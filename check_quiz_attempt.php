<?php
session_start();
include 'core_db.php';

if (!isset($_GET['regNo']) || !isset($_GET['quizId'])) {
    echo json_encode(["status" => "error"]);
    exit;
}

$RegNo = $_GET['regNo'];
$QuizId = $_GET['quizId'];

// Check if student already attempted in either `student` or `stud` table
$result1 = $conn->query("SELECT * FROM student WHERE RegNo = '$RegNo' AND QuizId = '$QuizId'");
$result2 = $conn->query("SELECT * FROM stud WHERE regno = '$RegNo' AND QuizId = '$QuizId'");

if ($result1->num_rows > 0 || $result2->num_rows > 0) {
    echo json_encode(["status" => "exists"]);  // User already attempted quiz
} else {
    $_SESSION['agreed'] = 1;  // Mark agreement session
    echo json_encode(["status" => "not_exists"]);  // User can take quiz
}
exit;
?>
