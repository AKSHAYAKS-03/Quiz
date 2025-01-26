<?php
include_once 'core_db.php';
session_start();

if (!$_SESSION['logged'] || $_SESSION['logged'] === '') {
    header('Location: index.php');
    exit;
}

if ($conn->connect_errno) {
    echo "Failed to connect to MySQL: " . $conn->connect_error;
    exit();
}

if (isset($_POST['quizId'])) {
    $quizId = $conn->real_escape_string($_POST['quizId']);
    $selectedDept = $_POST['department']; 
    $selectedSec = $_POST['section'];
    $selectedYear = $_POST['year'];
    $selectedPerformance = $_POST['performance'];

    if ($quizId === 'all') {
        $sql = "SELECT * FROM student";  
    } else {
        $sql = "SELECT * FROM student WHERE QuizId = '$quizId'"; 
    }

    if ($selectedDept !== 'all') {
        if (strpos($sql, 'WHERE') !== false) {
            $sql .= " AND Department = '$selectedDept'";
        } else {
            $sql .= " WHERE Department = '$selectedDept'";
        }
    }
    if($selectedSec !== 'all'){
        if (strpos($sql, 'WHERE') !== false) {
            $sql .= " AND Section = '$selectedSec'";
        } else {
            $sql .= " WHERE Section = '$selectedSec'";
        }
    }
    if($selectedYear !== 'all'){
        if (strpos($sql, 'WHERE') !== false) {
            $sql .= " AND Year = '$selectedYear'";
        } else {
            $sql .= " WHERE Year = '$selectedYear'";
        }   
    }
    if($selectedPerformance !== 'all'){
        if($selectedPerformance==='toppers'){
            $performance = "percentage>=85";
        }
        else if($selectedPerformance==='aboveAverage'){
            $performance = "percentage>=60 AND percentage<85";
        }
        else if($selectedPerformance==='average'){
            $performance = "percentage>=40 AND percentage<60";
        }
        else if($selectedPerformance==='belowAverage'){
            $performance = "percentage<40";
        }

        if (strpos($sql, 'WHERE') !== false) {
            $sql .= " AND $performance";
        } else {
            $sql .= " WHERE $performance";
        }   
    }

    $sql .= " ORDER BY CAST(RollNo as UNSIGNED)";

    $records = $conn->query($sql);

    if ($records && $records->num_rows > 0) {
        $_SESSION['Sno'] = 0;
        while ($student = $records->fetch_assoc()) {
            $_SESSION['Sno']++;
            echo "<tr>";
            echo "<td>" . $_SESSION['Sno'] . "</td>";
            echo "<td>" . $student['Name'] . "</td>";
            echo "<td>" . $student['RollNo'] . "</td>";
            echo "<td>" . $student['Department'] . "</td>";
            echo "<td>" . $student['Section'] . "</td>";
            echo "<td>" . $student['Year'] . "</td>";
            echo "<td>" . $student['Score'] . "</td>";
            echo "<td>" . $student['percentage'] . "</td>";
            echo "<td>" . $student['Time'] . "</td>";
            echo "</tr>";
        }
    } else {
        echo 'empty';
    }
}

$conn->close();
?>
