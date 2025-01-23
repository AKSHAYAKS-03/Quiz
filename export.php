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
$performance = isset($_GET['performance']) ? $_GET['performance'] : 'all';

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
if (!empty($performance) && $performance !== 'all') {
    if($performance === '1')
        $performance = 'Toppers';
    else if($performance === '2')
        $performance = 'Average';
    else if($performance === '3')
        $performance = 'BelowAverage';
    else if($performance === '4')   
        $performance = 'Bottom';
    $filename .= "_{$performance}";
}

 $filename .= ".csv";

 header("Content-Disposition: attachment; filename=\"$filename\"");
 header("Content-Type: text/csv"); 

$flag = false;

$query = "SELECT Name, RollNo, Department,`Year`, Section, Score, `Percentage`,`Time` FROM student WHERE 1";

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

if ($performance !== 'all') {
    $performanceCondition = 'percentage>=0';
    if($performance === 'Toppers')
        $performanceCondition = 'percentage>=85';
    else if($performance === 'Average')
        $performanceCondition = 'percentage>=60 AND percentage<85';
    else if($performance === 'Below Average')
        $performanceCondition = 'percentage>=40 AND percentage<60';
    else if($performance === 'Bottom')
        $performanceCondition = 'percentage<40';

    $query .= " AND $performanceCondition";
}

$query .= " ORDER BY CAST(RollNo as UNSIGNED)";

if ($result = $conn->query($query)) {
    $output = fopen('php://output', 'w'); 
    while ($row = $result->fetch_assoc()) {
        if (!$flag) {
            fputcsv($output, array_keys($row));
            $flag = true;
        }
        $row['RollNo'] = "=\"" . $row['RollNo'] . "\"";
        fputcsv($output, $row);
    }
    fclose($output);
    $result->free();
} else {
    die('Query failed: ' . $conn->error);
}

$conn->close();
exit;
 ?>