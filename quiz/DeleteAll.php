<?php 
include_once '../core/connection.php';

if(!$_SESSION['logged'] || $_SESSION['logged']===''){
    header('Location: ../index.php');
    exit;
}

$conn->begin_transaction();

try {

    $stmt = $conn->prepare("DELETE FROM student WHERE QuizId IN (SELECT Quiz_Id FROM quiz_details)");
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM stud WHERE QuizId IN (SELECT Quiz_Id FROM quiz_details)");
    $stmt->execute();
    $stmt->close();

    $stmt2 = $conn->prepare("DELETE FROM multiple_choices WHERE QuizId IN (SELECT Quiz_Id FROM quiz_details)");
    $stmt2->execute();
    $stmt2->close();

    $stmt = $conn->prepare("DELETE FROM quiz_details");
    $stmt->execute();
    $stmt->close();
    
    $conn->commit();
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(array("message" => "All quizzes deleted successfully"));
    exit;
} 

 catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(array("message" => "Failed to delete all quizzes"));
    exit;
}
?>
