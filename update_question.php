<?php
include_once 'core_db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle JSON data for text updates or multipart for file uploads
    if (isset($_POST['field'])) {
        // JSON data for updating text fields
        $data = json_decode(file_get_contents("php://input"), true);

        $questionNo = $data['question_no'];
        $field = $data['field'];
        $value = $data['value'];
        $quizId = $_SESSION['active'];

        $allowedFields = ['Question', 'Choice1', 'Choice2', 'Choice3', 'Choice4', 'Answer'];

        if (!in_array($field, $allowedFields)) {
            http_response_code(400);
            echo json_encode(["message" => "Invalid field"]);
            exit;
        }

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
    } elseif (isset($_FILES['upload_file'])) {
        // Multipart data for image upload
        $quizId = $_SESSION['active'];
        $questionNo = $_POST['question_no'];

        $uploadFile = 'NULL';
        if ($_FILES['upload_file']['error'] === 0) {
            $target_dir = __DIR__ . "/uploads/" . $quizId . "/";

            // Create directory if it doesn't exist
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            // Generate unique file name to prevent conflicts
            $extension = strtolower(pathinfo($_FILES["upload_file"]["name"], PATHINFO_EXTENSION));
            $target_file = $target_dir . $questionNo . "." . $extension;
            $relative_path = "uploads/" . $quizId . "/" . $questionNo . "." . $extension;

            // Validate file type
            $check = getimagesize($_FILES["upload_file"]["tmp_name"]);
            if ($check !== false) {
                if (move_uploaded_file($_FILES["upload_file"]["tmp_name"], $target_file)) {
                    $uploadFile = $relative_path;

                    // Update image path in the database
                    $stmt = $conn->prepare("UPDATE multiple_choices SET img_path = ? WHERE QuizId = ? AND QuestionNo = ?");
                    $stmt->bind_param("sii", $uploadFile, $quizId, $questionNo);

                    if ($stmt->execute()) {
                        http_response_code(200);
                        echo json_encode(['message' => 'Image updated successfully.']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['message' => 'Failed to update image in database.']);
                    }
                    $stmt->close();
                }
            }
        }
    }
}
?>
