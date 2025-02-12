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
    $quizIdsArray = explode(',', $quizId);
    $selectedDept = $_POST['department']; 
    $selectedSec = $_POST['section'];
    $selectedYear = $_POST['year'];
    $selectedPerformance = $_POST['performance'];

    $whereConditions = [];

    if ($quizId === 'all' || (is_array($quizIdsArray) && in_array('all', $quizIdsArray))) {
        $quizIdsQuery = "SELECT DISTINCT Quiz_Id FROM quiz_details";
        $result = mysqli_query($conn, $quizIdsQuery);
    
        $quizIdsArray = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $quizIdsArray[] = $row['Quiz_Id'];
        }
    
        if (!empty($quizIdsArray)) {
            $quizIds = implode(",", array_map('intval', $quizIdsArray)); // Sanitize input
            $whereConditions[] = "s.QuizId IN ($quizIds)";
        } else {
            $whereConditions[] = "1 = 0"; // No quizzes exist, prevent invalid queries
        }
    }
    else if (!empty($quizIdsArray) && $quizId !== 'all') {
        $quizIds = implode(",", array_map('intval', $quizIdsArray)); // Sanitize input
        $whereConditions[] = "s.QuizId IN ($quizIds)";
    }
    else if($quizId !== 'all')
    {
        $whereConditions[] = "s.QuizId = $quizId";
    }


    if ($selectedDept !== 'all') {
        $whereConditions[] = "u.Department = '$selectedDept'";
    }
    if ($selectedSec !== 'all') {
        $whereConditions[] = "u.Section = '$selectedSec'";
    }
    if ($selectedYear !== 'all') {
        $whereConditions[] = "u.Year = '$selectedYear'";
    }
    if ($selectedPerformance !== 'all') {
        if ($selectedPerformance === 'toppers') {
            $whereConditions[] = "s.percentage >= 85";
        } elseif ($selectedPerformance === 'aboveAverage') {
            $whereConditions[] = "s.percentage >= 60 AND s.percentage < 85";
        } elseif ($selectedPerformance === 'average') {
            $whereConditions[] = "s.percentage >= 40 AND s.percentage < 60";
        } elseif ($selectedPerformance === 'belowAverage') {
            $whereConditions[] = "s.percentage < 40";
        }
    }

    $whereSQL = (!empty($whereConditions)) ? "WHERE " . implode(" AND ", $whereConditions) : "";

    $quizNamesQuery = "SELECT DISTINCT Quiz_Id, QuizName FROM quiz_details WHERE Quiz_Id IN ($quizIds)";
    $quizNamesResult = $conn->query($quizNamesQuery);
    $quizNames = [];
    while ($row = $quizNamesResult->fetch_assoc()) {
        $quizNames[$row['Quiz_Id']] = $row['QuizName'];
    }

    $sql = "SELECT s.RegNo, s.Name, u.Department, u.Section, u.Year, s.QuizId, s.percentage, s.Score, s.Time
            FROM student s
            JOIN users u ON s.RegNo = u.RegNo
            $whereSQL";

    $records = $conn->query($sql);

    if ($records && $records->num_rows > 0) {
        $students = [];

        while ($row = $records->fetch_assoc()) {
            $rollNo = $row['RegNo'];
            if (!isset($students[$rollNo])) {
                $students[$rollNo] = [
                    'Name' => $row['Name'],
                    'Department' => $row['Department'],
                    'Section' => $row['Section'],
                    'Year' => $row['Year'],
                    'QuizData' => []
                ];
            }
            $students[$rollNo]['QuizData'][$quizNames[$row['QuizId']]] = [
                'percentage' => $row['percentage'],
                'score' => $row['Score'],
                'time' => $row['Time']
            ];
        }

        if (count($quizIdsArray) > 1) { // Multiple selection
            echo "<table>";
            echo "<tr id='t1'>
                    <th onclick='sortTable(0)'>RegNo <span class='arrow'></span> </th>
                    <th onclick='sortTable(1)'>Name <span class='arrow'></span> </th>
                    <th onclick='sortTable(2)'>Department <span class='arrow'></span> </th>
                    <th onclick='sortTable(3)'>Section <span class='arrow'></span> </th>
                    <th onclick='sortTable(4)'>Year <span class='arrow'></span> </th>";
                $index = 5;                    
            foreach ($quizNames as $quizName){
                echo "<th onclick='sortTable($index)'>$quizName (%) <span class='arrow'></span> </th>";
                $index++;
            }
            echo "<th onclick='sortTable($index)'>Average Percentage <span class='arrow'></span> </th></tr>";
            echo "<tbody id='tablebody'>";
            foreach ($students as $rollNo => $student) {
                echo "<tr>";
                echo "<td>$rollNo</td>";
                echo "<td>{$student['Name']}</td>";
                echo "<td>{$student['Department']}</td>";
                echo "<td>{$student['Section']}</td>";
                echo "<td>{$student['Year']}</td>";

                $totalPercentage = 0;
                $quizCount = 0;

                foreach ($quizNames as $quizName) {
                    $percentage = isset($student['QuizData'][$quizName]['percentage']) ? $student['QuizData'][$quizName]['percentage'] : 0;
                    echo "<td>$percentage%</td>";
                    $totalPercentage += $percentage;
                    $quizCount++;
                }

                $averagePercentage = $quizCount > 0 ? round($totalPercentage / $quizCount, 2) : 0;
                echo "<td>$averagePercentage%</td>";
                echo "</tr>";
            }
            echo '</tbody></table>';
        } else { // Single selection
            echo "<table>
                <tr id='t1'>
                    <th onclick='sortTable(0)'>SNO <span class='arrow'></span> </th>
                    <th onclick='sortTable(1)'>NAME <span class='arrow'></span> </th>
                    <th onclick='sortTable(2)'>REGISTER NO <span class='arrow'></span> </th>
                    <th onclick='sortTable(3)'>DEPARTMENT <span class='arrow'></span> </th>
                    <th onclick='sortTable(4)'>SECTION <span class='arrow'></span> </th>
                    <th onclick='sortTable(5)'>YEAR <span class='arrow'></span> </th>
                    <th onclick='sortTable(6)'>SCORE <span class='arrow'></span> </th>
                    <th onclick='sortTable(7)'>PERCENTAGE <span class='arrow'></span> </th>
                    <th onclick='sortTable(8)'>TIME TAKEN <span class='arrow'></span> </th>
                  </tr>
                  <tbody id='tableBody'>";
            $sno = 1;
            foreach ($students as $rollNo => $student) {
                foreach ($student['QuizData'] as $quiz => $data) {
                    echo "<tr>";
                    echo "<td>$sno</td>";
                    echo "<td>{$student['Name']}</td>";
                    echo "<td>$rollNo</td>";
                    echo "<td>{$student['Department']}</td>";
                    echo "<td>{$student['Section']}</td>";
                    echo "<td>{$student['Year']}</td>";
                    echo "<td>{$data['score']}</td>";
                    echo "<td>{$data['percentage']}%</td>";
                    echo "<td>{$data['time']}</td>";
                    echo "</tr>";
                    $sno++;
                }
            }
            echo "</tbody></table>";
        }
    } else {
        echo 'empty';
    }
}

$conn->close();
?>
