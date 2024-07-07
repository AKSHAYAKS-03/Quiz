<?php
// session_start();
include 'core/db.php';

// Redirect to login if session variables are not set
if (!isset($_SESSION['RollNo']) || empty($_SESSION['RollNo'])) {
    header('Location: login_eg.php');
    exit;
}

$rollno = $_SESSION['RollNo'];
$quizid = $_SESSION['active'];
$currentIndex = isset($_SESSION['currentIndex']) ? $_SESSION['currentIndex'] : 0;
$question_duration = $_SESSION['question_duration'];
$questions = $_SESSION['shuffled_questions']; // Get shuffled questions

// Redirect to final page if all questions are answered
if ($currentIndex >= count($questions)) {
    header('Location: final.php');
    exit;
}

$currentQuestionNo = $questions[$currentIndex];

// Fetch the specific question from the database
$query = $conn->prepare("SELECT * FROM multiple_choices WHERE QuizId = ? AND QuestionNo = ?");
$query->bind_param("ii", $quizid, $currentQuestionNo);
$query->execute();
$result = $query->get_result()->fetch_assoc();

// Fetch options for the current question
$options = [
    $result['Choice1'],
    $result['Choice2'],
    $result['Choice3'],
    $result['Choice4']
];

// Shuffle options if required
if ($_SESSION['shuffle'] == 1) {
    shuffle($options);
}

$_SESSION['question_start_time'] = time();
echo $currentIndex. ' ' . count($questions);

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quizze</title>
    <style>
        body {
            background-color: #13274F;
            font-family: "Poppins", sans-serif;
            color: white;
            margin: 0;
            padding: 0;
        }
        .head {
            text-align: center;
            margin-top: 20px;
        }
        h1 {
            font-size: 36px;
        }
        .cot {
            width: 700px;
            background-color: white;
            padding: 50px;
            border-radius: 8px;
            margin: 50px auto;
            color: #333;
            box-shadow: 1px 1px 10px black;
            position: relative;
        }
        h2.ques {
            color: #13274F;
            margin-bottom: 20px;
        }
        .cot ul {
            list-style: none;
            padding: 0;
        }
        .cot ul li {
            color: #13274F;
            margin-bottom: 15px;
        }
        .cot input[type='radio'] {
            cursor: pointer;
        }
        .cot input[type="submit"] {
            background-color: #13274F;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
            font-size: 16px;
            position: relative;

        }
        .cot input[type="submit"]:hover {
            background: #fff;
            color: #13274F;
        }
        #response {
            font-family: monospace;
            width: 100px;
            text-align: right;
            font-weight: bold;
            font-size: 24px;
            padding-right: 20px;
            margin-top: -20px;
            position: absolute;  
            right: 0;
        }
        @keyframes pop {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
            }
        }
        .pop {
            animation: pop 0.5s ease-in-out;
        }
    </style>
</head>
<body oncontextmenu="return false;">
<div class="head">
    <div class="container">
        <h1><?php echo htmlspecialchars($_SESSION['quiz_name']); ?></h1>
    </div>
</div>
<div class="cot">
    <div id="response"></div>
    <form id="quizForm" method="POST" action="process.php">
        <h2 class="ques"><?php echo htmlspecialchars($result['Question']); ?></h2>
        <ul>
            <?php foreach ($options as $option): ?>
                <li>
                    <input type="radio" name="choice" value="<?php echo htmlspecialchars($option); ?>" required>
                    <?php echo htmlspecialchars($option); ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <input type="hidden" name="questionNo" id="questionNo" value="<?php echo $currentQuestionNo; ?>">
        <input type="hidden" name="total" id="total" value="<?php echo count($questions); ?>">
        <input type="hidden" name="timeout" id="timeout" value="0">
        <input type="hidden" name="currentIndex" id="currentIndex" value="<?php echo $currentIndex; ?>">
        <input type="submit" name="submit" value="Submit Answer" id="submit">
    </form>
</div>
<script>
    var durationStr = "<?php echo $question_duration; ?>"; // duration in "MM:SS" format
    var durationParts = durationStr.split(":");
    var minutes = parseInt(durationParts[0], 10);
    var seconds = parseInt(durationParts[1], 10);
    var duration = (minutes * 60) + seconds; // convert total duration to seconds
    var display = document.getElementById("response");


    function startTimer(duration, display) {
        var timer = duration, minutes, seconds;
        var halfway = Math.floor(duration / 2);
        var interval = setInterval(function () {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            display.textContent = minutes + ":" + seconds;

            if (timer <= halfway) {
                display.style.color = 'red';

            } else {
                display.style.color = 'green';
            }

            if (--timer < 0) {
                clearInterval(interval);                        
                // document.getElementById('timeout').value = "1"; // Set timeout flag
                console.log('Timeout reached, submitting form.');
                document.getElementById('submit').click();
                document.getElementById('quizForm').submit();
            }
                
                
                //     if (<?php echo $currentIndex; ?> == <?php echo count($questions) - 1; ?>) {

            //     document.getElementById('quizForm').submit();
            //     window.location.href = 'final.php';
            // } else {
                // move to next question and submit the last one
                // document.getElementById('questionNo').value = <?php echo $currentQuestionNo; ?>;
                // document.getElementById('total').value = <?php echo count($questions); ?>;
                // document.getElementById('timeout').value = 1;
                // document.getElementById('currentIndex').value = <?php echo $currentIndex; ?>;        
                // document.getElementById('quizForm').submit();                       
            // window.location.href = 'nextquestion.php';                                                                         
            
        }, 1000);
    }

    document.addEventListener('DOMContentLoaded', function () {
        startTimer(duration, display);
    });
</script>

</body>
</html>