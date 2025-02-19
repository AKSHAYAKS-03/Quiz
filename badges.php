<?php

session_start();
include 'core_db.php';
include 'header.php';

// // Check if the user is logged in
// if (!isset($_SESSION['rollno'])) {
//     header('Location: index.php');
//     exit();
// }

$studentId = '913122104012';
// $name = $_SESSION['Name'];
// $activeQuizId = $_SESSION['active'];

// Fetch all quizzes
$sqlQuizzes = "SELECT * FROM quiz_details";
$resultQuizzes = $conn->query($sqlQuizzes);

// Fetch student details (scores and time)
$sqlStudentDetails = "SELECT ID, QuizId, percentage, Time FROM student WHERE RollNo = $studentId";
$resultStudentDetails = $conn->query($sqlStudentDetails);

// Fetch the average score across all students
$sqlAvgScore = "SELECT AVG(percentage) AS avg_score FROM student";
$resultAvgScore = $conn->query($sqlAvgScore);
$rowAvgScore = $resultAvgScore->fetch_assoc();
$averageScore = $rowAvgScore['avg_score'];

// Fetch the average time across all students for each quiz
$sqlAvgTime = "SELECT AVG(Time) AS avg_time FROM student";
$resultAvgTime = $conn->query($sqlAvgTime);
$rowAvgTime = $resultAvgTime->fetch_assoc();
$averageTime = $rowAvgTime['avg_time'];

// Initialize arrays to track student's performance across quizzes
$studentPerformance = [];
$perfectScore = false;
$consistent = true;
$earlyBird = false;
$timeOverrun = false;

while ($student = $resultStudentDetails->fetch_assoc()) {
    $studentPerformance[$student['QuizId']] = [
        'score' => $student['percentage'],
        'time' => $student['Time']
    ];
}

while ($quiz = $resultQuizzes->fetch_assoc()) {
    $quizId = $quiz['Quiz_id'];
    $studentScore = isset($studentPerformance[$quizId]) ? $studentPerformance[$quizId]['score'] : 0;
    $studentTime = isset($studentPerformance[$quizId]) ? $studentPerformance[$quizId]['time'] : 0;

    // Check for Perfect Score Badge
    if ($studentScore == $quiz['TotalMarks']) {
        $perfectScore = true;
    }

    // Check if student has above-average score in this quiz
    if ($studentScore <= $averageScore) {
        $consistent = false;
    }

    // Check for Early Bird Badge
    if ($studentScore > $averageScore && $studentTime < $averageTime) {
        $earlyBird = true;
    }

    // Check for Time Overrun Badge
    if ($studentScore <= $averageScore && $studentTime > $averageTime) {
        $timeOverrun = true;
    }
}

// Assign badges based on overall performance
if ($perfectScore) {
    assignBadge($studentId, null, 'Perfect Score');
}

if ($consistent) {
    assignBadge($studentId, null, 'Consistent Performer');
}

if ($earlyBird) {
    assignBadge($studentId, null, 'Early Bird');
}

if ($timeOverrun) {
    assignBadge($studentId, null, 'Time Overrun');
}

// Function to assign a badge with an image
function assignBadge($studentId, $quizId, $badgeName) {
    global $conn;

    // Define the badge images based on badge name
    $badgeImages = [
        'Perfect Score' => 'images/perfect_score.png',
        'Consistent Performer' => 'images/consistent_performer.png',
        'Early Bird' => 'images/early_bird.png',
        'Time Overrun' => 'images/time_overrun.png'
    ];

    // Check if the badge has an image associated with it
    $badgeImage = isset($badgeImages[$badgeName]) ? $badgeImages[$badgeName] : 'images/default_badge.png';

    // Check if the student already has this badge
    $sqlCheckBadge = "SELECT * FROM badges WHERE RollNo = $studentId AND BadgeName = '$badgeName'";
    $resultCheckBadge = $conn->query($sqlCheckBadge);

    if ($resultCheckBadge->num_rows == 0) {
        // Insert the badge record with the image into the badges table
        $sqlAssignBadge = "INSERT INTO badges (BadgeName, RollNo, BadgeImage) VALUES ('$badgeName', $studentId, '$badgeImage')";
        if ($conn->query($sqlAssignBadge) === TRUE) {
            echo "Badge '$badgeName' assigned to student ID: $studentId.<br>";
        } else {
            echo "Error assigning badge: " . $conn->error . "<br>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Badge Profile</title>
    <style>
        body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f9;
    margin: 0;
    padding: 0;
}

.container {
    width: 80%;
    margin: 0 auto;
    padding-top: 50px;
    text-align: center;
}

h1 {
    font-size: 2em;
    color: #333;
    margin-bottom: 20px;
}

.badge-section {
    display: flex;
    justify-content: space-around;
    flex-wrap: wrap;
}

.badge {
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    width: 150px;
    margin: 10px;
    padding: 20px;
    text-align: center;
    transition: transform 0.3s;
}

.badge:hover {
    transform: scale(1.1);
}

.badge img {
    width: 100px;
    height: 100px;
    object-fit: contain;
}

.badge p {
    font-size: 1.1em;
    color: #333;
    margin-top: 10px;
    font-weight: bold;
}

#perfect-score {
    background-color: #ffeb3b; /* Gold */
}

#consistent-performer {
    background-color: #4caf50; /* Green */
}

#early-bird {
    background-color: #2196f3; /* Blue */
}

#time-overrun {
    background-color: #f44336; /* Red */
}

    </style>
</head>
<body>

<div class="container">
    <h1>Student Badge Profile</h1>
    <div class="badge-section">
        <div class="badge" id="perfect-score">
            <img src="images/perfect_score.png" alt="Perfect Score Badge">
            <p>Perfect Score</p>
        </div>
        <div class="badge" id="consistent-performer">
            <img src="images/consistent_performer.png" alt="Consistent Performer Badge">
            <p>Consistent Performer</p>
        </div>
        <div class="badge" id="early-bird">
            <img src="images/early_bird.png" alt="Early Bird Badge">
            <p>Early Bird</p>
        </div>
        <div class="badge" id="time-overrun">
            <img src="images/time_overrun.png" alt="Time Overrun Badge">
            <p>Time Overrun</p>
        </div>
    </div>
</div>

</body>
</html>
