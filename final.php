<?php
include 'core/db.php';
include 'header.php';
date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['login']) || empty($_SESSION['login'])) {
    header('location: index.php');
    exit;
}

$flag = true;

if (isset($_GET['exit']) && $_GET['exit'] == 1) {
    $flag = false;
}

$_SESSION['log'] = "";
$_SESSION['lo'] = "";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$quizId = $_SESSION['active'];
if($_SESSION['QuizType']===0){
    $table = 'multiple_choices';
}else {
    $table = 'fillup';
} 

$query_total = "SELECT * FROM $table WHERE QuizId = '$quizId'";
$result_total = $conn->query($query_total);
$total = $result_total->num_rows;

$RegNo = $_SESSION['RegNo'];
$total_time_seconds = 0;


for ($i = 1; $i <= $total; $i++) {
    $tim_query = "SELECT time FROM stud WHERE regno='$RegNo' AND questionno ='$i' and QuizId = '$quizId'";
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

        $total_time_formatted = sprintf('%02d:%02d:%02d', $total_hours, $total_minutes, $total_seconds);


    $_SESSION['percentage'] = round($_SESSION['score'] / $_SESSION['Active_NoOfQuestions'] * 100,2);

    $sql = "UPDATE student SET Score = ?, percentage = ?, Time = ? WHERE RegNo = ? and QuizId = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdssi", $_SESSION['score'],$_SESSION['percentage'], $total_time_formatted, $RegNo, $quizId);
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
    <link rel="stylesheet" type="text/css" href="css/final.css">
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

        function startTimer() {
            timer--;
            if (timer <= 0) {
                <?php $_SESSION['logi'] = true; ?>
                    window.location.href = "scoresheet.php";

            } else {
                document.getElementById('timer').textContent = timer;
                setTimeout(startTimer, 1000);
            }
        }

        startTimer();

        function getRandom(min, max) {
            return Math.random() * (max - min) + min;
        }
        document.addEventListener('DOMContentLoaded', function () {
            const props = document.querySelectorAll('.props');
            props.forEach(function (prop) {
                const randomX = getRandom(0, 1);
                const randomSpeed = getRandom(1, 3);
                const randomSize = getRandom(-20, 20);
                
                prop.style.top = `${getRandom(-200, -50)}px`; 
                prop.style.left = `calc(100vw * ${randomX})`; 
                prop.style.setProperty('--animation-speed', `${randomSpeed}s`); 
                prop.style.setProperty('--size-delta', `${randomSize}px`); 
            });
            
        });
    </script>
</body>
</html>