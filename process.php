<?php
session_start();
include 'core_db.php';

// Enable error reporting for debugging (remove or disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set the content type to JSON
header('Content-Type: application/json');

// Start output buffering to capture any unintended output
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

            $timeTaken = time() - $_POST['question_start_time'];

            $minutes = floor($timeTaken / 60);
            $seconds = $timeTaken % 60;
            $formattedTimeTaken = sprintf('%02d:%02d', $minutes, $seconds);

            $_SESSION['time_taken'] = $formattedTimeTaken;

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

            $answer_insert_query = "INSERT INTO stud (QuizId, regno,question, questionno, time, yanswer) VALUES (?,?,?,?,?,?)";
            $stmt = $conn->prepare($answer_insert_query);
            $stmt->bind_param("ississ",$quizid, $rollno,$questionName, $questionNo, $formattedTimeTaken,$selected_choice);
            $stmt->execute();
            $stmt->close();
        

            $_SESSION['currentIndex']++;

            if ($currentIndex < $total - 1) {
                $nextIndex = $currentIndex + 1;
                $nextQuestionNo = $_SESSION['shuffled_questions'][$nextIndex];

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

                $response = [
                    'status' => 'next_question',
                    'data' => [
                        'question' => $result['Question'],
                        'options' => $options,
                        'questionNo' => $nextQuestionNo,
                        'currentIndex' => $nextIndex
                    ]
                ];
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

// Get the buffer contents and clean the buffer
$output = ob_get_clean();

// If there is any unintended output, include it in the response for debugging
if (!empty($output)) {
    $response['output'] = $output;
}

// Output the JSON response
echo json_encode($response);
?>