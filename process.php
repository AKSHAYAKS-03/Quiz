<?php
session_start();
include 'core_db.php';
date_default_timezone_set('Asia/Kolkata');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

ob_start();

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit'])) {
        try {
            $rollno = $_SESSION['RollNo'];
            $quizid = $_SESSION['active'];
            $currentIndex = $_POST['currentIndex'];
            $questionNo = $_POST['questionNo'];
            $total = $_POST['total'];
            $selected_choice = isset($_POST['choice']) ? $_POST['choice'] : null;
            $questionName = $_POST['questionName'];
            $timeTakenSeconds = time() - $_POST['question_start_time'];
        
            
            $timeTaken = gmdate("H:i:s", $timeTakenSeconds);

            if($_SESSION['QuizType']===0){
                $answer_query = "SELECT Answer FROM multiple_choices WHERE QuizId = ? AND QuestionNo = ?";
                $stmt = $conn->prepare($answer_query);
                $stmt->bind_param("ii", $quizid, $questionNo);
                $stmt->execute();
                $stmt->bind_result($correct_answer);
                $stmt->fetch();
                $stmt->close();
                if ($selected_choice == $correct_answer) {
                    if (!isset($_SESSION['score'])) {
                        $_SESSION['score'] = 0;
                    }
                    $_SESSION['score'] += $_SESSION['question_marks'];
                }
            }
            else{
                $answer_query = "SELECT answer FROM answer_fillUp WHERE QuizId = ? AND Q_ID = ?";
                $stmt = $conn->prepare($answer_query);
                $stmt->bind_param("ii", $quizid, $questionNo);
                $stmt->execute();
                $result = $stmt->get_result();

                $question_Type = "SELECT Ques_Type FROM fillup WHERE QuizId = ? AND QuestionNo = ?";
                $stmt = $conn->prepare($question_Type);
                $stmt->bind_param("ii", $quizid, $questionNo);
                $stmt->execute();
                $result2 = $stmt->get_result();
                $Q_type = $result2->fetch_assoc();

                $is_correct = false;
                if($Q_type['Ques_Type'] == 0){
                    while ($row = $result->fetch_assoc()) {
                        if (strcasecmp(trim($selected_choice), trim($row['answer'])) === 0) { 
                            $is_correct = true;
                            break;
                        }
                    }
                }
                else{
                    $bound1 = null;
                    $bound2 = null;
                    while ($row = $result->fetch_assoc()) {
                        if ($bound1 == null) { 
                            $bound1 = floatval(trim($row['answer']));
                        } 
                        else{
                            $bound2 = floatval(trim($row['answer']));
                        }
                    }
                    if ($bound1 !== null && $bound2!== null && $selected_choice !== '') {
                        $selected_choice = floatval(trim($selected_choice)); 
                
                        if(($selected_choice >= $bound1 && $selected_choice <= $bound2) || ($selected_choice <= $bound1 && $selected_choice >= $bound2) ){
                            $is_correct = true;
                        }
                    }
                }
                $stmt->close();

                if ($is_correct) {
                    if (!isset($_SESSION['score'])) {
                        $_SESSION['score'] = 0;
                    }
                    $_SESSION['score'] += $_SESSION['question_marks'];
                }
            }

            $answer_insert_query = "INSERT INTO stud (QuizId, regno, questionno, time, yanswer) VALUES (?,?,?,?,?)";
            $stmt = $conn->prepare($answer_insert_query);
            $stmt->bind_param("isiss",$quizid, $rollno, $questionNo, $timeTaken,$selected_choice);
            $stmt->execute();
            $stmt->close();
        

            $_SESSION['currentIndex']++;

            if ($currentIndex < $total - 1) {
                $nextIndex = $currentIndex + 1;
                $nextQuestionNo = $_SESSION['shuffled_questions'][$nextIndex];

                if($_SESSION['QuizType']===0){
                    $query = $conn->prepare("SELECT * FROM multiple_choices WHERE QuizId = ? AND QuestionNo = ?");
                    $query->bind_param("ii", $quizid, $nextQuestionNo);
                    $query->execute();
                    $result = $query->get_result()->fetch_assoc();

                    $options = [
                        $result['Choice1'],
                        $result['Choice2'],
                        $result['Choice3'],
                        $result['Choice4']
                    ];

                    if ($_SESSION['shuffle'] == 1) {
                        shuffle($options);
                    }
                }
                else{
                    $query = $conn->prepare("SELECT * FROM FillUp WHERE QuizId = ? AND QuestionNo = ?");
                    $query->bind_param("ii", $quizid, $nextQuestionNo);
                    $query->execute();
                    $result = $query->get_result()->fetch_assoc();
                }
                $response = [
                    'status' => 'next_question',
                    'data' => [
                        'question' => $result['Question'],
                        'questionNo' => $nextQuestionNo,
                        'currentIndex' => $nextIndex,
                        'question_start_time' => time()
                    ]
                ];
                if ($_SESSION['QuizType'] === 0) {
                    $response['data']['options'] = $options;
                }

            } else {
                $response = ['status' => 'final'];
            }
        } catch (Exception $e) {
            $response = ['status' => 'error', 'message' => $e->getMessage()];
        }
    } else {
        $response = ['status' => 'error', 'message' => 'Invalid form submission'];
    }
} else {
    $response = ['status' => 'error', 'message' => 'Invalid request method'];
}

$output = ob_get_clean();

if (!empty($output)) {
    $response['output'] = $output;
}
echo json_encode($response);
?>