<?php
include_once 'core_db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $questionNo = $_POST['question_no'];
    $quizId = $_POST['quiz_id'];
    $updates = [];

    // Check and update text fields
    $fields = ["Question", "Choice1", "Choice2", "Choice3", "Choice4", "Answer"];
    foreach ($fields as $field) {
        if (isset($_POST[$field]) && $_POST[$field] !== '') {
            $updates[$field] = trim($_POST[$field]); 
        }
    }
    if (isset($updates['Answer'])) {
        $answer = $updates['Answer'];
        $choices = [
            $updates["Choice1"] ?? "",
            $updates["Choice2"] ?? "",
            $updates["Choice3"] ?? "",
            $updates["Choice4"] ?? ""
        ];

        if (!in_array($answer, $choices, true)) {
            echo json_encode(["message" => "Error: The correct answer must be one of the four choices."]);
            exit;
        }
    }


    // Handle image upload (optional)
    $uploadDir = "uploads/";
    $quizDir = $uploadDir . $quizId . "/";
    
    if (!is_dir($quizDir)) {
        mkdir($quizDir, 0777, true);
    }

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['jpg', 'jpeg', 'png'];
        $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (!in_array($imageFileType, $allowedTypes)) {
            echo json_encode(["message" => "Invalid image format"]);
            exit;
        }

        if ($_FILES['image']['size'] == 0) {
            echo json_encode(["message" => "Empty file uploaded"]);
            exit;
        }

        // Fetch old image path
        $oldImageQuery = "SELECT img_path FROM multiple_choices WHERE QuestionNo = ? AND QuizId = ?";
        $stmt = $conn->prepare($oldImageQuery);
        $stmt->bind_param("ii", $questionNo, $quizId);
        $stmt->execute();
        $stmt->bind_result($oldImagePath);
        $stmt->fetch();
        $stmt->close();

        // Remove old image file if it exists
        if ($oldImagePath && file_exists(realpath($oldImagePath))) {
            unlink(realpath($oldImagePath));
        }

        // Upload new image
        $relative_path = $quizDir . $questionNo . "." . $imageFileType;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $relative_path)) {
            $updates['img_path'] = $relative_path;
        } else {
            echo json_encode(["message" => "Failed to upload image"]);
            exit;
        }
    }

    // Construct SQL query for updating
    if (!empty($updates)) {
        $setClause = implode(", ", array_map(fn($key) => "$key = ?", array_keys($updates)));
        $values = array_values($updates);
        $values[] = $questionNo;
        $values[] = $quizId;

        $types = str_repeat("s", count($updates)) . "ii"; 
        $updateQuery = "UPDATE multiple_choices SET $setClause WHERE QuestionNo = ? AND QuizId = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param($types, ...$values);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Question updated successfully"]);
        } else {
            echo json_encode(["message" => "Database error: " . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(["message" => "No changes made"]);
    }

    $conn->close();
}
?>
