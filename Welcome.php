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

$activeQuizId = $_SESSION['active'];
$rollno = $_SESSION['RollNo'];
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
        }

        .header {
            margin-top: 20px;
            padding: 15px 0;
            text-align: center;
            font-size: 40px;
            font-weight: bold;
        }

        .container {
            color: #13274F;
            width: 500px;
            height: 300px;
            margin: 20px auto;
            background-color: white;
            padding: 50px;
            box-shadow: 1px 1px 10px black;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        h2 {
            margin-bottom: 20px;
        }

        ul {
            list-style-type: none;
            padding: 0;
            margin-left: 60px;
        }

        li {
            margin-bottom: 20px;
        }

        .new {
            margin-top: 20px;
            margin-left: 130px;
        }

        strong {
            margin-right: 40px;
        }

        .new input[type="submit"] {
            background-color: #13274F;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            margin: 5px;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .new input[type="submit"]:hover {
            background-color: #fff;
            color: #13274F;
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

<div class="header">
    <?php echo htmlspecialchars($_SESSION['quiz_name']); ?>
</div>

<div class="container">
    <font size='4'>
        <ul>
            <li><strong style="margin-right: 50px;">Number of Questions </strong> <?php echo htmlspecialchars($_SESSION["numberofquestions"]); ?></li>
            <li><strong style="margin-right: 160px;">Type </strong> Multiple Choice</li>
            <li><strong style="margin-right: 120px;">Total Marks </strong> <?php echo htmlspecialchars($_SESSION["Marks"]); ?> Marks</li>
            <li><strong style="margin-right: 175px;">Time </strong> <?php echo htmlspecialchars($_SESSION["duration"]); ?></li>
            <li><strong style="margin-right: 60px;">Time per Question </strong> <?php echo htmlspecialchars($_SESSION["question_duration"]); ?></li>
            <li><strong style="margin-right: 60px;">Marks per Question </strong> <?php echo htmlspecialchars($_SESSION["question_marks"]); ?></li>
            <!-- <li><strong style="margin-right: 60px;">Your Quiz will start at:</strong> <?php echo date('Y-m-d H:i:s', strtotime($_SESSION['startingtime'])); ?></li> -->
            <li><strong style="margin-right: 40px;">Your Quiz will start at </strong> <?php echo date('H:i  A', strtotime($_SESSION['startingtime'])); ?></li>


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



<!-- document.addEventListener('DOMContentLoaded', function () {
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
                    window.location.href = 'final.php';
                }
            }

            // Initial check
            checkTime();

            // Check every second
            setInterval(checkTime, 1000);
        });

        
    </script> -->