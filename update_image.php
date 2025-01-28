<?php
include_once 'core_db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve POST data
    $quizId = $ActiveQuizId;
    $questionNo = $_POST['question_no'];
    $question = $_POST['question_text'];
    $c1 = $_POST['choice1'];
    $c2 = $_POST['choice2'];
    $c3 = $_POST['choice3'];
    $c4 = $_POST['choice4'];
    $correct_choice = $_POST['correct_choice'];

    $uploadFile = 'NULL';

    // Handle file upload
    if (isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] === 0) {
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
            // Move file to target directory
            if (move_uploaded_file($_FILES["upload_file"]["tmp_name"], $target_file)) {
                $uploadFile = $relative_path;
            }
        }
    }

    // Insert question into database
    $stmt = $conn->prepare("INSERT INTO multiple_choices (QuizId, QuestionNo, Question, Choice1, Choice2, Choice3, Choice4, Answer, img_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssssss", $quizId, $questionNo, $question, $c1, $c2, $c3, $c4, $correct_choice, $uploadFile);

    ob_clean();

    if ($stmt->execute()) {
        // Update quiz details to increment question count
        $stmt2 = $conn->prepare("UPDATE quiz_details SET NumberOfQuestions = NumberOfQuestions + 1 WHERE Quiz_Id = ?");
        $stmt2->bind_param("i", $quizId);
        $stmt2->execute();
        $stmt2->close();

        http_response_code(200);
        echo json_encode(['message' => 'New question added successfully.']);
    } else {
        http_response_code(500);
        echo json_encode([
            "message" => "Failed to insert question.",
            "error" => $stmt->error
        ]);
    }

    $stmt->close();
    exit;
}
?>

