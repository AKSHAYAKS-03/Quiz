<?php
include_once 'core_db.php';
session_start();

if (!$_SESSION['logged'] || $_SESSION['logged'] === '') {
    header('Location: index.php');
    exit;
}

$quizId = $_SESSION['active'];

if (isset($_POST['questionId'])) {
    $questionId = $_POST['questionId'];

    $conn->begin_transaction();

    try {
        $deleteAnswersQuery = "DELETE FROM answer_fillup WHERE Q_Id = ? AND QuizId = ?";
        $stmtDeleteAnswers = $conn->prepare($deleteAnswersQuery);
        $stmtDeleteAnswers->bind_param("ii", $questionId, $quizId);
        $stmtDeleteAnswers->execute();

        $deleteQuestionQuery = "DELETE FROM fillup WHERE QuestionNo = ? AND QuizId = ?";
        $stmtDeleteQuestion = $conn->prepare($deleteQuestionQuery);
        $stmtDeleteQuestion->bind_param("ii", $questionId, $quizId);
        $stmtDeleteQuestion->execute();

        if ($stmtDeleteQuestion->affected_rows > 0) {
            $stmt2 = $conn->prepare("UPDATE quiz_details SET NumberOfQuestions = NumberOfQuestions - 1 WHERE Quiz_Id = ?");
            $stmt2->bind_param("i", $quizId);
            $stmt2->execute();
            $stmt2->close();
            
            $stmtUpdateActive = $conn->prepare("UPDATE quiz_details SET Active_NoOfQuestions = Least(Active_NoOfQuestions, NumberOfQuestions) WHERE Quiz_id = ?");
            $stmtUpdateActive->bind_param("i", $quizId);
            $stmtUpdateActive->execute();
            $stmtUpdateActive->close();
        }

        $conn->commit();

        echo "Question and associated answers deleted successfully.";
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        echo "Error deleting question and answers: " . $e->getMessage();
    }
}
?>
