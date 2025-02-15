<?php
include 'core/db.php';
// session_start();

if (!isset($_SESSION['login']) || empty($_SESSION['login']) || 
    !isset($_SESSION['logi']) || empty($_SESSION['logi']) || 
    !isset($_SESSION['RegNo']) || empty($_SESSION['RegNo']) || 
    !isset($_SESSION['Name']) || empty($_SESSION['Name'])) {
    header('Location: index.php');
    exit;
}

$RegNo = $_SESSION['RegNo'];
if (isset($_GET['quiz_id'])) {
    $quiz_id = intval($_GET['quiz_id']); 
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query_QuizName = $conn->prepare("SELECT QuizName,QuizType,Active_NoOfQuestions FROM quiz_details WHERE Quiz_Id = ?");
$query_QuizName->bind_param("i", $quiz_id);
$query_QuizName->execute();
$result_QuizName = $query_QuizName->get_result();
$quizDetails = $result_QuizName->fetch_assoc();
$QuizName = $quizDetails['QuizName'];
$QuizType = $quizDetails['QuizType'];
$activeQuestions = $quizDetails['Active_NoOfQuestions'];
$query_QuizName->close();

$mcq = $conn->prepare("
    SELECT 
        mc.QuestionNo, 
        mc.Question, 
        mc.Answer, 
        mc.Choice1, 
        mc.Choice2, 
        mc.Choice3, 
        mc.Choice4, 
        mc.img_path,
        s.yanswer AS student_answer
    FROM multiple_choices mc
    JOIN stud s ON s.QuizId = mc.QuizId AND s.QuestionNo = mc.QuestionNo
    WHERE s.regno = ? AND mc.QuizId = ?
    ORDER BY mc.QuestionNo ASC
");

$mcq->bind_param("si", $_SESSION['RegNo'], $quiz_id);
$mcq->execute();
$mcq_result = $mcq->get_result();
$questions_mcq = $mcq_result->fetch_all(MYSQLI_ASSOC);
$total_mcq = $mcq_result->num_rows;
$mcq->close();

$query_fillup = $conn->prepare("
SELECT 
    f.QuestionNo AS QuestionNo, 
    f.Question AS Question, 
    af.answer AS answer,
    f.Ques_Type AS questype
FROM 
    stud s
JOIN 
    fillup f 
ON 
    s.QuizId = f.QuizId AND s.questionno = f.QuestionNo
JOIN 
    answer_fillup af 
ON 
    f.QuizId = af.QuizId AND f.QuestionNo = af.Q_Id
WHERE 
    s.regno = ? 
");

$query_fillup->bind_param("s", $_SESSION['RegNo']); // Bind the RegNo parameter
$query_fillup->execute();
$fillup_result = $query_fillup->get_result();

$questions_fillup = [];
while ($row = $fillup_result->fetch_assoc()) {
    $questions_fillup[$row['QuestionNo']]['Question'] = $row['Question'];
    $questions_fillup[$row['QuestionNo']]['Answer'][] = $row['answer'];
    $questions_fillup[$row['QuestionNo']]['questype'] = $row['questype'];
}
$total_fillup = count($questions_fillup); // Get the total number of unique questions
$query_fillup->close();

$userquery = $conn->prepare("SELECT Score, Time FROM student WHERE RegNo = ? AND QuizId = ?");

$userquery->bind_param("si", $_SESSION['RegNo'], $quiz_id);
$userquery->execute();
$userquery_result = $userquery->get_result();

if ($userquery_result->num_rows > 0) {
    $row = $userquery_result->fetch_assoc();
    $score = $row['Score'];
    $time = $row['Time'];
}
// echo $score;

list($hours, $minutes, $seconds) = explode(':', $time);

if ($hours == 0 && $minutes == 0) {
    $display_time = intval($seconds) . ' sec';
} elseif ($hours == 0) {
    $display_time = intval($minutes) . ' min ' . intval($seconds) . ' sec';
} else {
    $display_time = intval($hours) . ' hr ' . intval($minutes) . ' min';
}

$user_answers_query = $conn->prepare("SELECT questionno, yanswer FROM stud WHERE regno = ? AND quizid = ?");
$user_answers_query->bind_param("si", $_SESSION['RegNo'], $quiz_id);
$user_answers_query->execute();
$user_answers_result = $user_answers_query->get_result();

$user_answers = [];
while ($row = $user_answers_result->fetch_assoc()) {
    $user_answers[$row['questionno']] = $row['yanswer'];
}

$user_answers_query->close();
$conn->close();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Answers</title>
    <link rel="stylesheet" type="text/css" href="css/answers.css">
    <link rel="stylesheet" type="text/css" href="css/navigation.css">
</head>
<body oncontextmenu="return false;">
    <div class="header">
        <a href="dashboard.php" id="back" title="Back">
                <img src="icons\back_white.svg" alt="back">
        </a>
    </div>
<div class="head">
    <h1><?php echo htmlspecialchars($QuizName) . " - " . $RegNo; ?></h1>
</div>

<div class="score">
    <div class="container">
    <div class="cot">
    <div class="score-container">
        <?php
        if ($QuizType == 0) {
            $percentage = $total_mcq==0?0:($score / $total_mcq)*100;
        } else if ($QuizType == 1) {
            $percentage = $total_fillup==0?0: ($score / $total_fillup) * 100;
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
                <li><strong style="margin-right: 70px;">Register No</strong> <?php echo htmlspecialchars($_SESSION['RegNo']); ?></li>
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
            <?php echo $index ?>.
            <?php if (!empty($question['Question'])): ?>
                <h2 class="ques" style='display: inline-block;'> <?php echo htmlspecialchars($question['Question']); ?></h2>
            <?php endif; ?>
            
            <?php if (!empty($question['img_path']) && trim($question['img_path']) !== '' && $question['img_path'] !== 'NULL'): ?>

                <center>
                    <img src="<?php echo htmlspecialchars($question['img_path']); ?>" 
                        alt="Question Image" class="question-img"
                        style="min-width:200px; min-height: 200px; max-width: 600px; max-height: 500px;">
                </center>
            <?php endif; ?>
            <ul>
            <?php
            $user_answer = trim(htmlspecialchars($user_answers[$question['QuestionNo']] ?? ''));
            $correct_answer = trim(htmlspecialchars($question['Answer']));
                 foreach (['Choice1', 'Choice2', 'Choice3', 'Choice4'] as $option): ?>
                    <li>
                        <?php
                        $choice = trim(htmlspecialchars($question[$option]));

                        $class = '';
                        if ($user_answer === $choice && $user_answer === $correct_answer) {
                            $class = 'correct-answer';
                        } elseif ($user_answer === $choice) {
                            $class = 'incorrect-answer'; 
                        } elseif ($correct_answer === $choice) {
                            $class = 'answer';
                        }
                    
                        echo "<span class='{$class}'>{$choice}</span>";                    
                        ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <br>
            <?php $index++; ?>
        <?php endforeach; ?>
    <?php endif; ?>
        
    <?php foreach ($questions_fillup as $questionNo => $question): ?>
    <div class="answer-container">
        <h2 class="ques"><?php echo $index; ?>. <?php echo htmlspecialchars($question['Question']); ?></h2>

        <?php 
            $questype = $question['questype'];
            $is_correct = false; // Initialize correctness flag
            $user_answer = isset($user_answers[$questionNo]) ? $user_answers[$questionNo] : 'No answer';
            $correct_answers = $question['Answer']; 

            if ($questype == 1) {    
                // Handle type 1 question logic (bounds check)
                $bound1 = null;
                $bound2 = null;

                // Fetch bounds from the correct answers
                foreach ($correct_answers as $answer) {
                    if ($bound1 === null) { 
                        $bound1 = floatval(trim($answer));
                    } else {
                        $bound2 = floatval(trim($answer));
                    }
                }

                // Validate user's answer
                if ($bound1 !== null && $bound2 !== null && $user_answer !== 'No answer') {
                    $user_answer_numeric = floatval(trim($user_answer));
                    if (($user_answer_numeric >= $bound1 && $user_answer_numeric <= $bound2) || 
                        ($user_answer_numeric <= $bound1 && $user_answer_numeric >= $bound2)) {
                        $is_correct = true;
                    }
                }
            } elseif ($questype == 0) {
                // Handle type 0 question logic (direct match)
                if (in_array($user_answer, $correct_answers)) {
                    $is_correct = true;
                }
            }
        ?>

        <p><strong style="margin-left:20px;">Your Answer:</strong>
            <?php if ($user_answer === 'No answer'): ?>
                <span class="no-answer">No answer</span>
            <?php else: ?>
                <span class="<?php echo $is_correct ? 'correct-answer' : 'incorrect-answer'; ?>">
                    <?php echo htmlspecialchars($user_answer); ?>
                </span>
            <?php endif; ?>
        </p>

        <div class="possible-answers" style="margin-left: 20px; display: flex; flex-direction: row; align-items: center;">
            <strong style="margin-top : -20px">Possible Answers:</strong>
            <ul style="display: flex; list-style-type: none; padding: 0; margin: 0; flex-direction: row; ">
                <?php foreach ($correct_answers as $answer): ?>
                    <li class="answer" style="margin-right: 10px;"><?php echo htmlspecialchars($answer); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <br>
    </div>
    <div class="divider"></div>
    <?php $index++; ?>
<?php endforeach; ?>

       
</div>
<br>

<script>
    const targetPercentage = <?php echo $percentage; ?>;
    const scoreRing = document.getElementById('scoreRing');
    const scoreText = document.getElementById('scoreText');
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