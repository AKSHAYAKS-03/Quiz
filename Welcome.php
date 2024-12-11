<?php
include 'core_db.php';
session_start();
date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['login']) || empty($_SESSION['login'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['active'] === 'None') {
    echo '<script> alert("Logging Out");</script>';
    $stmt = $conn->prepare("DELETE FROM student WHERE RollNo = ? AND QuizId = ?");
    $stmt->bind_param("si", $rollno, $activeQuizId);
    if ($stmt->execute()) {
        header("Location: login.php");
        exit;
    } else {
        echo "Error deleting record: " . $stmt->error;
    }
    $stmt->close();
}
$activeQuizId = $_SESSION['active'];
$rollno = $_SESSION['RollNo'];
$name = $_SESSION['Name'];

$_SESSION['quiz_name'] = "";
$_SESSION['Marks'] = "";
$_SESSION['duration'] = "";
$_SESSION['question_duration'] = "";
$_SESSION['question_marks'] = "";
$_SESSION['numberofquestions'] = "";
$_SESSION['shuffle'] = 0;
$_SESSION['currentIndex'] = 0; 
$_SESSION['startingtime'] = "";
$_SESSION['endingtime'] = "";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$quiz_query = "SELECT QuizName, TimeDuration, NumberOfQuestions, QuizType, active_NoOfQuestions, QuestionMark, QuestionDuration, IsShuffle, startingtime, EndTime
               FROM quiz_details 
               WHERE Quiz_ID = ?";
$stmt = $conn->prepare($quiz_query);
$stmt->bind_param("i", $activeQuizId);
$stmt->execute();
$quiz_result = $stmt->get_result();
$stmt->close();

if ($quiz_result->num_rows > 0) {
    $row = $quiz_result->fetch_assoc();
    $_SESSION['quiz_name'] = $row["QuizName"];
    $_SESSION["duration"] = $row["TimeDuration"];
    $_SESSION['QuizType'] = $row['QuizType'];
    $_SESSION['numberofquestions'] = $row["NumberOfQuestions"];
    $_SESSION['active_NoOfQuestions'] = $row["active_NoOfQuestions"]===0? $row["NumberOfQuestions"]: $row["active_NoOfQuestions"];
    $_SESSION['question_duration'] = $row["QuestionDuration"];
    $_SESSION['question_marks'] = $row["QuestionMark"];
    $_SESSION['shuffle'] = $row["IsShuffle"];
    $_SESSION['startingtime'] = $row["startingtime"];
    $_SESSION['endingtime'] = $row["EndTime"];
}
$_SESSION['Marks'] = $_SESSION['active_NoOfQuestions'] * $_SESSION['question_marks'];

$isshuffle = $_SESSION['shuffle'];

$query_questions = $conn->prepare("SELECT QuestionNo FROM multiple_choices WHERE QuizId = ?");
$query_questions->bind_param("i", $activeQuizId);
$query_questions->execute();
$result_questions = $query_questions->get_result();
$questions = [];
while ($row = $result_questions->fetch_assoc()) {
    $questions[] = $row['QuestionNo'];
}
$query_questions->close();

if ($isshuffle == 1) {
    shuffle($questions);
}

$_SESSION['shuffled_questions'] = $questions;

$_SESSION["start_time"] = date('i:s');
$start_time = $_SESSION["start_time"];

list($minutes, $seconds) = explode(':', $_SESSION['question_duration']);
$question_duration_seconds =((int)$minutes * 60) + (int)$seconds;
$total_duration_seconds = $_SESSION['active_NoOfQuestions'] * $question_duration_seconds;

// Format the total duration into minutes and seconds
$total_minutes = floor($total_duration_seconds / 60);
$total_seconds = $total_duration_seconds % 60;
$total_duration = sprintf('%02d:%02d', $total_minutes, $total_seconds);

$_SESSION["duration"] = $total_duration;

list($start_minutes, $start_seconds) = explode(':', $start_time);
$start_time_seconds = ((int)$start_minutes * 60) + $start_seconds;

list($total_minutes, $total_seconds) = explode(':', $total_duration);
$total_duration_seconds = ((int)$total_minutes * 60) + $total_seconds;

$end_time_seconds = $start_time_seconds + $total_duration_seconds;

$end_minutes = floor($end_time_seconds / 60);
$end_seconds = $end_time_seconds % 60;
$end_time = sprintf('%02d:%02d', $end_minutes, $end_seconds);
$_SESSION["end_time"] = $end_time;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['start'])) {
        $_SESSION['score'] = 0;
        header('Location: question.php');
        exit;
    }

    if (isset($_POST['Logout'])) {
        $stmt = $conn->prepare("DELETE FROM student WHERE RollNo = ? AND QuizId = ?");
        $stmt->bind_param("si", $rollno, $activeQuizId);
        if ($stmt->execute()) {
            header("Location: login.php");
            exit;
        } else {
            echo "Error deleting record: " . $stmt->error;
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz - Welcome</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #13274F;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-image: url("img3.jpg");
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-position: center;
            background-size: cover;
        }

        .header {
            margin-top: 20px;
            padding: 10px;
            text-align: center;
            font-size: 36px;
            font-weight: 600;
            text-transform: uppercase;
        }

                
                ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        li {
            display: flex;
            justify-content: flex-start; 
            /* margin-bottom: 10px; */
            padding: 10px;
            gap: 100px; 
        }

        strong {
            margin-right: auto; 
        }
        @keyframes fadeIn {
    0% {
        opacity: 0;
        transform: translateY(-20px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.container {
    color: #13274F;
    width: 600px;
    margin: 20px auto;
    background-color: white;
    padding: 30px;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: left;
    
    animation: fadeIn 0.5s ease-out forwards;
    transition: background-color 0.3s ease; 
}

.container:hover {
    background-color: #f4f4f4;
}

        .btn {
            display: inline-block;
            padding: 12px 25px;
            margin-top: 20px;
            background-color: #13274F;
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background-color: #fff;
            color: #13274F;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(2px);
        }

        .start {
            border: none;
            background-color: #13274F;
        }

        .Logout {
            border: none;
            background-color: #13274F;
        }
        /* span{
            margin-right : 30px;
        } */
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var startButton = document.getElementById('start');
            var startingTime = new Date("<?php echo $_SESSION['startingtime']; ?>").getTime();
            var endingTime = new Date("<?php echo $_SESSION['endingtime']; ?>").getTime();

            function checkTime() {
                var currentTime = new Date().getTime();

                if (currentTime >= startingTime) {
                    startButton.disabled = false;
                } else {
                    startButton.disabled = true;
                }

                if (currentTime >= endingTime) {
                    alert("QUIZ OVER");
                    window.location.href = 'login.php';
                }
            }

            checkTime();
            setInterval(checkTime, 1000);
        });
    </script>
</head>
<body>

<div class="header">
    <?php echo htmlspecialchars($_SESSION['quiz_name']); ?>
</div>

<div class="container">
    <h2>Welcome, <?php echo htmlspecialchars($name); ?>!</h2>
    <ul>
        <li><strong>Number of Questions</strong><span><?php echo htmlspecialchars($_SESSION["active_NoOfQuestions"]); ?></span></li>
        <li><strong>Type</strong><span> <?php echo $_SESSION["QuizType"]===0? "Multiple Choices": 'Fill Up'?></span></li>
        <li><strong>Total Marks</strong><span><?php echo htmlspecialchars($_SESSION["Marks"]); ?> Marks</span></li>
        <li><strong>Time</strong><span><?php echo htmlspecialchars($_SESSION["duration"]); ?></span></li>
        <li><strong>Time per Question</strong><span><?php echo htmlspecialchars($_SESSION["question_duration"]); ?></span></li>
        <li><strong>Marks per Question</strong><span><?php echo htmlspecialchars($_SESSION["question_marks"]); ?></span></li>
        <li><strong>Your Quiz will start at</strong><span><?php echo date('H:i A', strtotime($_SESSION['startingtime'])); ?></span></li>
    </ul>
    <form method="post" action="welcome.php">
        <div class="btn-container">
            <input type="submit" name="start" value="Start Quiz" id="start" class="btn start" disabled>
            <input type="submit" name="Logout" value="Logout" class="btn Logout">
        </div>
    </form>
</div>

</body>
</html>