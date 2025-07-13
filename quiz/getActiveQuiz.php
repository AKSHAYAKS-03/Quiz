<?php
include_once 'core/connection.php';

$activeQuizQuery = "SELECT QuizName, Quiz_Id, QuizType FROM quiz_details WHERE IsActive = 1 LIMIT 1";
$activeQuizResult = $conn->query($activeQuizQuery);
$activeQuizData = $activeQuizResult->fetch_assoc();

$activeQuiz = $activeQuizData['QuizName'] ?? 'None';
$activeQuizId = $activeQuizData['Quiz_Id'] ?? 'None';
$quizType = $activeQuizData['QuizType'] ?? 'None';

$_SESSION['active'] = $activeQuizId;
$_SESSION['activeQuiz'] = $activeQuiz;
$_SESSION['QuizType'] = $quizType;
?>