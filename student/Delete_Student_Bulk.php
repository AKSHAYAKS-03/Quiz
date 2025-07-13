<?php
include_once '../core/connection.php';

if (!$_SESSION['logged'] || $_SESSION['logged'] === '') {
    header('Location: ../index.php');
    exit;
}

$inputData = json_decode(file_get_contents("php://input"), true);

if (isset($inputData['regNos']) && !empty($inputData['regNos'])) {
    $regNos = $inputData['regNos'];

    $placeholders = implode(',', array_fill(0, count($regNos), '?'));
    $sql = "DELETE FROM users WHERE RegNo IN ($placeholders)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param(str_repeat('s', count($regNos)), ...$regNos);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);                        
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete students.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare the query.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'No students selected for deletion.']);
}

$conn->close();
?>
