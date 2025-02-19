<?php
include 'core_db.php';
include 'header.php';
session_start();
date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['login']) || empty($_SESSION['login'])) {
    header('Location: index.php');
    exit;
}

$RegNo = $_SESSION['RegNo'];
$name = $_SESSION['Name'];
$activeQuizId = $_SESSION['active'];
$timeFormatted= $_SESSION['timeFormatted'];

if ($_SESSION['active'] === 'None') {
    echo '<script> alert("Logging Out");</script>';
    $stmt = $conn->prepare("DELETE FROM student WHERE RegNo = ? AND QuizId = ?");
    $stmt->bind_param("si", $RegNo, $activeQuizId);
    if ($stmt->execute()) {
        header("Location: index.php");
        exit;
    } else {
        echo "Error deleting record: " . $stmt->error;
    }
    $stmt->close();
}

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

$quiz_query = "SELECT QuizName, TimeDuration, NumberOfQuestions, QuizType, Active_NoOfQuestions, QuestionMark, QuestionDuration,TimerType,IsShuffle, startingtime, EndTime
               FROM quiz_details WHERE Quiz_ID = ?";
$stmt = $conn->prepare($quiz_query);
$stmt->bind_param("i", $activeQuizId);
$stmt->execute();
$quiz_result = $stmt->get_result();
$stmt->close();

if ($quiz_result->num_rows > 0) {
    $row = $quiz_result->fetch_assoc();
    $_SESSION['quiz_name'] = $row["QuizName"];
    $_SESSION['duration'] = $row["TimeDuration"];
    $_SESSION['QuizType'] = $row['QuizType'];
    $_SESSION['numberofquestions'] = $row["NumberOfQuestions"];
    $_SESSION['Active_NoOfQuestions'] = $row["Active_NoOfQuestions"]===0? $row["NumberOfQuestions"]:  min($row["Active_NoOfQuestions"], $row["NumberOfQuestions"]);
    $_SESSION['question_duration'] = $row["QuestionDuration"];
    $_SESSION['question_marks'] = $row["QuestionMark"];
    $_SESSION['shuffle'] = $row["IsShuffle"];
    $_SESSION['TimerType'] = $row["TimerType"];
    $_SESSION['startingtime'] = $row["startingtime"];
    $_SESSION['endingtime'] = $row["EndTime"];

    // echo $_SESSION["duration"]." ".$_SESSION['question_duration']." ".$_SESSION['TimerType'];
}
// echo $_SESSION['Active_NoOfQuestions'];
$_SESSION['Marks'] = $_SESSION['Active_NoOfQuestions'] * $_SESSION['question_marks'];

$isshuffle = $_SESSION['shuffle'];

if($_SESSION['QuizType']===0){
    $query_questions = $conn->prepare("SELECT QuestionNo FROM multiple_choices WHERE QuizId = ?");
    $query_questions->bind_param("i", $activeQuizId);
    $query_questions->execute();
    $result_questions = $query_questions->get_result();
}
else{
    $query_questions = $conn->prepare("SELECT QuestionNo FROM FillUp WHERE QuizId = ?");
    $query_questions->bind_param("i", $activeQuizId);
    $query_questions->execute();
    $result_questions = $query_questions->get_result();
}

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
$total_duration_seconds = $_SESSION['Active_NoOfQuestions'] * $question_duration_seconds;

$total_hours = floor($total_duration_seconds / 3600);
$total_duration_seconds = $total_duration_seconds % 3600;
$total_minutes = floor($total_duration_seconds / 60);
$total_seconds = $total_duration_seconds % 60;
$total_duration = sprintf('%02d:%02d:%02d', $total_hours,$total_minutes, $total_seconds);

// echo $total_duration;
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
        $result1 = $conn->query('SELECT * FROM student WHERE RegNo = ' . $RegNo . ' AND QuizId = ' . $activeQuizId);
        $result2 = $conn->query('SELECT * FROM stud WHERE regno='.$RegNo.' AND QuizId='.$activeQuizId);

        if($result->num_rows > 0 || $result2->num_rows > 0){
            echo "<script>alert('You already attended the quiz!');
            console.log('student & stud');</script>";
            $_SESSION['login'] = FALSE;
            $_SESSION['logi'] = FALSE;
            $_SESSION['log'] = FALSE;
            header('Refresh: 0.5; url=index.php'); 
            exit;
        }
        $_SESSION['score'] = 0;
        header('Location: question.php');
        exit;
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
    <!-- <link rel="stylesheet" type="text/css" href="css/welcome.css"> -->
    <!-- <script src='DisableKeys.jsF'></script>
    <script src='inspect.js'></script> -->
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
    margin-top: 100px;
    background-color: white;
    padding: 10px;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;

    
    animation: fadeIn 0.5s ease-out forwards;
    transition: background-color 0.3s ease; 
}

.container ul {
    /* background-color: black; */
    width: 100%;
    margin-left:70px;
    list-style: none;
    padding: 0;
}

.container li {
    display: flex;
    align-items: center;
    margin-left:70px;

    /* justify-content: space-between; Ensures spacing between strong and span */
    margin-bottom:20px;
}

.container li strong {
    flex: 1;
    text-align: left;
}

.container li span {
    flex: 1;
    text-align: left;
}


.btn {
    display: inline-block;
    padding: 12px 25px;
    margin-top: 0px;
    margin-bottom:5px;
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

.header h2 {
    margin-top: 20px;
    margin-bottom: 20px;
    padding: 10px;
    text-align: center;
    font-size: 36px;
    font-weight: 600;
    text-transform: uppercase;
}
.header{
    position: fixed;
    width: 100%;
    top: 0;
    z-index: 1000;
}
.header a {
    text-decoration: none;
    padding: 2px;
    display: inline-block;
    position: absolute;
    top: 5px;
    right: 10px;
    padding: 8px;
}
.header #back{
    left: 10px;
    color: #fff;
}
.header a img {
    margin-top: 20px;   
    cursor: pointer;
    width: 24px;  
    height: px; 
}
#back{
    right: 99%;
}
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
                    window.location.href = 'index.php';
                }
            }

            checkTime();
            setInterval(checkTime, 1000);
        });
        
    </script>
</head>
<body>

<div class="header">
    <h2><?php echo htmlspecialchars($_SESSION['quiz_name']); ?></h2>
         <a href="Dashboard.php" id="back" title="Back">
            <img src="icons\back_white.svg" alt="back">
        </a>
    </div>

<div class="container">
    <h2>Welcome, <?php echo htmlspecialchars($name); ?>!</h2>
    <ul>
    <li><strong>Number of Questions:</strong> <span><?php echo htmlspecialchars($_SESSION["Active_NoOfQuestions"]); ?></span></li>
    <li><strong>Quiz Type:</strong> <span><?php echo $_SESSION["QuizType"] === 0 ? "Multiple Choice" : "Fill in the Blanks"; ?></span></li>
    <li><strong>Total Marks:</strong> <span><?php echo htmlspecialchars($_SESSION["Marks"]); ?></span></li>
    <li><strong>Duration:</strong> <span><?php echo htmlspecialchars($timeFormatted); ?></span></li>
    <li><strong>Marks per Question:</strong> <span><?php echo htmlspecialchars($_SESSION["question_marks"]); ?></span></li>
    <li><strong>Your quiz will start at:</strong> <span><?php echo date('H:i A', strtotime($_SESSION['startingtime'])); ?></span></li>
</ul>

    <form method="post" action="welcome.php">
        <div class="btn-container">
            <input type="submit" name="start" value="Start Quiz" id="start" class="btn start" disabled>
        </div>
    </form>
</div>

</body>
</html>

