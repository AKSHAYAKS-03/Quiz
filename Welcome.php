<?php
include 'core_db.php';
 session_start();
 date_default_timezone_set('Asia/Kolkata');


// Redirect to login page if not logged in
if (!isset($_SESSION['login']) || empty($_SESSION['login'])) {
    header('Location: login.php');
    exit;
}
// echo $activeQuizId;

if($_SESSION['active']==='None'){
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

// echo $activeQuizId.'<br>';

// Initialize session variables
$_SESSION['quiz_name'] = "";
$_SESSION['Marks'] = "";
$_SESSION['duration'] = "";
$_SESSION['question_duration'] = "";
$_SESSION['question_marks'] = "";
$_SESSION['numberofquestions'] = "";
$_SESSION['shuffle'] = 0;
$_SESSION['currentIndex'] = 0; // Initialize current question index
$_SESSION['startingtime'] = "";
$_SESSION['endingtime'] = "";

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$quiz_query = "SELECT QuizName, TotalMarks, TimeDuration, NumberOfQuestions, QuestionMark, QuestionDuration, IsShuffle, startingtime,EndTime
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
    $_SESSION['Marks'] = $row["TotalMarks"];
    $_SESSION["duration"] = $row["TimeDuration"];
    $_SESSION['numberofquestions'] = $row["NumberOfQuestions"];
    $_SESSION['question_duration'] = $row["QuestionDuration"];
    $_SESSION['question_marks'] = $row["QuestionMark"];
    $_SESSION['shuffle'] = $row["IsShuffle"];
    $_SESSION['startingtime'] = $row["startingtime"];
    $_SESSION['endingtime'] = $row["EndTime"];
}

$isshuffle = $_SESSION['shuffle'];

// Fetch all questions
$query_questions = $conn->prepare("SELECT QuestionNo FROM multiple_choices WHERE QuizId = ?");
$query_questions->bind_param("i", $activeQuizId);
$query_questions->execute();
$result_questions = $query_questions->get_result();
$questions = [];
while ($row = $result_questions->fetch_assoc()) {
    $questions[] = $row['QuestionNo'];
}
$query_questions->close();

// Shuffle questions if required
if ($isshuffle == 1) {
    shuffle($questions);
}

// Store the shuffled questions in the session
$_SESSION['shuffled_questions'] = $questions;

$_SESSION["start_time"] = date('i:s'); // Store the current time as start time
$start_time = $_SESSION["start_time"];

$total_duration = $_SESSION['duration'];

// Extract minutes and seconds from start time
list($start_minutes, $start_seconds) = explode(':', $start_time);
$start_time_seconds = ($start_minutes * 60) + $start_seconds;

// Extract minutes and seconds from total duration
list($total_minutes, $total_seconds) = explode(':', $total_duration);
$total_duration_seconds = ($total_minutes * 60) + $total_seconds;

// Calculate end time in seconds
$end_time_seconds = $start_time_seconds + $total_duration_seconds;

// Convert end time back to minutes and seconds format
$end_minutes = floor($end_time_seconds / 60);
$end_seconds = $end_time_seconds % 60;
$end_time = sprintf('%02d:%02d', $end_minutes, $end_seconds);
$_SESSION["end_time"]  = $end_time;


// echo $start_time . ' ' . $end_time .'<br>';
// echo $_SESSION["start_time"] . ' ' .$_SESSION["end_time"];
// echo ' <br>'.$_SESSION["endingtime"];

//echo $start_time<$end_time? 1:0;


// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['start'])) {
        // Initialize quiz variables
        $_SESSION['score'] = 0;
        
        // Redirect to the first question
        header('Location: question.php');
        exit;
    }

    if (isset($_POST['Logout'])) {
        // Perform logout action
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
<html>
<head>
    <title>Quizze</title>
    <style>
        body {
            background-color: #13274F;
            color: #fff;
            font-family: "Poppins", sans-serif;
            margin: 0;
            padding: 0;
            background-image: url("img3.jpg");
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-position: center;
            font-family: 'Poppins', sans-serif;
            background-size: cover;
        }

        .header {
            margin-top: 10px;
            padding: 15px 0;
            font-size: 40px;
            font-weight: bold;
        }

        .container {
            color: #13274F;
            width: 900px;
            height: auto; /* Dynamic height */
            margin: 20px auto;
            background-color: white;
            padding: 50px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: left;
        }

        h2 {
            margin-bottom: 20px;
        }

        ul {
            list-style: none;
            padding: 0;
            width: 100%;
        }

        li {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        strong {
            margin-right: 100px;
        }

        .new {
            margin-top: 20px;
            text-align: center;
        }

        .new input[type="submit"] {
            background-color: #fff;
            color: #13274F;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            margin: 5px;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .new input[type="submit"]:hover {
            background-color: #13274F;
            color: #fff;
        }        
      
    </style>
    <script>
     document.addEventListener('DOMContentLoaded', function () {
            var startButton = document.getElementById('start');
            var startingTime = new Date("<?php echo $_SESSION['startingtime']; ?>").getTime();
            var endingTime = new Date("<?php echo $_SESSION['endingtime']; ?>").getTime();

            function checkTime() {
                var currentTime = new Date().getTime();

                // Enable the start button when the current time reaches the starting time
                if (currentTime >= startingTime) {
                    startButton.disabled = false;
                } else {
                    startButton.disabled = true;
                }

                // Redirect to final.php when the current time reaches the ending time
                if (currentTime >= endingTime) {
                    alert("QUIZ OVER");
                    window.location.href = 'login.php';
                }
            }

            // Initial check
            checkTime();

            // Check every second
            setInterval(checkTime, 1000);
        });

        
    </script>
    
    
    <script type="text/javascript" src="inspect.js"></script>

</head>
<body>

<center><div class="header">
    <?php echo htmlspecialchars($_SESSION['quiz_name']); ?>
</div></center>

<div class="container">
    <h2 style="margin-bottom: 50px;margin-top:-20px"><?php echo $name; ?></h2>
         <font size='4'>    
        <ul>
            <li>
                <strong>Number of Questions</strong>
                <span><?php echo htmlspecialchars($_SESSION["numberofquestions"]); ?></span>
            </li>
            <li>
                <strong>Type</strong>
                <span>Multiple Choice</span>
            </li>
            <li>
                <strong>Total Marks</strong>
                <span><?php echo htmlspecialchars($_SESSION["Marks"]); ?> Marks</span>
            </li>
            <li>
                <strong>Time</strong>
                <span><?php echo htmlspecialchars($_SESSION["duration"]); ?></span>
            </li>
            <li>
                <strong>Time per Question</strong>
                <span><?php echo htmlspecialchars($_SESSION["question_duration"]); ?></span>
            </li>
            <li>
                <strong>Marks per Question</strong>
                <span><?php echo htmlspecialchars($_SESSION["question_marks"]); ?></span>
            </li>
            <li>
                <strong>Your Quiz will start at</strong>
                <span><?php echo date('H:i A', strtotime($_SESSION['startingtime'])); ?></span>
            </li>
        </ul>      

    </font>
    <form method="post" action="welcome.php">
        <div class="new">
            <input type="submit" name="start" value="Start Quiz" id="start"  disabled>
            <input type="submit" name="Logout" value="Logout">
        </div>
    </form>
</div>

</body>
</html>

