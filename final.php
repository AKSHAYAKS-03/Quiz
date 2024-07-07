<?php
include 'core/db.php';
//session_start(); // Ensure session is started

//Redirect to login page if not logged in
if (!isset($_SESSION['login']) || empty($_SESSION['login'])) {
    header('location: login_eg.php');
    exit;
}

// Initialize session variables
$_SESSION['log'] = "";
$_SESSION['lo'] = "";

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch total number of questions for the quiz
$quizId = $_SESSION['active'];
$query_total = "SELECT * FROM multiple_choices WHERE QuizId = '$quizId'";
$result_total = $conn->query($query_total);
$total = $result_total->num_rows;

// Initialize variables for time calculation
$rollno = $_SESSION['RollNo'];
$total_time_seconds = 0;


for ($i = 1; $i <= $total; $i++) {
    // Fetch time taken for the current question from the database
    $tim_query = "SELECT time FROM stud WHERE regno='$rollno' AND question='$i'";
    $tim_result = $conn->query($tim_query);

    if ($tim_result->num_rows > 0) {
        $row = $tim_result->fetch_assoc();
        $time = $row['time'];
        
        // Convert "MM:SS" format to seconds
        list($minutes, $seconds) = explode(':', $time);
        $time_in_seconds = ($minutes * 60) + $seconds;

        // Add time taken for this question to total time
        $total_time_seconds += $time_in_seconds;

    }
}

// Convert total time in seconds to minutes and seconds
$total_minutes = floor($total_time_seconds / 60);
$total_seconds = $total_time_seconds % 60;
$total_time_formatted = sprintf('%02d:%02d', $total_minutes, $total_seconds);


$_SESSION['total_time'] = $total_time_formatted;

$sql = "UPDATE student SET Score = ?, Time = ? WHERE RollNo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $_SESSION['score'], $total_time_formatted, $rollno);
$stmt->execute();
$stmt->close();

$conn->close();
?>


<!DOCTYPE html>
<html>
<head>
    <title>Final</title>
    <style>
        body {
            font-family: "poppins", sans-serif;
            /* background-color: #f2f2f2; */
            align-items: center;
            text-align: center;
            padding:100px;
            background-color: #13274F;
            color :white;
        }

        #timer {
            font-family: "monospace";
            margin-top:50px;
            color: white;
            font-size: 100px;
            text-align: center;
            text-shadow: 1px 1px 5px black;
        }
    </style>
</head>
<body oncontextmenu="return false;">
<!-- <div class="header">
    <div class="container">
        <h2>Thank You</h2>
    </div>
</div> -->
<div class="content">        
            <br><br>
            <div><h1>Loading Your Scoresheet<h1></div>

            <div id="timer"> </div>
            <script>
                var timer = 5; // Adjust countdown timer duration as needed
                document.getElementById('timer').innerHTML = timer;
                
                // Function to start countdown timer
                function startTimer() {
                    timer--;
                    if (timer <= 0) {
                        <?php $_SESSION['logi'] = TRUE; ?>
                        window.location.href = "scoresheet.php";
                    } else {
                        document.getElementById('timer').innerHTML = timer;
                        setTimeout(startTimer, 1000);
                    }
                }
                
                // Start countdown timer immediately
                startTimer();
            </script>
            <br>        
</div>
</body>
</html>
