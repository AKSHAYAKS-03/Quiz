<?php
include 'core/db.php';
// session_start(); // Start session at the beginning

// Redirect to login page if not logged in or session variables are not set
if (!isset($_SESSION['login']) || empty($_SESSION['login']) ||
    !isset($_SESSION['logi']) || empty($_SESSION['logi']) ||
    !isset($_SESSION['RollNo']) || empty($_SESSION['RollNo']) ||
    !isset($_SESSION['Name']) || empty($_SESSION['Name'])
    ) {
  
    header('Location: login.php');
    exit;
}

$rollno = $_SESSION['RollNo'];


// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all questions for the quiz
$query_total = $conn->query("SELECT * FROM multiple_choices WHERE QuizId = '" . $_SESSION['active'] . "'");
$total = $query_total->num_rows;

$query_answers = $conn->prepare("SELECT Question, Answer, Choice1, Choice2, Choice3, Choice4 FROM multiple_choices WHERE QuizId = ?");
$query_answers->bind_param("i", $_SESSION['active']);
$query_answers->execute();
$questions = $query_answers->get_result()->fetch_all(MYSQLI_ASSOC);

$userquery = $conn->prepare("SELECT * FROM student WHERE RollNo = ? AND QuizId = ?");
$userquery->bind_param("si", $_SESSION['RollNo'], $_SESSION['active']);
$userquery->execute();

$userquery_result = $userquery->get_result();

if ($userquery_result->num_rows > 0) {
    // Fetching the first row from the result set
    $row = $userquery_result->fetch_assoc();
    $score = $row['Score'];
    $time = $row['Time'];
}
// echo $time . " ". $total_time;

list($minutes, $seconds) = explode(":", $time);

if($minutes == 0) {
$unit = "sec";
}
else {
$unit = "min";
}


// Fetch user's answers for the quiz
$user_answers_query = $conn->prepare("SELECT question, yanswer FROM stud WHERE regno = ? AND quizid = ?");
$user_answers_query->bind_param("si", $_SESSION['RollNo'], $_SESSION['active']);
$user_answers_query->execute();
$user_answers_result = $user_answers_query->get_result();

$user_answers = [];
while ($row = $user_answers_result->fetch_assoc()) {
    $user_answers[$row['question']] = $row['yanswer'];
    // echo "User's Answer: " . $user_answers[$row['question']]  . "<br>";
 
}

// // Print user answers
// foreach ($questions as $question) {
//     $user_answer = isset($user_answers[$question['Question']]) ? htmlspecialchars($user_answers[$question['Question']]) : '';
//     echo "Question: " . htmlspecialchars($question['Question']) . "<br>";
//     echo "User's Answer: " . $user_answers[$question['Question']]. "<br>";
//     echo "<hr>";
// }



// Handle logout
if (isset($_POST['Logout'])) {
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
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

        }
        .head {
            text-align: center;
            margin-top: 20px;
        }
        
        
        li{
            font-size: 20px;
            margin-bottom: 20px;
            text-decoration: none;
        }
        ul{
            margin-left:50px;
            list-style: none;
            padding: 20px;
        }
        .score{
            margin-top: 50px;

            display: flex;
            flex-direction: column;
        }
        .container{  
            position: relative;
            margin-left: 550px;          
            border-radius:8px;
            height: auto;
            width:700px;
            display: flex;   
            padding: 30px;      
            flex-direction: column;
            margin-top: 20px;
            background-color: #fff;
            color: #13274F;
        }
        .correct-answer {
            color: green;
        }
        .incorrect-answer {
            color: red;
        }
        .Logout {
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

        .Logout:hover {
            background-color: #fff;
            color: #13274F;
        }
        .score-final{
            margin-left:500px;
            font-size: 30px;
            color: #13274F;
        }
        .cot {
            width: 400px;
            background-color: white;
            padding: 40px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            margin: 50px auto;
            color: #333;
            box-shadow: 1px 1px 10px black;
            position: fixed;
            margin-top: 20px;
            margin-left: 30px;

        }
    </style>
</head>
<body oncontextmenu="return false;">
<div class="head">
<h2><?php echo htmlspecialchars($_SESSION['Name']); ?></h2>
</div>
<div class="score"> 
<div class="cot">
        <font size='05'>
            <ul>
                <li><strong style="margin-right:127px;">Name  </strong> <?php echo htmlspecialchars($_SESSION['Name']); ?></li>
                <li><strong style="margin-right:70px;">Register No  </strong> <?php echo htmlspecialchars($_SESSION['RollNo']); ?></li>
                <li><strong style="margin-right:70px;">Department </strong> <?php echo htmlspecialchars($_SESSION['dept']); ?></li>
                <li><strong style="margin-right:75px;">Your Score  </strong> <?php echo htmlspecialchars($score) ?>/<?php echo $total; ?></li>
                <li><strong style="margin-right:70px;">Time Taken </strong><?php echo htmlspecialchars($time) ?> <?php echo $unit; ?></li>
            </ul>
        </font>        
</div> 
    <div class="container">
        <div class="score-final"><?php echo $score ?> / <?php echo $total ?></div>
    <?php foreach ($questions as $question): ?>
    <h2 class="ques"><?php echo htmlspecialchars($question['Question']); ?></h2>
    <ol>
        <?php foreach (['Choice1', 'Choice2', 'Choice3', 'Choice4'] as $option): ?>
            <li>
                <?php
                $choice = htmlspecialchars($question[$option]);
                $user_answer = isset($user_answers[$question['Question']]) ? htmlspecialchars($user_answers[$question['Question']]) : '';
                $correct_answer = htmlspecialchars($question['Answer']);

                // Determine if the current option is the correct answer or user's selected answer
                if ($user_answer === $choice && $user_answer === $correct_answer) {
                    echo "<span class='correct-answer'>{$choice}</span>";
                } elseif ($user_answer === $choice && $user_answer !== $correct_answer) {
                    echo "<span class='incorrect-answer'>{$choice}</span>";
                } elseif ($correct_answer === $choice) {
                    echo "<span class='correct-answer'>{$choice}</span>";
                } else {
                    echo $choice;
                }
                ?>
            </li>
        <?php endforeach; ?>
    </ol>
<?php endforeach; ?>
<form method='post' action=Answers.php>
<input type="submit" name="Logout" class="Logout" value="Logout">
</form>
</div>
</body>
</html>