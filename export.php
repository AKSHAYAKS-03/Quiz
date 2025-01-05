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
$department = isset($_GET['department']) ? $_GET['department'] : 'all';
$section = isset($_GET['section']) ? $_GET['section'] : 'all';
$year = isset($_GET['year']) ? $_GET['year'] : 'all';
if($year ==='1')
    $year = 'I';
else if($year ==='2')
    $year = 'II';
else if($year ==='3')
    $year = 'III';
else if($year ==='4')
    $year = 'IV';

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

if (empty($quizName)) {
    $quizName = "quiz";
}

$quizName = preg_replace("/[^a-zA-Z0-9_-]/", "_", $quizName);

$filename = "{$quizName}_" . date('Y-m-d');

if (!empty($department) && $department !== 'all') {
    $filename .= "_{$department}";
}
if (!empty($year) && $year !== 'all') {
    $filename .= "_{$year}";
}
if (!empty($section) && $section !== 'all') {
    $filename .= "_{$section}";
}

$filename .= ".xls";

header("Content-Disposition: attachment; filename=\"$filename\"");
header("Content-Type: application/vnd.ms-excel");

$flag = false;

$query = "SELECT Name, RollNo, Department, Year, Section, Score, Time FROM student WHERE 1";

if ($quizId !== 'all') {
    $query .= " AND QuizId = $quizId";
}

if ($department !== 'all') {
    $query .= " AND Department = '$department'";
}

if ($section !== 'all') {
    $query .= " AND Section = '$section'";
}

if ($year !== 'all') {
    $query .= " AND Year = '$year'";
}

$query .= " ORDER BY Score DESC, Time";

if ($result = $conn->query($query)) {
    while ($row = $result->fetch_assoc()) {
        if (!$flag) {
            echo implode("\t", array_keys($row)) . "\r\n";
            $flag = true;
        }
        array_walk($row, 'cleanData');
        
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
