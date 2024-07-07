<?php
include_once 'core_db.php';

function cleanData(&$str) {
    $str = preg_replace("/\t/", "\\t", $str);
    $str = preg_replace("/\r?\n/", "\\n", $str);
    if (strstr($str, '"')) {
        $str = '"' . str_replace('"', '""', $str) . '"';
    }
}

$quizId = isset($_GET['quizId']) ? $_GET['quizId'] : 0;
if($quizId === 0 && $_SESSION['active'] !== 'None'){ 
    $quizId = $_SESSION['active'];
}

$quizName = '';

$query = "SELECT QuizName FROM quiz_details WHERE Quiz_Id = ?";
if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $quizId);
    $stmt->execute();
    $stmt->bind_result($quizName);
    $stmt->fetch();
    $stmt->close();
}

// If quiz name is not found, use a default name
if (empty($quizName)) {
    $quizName = "quiz";
}

// Sanitize quiz name to be used in the filename
$quizName = preg_replace("/[^a-zA-Z0-9_-]/", "_", $quizName);

// Filename for download
$filename = "{$quizName}_" . date('Y-m-d') . ".xls";

header("Content-Disposition: attachment; filename=\"$filename\"");
header("Content-Type: application/vnd.ms-excel");

$flag = false;

if($quizId === 'all')
    $query = "SELECT Name, RollNo, Department, Score, Time FROM student ORDER BY Score DESC, Time";
else
    $query = "SELECT Name, RollNo, Department, Score, Time FROM student where QuizId = $quizId ORDER BY Score DESC, Time";
if ($result = $conn->query($query)) {
    while ($row = $result->fetch_assoc()) {
        if (!$flag) {
            // Display field/column names as first row
            echo implode("\t", array_keys($row)) . "\r\n";
            $flag = true;
        }
        array_walk($row, 'cleanData');
        
        // Ensure RollNo is treated as text
        $row['RollNo'] = "=\"" . $row['RollNo'] . "\"";
        
        echo implode("\t", array_values($row)) . "\r\n";
    }
    $result->free();
} else {
    die('Query failed: ' . $conn->error);
}

$conn->close();
exit;
?>
