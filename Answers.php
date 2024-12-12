<?php
// Ensure session is started and redirect if not logged in
include 'core/db.php';
// session_start();

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

// Fetch the Quiz Name
$query_QuizName = $conn->prepare("SELECT QuizName,QuizType FROM quiz_details WHERE Quiz_Id = ?");
$query_QuizName->bind_param("i", $_SESSION['active']);
$query_QuizName->execute();
$result_QuizName = $query_QuizName->get_result();
$quizDetails = $result_QuizName->fetch_assoc();
$QuizName = $quizDetails['QuizName'];
$QuizType = $quizDetails['QuizType'];
$query_QuizName->close();

// Fetch Multiple Choice Questions and Answers
$mcq = $conn->prepare("SELECT QuestionNo, Question, Answer, Choice1, Choice2, Choice3, Choice4 FROM multiple_choices WHERE QuizId = ?");
$mcq->bind_param("i", $_SESSION['active']);
$mcq->execute();
$mcq_result = $mcq->get_result();
$questions_mcq = $mcq_result->fetch_all(MYSQLI_ASSOC);
$total_mcq = $mcq_result->num_rows;
$mcq->close();

// Fetch Fill-up Questions and Answers
$query_fillup = $conn->prepare("
    SELECT f.QuestionNo, f.Question, a.answer 
    FROM fillup f
    LEFT JOIN answer_fillup a ON f.QuestionNo = a.Q_Id
    WHERE f.QuizId = ?
");
$query_fillup->bind_param("i", $_SESSION['active']);
$query_fillup->execute();
$fillup_result = $query_fillup->get_result();
$questions_fillup = [];

while ($row = $fillup_result->fetch_assoc()) {
    $questions_fillup[$row['QuestionNo']]['Question'] = $row['Question'];
    $questions_fillup[$row['QuestionNo']]['Answers'][] = $row['answer'];
}

$total_fillup = count($questions_fillup);
$query_fillup->close();

// echo $total_fillup." ".$total_mcq;

// Fetch User Score and Time
$userquery = $conn->prepare("SELECT Score, Time FROM student WHERE RollNo = ? AND QuizId = ?");
$userquery->bind_param("si", $_SESSION['RollNo'], $_SESSION['active']);
$userquery->execute();
$userquery_result = $userquery->get_result();

if ($userquery_result->num_rows > 0) {
    $row = $userquery_result->fetch_assoc();
    $score = $row['Score'];
    $time = $row['Time'];
}

list($hours, $minutes, $seconds) = explode(':', $time);

// Format time for display
if ($hours == 0 && $minutes == 0) {
    $display_time = intval($seconds) . ' sec';
} elseif ($hours == 0) {
    $display_time = intval($minutes) . ' min ' . intval($seconds) . ' sec';
} else {
    $display_time = intval($hours) . ' hr ' . intval($minutes) . ' min';
}

// Fetch User's Answers (for both MCQ and Fill-up)
$user_answers_query = $conn->prepare("SELECT questionno, yanswer FROM stud WHERE regno = ? AND quizid = ?");
$user_answers_query->bind_param("si", $_SESSION['RollNo'], $_SESSION['active']);
$user_answers_query->execute();
$user_answers_result = $user_answers_query->get_result();

$user_answers = [];
while ($row = $user_answers_result->fetch_assoc()) {
    $user_answers[$row['questionno']] = $row['yanswer'];
}

$user_answers_query->close();
$conn->close();

?>s

<!DOCTYPE html>
<html>
<head>
    <title>Answers</title>
    <link rel="stylesheet" type="text/css" href="css/answers.css">
</head>
<body oncontextmenu="return false;">
<div class="head">
    <h1><?php echo htmlspecialchars($QuizName) . " - " . $rollno; ?></h1>
</div>

<div class="score">
    <div class="container">
    <div class="cot">
    <div class="score-container">
    <?php
    if ($QuizType == 0) {
        $percentage = ($score / $total_mcq) * 100;
    } else if ($QuizType == 1) {
        $percentage = ($score / $total_fillup) * 100;
    }
    ?>
    <div class="score-ring" id="scoreRing" style="--percentage: 0%;"></div>
    <div class="inner-circle">
        <div class="score-final" id="scoreText">0</div>
    </div>
</div>



            <div class="parentdiv">
                <ul>
                    <li><strong style="margin-right: 127px;">Name</strong> <?php echo htmlspecialchars($_SESSION['Name']); ?></li>
                    <li><strong style="margin-right: 70px;">Register No</strong> <?php echo htmlspecialchars($_SESSION['RollNo']); ?></li>
                    <li><strong style="margin-right: 70px;">Department</strong> <?php echo htmlspecialchars($_SESSION['dept']); ?></li>
                    <li><strong style="margin-right: 75px;">Your Score</strong> <?php echo htmlspecialchars($score) ?>/<?php echo ($total_mcq + $total_fillup); ?></li>
                    <li><strong style="margin-right: 70px;">Time Taken</strong> <?php echo htmlspecialchars($display_time); ?></li>
                </ul>
            </div>
        </div>

        <center><h2><strong>ANSWERS</strong></h2></center>
        <br>
        <div class="answers-container">
    <?php $index = 1; ?>

    <!-- Display MCQ Questions if QuizType is 0 -->
    <?php if ($QuizType == 0): ?>
        <?php foreach ($questions_mcq as $question): ?>
            <h2 class="ques"><?php echo $index ?>. <?php echo htmlspecialchars($question['Question']); ?></h2>
            <ul>
                <?php foreach (['Choice1', 'Choice2', 'Choice3', 'Choice4'] as $option): ?>
                    <li>
                        <?php
                        $choice = htmlspecialchars($question[$option]);
                        $user_answer = $user_answers[$question['QuestionNo']] ?? '';
                        $correct_answer = htmlspecialchars($question['Answer']);

                        // Check if the choice is the user's answer and/or the correct answer
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
    <?php endif; ?>
    
    <!-- Display Fill-up Questions with Answers -->
    

    <?php foreach ($questions_fillup as $questionNo => $question): ?>
        <div class="answer-container">
            <h2 class="ques"><?php echo $index ?>. <?php echo htmlspecialchars($question['Question']); ?></h2>

            <!-- Get User's Answer for Fill-up Question -->
            <?php 
                $user_answer = isset($user_answers[$questionNo]) ? $user_answers[$questionNo] : 'No answer';
                $correct_answers = $question['Answers']; // Possible answers
            ?>
            <p><strong style="margin-left:20px;">Your Answer:</strong>
            <?php if ($user_answer === 'No answer'): ?>
                <span class="no-answer">No answer</span>
            <?php else: ?>
                <!-- If the answer is correct -->
                <span class="<?php echo in_array($user_answer, $correct_answers) ? 'correct-answer' : 'incorrect-answer'; ?>">
                    <?php echo htmlspecialchars($user_answer); ?>
                </span>
            <?php endif; ?>
        </p>
            <ul>
                <div class="possible-answers" style="display: flex; flex-direction: row;"><strong>Possible Answers:</strong>
                <?php foreach ($correct_answers as $answer): ?>
                    <li class="answer"><?php echo htmlspecialchars($answer); ?></li>
                <?php endforeach; ?>
                </div>
            </ul>
            <br>
            </div>
            <div class="divider"></div>
            <?php $index++; ?>
        <?php endforeach; ?>
</div>
<br>
<form method="post" action="login.php">
    <input type="submit" name="Logout" class="Logout" value="Logout">
</form>

<script>
    // Smoothly animate the score percentage
    const targetPercentage = <?php echo $percentage; ?>;
    const scoreRing = document.getElementById('scoreRing');
    const scoreText = document.getElementById('scoreText');
    //get score $score
    const score = <?php echo $score; ?>
    
    let currentPercentage = 0;

    const interval = setInterval(() => {
        if (currentPercentage >= targetPercentage) {
            clearInterval(interval);
        } else {
            currentPercentage++;
            scoreRing.style.setProperty('--percentage', `${currentPercentage}%`);
            scoreText.textContent = `${currentPercentage}%`;
            scoreText.innerHTML = `${score}`;
        }
    }, 30); 
</script>


</body>
</html>
