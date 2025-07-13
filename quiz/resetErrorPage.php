<?php
include_once '../core/header.php';

// Check if session data exists
if (!isset($_SESSION['Name'])) {
    echo "No reset data found.";
    exit;
}

// Retrieve details from the session
$user_name = $_SESSION['Name'];
$user_roll = $_SESSION['RollNo'];
$user_dept = $_SESSION['dept'];
$user_year = $_SESSION['year'];
$user_section = $_SESSION['sec'];  

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['Logout'])) {
        $stmt = $conn->prepare("DELETE FROM student WHERE RollNo = ? AND QuizId = ?");
        $stmt->bind_param("si", $rollno, $activeQuizId);
        if ($stmt->execute()) {
            header("Location: ../auth/Student_logout.php");
            exit;
        } else {
            echo "Error deleting record: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Reset Notification</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <script src='../assets/scripts/inspect.js'></script>
    <link rel="stylesheet" type="text/css" href="../assets/css/resetErrorPage.css">
</head>
<body>
    <div class="container">
        <h1>Quiz Attempt Reset</h1>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($user_name); ?></p>
        <p><strong>Roll Number:</strong> <?php echo htmlspecialchars($user_roll); ?></p>
        <p><strong>Department:</strong> <?php echo htmlspecialchars($user_dept); ?></p>
        <p><strong>Year & Sec:</strong> <?php echo htmlspecialchars($user_year)." ".htmlspecialchars($user_section); ?></p>      <br>
        <p style="font-weight: bold;"> **If you believe this reset was a mistake, please contact your instructor</a> or the quiz admin for clarification.</p>
        <form method="post" action="welcome.php">
            <input type="submit" name="Logout" value="Logout" class="btn Logout">
        </form>
    </div>
    
</body>
</html>