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

if (isset($_POST['quizId']) && isset($_POST['limit'])) {
    $quizId = $conn->real_escape_string($_POST['quizId']);
    $limit = $conn->real_escape_string($_POST['limit']);
    $selectedDept = $_POST['department']; 

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

    $sql .= " ORDER BY Score DESC, `Time`";

    if ($limit !== 'all') {
        $sql .= " LIMIT " . intval($limit);
    }

    $records = $conn->query($sql);

    if ($records && $records->num_rows > 0) {
        echo '<div><table>
                <tr>
                    <th>SNO</th>
                    <th>NAME</th>
                    <th>REGISTER NO</th>
                    <th>DEPARTMENT</th>
                    <th>SCORE</th>
                    <th>TIME (in MIN)</th>
                </tr>';
        $_SESSION['Sno'] = 0;
        while ($student = $records->fetch_assoc()) {
            $_SESSION['Sno']++;
            echo "<tr>";
            echo "<td>" . $_SESSION['Sno'] . "</td>";
            echo "<td>" . $student['Name'] . "</td>";
            echo "<td>" . $student['RollNo'] . "</td>";
            echo "<td>" . $student['Department'] . "</td>";
            echo "<td>" . $student['Score'] . "</td>";
            echo "<td>" . $student['Time'] . "</td>";
            echo "</tr>";
        }
        echo '</table></div>';
    } else {
        echo '<p>No records found for the selected quiz.</p>';
    }
}

$conn->close();
?>
