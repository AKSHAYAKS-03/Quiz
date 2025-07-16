<?php
include_once '../core/header.php';

if (!isset($_GET['regNo']) || !isset($_GET['quizId'])) {
    echo json_encode(["status" => "error"]);
    exit;
}

$RegNo = $_GET['regNo'];
$QuizId = $_GET['quizId'];
$attendedAt = date('Y-m-d');

// Check if student already attempted in either `student` or `stud` table
$result1 = $conn->query("SELECT * FROM student WHERE RegNo = '$RegNo' AND QuizId = '$QuizId'");
$result2 = $conn->query("SELECT * FROM stud WHERE regno = '$RegNo' AND QuizId = '$QuizId'");

if ($result1->num_rows > 0 || $result2->num_rows > 0) {
    echo json_encode(["status" => "exists"]);  // User already attempted quiz
} else {
    $stmt = $conn->prepare("INSERT INTO student (RegNo, QuizId, Attended_At) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $RegNo, $QuizId, $attendedAt);
    $stmt->execute();

    $stmt->close();

    echo json_encode(["status" => "not_exists"]);  // User can take quiz
}
exit;
?>
