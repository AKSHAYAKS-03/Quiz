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

    
    $percentages = [];

    while ($quiz = $quizzes_result->fetch_assoc()) {
        $quiz_labels[] = $quiz['QuizName'];
        $percentages[] = $quiz['percentage'];
    }


    //get the percentage of each quiz
    $sql = "SELECT q.QuizName, s.percentage
    FROM quiz_details q
    JOIN student s ON q.Quiz_id = s.QuizId AND s.RegNo = '$RegNo'
    WHERE s.RegNo = '$RegNo'";  
    
    $result = $conn->query($sql);    

    $quiz_labels = [];
    $percentages_stud = [];

    while ($quiz = $result->fetch_assoc()) {
        $quiz_labels[] = $quiz['QuizName'];
        $percentages_stud[] = $quiz['percentage'];
    }
    // // print the pecentages 
    // for($i = 0; $i < count($quiz_labels); $i++){
    //     echo $quiz_labels[$i].": ".$percentages_stud[$i]."<br>";
    // }

    $RegNo = $_SESSION['RegNo'];
    $query = "SELECT Name, RegNo, Department, Section,Year
            FROM users WHERE RegNo = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $RegNo);
    $stmt->execute();
    $stmt->bind_result($name, $RegNo, $department, $section, $year);
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
    $totalScore = 0;
    while ($row = $results->fetch_assoc()) {
        $quizLabels[] = $row['QuizName'];
        $quizScores[] = $row['Score'];    
        $totalScore += $row['Score'];        
    }

    $averageScore = $totalScore / count($quizScores);
    $averageScoreJS = json_encode($averageScore);

    //leaderboard
    $leaderboardQuery = mysqli_query($conn, "
            SELECT q.Quiz_id, q.QuizName, s.Name, s.Score, u.Avatar
            FROM student s
            JOIN quiz_details q ON s.QuizId = q.Quiz_id
            JOIN users u ON s.RegNo = u.RegNo
            WHERE s.RegNo LIKE '913122104%'
            ORDER BY q.Quiz_id, s.Score DESC
        ");

        $leaderboardData = [];
        while ($row = mysqli_fetch_assoc($leaderboardQuery)) {
            $leaderboardData[$row['Quiz_id']]['QuizName'] = $row['QuizName'];    
            $leaderboardData[$row['Quiz_id']]['TopStudents'][] = [
                'Name' => $row['Name'],
                'Score' => $row['Score'],
                'Avatar' => $row['Avatar']
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

        //get avatar of the user 
        $sql = "SELECT Avatar FROM users WHERE RegNo = '$RegNo'";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $avatar = $row['Avatar'];

        $avatar = $avatar ? $avatar : "avatar/a.jpg";



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
        font-family: 'Arial', 'Helvetica', 'Verdana', sans-serif;
        margin: 0; 
        padding: 10px; 
        background: black; 
        display: flex; 
        position: relative; /* Ensure body does not take full width */
        /* bacjkground-color: #fff; */
        /* scale : 0.95; */
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
        background: linear-gradient(170deg,rgba(35, 121, 124, 0.85),rgb(52, 82, 140));
        color: white; 
        /* padding: 15px; */
        width: 858px;
        height:250px; 
        border-radius: 8px; 
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .slider-container {        
      position: relative;
      width: 900px;
      height: 300px; 
      overflow: hidden;
    }
    .greeting-text {
        /* background-color:pink; */
         /* padding:20px; */
      flex: 1;
      margin-left:60px;
      margin-right: 40px;
    }
    .greeting-text h1 {
      margin: 0 0 10px;
    }
  
    .slides {
      display: flex;
      width: 300%; /* Adjust if you have more or fewer slides */
      height: 100%;
      transition: transform 0.5s ease-in-out;
    }
    /* Each slide takes full viewport size */
    .slide {
      width: 100vw;
      height: 100vh;
      flex-shrink: 0;
      padding: 20px;
      background-color: #fff;
    }
      
    /* Navigation arrow buttons */
    .arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background : transparent;
      color: #fff;
      border: none;
      font-size: 2rem;
      padding: 10px;
      cursor: pointer;
      z-index: 1000;
    }
    .arrow.left {
      left: 20px;
    }
    .arrow.right {
      right: 20px;
    }

.content { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 20px; 
    margin-top: -40px;
    /* background-color: pink; */
}

.card { 
    background:#fff; 
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
    font-family: 'Arial', 'Helvetica', 'Verdana', sans-serif;
}

.active-quiz:hover { 
    transform: scale(1.05); 
}



.sidebar {
    width: 290px;
    height: 800px;
    margin-top: -40px; /* Starts below header */
    margin-left: 30px;
    margin-right: 30px;
    background-color : rgb(255, 255, 255);
    /* background: linear-gradient(250deg,rgba(22, 77, 79, 0.85), #13274F); */
    padding: 20px;
    color: black;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    font-family: 'Arial', 'Helvetica', 'Verdana', sans-serif;
    position: fixed; 
    
    top: 70px; /* Align with the content area */
    left: 0; /* Fixed on the left side of the screen */
    overflow-y: auto; /* Allows scrolling if content overflows */
}

.profile-header {
    /* display :flex; */
    padding:40px;
    text-align: center;
    position: relative;  
    color:black;
    margin-top:40px;
    /* background-color:blue; */

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
    /* border-radius: 8px;     */
    /* background-color : #f9f9f9; */
    color:black;
    margin: 8px 0;
    /* box-shadow: 0 2px 5px rgba(0,0,0,0.1); */
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


  .leaderboard {
    /* background-color: pink; */
    /* margin-top: -20px; */
    padding: 20px;
    width: auto;
    height: auto;
    border-radius: 10px;
    text-align: center;
    align-items: center;
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
    /* height:80%; */
    /* background-color: yellow; */
    transition: transform 0.5s ease-in-out;
}

.quiz-leaderboard.active {
    display: block; /* Show only the active quiz */
}
.quiz-leaderboard canvas {
    /* margin-top : -0px; */
    /* display: block; */
    /* background-color: green; */

    width: 290px !important;
    height: 300px !important;    
    
}
#scoreBarChart {
    width: 100% !important;
    height: 250px !important;       
    /* background-color: yellow; */

}
#timePieChart {
    width: 200px !important;
    height: 200px !important;
}
.charts {
    display: flex;
    width: 150%;
    /* margin-left:20px; */
    /* background-color:rgb(255, 0, 0); */
    justify-content: space-between;
    align-items: center;

}
.badges{
        width: 80%;
        height:300px;
        margin-left: 20px;
        /* background-color: green; */
    }

.barc {
    width: 100%;
    margin-left:20px;

    height: 300px;
    overflow-x: auto;    
}

.piec {
    width: 80%;
    height: 300px;
}

.barc > div {
    width: 100%;
    overflow-x: auto; 
} 


.quiz-list {
    display: flex;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    gap: 20px;
    width: 100%;
    padding-bottom: 10px;
}

.quiz-item {
    flex-shrink: 0;
    width: 250px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background-color: #f9f9f9 ;
    padding: 25px;
    height:200px;
    box-sizing: border-box;
    scroll-snap-align: start;
    text-align: center;
    /* background: linear-gradient(170deg,rgba(22, 77, 79, 0.85),rgb(95, 114, 151)); */

}

.quiz-item p {
    font-size: 16px;
    margin-bottom: 10px;
    color: black;
}

.quiz-item button {
    background:#13274F;
    color: white;
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
    margin-top: 20px;
    width: 98%;
    /* margin-left:450px; */
    height: 350px;
}

    .progress-circle {
      position: relative;
      width:300px;
      /* background-color: pink; */
      margin-right: 20px
    }
    /* Center text over the canvas */
    .progress-text {
      position: absolute;
      top: 50%;
      left: 60%;
      transform: translate(-50%, -50%);
      font-weight: bold;
      font-size: 20px;
    }
    #progressCanvas{

        /* margin-left:-100px; */
        width: 350px !important;
        height: 190px !important;
            
    }
   
    .recent-activity{
        color:black;
    }
    .recent-activity p{
        font-weight: bold;
        margin-bottom: 10px;
        color:black;
    } 
    .active-btn{
  background-color: #13274F;
  margin-top: 0px;
  color: #fff;
  padding: 10px 15px;
  border: none;
  border-radius: 8px;
  font-size: 18px;
  font-weight: 500;
  cursor: pointer;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
  transition: background-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
  animation: pulse 2s infinite ease-in-out; 
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
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.15); } 
        100% { transform: scale(1); }
    }
    .fullcontainer{
        background-color:rgb(236, 236, 238);
    }
    .active_quiz{
        /* background-color:rgb(211, 35, 35); */
        /* height: 250px; */
        
    }
    </style>
</head>
<body>
<div class ="fullcontainer">
        <div class="sidebar">
            <div class="profile-header">

            <!-- <div class="imgg" style="    background: linear-gradient(170deg,rgba(22, 77, 79, 0.85),rgb(95, 114, 151)); -->
            <img src="<?php echo htmlspecialchars($avatar); ?>" id="avatar" alt="Avatar" onclick="toggleAvatarDropdown()">
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
    
    <div class="container">   
            
            <div class="content">
          
            <div class="slider-container">
                <div class="slides">
                <!-- Slide 1: Greeting Block -->
                <div class="slide">
                    <div class="greeting-block">
                    <div class="greeting-text">
                        <h1>Welcome, <?php echo $studentDetails['Name']; ?></h1>
                        <p>Your current progress:
                        <span id="progressPercentageText"><?php echo $studentPercentage; ?>%</span>
                        </p>
                    </div>
                    <div class="progress-circle" id="progressCircle">
                        <canvas id="progressCanvas"></canvas>
                        <div class="progress-text" id="progressText">0%</div>
                    </div>
                    </div>
                </div>
                <!-- Slide 2: Additional Information -->
                <div class="slide">
                    <h2>Additional Information</h2>
                    <p>Here you can display more dashboard details, announcements, or other info.</p>
                </div>
                <!-- Slide 3: More Details -->
                <div class="slide">
                    <h2>More Details</h2>
                    <p>This slide can include further details, charts, or other data relevant to the student.</p>
                </div>
                </div>
                <!-- Navigation Arrows -->
                <button class="arrow left" id="prevBtn">&#8592;</button>
                <button class="arrow right" id="nextBtn">&#8594;</button>
            </div>

        <!-- onclick="window.location.href='Welcome.php?quiz_id=// -->
        
        <!-- </div> -->        

        <div class="card active_quiz">
        <h1>Active Quiz</h1>
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
                        <p class="activequiz-title"><h3>' . htmlspecialchars($active_quiz['QuizName']).'</h3> '. ($active_quiz['QuizType'] == '0' ? 'MCQ' : 'FillUp') . '</p>
                        <p>' . htmlspecialchars($active_quiz['Active_NoOfQuestions']) . ' Questions | ' . $display_time . '</p>
                        <p>' . htmlspecialchars($active_quiz['TotalMarks']) . ' Marks</p>
                        <center><button class="active-btn" type="button" onclick="window.location.href=\'Welcome.php\'">Start Quiz</button></center>
                    </div>
                </div>';
        } else {
            echo '<p class="no-quiz">No active quizzes at the moment.</p>';
        }        
        ?>
        </div>
        <div class="card" style="margin-top:0px;height:auto;">
                <h2>Average Performance (Line Chart)</h2>
                <canvas id="avgChart"></canvas>
        </div>
        <div class="leaderboard card">
            <h2>Leaderboard</h2>           

            <!-- <div class="quiz-container"> -->
                <?php foreach ($leaderboardData as $quizId => $quizInfo): ?>
                    <div class="quiz-leaderboard" data-quiz-id="<?php echo $quizId; ?>">
                        <h3>Quiz: <?php echo $quizInfo['QuizName']; ?></h3>
                       <center> <canvas id="chart-<?php echo $quizId; ?>" style="width: 100%; height: 300px;"></canvas></center>
                    </div>
                <?php endforeach; ?>
            <!-- </div> -->
            <div class="nav-buttons">
                <button id="prev_Btn">⬅</button>
                <button id="next_Btn">➡</button>
            </div>
        </div>
       
        <div class="card charts">
                
        <div class="card piec">
            <h2>Time of Completion (Pie Chart)</h2>
            <canvas id="timePieChart"></canvas>
        </div>         
        <div class="card barc">
                <h2>Scores (Bar Chart)</h2>
                    <canvas id="scoreBarChart"></canvas>
        </div>
        <div class="card badges">
                <h2>Badges</h2>
                <p>🏆 High Scorer | 🕒 Fast Finisher | 📚 Quiz Master</p>
        </div>
        </div>

        </div>


      
        <div class="card quiz">
            <h2>All Quizzes</h2>
            <div class="quiz-list">
                <?php foreach ($quiz_labels as $index => $quiz_name): ?>
                    <div class="quiz-item">
                        <h2 style="color:black;"><?php echo $quiz_name; ?></h2>
                        <p>Score: <?php echo $scores[$index]; ?>%<p>
                        <button onclick="window.location.href='Answers.php?quiz_id=<?php echo $index + 1; ?>'">View Answers</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="pagination">
                <button>&laquo; Prev</button>
                <button>Next &raquo;</button>
            </div>
        </div>

    </div>
</div>    

    <script>

        //slides
        const slides = document.querySelector('.slides');
        const totalSlides = document.querySelectorAll('.slide').length;
        let currentSlide = 0;

        document.getElementById('nextBtn').addEventListener('click', () => {
        currentSlide = (currentSlide + 1) % totalSlides;
        updateSlidePosition();
        });

        document.getElementById('prevBtn').addEventListener('click', () => {
        currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
        updateSlidePosition();
        });

        function updateSlidePosition() {
        slides.style.transform = `translateX(-${currentSlide * 100}vw)`;
        }

        // Progress Circle
        function renderProgressChart() {
            const canvas = document.getElementById('progressCanvas');
            
        const progressPercent = <?php echo $studentPercentage; ?>;
        const duration = 1000; 
        
        const ctx_new = canvas.getContext('2d');
        if (canvas.chart) {
                canvas.chart.destroy();
                delete canvas.chart;
            }
            
        const progressText = document.getElementById('progressText');
        
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const radius = 50;      
        const lineWidth = 20; 
        
        let startTime = null;

        function drawBackgroundCircle() {
            ctx_new.beginPath();
            ctx_new.arc(centerX, centerY, radius, 0, Math.PI * 2);
            ctx_new.strokeStyle = 'rgb(189, 189, 189)';
            ctx_new.lineWidth = lineWidth;
            ctx_new.stroke();
        }

        function drawProgressCircle(percent) {
            const endAngle = (-Math.PI / 2) + (Math.PI * 2 * (percent / 100)) + 0.02; 
            ctx_new.beginPath();
            ctx_new.arc(centerX, centerY, radius, -Math.PI / 2, endAngle);
            ctx_new.strokeStyle = 'rgba(255, 255, 255, 0.85)';
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

    }
        
    // Line Chart    
    
    function renderAvgChart() {
            const canvas = document.getElementById('avgChart');
                                
        var quizLabels = <?php echo json_encode($quizLabels); ?>;
        var quizScores = <?php echo json_encode($quizScores); ?>;
        var percentages = <?php echo json_encode($percentages_stud); ?>.map(parseFloat); 
        // console.log(percentages);

        var maxPercentage = Math.max(...percentages);
        var yAxisMax = Math.min(100, maxPercentage + 5); 

        var ctx = document.getElementById('avgChart').getContext('2d');

        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(93, 128, 129, 0.85)');
        gradient.addColorStop(1, 'rgba(83, 113, 160, 0.85)');
        if (canvas.chart) {
                canvas.chart.destroy();
                delete canvas.chart;
            }
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: quizLabels,
                datasets: [{
                    label: 'Performance Average',
                    borderColor: '#13274F',
                    backgroundColor: gradient,
                    data: percentages,  
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
                            color: 'rgba(255, 255, 255, 0.1)' 
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
    }

        //leaderboard
    
        const leaderboardData = <?php echo json_encode($leaderboardData); ?>;
        const quizIds = Object.keys(leaderboardData);
        let currentQuizIndex = 0;

        function renderChart(quizId) {
            const canvas = document.getElementById(`chart-${quizId}`);
          

            const quizInfo = leaderboardData[quizId];
            let topStudents = quizInfo.TopStudents.slice(0, 3);
            if (topStudents.length === 3) {
                topStudents = [ topStudents[1], topStudents[0], topStudents[2] ];
            }
            const labels = topStudents.map((student, index) => {
                if(index === 0) return `2nd: ${student.Name}`;
                if(index === 1) return `1st: ${student.Name}`;
                if(index === 2) return `3rd: ${student.Name}`;
                return student.Name;
            });
            const scores = topStudents.map(student => student.Score);
            const avatars = topStudents.map(student => student.Avatar);
            const ctx_bar = canvas.getContext('2d');
            var yAxisMax = Math.max(...scores) + 5;


            var gradient1 = ctx_bar.createLinearGradient(0, 0, 0, 400);
            gradient1.addColorStop(0, 'rgba(22, 77, 79, 0.85)');
            gradient1.addColorStop(1, 'rgba(22, 77, 79, 0.4)');

            var gradient2 = ctx_bar.createLinearGradient(0, 0, 0, 400);
            gradient2.addColorStop(0, 'rgba(77, 81, 91, 0.85)');
            gradient2.addColorStop(1, 'rgba(77, 81, 91, 0.4)');

            var gradient3 = ctx_bar.createLinearGradient(0, 0, 0, 400);
            gradient3.addColorStop(0, 'rgba(65, 89, 130, 0.85)');
            gradient3.addColorStop(1, 'rgba(108, 145, 210, 0.4)');
            if (canvas.chart) {
                canvas.chart.destroy();
                delete canvas.chart;
            }
            
            const newChart = new Chart(ctx_bar, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Scores',
                        data: scores,
                        backgroundColor: [gradient1, gradient2, gradient3],
                        borderColor: '#222',
                        borderWidth: 1,
                        borderRadius: 5, 
                        hoverBackgroundColor: ['#174D4F', '#4D515B', '#6C91D2'], 
                        barThickness: 80, 
                        maxBarThickness: 100,
                        tension: 0.4 
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            display : false,
                        },
                        y: {
                            beginAtZero: true,
                            max: yAxisMax,
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
                        easing: 'easeOutBounce' 
                    }
                },
                plugins: [{
                    id: 'customAvatarPlugin',
                    beforeDraw: (chart) => {
                        const ctx = chart.ctx;
                        const chartArea = chart.chartArea;
                        const bars = chart.getDatasetMeta(0).data;

                        bars.forEach((bar, index) => {
                            const avatarSize = 40;
                            const x = bar.x - avatarSize / 2;
                            const y = bar.y - avatarSize - 10; 

                            const img = new Image();
                            img.src = avatars[index]; 
                            img.onload = function () {
                                ctx.drawImage(img, x, y, avatarSize, avatarSize);
                            };
                        });
                    }
                }]
            });

            canvas.chart = newChart;
        }          

            document.querySelectorAll('.quiz-leaderboard')[currentQuizIndex].classList.add('active');
            renderChart(quizIds[currentQuizIndex]);

            document.getElementById('prev_Btn').addEventListener('click', () => {
                document.querySelectorAll('.quiz-leaderboard')[currentQuizIndex].classList.remove('active');
                currentQuizIndex = (currentQuizIndex - 1 + quizIds.length) % quizIds.length;
                document.querySelectorAll('.quiz-leaderboard')[currentQuizIndex].classList.add('active');

                if (!document.getElementById(`chart-${quizIds[currentQuizIndex]}`).dataset.rendered) {
                    renderChart(quizIds[currentQuizIndex]);
                    document.getElementById(`chart-${quizIds[currentQuizIndex]}`).dataset.rendered = true;
                }
            });

            document.getElementById('next_Btn').addEventListener('click', () => {
                document.querySelectorAll('.quiz-leaderboard')[currentQuizIndex].classList.remove('active');
                currentQuizIndex = (currentQuizIndex + 1) % quizIds.length;
                document.querySelectorAll('.quiz-leaderboard')[currentQuizIndex].classList.add('active');

                if (!document.getElementById(`chart-${quizIds[currentQuizIndex]}`).dataset.rendered) {
                    renderChart(quizIds[currentQuizIndex]);
                    document.getElementById(`chart-${quizIds[currentQuizIndex]}`).dataset.rendered = true;
                }
            });



        // Pie Chart
        function renderPieChart() {
            var canvas = document.getElementById('timePieChart');
          
        var quizNames = <?php echo json_encode($quizNames); ?>;
        var totalTimes = <?php echo json_encode($totalTimes); ?>;
        // console.log(totalTimes);

        function timeToMinutes(timeStr) {
            const [hours, minutes, seconds] = timeStr.split(':').map(Number);
            return hours * 60 + minutes + (seconds / 60); 
        }

        const totalTimesInMinutes = totalTimes.map(time => timeToMinutes(time));    
        // console.log(totalTimesInMinutes);

            var colors = ['#e7eaf6', '#a2a8d3', '#38598b', '#113f67', 'rgba(22, 77, 79, 0.85)', 'rgba(77, 81, 91, 0.85)', 'rgba(77, 81, 91, 0.4)', 'rgba(108, 145, 210, 0.85)', 'rgba(108, 145, 210, 0.4)'];

            function getRandomColor() {
                return colors[Math.floor(Math.random() * colors.length)];
            }

            var ctx_pie = document.getElementById('timePieChart').getContext('2d');

            function getRandomGradient(ctx_pie) {
                const gradient = ctx_pie.createLinearGradient(0, 0, 0, 400); 
                const color1 = getRandomColor();
                const color2 = getRandomColor();

                gradient.addColorStop(0, color1);
                gradient.addColorStop(1, color2);

                return gradient;
            }

            var barGradients = totalTimesInMinutes.map(() => getRandomGradient(ctx_pie));
            if (canvas.chart) {
                canvas.chart.destroy();
            }
            new Chart(ctx_pie, {
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
                                    const originalTime = totalTimes[context.dataIndex];  
                                    return `${context.label}: ${percentage}% (${originalTime})`;
                                }
                            }
                        }
                    },
                    animation: { 
                        animateRotate: true, 
                        duration: 1500, 
                        easing: 'easeOutBounce' 
                    }
                }
            });
         
        }

        // Bar Chart
        function renderScoreChart() {
            var canvas = document.getElementById('scoreBarChart');
          
            
            var quizLabels = <?php echo json_encode($quizLabels); ?>;
            var quizScores = <?php echo json_encode($quizScores); ?>;
            var colors2 = ['#e7eaf6', '#a2a8d3', '#38598b', '#113f67', 'rgba(22, 77, 79, 0.85)', 'rgba(77, 81, 91, 0.85)', 'rgba(77, 81, 91, 0.4)', 'rgba(108, 145, 210, 0.85)', 'rgba(108, 145, 210, 0.4)'];

            function getRandomColor2() {
                return colors2[Math.floor(Math.random() * colors2.length)];
            }

            function getRandomGradient2(ctx_pie) {
                const gradient = ctx_pie.createLinearGradient(0, 0, 0, 400);
                const color1 = getRandomColor2();
                const color2 = getRandomColor2();
                gradient.addColorStop(0, color1);
                gradient.addColorStop(1, color2);
                return gradient;
            }

            var ctx_bar = document.getElementById('scoreBarChart').getContext('2d');

            // Create an array of gradients for each bar
            var barGradients = quizScores.map(() => getRandomGradient2(ctx_bar));
            if (canvas.chart) {
                canvas.chart.destroy();
                delete canvas.chart;
            }
            new Chart(ctx_bar, {
                type: 'bar',
                data: {
                    labels: quizLabels,
                    datasets: [{
                        label: 'Scores',
                        data: quizScores,
                        backgroundColor: barGradients, // Apply different gradients for each bar
                        borderWidth: 1,
                        borderColor: 'black',
                        borderRadius: 5,
                        barThickness: 80,
                        maxBarThickness: 100,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            display: false,
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
                        }
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
                        easing: 'easeOutBounce'
                    }
                }
            });
        }

        //avatar 
        function toggleAvatarDropdown() {
            const dropdown = document.getElementById('avatarDropdown');
            dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';
        }

        function selectAvatar(avatarSrc) {

            if (!confirm("Are you sure you want to update your avatar?")) {
                return;
            }

            document.getElementById('avatar').src = avatarSrc;
            document.getElementById('avatarDropdown').style.display = 'none';

            let xhr = new XMLHttpRequest();
            xhr.open("POST", "update_avatar.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                        console.log(xhr.responseText); 
                        alert(xhr.responseText);
                        location.reload();
                    }
                };
                xhr.send("avatar=" + encodeURIComponent(avatarSrc));
            }


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

        const scrollAmount = 270; 

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

        //for scroll animation
        window.addEventListener('scroll', () => {
            const rect = document.getElementById('timePieChart')?.getBoundingClientRect();
            const rect2 = document.getElementById('scoreBarChart')?.getBoundingClientRect();
            // const rect3 = document.getElementById('progressCanvas')?.getBoundingClientRect();
            // const rect4 = document.getElementById('avgChart')?.getBoundingClientRect();
            
            if (rect && rect.top < window.innerHeight && rect.bottom > 0) {
                renderPieChart();
            }
            if (rect2 && rect2.top < window.innerHeight && rect2.bottom > 0) {
                renderScoreChart(); 
            }
            // if (rect3 && rect3.top > window.innerHeight && rect3.bottom  0) {
            //     renderProgressChart();
            // }
            // if (rect4 && rect4.top < window.innerHeight && rect4.bottom > 0) {
            //     renderAvgChart();
            // }

        });

        
       </script>

</body>
</html>
