<?php
include 'core/db.php';
date_default_timezone_set('Asia/Kolkata');

//Redirect to login page if not logged in
if (!isset($_SESSION['login']) || empty($_SESSION['login'])) {
    header('location: login.php');
    exit;
}

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

$quizId = $_SESSION['active'];
if($_SESSION['QuizType']===0){
    $table = 'multiple_choices';
}else {
    $table = 'fillup';
} 

// Fetch total number of questions for the quiz
$query_total = "SELECT * FROM $table WHERE QuizId = '$quizId'";
$result_total = $conn->query($query_total);
$total = $result_total->num_rows;

// Initialize variables for time calculation
$rollno = $_SESSION['RollNo'];
$total_time_seconds = 0;


for ($i = 1; $i <= $total; $i++) {
    $tim_query = "SELECT time FROM stud WHERE regno='$rollno' AND questionno ='$i' and QuizId = '$quizId'";
    $tim_result = $conn->query($tim_query);

    if ($tim_result->num_rows > 0) {
        $row = $tim_result->fetch_assoc();
        $time = $row['time'];
        
        list($hours, $minutes, $seconds) = explode(':', $time);
        $time_in_seconds = ($hours * 3600) + ($minutes * 60) + $seconds;
    
        $total_time_seconds += $time_in_seconds;
    }
}

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
    <script src='inspect.js'></script>
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
            overflow: hidden; 
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
            z-index: 2;
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
            z-index: 1; 
        }

        .props {
            position: absolute;
            top: -50px; 
            left: calc(100vw * var(--random-x)); 
            width: calc(30px + var(--size-delta)); 
            max-height: 150px; 
            animation: fall var(--animation-speed) linear infinite; 
        }

        @keyframes fall {
            0% {
                transform: translateY(-50px) scale(0); 
            }
            100% {
                transform: translateY(calc(100vh + 50px)) scale(1);
            }
        }

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
        <img class="props" src="imgs/sparkler.png" alt="Falling Prop"> 
        <img class="props"  src="imgs/firework.png" alt="Falling Prop"> 
        <img class="props"  src="imgs/fireworks.png" alt="Falling Prop"> 
        <img class="props"  src="imgs/confetti.png" alt="Falling Prop"> 
        <img class="props"  src="imgs/confetti.gif" alt="Falling Prop"> 
        <img class="props"  src="imgs/fireworks2.gif" alt="Falling Prop"> 
        <img class="props"  src="imgs/balloons.gif" alt="Falling Prop"> 
        <img class="props"  src="imgs/fireworks (1).png" alt="Falling Prop"> 
        <img class="props"  src="imgs/firecracker.png" alt="Falling Prop"> 
        <img class="props"  src="imgs/craker.png" alt="Falling Prop"> 
        <img class="props"  src="imgs/cookie.png" alt="Falling Prop"> 
        <img class="props"  src="imgs/garlands.png" alt="Falling Prop"> 
        <img class="props" src="imgs/sparkler.png" alt="Falling Prop"> 
        <img class="props"  src="imgs/firework.png" alt="Falling Prop"> 
        <img class="props"  src="imgs/fireworks.png" alt="Falling Prop"> 
        <img class="props"  src="imgs/confetti.png" alt="Falling Prop"> 
        <img class="props"  src="imgs/confetti.gif" alt="Falling Prop"> 
        <img class="props"  src="imgs/fireworks2.gif" alt="Falling Prop"> 
        <img class="props"  src="imgs/balloons.gif" alt="Falling Prop"> 
        <img class="props"  src="imgs/fireworks (1).png" alt="Falling Prop"> 
        <img class="props"  src="imgs/firecracker.png" alt="Falling Prop"> 
        <img class="props"  src="imgs/craker.png" alt="Falling Prop"> 
        <img class="props"  src="imgs/cookie.png" alt="Falling Prop"> 
        <img class="props"  src="imgs/garlands.png" alt="Falling Prop"> 

    </div>

    <script>
        var timer = 5; 
        document.getElementById('timer').textContent = timer;

        // Function to start countdown timer
        function startTimer() {
            timer--;
            if (timer <= 0) {
                <?php $_SESSION['logi'] = true; ?>
                if (<?php echo $flag ? 'true' : 'false'; ?>) {
                    window.location.href = "scoresheet.php";
                // } else {
                //     window.location.href = "scoresheet.php?exitscore=1";
                }
            } else {
                document.getElementById('timer').textContent = timer;
                setTimeout(startTimer, 1000);
            }
        }

        startTimer();

        function getRandom(min, max) {
            return Math.random() * (max - min) + min;
        }

        // Set random positions and animation durations for each prop
        document.addEventListener('DOMContentLoaded', function () {
            const props = document.querySelectorAll('.props');
            props.forEach(function (prop) {
                // Randomize position and animation speed
                const randomX = getRandom(0, 1);
                const randomSpeed = getRandom(1, 3);
                const randomSize = getRandom(-20, 20);
                
                prop.style.top = `${getRandom(-200, -50)}px`; // Random top position
                prop.style.left = `calc(100vw * ${randomX})`; // Random horizontal position
                prop.style.setProperty('--animation-speed', `${randomSpeed}s`); // Set animation speed
                prop.style.setProperty('--size-delta', `${randomSize}px`); // Random size variation
            });
            
        });
    </script>
</body>
</html>