<?php
include_once 'core_db.php';
session_start();

if (!$_SESSION['logged'] || $_SESSION['logged'] === '') {
    header('Location: index.php');
    exit;
}

$quizId = $_SESSION['active'];

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['questionId']) && isset($data['optionValue'])) {
    $questionId = $data['questionId'];
    $optionValue = $data['optionValue'];

    $deleteQuery = "DELETE FROM answer_fillup WHERE Q_Id = ? AND answer = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("is", $questionId, $optionValue); 
    $stmt->execute();
    $stmt->close();

    echo json_encode(['message' => 'Option removed successfully']);

} else {
    echo json_encode(['message' => 'Invalid data']);
}
?>
