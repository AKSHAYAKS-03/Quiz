<?php
include_once 'core_db.php';

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['logged']) || $_SESSION['logged'] === '') {
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $startTime = $data['startTime'] ?? null;
    $endTime = $data['endTime'] ?? null;
    $quizId = $data['quizId'] ?? null;

    if ($quizId && $startTime && $endTime) {
        $stmt = $conn->prepare("UPDATE quiz_details SET startingtime = ?, EndTime = ? WHERE Quiz_Id = ?");
        $stmt->bind_param('ssi', $startTime, $endTime, $quizId);
        if ($stmt->execute()) {
            $newActiveQuizId = $quizId;

            $conn->query("UPDATE quiz_details SET IsActive = 0");

            $conn->query("UPDATE quiz_details SET IsActive = 1 WHERE Quiz_Id = $newActiveQuizId");

            $activeQuizRes = $conn->query("SELECT QuizName FROM quiz_details WHERE Quiz_Id = $newActiveQuizId");
            $activeQuiz = $activeQuizRes->fetch_assoc()['QuizName'];

            $activeQuizId = $newActiveQuizId;
            $_SESSION['active'] = $activeQuizId;
            $_SESSION['activeQuiz'] = $activeQuiz;
            echo json_encode(['message' => 'Quiz time updated successfully.']);
        } else {
            echo json_encode(['message' => 'Failed to update quiz time.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['message' => 'Invalid input.']);
    }
    exit;
}

if (!isset($_GET['quizId'])) {
    echo json_encode(['message' => 'Quiz ID not provided']);
    exit;
} else {
    $quizId = $_GET['quizId'];
    $query = "SELECT QuizName, startingTime, EndTime, TimeDuration FROM quiz_details WHERE Quiz_Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $quizId);
    $stmt->execute();
    $stmt->bind_result($QuizName, $startTime, $endTime, $timeDuration);
    $stmt->fetch();
    $stmt->close();

    $response = [
        'quizHeader' => 'Modify '.$QuizName.' Quiz Time',
        'startTime' => $startTime,
        'endTime' => $endTime,
        'quizDuration' => $timeDuration
    ];

    echo json_encode($response);
    exit;
}
?>