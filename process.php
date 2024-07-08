<?php
// session_start();
include 'core/db.php';
// Check if the form was submitted

// $timeout = isset($_POST['timeout']) ? $_POST['timeout'] : '0';
// $_SESSION['timeout'] = $timeout;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   

    // Example: Check if a specific field exists in $_POST (e.g., submit button)
    if (isset($_POST['submit']) ) {

        $rollno = $_SESSION['RollNo'];
        $quizid = $_SESSION['active'];
        $currentIndex = $_POST['currentIndex'];
        $questionNo = $_POST['questionNo'];
        $total = $_POST['total'];
        $selected_choice = isset($_POST['choice']) ? $_POST['choice'] : null;



    $timeTaken = time() - $_SESSION['question_start_time'];
        
    // Convert the time taken to MM:SS format
    $minutes = floor($timeTaken / 60);
    $seconds = $timeTaken % 60;
    $formattedTimeTaken = sprintf('%02d:%02d', $minutes, $seconds);

    // Save the formatted time taken in your database or session
    // Example: Save in session
    $_SESSION['time_taken'] = $formattedTimeTaken;


    // Retrieve correct answer from the database
    $answer_query = "SELECT Answer FROM multiple_choices WHERE QuizId = ? AND QuestionNo = ?";
    $stmt = $conn->prepare($answer_query);
    $stmt->bind_param("ii", $quizid, $questionNo);
    $stmt->execute();
    $stmt->bind_result($correct_answer);
    $stmt->fetch();
    $stmt->close();

    // Check if selected choice matches correct answer and update score if so
    if ($selected_choice == $correct_answer) {
        if (!isset($_SESSION['score'])) {
            $_SESSION['score'] = 0;
        }
        $_SESSION['score'] += $_SESSION['question_marks']; // Increment score
    }

    // Insert answer and time taken into database
    $answer_insert_query = "INSERT INTO stud (QuizId, regno, question, time) VALUES (?, ?, ?,?)";
    $stmt = $conn->prepare($answer_insert_query);
    $stmt->bind_param("isis",$quizid, $rollno, $questionNo, $formattedTimeTaken);
    $stmt->execute();
    $stmt->close();

    // Update current index for the next question
    $_SESSION['currentIndex']++;

    // Redirect to next question or final page based on current index
    if ($currentIndex < $total - 1) {
        header('Location: question.php');
        exit;
    } else {
        header('Location: final.php');
        exit;
    }
}
    else {
        // Handle case where form was submitted but the expected submit button is not found
        // This could be due to an error in form rendering or tampering with the form
        // Redirect or handle the error appropriately
        header('Location: login.php'); // Example: Redirect to an error page
        exit;
    }
}

else {
    // Handle case where the form was not submitted (direct access to process.php without POST data)
    // Redirect or handle the error appropriately
    header('Location: error.php'); // Example: Redirect to an error page
    exit;
}
?>