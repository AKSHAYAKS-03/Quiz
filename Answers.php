<?php
include 'core_db.php';

// Ensure session is started and redirect if not logged in
session_start();
if (!isset($_SESSION['login']) || empty($_SESSION['login']) ||
    !isset($_SESSION['logi']) || empty($_SESSION['logi']) ||
    !isset($_SESSION['RollNo']) || empty($_SESSION['RollNo']) ||
    !isset($_SESSION['Name']) || empty($_SESSION['Name'])) {
    header('Location: login.php');
    exit;
}

$rollno = $_SESSION['RollNo'];

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query_QuizName = $conn->prepare("SELECT QuizName FROM quiz_details WHERE Quiz_Id = ?");
$query_QuizName->bind_param("i", $_SESSION['active']);
$query_QuizName->execute();
$result_QuizName = $query_QuizName->get_result();
$QuizName = $result_QuizName->fetch_assoc();

// Fetch all questions for the quiz with QuestionNo
$query_total = $conn->prepare("SELECT * FROM multiple_choices WHERE QuizId = ?");
$query_total->bind_param("i", $_SESSION['active']);
$query_total->execute();
$result_total = $query_total->get_result();
$total = $result_total->num_rows;

// Fetch all questions with answers for comparison
$query_answers = $conn->prepare("SELECT QuestionNo, Question, Answer, Choice1, Choice2, Choice3, Choice4 FROM multiple_choices WHERE QuizId = ?");
$query_answers->bind_param("i", $_SESSION['active']);
$query_answers->execute();
$result_answers = $query_answers->get_result();
$questions = $result_answers->fetch_all(MYSQLI_ASSOC);

$userquery = $conn->prepare("SELECT * FROM student WHERE RollNo = ? AND QuizId = ?");
$userquery->bind_param("si", $_SESSION['RollNo'], $_SESSION['active']);
$userquery->execute();

$userquery_result = $userquery->get_result();

if ($userquery_result->num_rows > 0) {
    $row = $userquery_result->fetch_assoc();
    $score = $row['Score'];
    $time = $row['Time'];
}

list($hours, $minutes, $seconds) = explode(':', $time);

// Determine the appropriate time format to display
if ($hours == 0 && $minutes == 0) {
    $display_time = intval($seconds) . ' sec';
} elseif ($hours == 0) {
    $display_time = intval($minutes) . ' min ' . intval($seconds) . ' sec';
} else {
    $display_time = intval($hours) . ' hr ' . intval($minutes) . ' min';
}

// Fetch user's answers for the quiz
$user_answers_query = $conn->prepare("SELECT questionno, yanswer FROM stud WHERE regno = ? AND quizid = ?");
$user_answers_query->bind_param("si", $_SESSION['RollNo'], $_SESSION['active']);
$user_answers_query->execute();
$user_answers_result = $user_answers_query->get_result();

$user_answers = [];
while ($row = $user_answers_result->fetch_assoc()) {
    $user_answers[$row['questionno']] = $row['yanswer'];
}

// Handle logout
if (isset($_POST['Logout'])) {
    session_unset(); 
    session_destroy(); 
    header('Location: login.php');
    exit;
}

// Close MySQLi connection
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Score Sheet</title>
    <style>
        body {
            background-color: #13274F;
            font-family: "Poppins", sans-serif;
            color: white;
            margin: 0;
            padding: 0;
            background-image: url("img3.jpg");
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-position: center;
            font-family: 'Poppins', sans-serif;
            background-size: cover;
        }
        .head {
            text-align: center;
            margin-top: 30px;
            text-transform: uppercase;

        }
        .parentdiv li {
            margin-left: 40px;
            font-size: 20px;
            margin-bottom: 20px;
            text-decoration: none;
        }
        .parentdiv ul {
            margin-left: 50px;
            list-style: none;
            padding: 20px;
        }
        .score {
            margin-top: 30px;
            display: flex;
            flex-direction: column;
            margin-bottom: 30px;
        }
        .container {
            border-radius: 8px;
            height: auto;
            width: 900px;
            margin: 0 auto;
            display: flex;
            padding: 30px;
            flex-direction: column;
            margin-top: 20px;
            background-color: #f0f2f5;
            color: #13274F;
        }
        .correct-answer {
            color: darkgreen; 
            font-size: 22px;
            font-weight: bold;
        }

        .incorrect-answer {
            color: red;
            font-size: 22px;
            font-weight: bold;
        }

        .answer {
            color: #86af49; 
        }
        

        .Logout{
            background-color: #13274F;
            margin-top: 0px;
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
        }

        .Logout:hover {
            background-color: #fff;
            color: #13274F;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
            transform: scale(1.05);
        }

        .Logout:active {
            transform: scale(0.98);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
        }

      
        .cot {
            width: auto;
            background-color: white;
            padding: 40px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            margin: 50px auto;
            color: #333;
            margin-top: 20px;
            margin-left: 100px;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
        } 

        .score-container {
            width: 200px;
            height: 200px;
            position: relative;
            background: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            margin: 0 auto;
        }


                .circle {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: conic-gradient(#32CD32 var(--percentage), #fff 0);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: --percentage 2s ease-out;
            clip-path: circle(50%);
        }

        .circle .score-final {
            font-size: 30px;
            font-weight: bold;
            color: black;
            z-index: 1;
        }

        @media (max-width: 768px) {
            .score-container {
                width: 160px;
                height: 160px;
            }
            .circle {
                width: 150px;
                height: 150px;
            }
        }

        
        .parentdiv {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .answers-container {
            width: 800px;
            padding: 50px;
            background-color: #fff;
            color: black;
            border-radius: 8px;
            font-size: 20px;
        }
        .ques {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .answers-container ul {
            padding-left: 20px;
            font-size: 20px;
            margin-bottom: 20px;
        }
        .answers-container li {
            list-style-type: lower-alpha;
            margin-left: 50px;
            font-size: 20px;
            margin-bottom: 20px;
        }
        .response-labels {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 20px;
}

.response {
    padding: 12px 20px;
    font-weight: 500;
    font-size: 14px;
    border-radius: 8px;
    width: 200px;
    text-align: center;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.success {
    background-color: darkgreen;  
    border-color: darkgreen;      
    color: white;
}

.warning {
    background-color: #86af49;  
    border-color: #86af49;      
    color: white;
}

.error {
    background-color: red;  
    border-color: red;      
    color: white;
}

.response:hover {
    transform: translateY(-5px);  
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2); 
}

    </style>
</head>
<body oncontextmenu="return false;">
<div class="head">
    <h1><?php echo $QuizName['QuizName']." - ".$rollno;?></h1>
</div>
<div class="score">
    <div class="container">
  
    <div class="cot">
    <div class="score-container">
        <div class="circle" style="--percentage: <?php echo ($score / $total) * 100; ?>%;">
            <div class="score-final"><?php echo $score ?> / <?php echo $total ?></div>
        </div>
        
    </div>
   

    <div class="parentdiv">
            <ul>
                <li><strong style="margin-right: 127px;">Name  </strong> <?php echo htmlspecialchars($_SESSION['Name']); ?></li>
                <li><strong style="margin-right: 70px;">Register No  </strong> <?php echo htmlspecialchars($_SESSION['RollNo']); ?></li>
                <li><strong style="margin-right: 70px;">Department </strong> <?php echo htmlspecialchars($_SESSION['dept']); ?></li>
                <li><strong style="margin-right: 75px;">Your Score  </strong> <?php echo htmlspecialchars($score) ?>/<?php echo $total; ?></li>
                <li><strong style="margin-right: 70px;">Time Taken </strong><?php echo htmlspecialchars($time) ?></li>
            </ul>
     </div>   
     
    </div>
    <center><h2><strong>ANSWERS</strong><h2></center>

    <div class="response-labels">
    <div class="response success">Selected And Correct</div>
    <div class="response warning">Correct Answer</div>
    <div class="response error">Selected But Wrong</div>
</div>

<br>
    <div class="answers-container">
    <?php $index = 1; ?>
    <?php foreach ($questions as $question): ?>
    <h2 class="ques"><?php echo $index ?> . <?php echo htmlspecialchars($question['Question']); ?></h2>
    <ul>
        <?php foreach (['Choice1', 'Choice2', 'Choice3', 'Choice4'] as $option): ?>
            <li>
                <?php
                $choice = htmlspecialchars($question[$option]);
                $user_answer = isset($user_answers[$question['QuestionNo']]) ? htmlspecialchars($user_answers[$question['QuestionNo']]) : '';
                $correct_answer = htmlspecialchars($question['Answer']);

                if ($user_answer === $choice && $user_answer === $correct_answer) {
                    echo "<span class='correct-answer'>{$choice}</span>";
                } elseif ($user_answer === $choice && $user_answer !== $correct_answer) {
                    echo "<span class='incorrect-answer'>{$choice}</span>";
                } elseif ($correct_answer === $choice) {
                    echo "<span class='answer'>{$choice}</span>";
                } else {
                    echo $choice;
                }
                ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <br>
    <?php $index++; ?>
    <?php endforeach; ?>

    </div>        
    <br>
        <form method='post' action="Answers.php">
            <input type="submit" name="Logout" class="Logout" value="Logout">
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const score = <?php echo $score; ?>;
    const total = <?php echo $total; ?>;
    const circle = document.querySelector('.circle');
    const scoreFinal = document.querySelector('.score-final');
    const duration = 2000; 
    const fps = 60; 

    // Calculate step size based on animation duration and total frames
    const step = (score / total) * 100 / (duration / 1000 * fps);
    let currentPercentage = 0;

    scoreFinal.textContent = `0 / ${total}`;

    const animateScore = setInterval(() => {
        currentPercentage += step;
        if (currentPercentage >= (score / total) * 100) {
            currentPercentage = (score / total) * 100;
            clearInterval(animateScore);
        }
        circle.style.setProperty('--percentage', `${currentPercentage}%`);
        scoreFinal.textContent = `${Math.round(currentPercentage * total / 100)} / ${total}`;
    }, 1000 / fps); 
});

</script>
</body>
</html>
