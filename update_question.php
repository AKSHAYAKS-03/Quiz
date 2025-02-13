<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once 'core_db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $jsonData = file_get_contents("php://input");
    $data = json_decode($jsonData, true);

    if ($data && isset($data['field'])) {

        $questionNo = $data['question_no'];
        $field = $data['field'];
        $value = $data['value'];
        $quizId = $_SESSION['active'];

        // Sanitize the fields and validate
        $allowedFields = ['Question', 'Choice1', 'Choice2', 'Choice3', 'Choice4', 'Answer'];
        if (!in_array($field, $allowedFields)) {
            http_response_code(400);
            echo json_encode(["message" => "Invalid field"]);
            exit;
        }

        // Update query
        $query = "UPDATE multiple_choices SET $field = ? WHERE QuestionNo = ? AND QuizId = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sii", $value, $questionNo, $quizId);
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(["message" => "Updated successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update"]);
        }
        $stmt->close();
        exit;
    } elseif (isset($_FILES['upload_file'])) {
        // Handling file upload
        $quizId = $_SESSION['active'];
        $questionNo = $_POST['question_no'];
    
        if ($_FILES['upload_file']['error'] === 0) {
            $target_dir = __DIR__ . "/uploads/" . $quizId . "/";
    
            // Create directory if it doesn't exist
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
    
            // Remove the existing image if it exists
            $stmt_old = $conn->prepare("SELECT img_path FROM multiple_choices WHERE QuizId = ? AND QuestionNo = ?");
            $stmt_old->bind_param("ii", $quizId, $questionNo);
            $stmt_old->execute();
            $result_old = $stmt_old->get_result();
            if ($row_old = $result_old->fetch_assoc()) {
                if (!empty($row_old['img_path'])) {
                    // Build the absolute path to the old file
                    $oldFile = __DIR__ . "/" . $row_old['img_path'];
                    if (file_exists($oldFile)) {
                        unlink($oldFile); // Remove the old image file
                    }
                }
            }
            $stmt_old->close();
    
            // Process the new file upload
            $extension = strtolower(pathinfo($_FILES["upload_file"]["name"], PATHINFO_EXTENSION));
            $target_file = $target_dir . $questionNo . "." . $extension;
            $relative_path = "uploads/" . $quizId . "/" . $questionNo . "." . $extension;
    
            // Validate image file type
            $check = getimagesize($_FILES["upload_file"]["tmp_name"]);
            if ($check !== false) {
                if (move_uploaded_file($_FILES["upload_file"]["tmp_name"], $target_file)) {
                    // Update image path in the database
                    $stmt = $conn->prepare("UPDATE multiple_choices SET img_path = ? WHERE QuizId = ? AND QuestionNo = ?");
                    $stmt->bind_param("sii", $relative_path, $quizId, $questionNo);
    
                    if ($stmt->execute()) {
                        http_response_code(200);
                        echo json_encode(['message' => 'Image updated successfully.']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['message' => 'Failed to update image in database.']);
                    }
                    $stmt->close();
                    exit;
                } else {
                    echo json_encode(['message' => 'Error moving uploaded file.']);
                }
            } else {
                echo json_encode(['message' => 'File is not a valid image.']);
            }
        } else {
            echo json_encode(['message' => 'File upload error.']);
        }
    }
}   

http_response_code(400);
echo json_encode(['message' => 'Invalid request.']);
exit;
?>