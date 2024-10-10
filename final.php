<?php
include 'core/db.php';
// session_start(); // Ensure session is started
date_default_timezone_set('Asia/Kolkata');

// Redirect to login page if not logged in
// if (!isset($_SESSION['login']) || empty($_SESSION['login'])) {
//     header('location: login.php');
//     exit;
// }

$flag = true;

if (isset($_GET['exit']) && $_GET['exit'] == 1) {
    $flag = false;
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
    $tim_query = "SELECT time FROM stud WHERE regno='$rollno' AND questionno ='$i' and QuizId = '$quizId'";
    $tim_result = $conn->query($tim_query);

    if ($tim_result->num_rows > 0) {
        $row = $tim_result->fetch_assoc();
        $time = $row['time'];
        
        // Convert "HH:MM:SS" format to seconds
        list($hours, $minutes, $seconds) = explode(':', $time);
        $time_in_seconds = ($hours * 3600) + ($minutes * 60) + $seconds;
    
        // Add time taken for this question to total time
        $total_time_seconds += $time_in_seconds;
    }
}

        // Convert total time in seconds to hours, minutes, and seconds
        $total_hours = floor($total_time_seconds / 3600);
        $total_minutes = floor(($total_time_seconds % 3600) / 60);
        $total_seconds = $total_time_seconds % 60;

        // Format the time as HH:MM:SS
        $total_time_formatted = sprintf('%02d:%02d:%02d', $total_hours, $total_minutes, $total_seconds);



$sql = "UPDATE student SET Score = ?, Time = ? WHERE RollNo = ? and QuizId = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $_SESSION['score'], $total_time_formatted, $rollno, $quizId);
$stmt->execute();
$stmt->close();

$_SESSION['Score'] = "";
$_SESSION['total_time'] = $total_time_formatted;



$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final Scoresheet</title>
    <style>
        body {
            font-family: "Poppins", sans-serif;
            background-color: #fff;
            color: #ffffff;
            text-align: center;
            padding: 100px;
            margin: 0;
            background-image: url("img3.jpg");
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-position: center;
            background-size: cover;
            overflow: hidden; /* Hide overflow to prevent scrolling */
        }

        #timer-container {
            position: relative;
            display: flex;
            color : #13274F;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        #timer {
            font-family: "monospace";
            font-size: 100px;
            color : #13274F;
            text-shadow: 1px 1px 5px white;
            z-index: 2; /* Ensure timer is on top */
        }

        .celebration {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1; /* Ensure props are behind the timer */
        }

        .props {
            position: absolute;
            top: -50px; /* Start above the viewport */
            left: calc(100vw * var(--random-x)); /* Spread across the width randomly */
            width: calc(30px + var(--size-delta)); /* Adjust size of props with random delta */
            max-height: 150px; /* Limit maximum height */
            animation: fall var(--animation-speed) linear infinite; /* Variable animation speed */
        }

        /* Define animation keyframes */
        @keyframes fall {
            0% {
                transform: translateY(-50px) scale(0); /* Start position and scale */
            }
            100% {
                transform: translateY(calc(100vh + 50px)) scale(1); /* End position and scale */
            }
        }


        /* Remove background for GIF images */
        .props[src$=".gif"] {
            background-color: transparent;
            border-radius: 10px;
        }
    </style>
</head>
<body oncontextmenu="return false;">
    <div id="timer-container">
        <h1>Loading Your Scoresheet</h1>
        <div id="timer">5</div>
    </div>

    <div class="celebration">
        <img class="props" src="imgs/sparkler.png" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props"  src="imgs/firework.png" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props"  src="imgs/fireworks.png" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props"  src="imgs/confetti.png" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props"  src="imgs/confetti.gif" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props"  src="imgs/fireworks2.gif" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props"  src="imgs/balloons.gif" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props"  src="imgs/fireworks (1).png" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props"  src="imgs/firecracker.png" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props"  src="imgs/craker.png" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props"  src="imgs/cookie.png" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props"  src="imgs/garlands.png" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props" src="imgs/sparkler.png" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props"  src="imgs/firework.png" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props"  src="imgs/fireworks.png" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props"  src="imgs/confetti.png" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props"  src="imgs/confetti.gif" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props"  src="imgs/fireworks2.gif" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props"  src="imgs/balloons.gif" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props"  src="imgs/fireworks (1).png" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props"  src="imgs/firecracker.png" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props"  src="imgs/craker.png" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props"  src="imgs/cookie.png" alt="Falling Prop"> <!-- Replace with your prop image -->
        <img class="props"  src="imgs/garlands.png" alt="Falling Prop"> <!-- Replace with your prop image -->

    </div>

    <script>
        var timer = 5; // Adjust countdown timer duration as needed
        document.getElementById('timer').textContent = timer;

        // Function to start countdown timer
        function startTimer() {
            timer--;
            if (timer <= 0) {
                <?php $_SESSION['logi'] = true; ?>
                if (<?php echo $flag ? 'true' : 'false'; ?>) {
                    window.location.href = "scoresheet.php";
                } else {
                    window.location.href = "scoresheet.php?exitscore=1";
                }
            } else {
                document.getElementById('timer').textContent = timer;
                setTimeout(startTimer, 1000);
            }
        }

        // Start countdown timer immediately
        startTimer();

        // Function to generate random number between min and max (inclusive)
        function getRandom(min, max) {
            return Math.random() * (max - min) + min;
        }

        // Set random positions and animation durations for each prop
        document.addEventListener('DOMContentLoaded', function () {
            const props = document.querySelectorAll('.props');
            props.forEach(function (prop) {
                prop.style.top = `${getRandom(-200, -50)}px`; // Randomize starting top position
                prop.style.left = `calc(100vw * ${getRandom(0, 1)})`; // Randomize starting left position
                prop.style.setProperty('--animation-speed', `${getRandom(1,3)}s`); // Randomize animation duration (falling speed)
                prop.style.setProperty('--size-delta', `${getRandom(-20, 20)}px`); // Randomize size delta
            });
            
        });
    </script>
</body>
</html>
