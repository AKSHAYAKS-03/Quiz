<?php
include_once '../core/header.php';
if (!isset($_SESSION['logged']) || $_SESSION['logged'] === '') {
    header('Location: ../index.php');
    exit;
}

$quizId = $_SESSION['active'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $questionNo = $data['question_no'];

    $conn->begin_transaction();

    try {
        // Delete the question
        $stmt = $conn->prepare("DELETE FROM multiple_choices WHERE QuestionNo = ? AND QuizId = ?");
        $stmt->bind_param("ii", $questionNo, $quizId);
        $stmt->execute();
        $stmt->close();

        // Update the total number of questions in quiz_details
        $stmt2 = $conn->prepare("UPDATE quiz_details SET NumberOfQuestions = NumberOfQuestions - 1 WHERE Quiz_Id = ?");
        $stmt2->bind_param("i", $quizId);
        $stmt2->execute();
        $stmt2->close();

        // Shift the remaining question numbers down
        $stmt3 = $conn->prepare("UPDATE multiple_choices SET QuestionNo = QuestionNo - 1 WHERE QuestionNo > ? AND QuizId = ?");
        $stmt3->bind_param("ii", $questionNo, $quizId);
        $stmt3->execute();
        $stmt3->close();

        // Ensure Active_NoOfQuestions does not exceed the actual NumberOfQuestions
        $stmtUpdateActive = $conn->prepare("UPDATE quiz_details SET Active_NoOfQuestions = LEAST(Active_NoOfQuestions, NumberOfQuestions) WHERE Quiz_Id = ?");
        $stmtUpdateActive->bind_param("i", $quizId);
        $stmtUpdateActive->execute();
        $stmtUpdateActive->close();

        $conn->commit();

        $stmt4 = $conn->prepare("SELECT * FROM multiple_choices WHERE QuizId = ? ORDER BY QuestionNo");
        $stmt4->bind_param("i", $quizId);
        $stmt4->execute();
        $result = $stmt4->get_result();
        $questions = array();
        while ($row = $result->fetch_assoc()) {
            $questions[] = $row;
        }
        $stmt4->close();

        http_response_code(200);
        echo json_encode(array(
            "message" => "Question deleted successfully and question numbers updated.",
            "questions" => $questions
        ));
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(array("message" => "Failed to update question numbers."));
    }
}
?>