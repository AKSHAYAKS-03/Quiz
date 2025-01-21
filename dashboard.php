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
while ($row = $results->fetch_assoc()) {
    $completedQuizIds[] = $row['QuizId'];
    $quizScores[$row['QuizName']] = $row['Score'];
}

//for charts data
$query = "
    SELECT q.Quiz_id, q.QuizName, MAX(s.Score) AS HighestScore,
           SUM(CASE WHEN s.RollNo = ? THEN s.Score ELSE 0 END) AS userscore
    FROM student s
    JOIN quiz_details q ON s.QuizId = q.Quiz_id
    WHERE s.RollNo = ?
    GROUP BY q.Quiz_id, q.QuizName
    ORDER BY q.Quiz_id";

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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="header">
        <h1>Welcome, <?= htmlspecialchars($name); ?>!</h1>
        <p><?= htmlspecialchars($rollno); ?></p>
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
        <div class="content_chart">
                <h2>Your Performance</h2>
                <!-- <canvas id="scoreComparisonChart" width="400" height="200"></canvas> -->
                <canvas id="scoreChart" width="400" height="200"></canvas>
        </div>
        </div>
        <!-- Right Sidebar: Available Quizzes -->
        <div class="right-sidebar">
            <h2>Available Quizzes</h2>
            <ul class="quiz-list">
                <?php if ($quizzes->num_rows > 0): ?>
                    <?php while ($quiz = $quizzes->fetch_assoc()): ?>
                        <li class="quiz-item">
                            <h3><?= htmlspecialchars($quiz['QuizName']); ?></h3>
                            <?php
                            if($quiz['QuizType'] == 1){
                                echo "<p>Fill Up</p>";
                            }
                            else{
                                echo "<p>Multiple Choice</p>";
                            }
                            ?>
                            <p>Questions: <?= htmlspecialchars($quiz['NumberOfQuestions']); ?></p>
                            <p>Duration: <?= htmlspecialchars($quiz['TimeDuration']); ?> mins</p>
                            <a href="Welcome.php" class="btn">Start Quiz</a>
                        </li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No available quizzes at the moment.</p>
                <?php endif; ?>
            </ul>
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
</script>


</body>
</html>