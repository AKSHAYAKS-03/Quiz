<?php
session_start();
include 'core_db.php';

// Check if the user is logged in
if (!isset($_SESSION['RegNo'])) {
    header('Location: index.php');
    exit();
}

$RegNo = $_SESSION['RegNo'];
$name = $_SESSION['Name'];
$activeQuizId = $_SESSION['active'];


// Fetch student details (assuming logged-in student ID is 1)
$student_query = "SELECT * FROM student WHERE RegNo = $RegNo";
$student = $conn->query($student_query)->fetch_assoc();

    // Fetch active quiz
    $active_quiz_query = "SELECT * FROM quiz_details WHERE IsActive = 1 LIMIT 1";
    $active_quiz = $conn->query($active_quiz_query)->fetch_assoc();

    // Fetch all quizzes and student scores
    $regnoprefix = substr($RegNo, 0, 9);

    //get quizzes with same regno
    $all_quiz_query = "SELECT DISTINCT q.Quiz_id
    FROM quiz_details q
    JOIN student s ON q.Quiz_id = s.QuizId
    WHERE LEFT(s.RegNo, 9) = $regnoprefix";

    $all_quiz_result = $conn->query($all_quiz_query);


    
    $quizzes_query = "
    SELECT 
            q.Quiz_Id, 
            q.QuizName, 
            CASE 
                WHEN s.RegNo IS NOT NULL THEN s.percentage 
                ELSE NULL 
            END AS percentage
            FROM quiz_details q
            LEFT JOIN student s ON q.Quiz_Id = s.QuizId AND s.RegNo = '$RegNo'
            WHERE s.RegNo = '$RegNo' 
            OR q.Quiz_id IN ($all_quiz_query)
            GROUP BY q.Quiz_Id, q.QuizName";
    $quizzes_result = $conn->query($quizzes_query);

        // Prepare data for charts
    $scores = [];
    $percentages = [];
    $quiz_labels = [];
    // $completion_times = [];

    while ($quiz = $quizzes_result->fetch_assoc()) {
        $quiz_labels[] = $quiz['QuizName'];
        $scores[] = $quiz['Score'];
        $percentages[] = $quiz['percentage'];
        $completion_times[] = $quiz['Time'];
    }
    $RegNo = $_SESSION['RegNo'];
    $query = "SELECT Name, RegNo, Department, Section,Year 
            FROM users WHERE RegNo = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $RegNo);
    $stmt->execute();
    $stmt->bind_result($name, $RegNo, $department, $section, $year, $time);
    $result = $stmt->get_result();
    $studentDetails = $result->fetch_assoc();

    // Fetching student's scores
    $query = "
        SELECT q.QuizName, s.Score
        FROM student s
        JOIN quiz_details q ON s.QuizId = q.Quiz_id
        WHERE s.RegNo = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $RegNo);
    $stmt->execute();
    $results = $stmt->get_result();
    
    $quizLabels = [];
    $quizScores = [];
    
    while ($row = $results->fetch_assoc()) {
        $quizLabels[] = $row['QuizName'];
        $quizScores[] = $row['Score'];

    }

    //leaderboard
    $leaderboardQuery = mysqli_query($conn, "
            SELECT q.Quiz_id, q.QuizName, s.Name, s.Score
            FROM student s
            JOIN quiz_details q ON s.QuizId = q.Quiz_id
            ORDER BY q.Quiz_id, s.Score DESC
        ");

        $leaderboardData = [];
        while ($row = mysqli_fetch_assoc($leaderboardQuery)) {
            $leaderboardData[$row['Quiz_id']]['QuizName'] = $row['QuizName'];
            $leaderboardData[$row['Quiz_id']]['TopStudents'][] = [
                'Name' => $row['Name'],
                'Score' => $row['Score']
            ];
        }

        //piechart
        $sql = "SELECT q.QuizName, s.Time AS TotalTime 
            FROM student s
            JOIN quiz_details q ON s.QuizId = q.Quiz_id AND s.RegNo = $RegNo
            GROUP BY q.QuizName";

        $result = $conn->query($sql);

        $quizNames = [];
        $totalTimes = [];

        while ($row = $result->fetch_assoc()) {
            $quizNames[] = $row['QuizName'];
            $totalTimes[] = $row['TotalTime'];
        }

        //for all quizes display 
        
        // $sqlStudentDept = "SELECT Department FROM users WHERE RegNo = '$RegNo'";
        // $resultStudentDept = $conn->query($sqlStudentDept);
        // $rowStudentDept = $resultStudentDept->fetch_assoc();
        // $studentDept = $rowStudentDept['Department'];

        $sqlQuizzes = "
            SELECT DISTINCT q.Quiz_id, q.QuizName, s.percentage
            FROM quiz_details q
            INNER JOIN student s ON q.Quiz_id = s.QuizId AND s.RegNo = '$RegNo'
            WHERE s.RegNo = '$RegNo' 
        ";
        
        $resultQuizzes = $conn->query($sqlQuizzes);

        $quiz_labels = [];
        $scores = [];

        while ($quiz = $resultQuizzes->fetch_assoc()) {
            $quiz_labels[] = $quiz['QuizName'];
            $scores[] = $quiz['percentage'] ? $quiz['percentage'] : 'Not Attempted';
        }

        //progress circle
        $sqlQuizzes = "SELECT * FROM quiz_details";
        $resultQuizzes = $conn->query($sqlQuizzes);

        $sqlStudentDetails = "SELECT RegNo, QuizId, percentage, Time FROM student WHERE RegNo = $RegNo";
        $resultStudentDetails = $conn->query($sqlStudentDetails);

        $studentPercentage = 0;
        if ($resultStudentDetails && $resultStudentDetails->num_rows > 0) {
            $studentDetail = $resultStudentDetails->fetch_assoc();
            $studentPercentage = $studentDetail['percentage'];
        }

    ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Quiz Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.3.0/chart.min.js"></script>

    <style>
       body { 
    font-family: Arial, sans-serif; 
    margin: 0; 
    padding: 10px; 
    background: #fff; 
    display: flex; 
    position: relative; /* Ensure body does not take full width */
}

.container { 
    padding: 50px;
    margin-left: 310px;
    /* margin-top:10px;  */
    /* background-color: yellow; */
    scale: 0.99; 

}
/* 
.header { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    background-color: #13274F;
    color: white; 
    padding: 15px;
    width: 900px;
    height: 200px; 
    border-radius: 8px; 
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
} */
.greeting-block {
      display: flex;
      justify-content: space-between; 
    align-items: center; 
    background: linear-gradient(170deg,rgba(22, 77, 79, 0.85), #13274F);
    color: white; 
    padding: 15px;
    width: 900px;
    height: 200px; 
    border-radius: 8px; 
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .greeting-text {
        padding:20px;
      flex: 1;
      margin-right: 20px;
    }
    .greeting-text h1 {
      margin: 0 0 10px;
    }

.content { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 20px; 
    margin-top: -40px;
    /* background-color: pink; */
}

.card { 
    background:rgb(236, 236, 238); 
    /* #f9f9f9; */
    padding: 15px; 
    border-radius: 8px; 
    box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
    font-size: 16px;
}

.chart-container { 
    width: 100%; 
    padding: 15px; 
    border-radius: 10px; 
    box-shadow: 0 4px 8px rgba(0,0,0,0.1); 
}

.active-quiz {  
    color: black; 
    padding: 20px; 
    border-radius: 10px; 
    box-shadow: 0 4px 8px rgba(0,0,0,0.2); 
    text-align: center; 
    cursor: pointer; 
    transition: transform 0.3s; 
    font-family: Arial, sans-serif;
}

.active-quiz:hover { 
    transform: scale(1.05); 
}

#timePieChart {
    width: 300px !important;
    height: 300px !important;
}

.sidebar {
    width: 290px;
    height: 730px;
    margin-top: -40px; /* Starts below header */
    margin-left: 30px;
    margin-right: 30px;
    background: linear-gradient(250deg,rgba(22, 77, 79, 0.85), #13274F);
    padding: 20px;
    color: black;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    font-family: 'Arial', sans-serif;
    position: fixed; /* Fixed to the side */
    top: 70px; /* Align with the content area */
    left: 0; /* Fixed on the left side of the screen */
    overflow-y: auto; /* Allows scrolling if content overflows */
}

.profile-header {
    /* display :flex; */
    padding: 10px;
    text-align: center;
    position: relative;  
      color:white;

}

.profile-header img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    cursor: pointer;
    transition: transform 0.3s;
}

.profile-header img:hover {
    transform: scale(1.1);
}

.edit-btn {
    top: 10px;
    right: 10px;
    /* background: #13274F; */
    color: #13274F;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
}

.student-info p {
    /* background: #fff; */
    padding: 10px;
    border-radius: 8px;    background-color : #f9f9f9;

    color:black;
    margin: 8px 0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

/* Modal Styling */
.modal {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: linear-gradient(180deg, #13274F,rgb(79, 136, 197),rgb(255, 255, 255));
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    width: 300px;
}

#avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    cursor: pointer;
    border: 2px solid #13274F;
    transition: transform 0.2s;
}

#avatar:hover {
    transform: scale(1.05);
}

.avatar-dropdown {
    display: none;  /* Hidden by default */
    position: absolute;
    top: 90px;      /* Position below the avatar */
    left: 0;
    background-color: white;
    border: 1px solid #ccc;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    padding: 10px;
    z-index: 1000;
}

.avatar-dropdown img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    margin: 5px;
    cursor: pointer;
    transition: transform 0.2s, border 0.2s;
    border: 2px solid transparent;
}

.avatar-dropdown img:hover {
    transform: scale(1.1);
    border: 2px solid #13274F;
}
.avatar-grid img {
    width: 70px;
    height: 70px;
    margin: 10px;
    border-radius: 50%;
    cursor: pointer;
    transition: transform 0.3s;
}

.avatar-grid img:hover {
    transform: scale(1.2);
}

.sidebar-title {
    
    font-size: 1.5rem;
    margin-bottom: 15px;
    background-color : #f9f9f9;
    color: #333;
    text-align: center;
    border-bottom: 2px solid #13274F;
    padding-bottom: 10px;
}

.active-btn{
  background-color: #13274F;
  margin-top: 0px;
  color: #fff;
  padding: 10px 15px;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 500;
  cursor: pointer;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
  transition: background-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
  }
  
  .active-btn:hover {
  background-color: #fff;
  color: #13274F;
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
  transform: scale(1.05);
  }
  
  .active-btn:active {
  transform: scale(0.98);
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
  }
  .leaderboard {
    /* background-color: pink; */
    padding: 20px;
    height: auto;
    border-radius: 10px;
    text-align: center;
}


.nav-buttons {
    display: flex;
    justify-content: center;
    margin-bottom: 15px;
}

.nav-buttons button {
    background-color: #13274F;
    color: white;
    border: none;
    padding: 10px 20px;
    margin: 0 10px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: background 0.3s;
}

.nav-buttons button:hover {
    background-color:rgb(255, 255, 255);
    color : #13274F
}

.quiz-container {
    position: relative;
    overflow: hidden;
}

.quiz-leaderboard {
    display: none; 
    /* height:; */
    /* background-color: yellow; */
    transition: transform 0.5s ease-in-out;
}

.quiz-leaderboard.active {
    display: block; /* Show only the active quiz */
}
.quiz-leaderboard canvas {
    width: 290px !important;
    height: 270px !important;    
    
}
#scoreBarChart {
    width: 100% !important;
    height: 100% !important;   
     /* background-color: yellow; */

}

.charts {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.barc {
    width: 100%;
}

.piec {
    
}

.barc > div {
    width: 100%;
    overflow-x: auto; /* Enables horizontal scrolling */
} 


.quiz-list {
    display: flex;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    gap: 20px;
    padding-bottom: 10px;
}

.quiz-item {
    flex-shrink: 0;
    width: 250px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background-color: #f4f4f9;
    padding: 25px;
    height:200px;
    box-sizing: border-box;
    scroll-snap-align: start;
    text-align: center;
    background: linear-gradient(170deg,rgba(22, 77, 79, 0.85),rgb(95, 114, 151));

}

.quiz-item p {
    font-size: 16px;
    margin-bottom: 10px;
    color: #fff;
}

.quiz-item button {
    background:rgb(255, 255, 255);
    color: black;
    border: none;
    padding: 8px 15px;
    cursor: pointer;
    border-radius: 5px;
    transition: background-color 0.3s;
}

.quiz-item button:hover {
    background-color: #fff;
    color:#13274F;
}

.pagination {
    display: flex;
    justify-content: center;
    margin-top: 20px;
}

.pagination button {
    background: #13274F;
    color: white;
    border: none;
    padding: 8px 15px;
    cursor: pointer;
    margin: 0 5px;
    border-radius: 5px;
}

.pagination button:hover {
    background-color: #fff;
    color:#13274F;
}
.quiz{
    margin-top: -350px;
    width: 800px;
    margin-left:450px;
    height: 300px;
}

    .progress-circle {
      position: relative;
      width:auto;
      /* background-color: pink; */
    }
    /* Center text over the canvas */
    .progress-text {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-weight: bold;
      font-size: 20px;
    }
    #progressCanvas{

        /* margin-left:-100px; */
        width: 500px !important;
        height: 250px !important;
            
    }
    .badges{
        width:400px;
        height:300px;
        margin-bottom: 20px;
        /* background-color: green; */
    }
    .recent-activity{
        color:white;
    }
    .recent-activity p{
        font-weight: bold;
        margin-bottom: 10px;
        color:black;
    }
    </style>
</head>
<body>

            <div class="sidebar">
            <div class="profile-header">

                <img src="avatar/a.jpg" id="avatar" alt="Avatar" onclick="toggleAvatarDropdown()">
                <div id="avatarDropdown" class="avatar-dropdown">
                    <img src="avatar/f.jpg" onclick="selectAvatar('avatar/f.jpg')">
                    <img src="avatar/b.png" onclick="selectAvatar('avatar/b.png')">
                    <img src="avatar/c.png" onclick="selectAvatar('avatar/c.png')">
                    <img src="avatar/e.jpg" onclick="selectAvatar('avatar/e.jpg')">
                
                </div>
                <h3 id="studentName"><?php echo $studentDetails['Name']; ?></h3>

            </div>

        <div class="student-info" id="infoView">
        <!-- <button class="edit-btn" onclick="toggleEdit()">✎</button> -->

            <p><strong>Reg No:</strong> <?php echo $studentDetails['RegNo']; ?></p>
            <p><strong>Department:</strong> <?php echo $studentDetails['Department']; ?></p>
            <p><strong>Section:</strong> <?php echo $studentDetails['Section']; ?></p>
            <p><strong>Year:</strong> <?php echo $studentDetails['Year']; ?></p>
            <br><hr>
            <div class="recent-activity">
            <h2>Recent Activity</h2>
            <?php
            // Assuming you have the recent quiz details stored
            $recent_quiz_name = $quiz_labels[count($quiz_labels) - 1]; // Last quiz in the list
            $recent_score = $scores[count($scores) - 1]; // Last quiz score

            // Calculate the performance message
            if ($recent_score >= 90) {
                $performance_message = "Perfect!";
            } elseif ($recent_score >= 70) {
                $performance_message = "Good job! Keep improving!";
            } else {
                $performance_message = "Needs improvement. Keep trying!";
            }
            ?>
            <div class="quiz-item-recent">
                <p><strong>Recent Quiz:</strong> <?php echo $recent_quiz_name; ?></p>
                <p><strong>Score:</strong> <?php echo $recent_score; ?>%</p>
                <p><strong>Status:</strong> <?php echo $performance_message; ?></p>
            </div>
        </div>

        </div>

        <!-- <div id="editForm" style="display: none;">
            <form method="POST" action="update_details.php" enctype="multipart/form-data">
                <label>Name: <input type="text" name="name" value="<?php echo $studentDetails['Name']; ?>"></label>
                <label>Reg No: <input type="text" name="regno" value="<?php echo $studentDetails['RegNo']; ?>"></label>
                <label>Department: <input type="text" name="dept" value="<?php echo $studentDetails['Department']; ?>"></label>
                <label>Section: <input type="text" name="sec" value="<?php echo $studentDetails['Section']; ?>"></label>
                <label>Year: <input type="text" name="year" value="<?php echo $studentDetails['Year']; ?>"></label>
                <button type="submit">Save Changes</button>
            </form>
        </div> -->
    </div>
    <div id="avatarModal" class="modal">
        <div class="modal-content">
            <h3>Select Your Avatar</h3>
            <div class="avatar-grid">
                <img src="avatar/f.jpg" onclick="selectAvatar('avatar/f.jpg')">
                <img src="avatar/b.png" onclick="selectAvatar('avatar/b.png')">
                <img src="avatar/c.png" onclick="selectAvatar('avatar/c.png')">
                <img src="avatar/e.jpg" onclick="selectAvatar('avatar/e.jpg')">
            </div>
            <button onclick="closeAvatarModal()">Close</button>
        </div>
    </div>
    
    
    <div class="container">   
            
            <div class="content">
          
            <div class="greeting-block">
                <div class="greeting-text">
                    <h1>Welcome, Student!</h1>
                    <p>Your current progress: <span id="progressPercentageText"><?php echo $studentPercentage; ?>%</span></p>
                </div>
                <div class="progress-circle" id="progressCircle">
                    <canvas id="progressCanvas"></canvas>
                    <div class="progress-text" id="progressText">0%</div>
                </div>
            </div>


        <!-- onclick="window.location.href='Welcome.php?quiz_id=// -->
        <div class="card">
        <h2>📝 Active Quiz</h2>
        <?php 
        if ($active_quiz) {
            $time = $active_quiz['TimeDuration'];
            list($hours, $minutes, $seconds) = explode(':', $time);
    
            if ($hours == 0 && $minutes == 0) {
                $display_time = intval($seconds) . ' sec';
            } elseif ($hours == 0) {
                $display_time = intval($minutes) . ' min ' . intval($seconds) . ' sec';
            } else {
                $display_time = intval($hours) . ' hr ' . intval($minutes) . ' min';
            }
            echo '<div class="activequiz-item">
                    <div class="activequiz-icon"><i class="fas fa-bolt"></i></div>
                    <div class="activequiz-details">
                        <p class="activequiz-title">' . htmlspecialchars($active_quiz['QuizName']) . '</p>
                        <p class="activequiz-type">' . ($active_quiz['QuizType'] == '0' ? 'MCQ' : 'FillUp') . '</p>
                        <p>' . htmlspecialchars($active_quiz['Active_NoOfQuestions']) . ' Questions | ' . $display_time . '</p>
                        <p>' . htmlspecialchars($active_quiz['TotalMarks']) . ' Marks</p>
                        <button class="active-btn" type="button" onclick="window.location.href=\'Welcome.php\'">Start Quiz</button>;
                    </div>
                </div>';            
        } else {
            echo '<p class="no-quiz">No active quizzes at the moment.</p>';
        }        
        ?>
        </div>
        <!-- </div> -->

        <div class="card" style="margin-top:-40px;height:auto;">
                <h2>Average Performance (Line Chart)</h2>
                <canvas id="avgChart"></canvas>
        </div>

        <div class="leaderboard card">
            <h2>🏆 Leaderboard</h2>           

            <div class="quiz-container">
                <?php foreach ($leaderboardData as $quizId => $quizInfo): ?>
                    <div class="quiz-leaderboard" data-quiz-id="<?php echo $quizId; ?>">
                        <h3>Quiz: <?php echo $quizInfo['QuizName']; ?></h3>
                        <canvas id="chart-<?php echo $quizId; ?>" style="width: 100%; height: 300px;"></canvas>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="nav-buttons">
                <button id="prevBtn">⬅</button>
                <button id="nextBtn">➡</button>
            </div>
        </div>



        <div class="card charts">
            <div class="barc">
                <h2>Scores (Bar Chart)</h2>
                <div style="width: 100%; overflow-x: auto;">
                    <canvas id="scoreBarChart"></canvas>
                </div>
            </div>   
        </div>
        <div class="card charts">

            <div class="piec">
                <h2>Time of Completion (Pie Chart)</h2>
                <canvas id="timePieChart"></canvas>
            </div>         
        </div>

        <div class="card badges">
                <h2>Badges</h2>
                <p>🏆 High Scorer | 🕒 Fast Finisher | 📚 Quiz Master</p>
            </div>
        </div>

        <div class="card quiz">
            <h2>All Quizzes</h2>
            <div class="quiz-list">
                <?php foreach ($quiz_labels as $index => $quiz_name): ?>
                    <div class="quiz-item">
                        <h2 style="color:white;"><?php echo $quiz_name; ?></h2>
                        <p>Score: <?php echo $scores[$index]; ?>%<p>
                        <button onclick="window.location.href='Answers.php?quiz_id=<?php echo $index + 1; ?>'">View Answers</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- <div class="pagination">
                <button>&laquo; Prev</button>
                <button>Next &raquo;</button>
            </div> -->
        </div>

    </div>

    <script>
        // Progress Circle
        // const ctxProgress = document.getElementById('progressCircle').getContext('2d');
        const progressPercent = <?php echo $studentPercentage; ?>;
        const duration = 1000; // Duration of the animation in milliseconds
        
        const canvas = document.getElementById('progressCanvas');
        const ctx_new = canvas.getContext('2d');
        const progressText = document.getElementById('progressText');
        
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const radius = 50;      // Radius of the circle
        const lineWidth = 20;   // Thickness of the arc
        
        let startTime = null;

        function drawBackgroundCircle() {
            ctx_new.beginPath();
            ctx_new.arc(centerX, centerY, radius, 0, Math.PI * 2);
            ctx_new.strokeStyle = 'rgb(226, 226, 228)';
            ctx_new.lineWidth = lineWidth;
            ctx_new.stroke();
        }

        function drawProgressCircle(percent) {
            // Start at -90° (top) and calculate the end angle based on progress
            const endAngle = (-Math.PI / 2) + (Math.PI * 2 * (percent / 100)) + 0.02; 
            ctx_new.beginPath();
            ctx_new.arc(centerX, centerY, radius, -Math.PI / 2, endAngle);
            ctx_new.strokeStyle = 'rgba(111, 128, 157, 0.85)';
            ctx_new.lineWidth = lineWidth;
            ctx_new.lineCap = 'round';
            ctx_new.stroke();
        }

        function animate(timestamp) {
            if (!startTime) startTime = timestamp;
            const elapsed = timestamp - startTime;
            
            const currentProgress = Math.min(elapsed / duration, 1) * progressPercent;
            ctx_new.clearRect(0, 0, canvas.width, canvas.height);
            drawBackgroundCircle();
            drawProgressCircle(currentProgress);
            progressText.textContent = Math.floor(currentProgress) + '%';
            
            if (elapsed < duration) {
            requestAnimationFrame(animate);
            }
        }
        requestAnimationFrame(animate);


        // Line Chart
      
      
        // new Chart(document.getElementById('avgChart'), {
        //     type: 'line',
        //     data: { labels: ['Quiz 1', 'Quiz 2', 'Quiz 3'], datasets: [{ label: 'Average', data: [85, 90, 78], borderColor: '#6200ea', fill: false }] }
        // });
            

        var quizLabels = <?php echo json_encode($quizLabels); ?>;
        var quizScores = <?php echo json_encode($quizScores); ?>;

        var scoresArray = Object.values(quizScores);
        var maxScore = Math.max(...scoresArray);
        var yAxisMax = maxScore + 5;

        var ctx = document.getElementById('avgChart').getContext('2d');

        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(22, 77, 79, 0.85)');
        gradient.addColorStop(1, 'rgba(57, 93, 151, 0.85)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: quizLabels,
                datasets: [{
                    label: 'Performance Average',
                    data: quizScores,
                    borderColor: '#13274F',
                    backgroundColor: gradient,  
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#13274F',
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        display: true,
                        ticks: {
                            color: 'black'
                        }
                    },
                    y: {
                        beginAtZero: false,
                        max: yAxisMax,
                        ticks: {
                            stepSize: 5,
                            color: 'black' 
                        },
                        grid: {
                            color: 'rgba(255, 0, 0, 0.1)' 
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#13274F'
                        }
                    }
                }
            }
        });


        //leaderboard
    
            const leaderboardData = <?php echo json_encode($leaderboardData); ?>;
            const quizIds = Object.keys(leaderboardData);
            let currentQuizIndex = 0;

            // Function to Render Charts
            function renderChart(quizId) {
            const quizInfo = leaderboardData[quizId];
            const names = quizInfo.TopStudents.slice(0, 3).map(student => student.Name);
            const scores = quizInfo.TopStudents.slice(0, 3).map(student => student.Score);

            const ctx = document.getElementById(`chart-${quizId}`).getContext('2d');

        // Gradient Background for Bars
         var gradient1 = ctx.createLinearGradient(0, 0, 0, 400);
            gradient1.addColorStop(0, 'rgba(22, 77, 79, 0.85)');
            gradient1.addColorStop(1, 'rgba(22, 77, 79, 0.4)');

            var gradient2 = ctx.createLinearGradient(0, 0, 0, 400);
            gradient2.addColorStop(0, 'rgba(77, 81, 91, 0.85)');
            gradient2.addColorStop(1, 'rgba(77, 81, 91, 0.4)');

            var gradient3 = ctx.createLinearGradient(0, 0, 0, 400);
            gradient3.addColorStop(0, 'rgba(65, 89, 130, 0.85)');
            gradient3.addColorStop(1, 'rgba(108, 145, 210, 0.4)');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: names,
                    datasets: [{
                        label: 'Scores',
                        data: scores,
                        backgroundColor: [gradient1, gradient2, gradient3],
                        borderColor: '#222',
                        borderWidth: 1,
                        borderRadius: 5, // Rounded Corners
                        hoverBackgroundColor: ['#174D4F', '#4D515B', '#6C91D2'], // Darker on Hover
                        barThickness: 80, // Bar Width
                        maxBarThickness: 100,
                        tension: 0.4 // Smoothness
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x:{
                            display : false,
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            },
                            ticks: {
                                color: '#333',
                                font: { size: 14, weight: 'bold' }
                            }
                        },              
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#fff',
                            titleColor: '#000',
                            bodyColor: '#000',
                            borderColor: '#ccc',
                            borderWidth: 1,
                            padding: 10,
                            cornerRadius: 10
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutBounce' // Bouncy Animation
                    }
                }
            });
        }


            // Show the First Quiz Initially
            document.querySelectorAll('.quiz-leaderboard')[currentQuizIndex].classList.add('active');
            renderChart(quizIds[currentQuizIndex]);

            // Navigation Buttons Logic
            document.getElementById('prevBtn').addEventListener('click', () => {
                document.querySelectorAll('.quiz-leaderboard')[currentQuizIndex].classList.remove('active');
                currentQuizIndex = (currentQuizIndex - 1 + quizIds.length) % quizIds.length;
                document.querySelectorAll('.quiz-leaderboard')[currentQuizIndex].classList.add('active');

                if (!document.getElementById(`chart-${quizIds[currentQuizIndex]}`).dataset.rendered) {
                    renderChart(quizIds[currentQuizIndex]);
                    document.getElementById(`chart-${quizIds[currentQuizIndex]}`).dataset.rendered = true;
                }
            });

            document.getElementById('nextBtn').addEventListener('click', () => {
                document.querySelectorAll('.quiz-leaderboard')[currentQuizIndex].classList.remove('active');
                currentQuizIndex = (currentQuizIndex + 1) % quizIds.length;
                document.querySelectorAll('.quiz-leaderboard')[currentQuizIndex].classList.add('active');

                if (!document.getElementById(`chart-${quizIds[currentQuizIndex]}`).dataset.rendered) {
                    renderChart(quizIds[currentQuizIndex]);
                    document.getElementById(`chart-${quizIds[currentQuizIndex]}`).dataset.rendered = true;
                }
            });



        // Pie Chart
        var quizNames = <?php echo json_encode($quizNames); ?>;
        var totalTimes = <?php echo json_encode($totalTimes); ?>;
        console.log(totalTimes);

        function timeToMinutes(timeStr) {
            const [hours, minutes, seconds] = timeStr.split(':').map(Number);
            return hours * 60 + minutes + Math.floor(seconds / 60); 
        }    

        const totalTimesInMinutes = totalTimes.map(time => timeToMinutes(time));

        
        var colors = ['#e7eaf6', '#a2a8d3', '#38598b', '#113f67','rgba(22, 77, 79, 0.85)', 'rgba(77, 81, 91, 0.85)','rgba(77, 81, 91, 0.4)', 'rgba(108, 145, 210, 0.85)','rgba(108, 145, 210, 0.4)'];
        function getRandomColor() {
            return colors[Math.floor(Math.random() * colors.length)];
        }

        // Random Gradient Generator
        function getRandomGradient(ctx) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 400); // Vertical gradient

            // Pick two random colors
            const color1 = getRandomColor();
            const color2 = getRandomColor();

            gradient.addColorStop(0, color1);
            gradient.addColorStop(1, color2);

            return gradient;
        }
        var barGradients = totalTimesInMinutes.map(() => getRandomGradient(ctx));

        new Chart(document.getElementById('timePieChart'), {
            type: 'pie',
            data: {
                labels: quizNames,
                datasets: [{
                    data: totalTimesInMinutes,
                    backgroundColor: barGradients,  
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: false,
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.raw / total) * 100).toFixed(1);
                                const originalTime = totalTimes[context.dataIndex];  // Show original time
                                return `${context.label}: ${percentage}% (${originalTime})`;
                            }
                        }
                    }
                },
                animation: { animateRotate: true, duration: 1500, easing: 'easeOutBounce' }
            }
        });



        // Bar Chart
        
        var quizLabels = <?php echo json_encode($quizLabels); ?>;
        var quizScores = <?php echo json_encode($quizScores); ?>;


        var ctx = document.getElementById('scoreBarChart').getContext('2d');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: quizLabels, // The labels for the bars
                datasets: [{
                    label: 'Scores',
                    data: quizScores, // The data for the bars
                    backgroundColor: barGradients,
                    borderWidth: 1,
                    borderRadius: 5, // Rounded Corners
                    barThickness: 80, // Bar Width
                    maxBarThickness: 100,
                    tension: 0.4 // Smoothness
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        display: false, // Hides the X-axis
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)' // Light grid color
                        },
                        ticks: {
                            color: '#333',
                            font: { size: 14, weight: 'bold' } // Custom font for Y-axis ticks
                        }
                    }
                },
                plugins: {
                    legend: { display: false }, // Hides the legend
                    tooltip: {
                        backgroundColor: '#fff',
                        titleColor: '#000',
                        bodyColor: '#000',
                        borderColor: '#ccc',
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 10
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutBounce' // Bouncy Animation
                }
            }
        });

                function toggleEdit() {
            const view = document.getElementById('infoView');
            const form = document.getElementById('editForm');
            view.style.display = view.style.display === 'none' ? 'block' : 'none';
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        function toggleAvatarDropdown() {
    const dropdown = document.getElementById('avatarDropdown');
    dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';
}

function selectAvatar(avatarSrc) {
    document.getElementById('avatar').src = avatarSrc;
    document.getElementById('avatarDropdown').style.display = 'none';
}

// Hide dropdown when clicking outside
document.addEventListener('click', function(event) {
    const avatar = document.getElementById('avatar');
    const dropdown = document.getElementById('avatarDropdown');
    if (!avatar.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.style.display = 'none';
    }
});

    let currentScroll = 0;
    const quizList = document.querySelector('.quiz-list');
    const prevButton = document.querySelector('.pagination button:first-child');
    const nextButton = document.querySelector('.pagination button:last-child');

    const scrollAmount = 270; // Adjust based on your quiz item width

    prevButton.addEventListener('click', () => {
        currentScroll -= scrollAmount;
        quizList.scrollTo({
            left: currentScroll,
            behavior: 'smooth'
        });
    });

    nextButton.addEventListener('click', () => {
        currentScroll += scrollAmount;
        quizList.scrollTo({
            left: currentScroll,
            behavior: 'smooth'
        });
    });

    </script>

</body>
</html>
