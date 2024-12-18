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

    // Start a transaction to ensure that both deletions are successful
    $conn->begin_transaction();

    try {
        // Step 1: Delete the answers associated with the question
        $deleteAnswersQuery = "DELETE FROM answer_fillup WHERE Q_Id = ? AND QuizId = ?";
        $stmtDeleteAnswers = $conn->prepare($deleteAnswersQuery);
        $stmtDeleteAnswers->bind_param("ii", $questionId, $quizId);
        $stmtDeleteAnswers->execute();

        // Step 2: Delete the question itself
        $deleteQuestionQuery = "DELETE FROM fillup WHERE QuestionNo = ? AND QuizId = ?";
        $stmtDeleteQuestion = $conn->prepare($deleteQuestionQuery);
        $stmtDeleteQuestion->bind_param("ii", $questionId, $quizId);
        $stmtDeleteQuestion->execute();

        // Step 3: Update the number of questions
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

        // Commit the transaction
        $conn->commit();

        echo "Question and associated answers deleted successfully.";
    } catch (mysqli_sql_exception $e) {
        // Rollback the transaction if something goes wrong
        $conn->rollback();
        echo "Error deleting question and answers: " . $e->getMessage();
    }
}
?>
