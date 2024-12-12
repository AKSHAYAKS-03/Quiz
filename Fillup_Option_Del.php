<?php
include_once 'core_db.php';
session_start();

if (!$_SESSION['logged'] || $_SESSION['logged'] === '') {
    header('Location: login.php');
    exit;
}

$quizId = $_SESSION['active'];

$data = json_decode(file_get_contents('php://input'), true);

// Check if the necessary data is available
if (isset($data['questionId']) && isset($data['optionValue'])) {
    $questionId = $data['questionId'];
    $optionValue = $data['optionValue'];

    // Prepare and execute the query to delete the option
    $deleteQuery = "DELETE FROM answer_fillup WHERE Q_Id = ? AND answer = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("is", $questionId, $optionValue); // 'i' for integer and 's' for string
    $stmt->execute();
    $stmt->close();

    // Respond with a success message
    echo json_encode(['message' => 'Option removed successfully']);

} else {
    // Respond with an error if data is missing
    echo json_encode(['message' => 'Invalid data']);
}
?>
