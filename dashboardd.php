<?php
session_start();
include 'core_db.php';

// Ensure the user is logged in
if (!isset($_SESSION['RollNo'])) {
    echo json_encode(['error' => 'User not logged in.']);
    exit();
}

// Get session and GET parameters
$rollno = $_SESSION['RollNo'];
$name = $_SESSION['Name'];
$quiz_id = intval($_GET['quiz_id']); // Sanitize the input


// Fetch data from database
$sql = "SELECT Name, Score, QuizId FROM student_new WHERE RollNo = '$rollno' AND QuizId = '$quiz_id'";
$result = $conn->query($sql);

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;  // Store each row in an array
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Include Chart.js library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h1>Dashboard - Quiz Scores</h1>
    
    <!-- Container for the Chart -->
    <canvas id="scoreChart" width="400" height="200"></canvas>

    <script>
        // Pass PHP array data to JavaScript
        var studentsData = <?php echo json_encode($students); ?>;

        // Prepare labels (student names) and data (scores)
        var labels = studentsData.map(function(student) { return student.Name; });
        var scores = studentsData.map(function(student) { return student.Score; });

        // Create the chart using Chart.js
        var ctx = document.getElementById('scoreChart').getContext('2d');
        var scoreChart = new Chart(ctx, {
            type: 'bar', // Bar chart
            data: {
                labels: labels, // Labels for the X-axis (student names)
                datasets: [{
                    label: 'Scores', // Chart label
                    data: scores, // Y-axis data (scores)
                    backgroundColor: 'rgba(54, 162, 235, 0.2)', // Bar color
                    borderColor: 'rgba(54, 162, 235, 1)', // Border color
                    borderWidth: 1 // Border width
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true // Start Y-axis at 0
                    }
                }
            }
        });
    </script>

</body>
</html>
