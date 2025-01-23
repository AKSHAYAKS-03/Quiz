<?php
session_start();
include 'core_db.php';

// // Check if the user is logged in
// if (!isset($_SESSION['rollno'])) {
//     header('Location: index.php');
//     exit();
// }

// Get session details
$rollno = $_SESSION['RollNo'];
$name = $_SESSION['Name'];

// Fetch student details
$query = "SELECT * FROM student WHERE RollNo = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $rollno);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Fetch active quizzes
$query = "
    SELECT Quiz_id, QuizName, QuizType, NumberOfQuestions, TimeDuration, TotalMarks, startingtime, EndTime
    FROM quiz_details
    WHERE IsActive = 1";
$quizzes = $conn->query($query);

//for all quiz 
$query = "
    SELECT Quiz_id, QuizName, QuizType, NumberOfQuestions, TimeDuration, TotalMarks, startingtime, EndTime,IsActive
    FROM quiz_details";
$allquizzes = $conn->query($query);


// Fetch student quiz results
$query = "
    SELECT q.QuizName, s.Score, q.TotalMarks, s.Time ,s.QuizId
    FROM  student s
    JOIN quiz_details q ON s.QuizId = q.Quiz_id
    WHERE s.RollNo = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $rollno);
$stmt->execute();
$results = $stmt->get_result();

$completedQuizIds = [];
$quizScores = [];
$totscore = 0;
while ($row = $results->fetch_assoc()) {
    $completedQuizIds[] = $row['QuizId'];
    $quizScores[$row['QuizName']] = $row['Score'];
    $totscore += $row['Score'];
}

// echo $totscore;
//for charts data
$query = "
  SELECT 
    q.Quiz_id, 
    q.QuizName, 
    (SELECT MAX(s1.Score) 
     FROM student s1 
     WHERE s1.QuizId = q.Quiz_id) AS HighestScore, 
    SUM(CASE WHEN s.RollNo = ? THEN s.Score ELSE 0 END) AS userscore
FROM 
    quiz_details q
INNER JOIN 
    student s ON s.QuizId = q.Quiz_id
WHERE 
    s.RollNo = ?
GROUP BY 
    q.Quiz_id, q.QuizName
ORDER BY 
    q.Quiz_id ";


$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $rollno, $rollno);
$stmt->execute();
$results = $stmt->get_result();

// Prepare data for chart
$quizIds = [];
$quizNames = [];
$userscore = [];
$highestScores = [];

while ($row = $results->fetch_assoc()) {
    $quizIds[] = $row['Quiz_id'];
    $quizNames[] = $row['QuizName'];
    $userscore[] = $row['userscore'];
    $highestScores[] = $row['HighestScore'];
}

//SQL Query for Overall Performance Summary
$query = "
  SELECT 
    COUNT(s.QuizId) AS total_quizzes_attempted,
    AVG(s.Score) AS average_score,
    MAX(s.Score) AS best_score
  FROM 
    student s
  WHERE 
    s.RollNo = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('s', $rollno);
$stmt->execute();
$results = $stmt->get_result();
$performanceData = $results->fetch_assoc();
// echo "<div class='performance-summary'>";
// echo "<h3>Overall Performance Summary</h3>";
// echo "<p>Total Quizzes Attempted: <strong>" . $performanceData['total_quizzes_attempted'] . "</strong></p>";
// echo "<p>Average Score: <strong>" . round($performanceData['average_score'], 2) . "</strong></p>";
// echo "<p>Best Score Achieved: <strong>" . $performanceData['best_score'] . "</strong></p>";
// echo "</div>";

//performance with completedtime

    $queryTrend = "
    SELECT 
    q.QuizName, 
    s.Score, 
    s.Time AS Time
    FROM 
    student s
    JOIN 
    quiz_details q ON s.QuizId = q.Quiz_id
    WHERE 
    s.RollNo = ?
    ORDER BY 
    s.Time";

    $stmtTrend = $conn->prepare($queryTrend);
    $stmtTrend->bind_param('s', $rollno);
    $stmtTrend->execute();
    $resultsTrend = $stmtTrend->get_result();

    $quizNames = [];
    $scores = [];

    while ($row = $resultsTrend->fetch_assoc()) {
    $quizNames[] = $row['QuizName'];  // Quiz Name
    $scores[] = $row['Score'];        // Score for each quiz
    }

    $completedTimes = [];
    while ($row = $resultsTrend->fetch_assoc()) {
        $completedTimes[] = $row['Time'];  
        echo $row['Time'];
        // Store completion time for trend analysis
        $scores[] = $row['Score'];
    }

//fetch student datas

  $query = "SELECT RollNo, Name, Department, Year, QuizId FROM student WHERE RollNo = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param('s', $rollno);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $rollno = $row['RollNo'];
    $name = $row['Name'];
    $department = $row['Department'];
    $year = $row['Year'];
    $quizId = $row['QuizId'];
  }

//   echo $rollno;
//   echo $name;
//   echo $department;
//   echo $year;
//   echo $quizId;

  // rank of the student
  $query = "
  SELECT 
      s1.RollNo, 
      s1.Name, 
      s1.QuizId, 
      s1.Score, 
      s1.Time,
      (SELECT COUNT(*) + 1 
       FROM student s2 
       WHERE s2.Department = s1.Department 
       AND s2.Year = s1.Year 
       AND s2.QuizId = s1.QuizId
       AND (s2.Score > s1.Score OR (s2.Score = s1.Score AND s2.Time < s1.Time))
      ) AS RankInDeptYearQuiz
  FROM student s1
  WHERE s1.Department = ? AND s1.Year = ?
  ORDER BY s1.QuizId ASC, RankInDeptYearQuiz ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $department, $year);
$stmt->execute();
$results = $stmt->get_result();

// Store data for chart
$quizData = [];
$studentRanks = [];

while ($row = $results->fetch_assoc()) {
    $quizData[] = [
        'quizId' => $row['QuizId'],
        'rollNo' => $row['RollNo'],
        'rank' => $row['RankInDeptYearQuiz']

    ];

    // Track the current student's ranks
    if ($row['RollNo'] == $rollno) {
        $studentRanks[$row['QuizId']] = $row['RankInDeptYearQuiz'];
       
    }
}

// Convert PHP data to JSON for JS
$studentRanksJson = json_encode($studentRanks);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- <link rel="stylesheet" href="css/dashboard.css"> -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 10px;
    background-color: #f4f4f9;
    color: #333;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}
.header {
    color: #13274F;
    display: flex;
    justify-content: space-between; /* Place content at opposite corners */
    align-items: center;  /* Align vertically in the center */
    padding: 20px;
}

.header h2 {
    margin: 0;
}

.user-info {
    text-align: right; /* Align user info to the right */
}

.content {
    display: flex;
    flex: 1;
}
.left-section {
    width: 60%;
    padding: 20px;
    border-radius: 20px;
    background-color: #fff;
}
.left-section h2 {
    color: #13274F;
}
.quiz-boxes {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    padding: 20px;
}
.quiz-box {
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease-in-out;
    position: relative;
}

.quiz-box:hover {
    transform: translateY(-5px);
}

.quiz-icon i {
    font-size: 40px;
    margin-bottom: 10px;
}

.quiz-title {
    font-size: 20px;
    font-weight: bold;
    margin: 5px 0;
    color: #333;
}

.quiz-status {
    font-style: italic;
    font-size: 16px;
    color: #666;
}

/* Animation for active quizzes */
@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
    }
}



.right-sidebar {
    width: 20%;
    background-color: #f9f9f9;
    padding: 20px;
    border-left: 2px solid #ddd;
}
.right-sidebar h2 {
    color: #13274F;
    margin-bottom: 20px;
}
.quiz-list {
    list-style: none;
    padding: 0;
}
.quiz-item {
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 15px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.quiz-item h3 {
    margin: 0;
    color: #13274F;
}
.quiz-item p {
    margin: 5px 0;
    font-size: 0.9em;
    color: #555;
}
.quiz-item .btn {
    display: inline-block;
    margin-top: 10px;
    padding: 10px 15px;
    background: #13274F;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}
.quiz-item .btn:hover {
    background-color: #0f1c3c;
}
.completed {
    color: green;
}

.inactive {
    color: gray;
}

.active {
    color: orange;
}

.active .quiz-icon{
    animation: pulse 2s infinite;
}
.quiz-score {
    font-size: 18px;
    font-weight: bold;
    margin-top: 10px;
    padding: 8px 0;
    border-radius: 5px;
    width: 80%;
    margin-left: auto;
    margin-right: auto;
    box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
}

.completed .quiz-score {
    color: #00796b;
}

.active .quiz-score {
    color: #fb8c00;
}

.inactive .quiz-score {
    color: #e53935;
}

.rank-card {
            background: linear-gradient(135deg, #00c6ff, #0072ff);
            color: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
        }
        .rank-number {
            font-size: 50px;
            font-weight: bold;
        }
        .quiz-title {
            font-size: 20px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Dashboard</h2>
        <div class="user-info">
            <h3>Welcome, <?= htmlspecialchars($name); ?>!</h3>
            <p><?= htmlspecialchars($rollno); ?></p>
        </div>
    </div>

    <div class="content">
        <!-- Left Section: Quiz Boxes -->
        <div class="left-section">
            <h2>Your Quizzes</h2>
            <div class="quiz-boxes">
                
            <?php if ($allquizzes->num_rows > 0): ?>
                <?php while ($quiz = $allquizzes->fetch_assoc()): ?>
                    <?php
                    // Determine the class and content based on quiz status
                    $quizClass = 'inactive'; 
                    $statusText = 'Not Completed';
                    $icon = '<i class="fas fa-clock"></i>'; // Default icon for inactive
                    $onclickAttr = ''; 
                    $quizScore = null;

                    if (in_array($quiz['Quiz_id'], $completedQuizIds)) {
                        $quizClass = 'completed';
                        $statusText = 'Completed';
                        $icon = '<i class="fas fa-check"></i>';  // Completed icon                        
                        $onclickAttr = "onclick=\"window.location.href='Answers.php?quiz_id=" . htmlspecialchars($quiz['Quiz_id']) . "'\"";

                        //to get the score for
                        if (isset($quizScores[$quiz['QuizName']])) {
                            $quizScore = $quizScores[$quiz['QuizName']];
                        }
                    } elseif ($quiz['IsActive']) {
                        $quizClass = 'active';
                        $statusText = 'Active Now';
                        $icon = '<i class="fas fa-bolt"></i>';  // Active icon
                        $onclickAttr = "onclick=\"window.location.href='Welcome.php?quiz_id=" . htmlspecialchars($quiz['Quiz_id']) . "'\"";
                    }
                    ?>                                                                                               
                    <div class="quiz-box <?= $quizClass; ?>" <?= $onclickAttr; ?>>
                    <?php if ($quizScore !== null && $quizClass === 'completed'): ?>
                            <!-- <div class="quiz-score"> -->
                                <span style="float: right;font-size: 20px;font-weight: bold;"><?= htmlspecialchars($quizScore);?></span>                                
                            <!-- </div> -->
                        <?php endif; ?>                        
                        <div class="quiz-icon"><?= $icon; ?></div>
                     
                        <p class="quiz-title"><?= htmlspecialchars($quiz['QuizName']); ?></p>
                        
                        <?php
                        $startTime = new DateTime($quiz['startingtime']);
                        $startDay = $startTime->format('l');
                        ?>
                        <p class="quiz-status"><?= $statusText; ?></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No quizzes attempted yet.</p>
            <?php endif; ?>

        </div>
        <div class="container mt-5">
    <h2 class="text-center mb-4">Your Quiz Rankings</h2>
    <div class="row">
        <?php 
        // Display the student's ranks for each quiz
        foreach ($studentRanks as $quizId => $rank): 
            // Optionally, fetch quiz title based on quizId if available in your data
            $quizTitle = "Quiz #" . $quizId;  // Replace with actual logic to get quiz names
        ?>
            <div class="col-md-4 mb-4">
                <div class="rank-card">
                    <div class="quiz-title"><?= htmlspecialchars($quizTitle); ?></div>
                    <div class="rank-number"><?= $rank; ?></div>
                    <p>Rank in Quiz</p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    </div>
        </div>
        <div class="analysis">
            <div class="content_chart">
                    <h2>Your Performance</h2>
                    <!-- <canvas id="scoreComparisonChart" width="400" height="200"></canvas> -->
                    <canvas id="scoreChart" width="400" height="200"></canvas>
                    <canvas id="lineChart"></canvas>
                    <canvas id="rankChart"></canvas>
             </div>
        </div>
        
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    // Pass PHP data to JavaScript
    var quizNames = <?php echo json_encode($quizNames); ?>;
    var userscore = <?php echo json_encode($userscore); ?>;
    var highestScores = <?php echo json_encode($highestScores); ?>;

    // Create the chart using Chart.js
    var ctx = document.getElementById('scoreChart').getContext('2d');
    var scoreChart = new Chart(ctx, {
        type: 'bar', // Bar chart
        data: {
            labels: quizNames, // Quiz names as labels
            datasets: [
                {
                    label: 'Your Score',
                    data: userscore, // The student's scores
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Highest Score',
                    data: highestScores, // Max possible scores
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true // Ensure Y-axis starts at 0
                }
            }
        }
    });
    var ctx = document.getElementById('lineChart').getContext('2d');
    var pieChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($quizNames); ?>,
            datasets: [{
                label: 'Scores',
                data: <?php echo json_encode($scores); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.5)',
                    'rgba(54, 162, 235, 0.5)',
                    'rgba(255, 206, 86, 0.5)',
                    'rgba(75, 192, 192, 0.5)'
                ]
            }]
        }
    });
   
</script>


</body>
</html>