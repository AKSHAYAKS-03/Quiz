<?php
include_once 'core_db.php';
session_start();

if (!isset($_GET['quizId'], $_GET['Quiztype'], $_GET['name'])) {
    die('Required parameters are missing!');
}

$quizId = $_GET['quizId'];
$quizType = $_GET['Quiztype'];
$name = $_GET['name'];
$filename = $name . '.csv';

$quiz = ($quizType === '1') ? 'fillup' : 'multiple_choices';

$result = $conn->query("DESCRIBE ".$quiz);
$columns = [];
while($row = $result->fetch_assoc()) {
    if($row['Field'] != 'QuizId' && $row['Field'] != 'QuestionNo') {
        
        $columns[] = $row['Field'];
    }
}

$query = "SELECT " . implode(", ", $columns) . " FROM " . $quiz . " WHERE QuizId = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $quizId); 

$stmt->execute();
$result = $stmt->get_result();

header("Content-Disposition: attachment; filename=\"$filename\"");
header("Content-Type: text/csv");

if ($result->num_rows > 0) {
    $output = fopen('php://output', 'w'); 
    $flag = false; 
    while ($row = $result->fetch_assoc()) {
        if (!$flag) {
            fputcsv($output, array_keys($row));
            $flag = true;
        }
        fputcsv($output, $row); 
    }

    fclose($output); 
    $result->free();
} else {
    die('No records found for the specified QuizId.');
}

$stmt->close();
$conn->close(); 
exit;
?>
