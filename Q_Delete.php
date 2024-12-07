<?php
include_once 'core_db.php';
session_start();

if(!$_SESSION['logged'] || $_SESSION['logged']===''){
    header('Location: login.php');
    exit;
}

$quizId = $_SESSION['active'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $questionNo = $_POST['question_no'];

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("DELETE FROM multiple_choices WHERE QuestionNo=? && QuizId = ?");
        $stmt->bind_param("ii", $questionNo, $quizId);
        $stmt->execute();
        $stmt->close();

        $stmt2 = $conn->prepare("UPDATE quiz_details SET NumberOfQuestions = NumberOfQuestions - 1 WHERE Quiz_Id = ?");
        $stmt2->bind_param("i", $quizId);
        $stmt2->execute();
        $stmt2->close();

        $stmt3 = $conn->prepare("UPDATE multiple_choices SET QuestionNo = QuestionNo - 1 WHERE QuestionNo > ? AND QuizId = ?");
        $stmt3->bind_param("ii", $questionNo, $quizId);
        $stmt3->execute();
        $stmt3->close();

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