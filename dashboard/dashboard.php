<?php
include_once '../core/header.php';

if (!isset($_SESSION['login']) || empty($_SESSION['login'])) {
    header('Location: ../index.php');
    exit;
}

    $RegNo = $_SESSION['RegNo'];
    $name = $_SESSION['Name'];
    $activeQuizId = $_SESSION['active'];
    
if (isset($_POST['update_password'])) {
    $oldPassword = $_POST['old_password'];
    $newPassword = $_POST['new_password'];

    $query = "SELECT Password FROM users WHERE RegNo = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $RegNo);
    $stmt->execute();
    $result =  $stmt->get_result()->fetch_assoc(); 
    $password = $result['Password'];

    if($password != $oldPassword){
        echo "<script>alert('Incorrect old password.');</script>";
        echo "<script>window.location.href = window.location.href;</script>";
        exit(); 
    }

    if (strlen($newPassword) < 8 || strlen($newPassword) > 12) {
     
        echo "<script>alert('Password length must be between 8-12 characters');</script>";
      
        echo "<script>window.location.href = window.location.href;</script>";
        exit(); 
  	  }
        $updateQuery = "UPDATE users SET Password = ? WHERE RegNo = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('si', $newPassword, $RegNo);
       
        if ($stmt->execute()) {
            echo "<script>alert('Password updated successfully!');</script>";
            echo "<script>window.location.href = window.location.href;</script>";
        } else {
            echo "<script>alert('Error updating password.');</script>";
	    echo "<script>window.location.href = window.location.href;</script>";
        }
    }
    $active_quiz_query = "SELECT * FROM quiz_details WHERE IsActive = 1 LIMIT 1";
    $active_quiz = $conn->query($active_quiz_query)->fetch_assoc();

    $activenoofques = isset($active_quiz["Active_NoOfQuestions"]) ? (int)$active_quiz["Active_NoOfQuestions"] : 0;

    $activemarks  = isset($active_quiz['TotalMarks']) ? (int)$active_quiz['TotalMarks'] : 0;
    $questimer = isset($active_quiz['TimeDuration']) ? (string)$active_quiz['TimeDuration'] : '0:00:00';

    $timeParts = explode(':', $questimer);

    if (count($timeParts) == 2) {
        $hours = 0;
        list($minutes, $seconds) = $timeParts;
    } elseif (count($timeParts) == 3) {
        list($hours, $minutes, $seconds) = $timeParts;
    } else {

        $hours = $minutes = $seconds = 0;
    }


    $active_time = ($hours * 3600) + ($minutes * 60) + $seconds;

    $hours = floor($active_time / 3600); 
    $minutes = floor(($active_time % 3600) / 60); 
    $seconds = $active_time % 60; 

    $timeFormatted = '';
    if ($hours > 0) {
        $timeFormatted .= $hours . ' hr ';
    }
    if ($minutes > 0) {
        $timeFormatted .= $minutes . ' min ';
    }
    if ($seconds > 0 || ($hours == 0 && $minutes == 0)) {
        $timeFormatted .= $seconds . ' sec';
    }

    $_SESSION['timeFormatted'] = $timeFormatted;
	
    $flag = false;
    if($activeQuizId !== 'None'){
        $student_active_quiz_query = "SELECT * FROM student WHERE RegNo = $RegNo AND QuizId = $activeQuizId";
        $student_active_quiz = $conn->query($student_active_quiz_query);
        if($student_active_quiz->num_rows > 0){
            $flag = true;        
        }
    }


    $regnoprefix = substr($RegNo, 0, 9);
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

	//echo "<script>console.log(count($quizLabels))</script>";
	$count = count($quizScores)== 0? 1: count($quizScores);
	$c = count($quizScores)==0?false:true;
    $averageScore = $totalScore / $count;
    $averageScoreJS = json_encode($averageScore);

    //leaderboard
    $leaderboardQuery = mysqli_query($conn, "
        SELECT q.Quiz_id, q.QuizName, u.Name, s.percentage, u.Avatar
        FROM student s
        JOIN quiz_details q ON s.QuizId = q.Quiz_id
        JOIN users u ON s.RegNo = u.RegNo
        WHERE s.RegNo LIKE '$regnoprefix%'
        ORDER BY q.Quiz_id, s.Score DESC , s.Time ASC
    ");

    $leaderboardData = [];
    while ($row = mysqli_fetch_assoc($leaderboardQuery)) {

        $leaderboardData[$row['Quiz_id']]['QuizName'] = $row['QuizName'];    
        $leaderboardData[$row['Quiz_id']]['TopStudents'][] = [
            'Name' => $row['Name'],
            'percentage' => $row['percentage'],
            'Avatar' => "../assets/".$row['Avatar']
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
            
    $sqlQuizzes = "
    SELECT DISTINCT q.Quiz_id, q.QuizName, s.percentage
    FROM quiz_details q
    INNER JOIN student s ON q.Quiz_id = s.QuizId AND s.RegNo = '$RegNo'
    WHERE s.RegNo = '$RegNo' AND q.Quiz_id <> '$activeQuizId'
    ORDER BY q.Quiz_id DESC
    ";
    
    $resultQuizzes = $conn->query($sqlQuizzes);
    
    $quiz_labels = [];
    $quiz_ids = [];  
    $scores = [];
    
    while ($quiz = $resultQuizzes->fetch_assoc()) {
        $quiz_labels[] = $quiz['QuizName'];
        $quiz_ids[] = $quiz['Quiz_id'];  
        $scores[] = $quiz['percentage'] ? $quiz['percentage'] : 'Not Attempted';
    }
    

    //progress circle
    $sqlQuizzes = "SELECT * FROM quiz_details";
    $resultQuizzes = $conn->query($sqlQuizzes);

    $sqlStudentDetails = "SELECT ROUND(AVG(percentage),2) AS percentage FROM student WHERE RegNo = $RegNo";
    $resultStudentDetails = $conn->query($sqlStudentDetails);

    $studentPercentage = 0;
    if ($resultStudentDetails && $resultStudentDetails->num_rows > 0) {
        $studentDetail = $resultStudentDetails->fetch_assoc();
        $studentPercentage = round($studentDetail['percentage']);
    }

    //get avatar of the user 
    $sql = "SELECT Avatar FROM users WHERE RegNo = '$RegNo'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $avatar = $row['Avatar'];

    $avatar = $avatar ? $avatar : "avatar/boy3.jpg";

    //get averagescore for all quizes for all students
    $totalquiz = 0;
    $sql = "
    SELECT 
        q.QuizName, q.Quiz_id,
        AVG(s.Score) AS AverageScore
    FROM 
        student s
    JOIN 
        quiz_details q ON s.QuizId = q.Quiz_id
    WHERE 
        s.RegNo LIKE '$regnoprefix%'
    GROUP BY 
        s.QuizId, q.QuizName
    ORDER BY 
        s.Attended_At;
    ";

    $result = $conn->query($sql);

    $quizIds_avg = [];
    $quizNames = [];
    $averageScores = [];

    while ($row = $result->fetch_assoc()) {
        $totalquiz++;
        $quizIds_avg[] = $row['Quiz_id'];
        $quizNames[] = $row['QuizName'];
        $averageScores[] = $row['AverageScore'];
    }
    echo "<script>console.log($totalquiz)</script>";

    $quizIdsJS = json_encode($quizIds_avg);
    $quizNamesJS = json_encode($quizNames);
    $averageScoresJS = json_encode($averageScores);
  
    // all time Toppers
             
  
    $performers = [];
    $currentStudentYear = $_SESSION['year'];
    $whereConditions = " AND u.year = '$currentStudentYear'"; 
    
    // Query for top performers - fetching more than one
    $performerQuery = $conn->query("SELECT s.RegNo,u.Name, SUM(s.percentage) as avg_percentage,u.Avatar,
                                    SUM(s.Time) AS TotalTime
                                    FROM student s
                                    JOIN users u ON s.RegNo = u.RegNo
                                    JOIN quiz_Details q ON s.QuizId = q.Quiz_id
                                    WHERE u.year = '$currentStudentYear' $whereConditions
                                    GROUP BY s.RegNo
                                    ORDER BY avg_percentage DESC, TotalTime ASC
                                    LIMIT 3"); 
    
    while ($performer = $performerQuery->fetch_assoc()) {
        $performer['avg_percentage'] = number_format((float)$performer['avg_percentage']/ $totalquiz, 2, '.', '');

        $performers[] = $performer;
    }
    
    if (empty($performers)) {
        echo "No top performers found for this year.";
    }
    
    $data['performers'] = $performers;
    
?>
        
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Quiz Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.3.0/chart.min.js"></script>
    <link rel="stylesheet" href="../assets/css/navigation.css"/>
    <link rel="stylesheet" href="../assets/css/dashboard.css"/>
    <style>
        .container { 
            scale: 0.81;  
            transform: scaleY(1.05); 
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="profile-header">

            <!-- <div class="imgg" style="    background: linear-gradient(170deg,rgba(22, 77, 79, 0.85),rgb(95, 114, 151)); -->
            <img src="<?php echo htmlspecialchars($avatar); ?>" id="avatar" alt="Avatar" onclick="toggleAvatarDropdown()">
                <div id="avatarDropdown" class="avatar-dropdown">
                    <img src="../assets/avatar/boy1.png" onclick="selectAvatar('../assets/avatar/boy1.png')">
                    <img src="../assets/avatar/boy2.png" onclick="selectAvatar('../assets/avatar/boy2.png')">
                    <img src="../assets/avatar/boy4.png" onclick="selectAvatar('../assets/avatar/boy4.png')">
                    <img src="../assets/avatar/boy5.png" onclick="selectAvatar('../assets/avatar/boy5.png')">
                    <img src="../assets/avatar/boy6.png" onclick="selectAvatar('../assets/avatar/boy6.png')">
                    <img src="../assets/avatar/boy3.png" onclick="selectAvatar('../assets/avatar/boy3.png')">
                    <img src="../assets/avatar/girl1.png" onclick="selectAvatar('../assets/avatar/girl1.png')">
                    <img src="../assets/avatar/girl2.png" onclick="selectAvatar('../assets/avatar/girl2.png')">
                    <img src="../assets/avatar/girl3.png" onclick="selectAvatar('../assets/avatar/girl3.png')">
                    <img src="../assets/avatar/girl4.png" onclick="selectAvatar('../assets/avatar/girl4.png')">
                    <img src="../assets/avatar/girl7.png" onclick="selectAvatar('../assets/avatar/girl7.png')">
                            
                    </div>
                    <br><br>
                    <h3 id="studentName" style="display:inline"><?php echo $studentDetails['Name']; ?></h3>
                    <a title="Change Password"><button type="button" id="editBtn" onclick="displayEdit()">✎</button></a>
                    </div>

                    <div class="student-info" id="infoView">
                <form method="POST" style='display:none' id="passform">
            <hr>
            <br>
           	 <label for="password" style="display:inline"><strong>Password:</strong></label>              	 
          	  <br><br>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                    <div style="position: relative; width: 220px;">
                        <input type="password" id="old_password" name="old_password" placeholder="Old password" 
                            style="width:100%; height:30px; padding-right: 30px;" required> 
                        <span id="toggleOldPassword" 
                            style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                            <img id="eyeIcon1" src="../assets/icons/eye_show.svg" alt="Show Password" width="18" height="14">
                        </span>
                    </div>

                    <div style="position: relative; width: 220px;">
                        <input type="password" id="new_password" name="new_password" placeholder="New password" 
                            style="width:100%; height:30px; padding-right: 30px;" required>
                            <span id="toggleNewPassword" 
                                style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                <img id="eyeIcon2" src="../assets/icons/eye_show.svg" alt="Show Password" width="18" height="14">
                            </span>
                    </div>
                </div>
          	  <br><br>
          	  
          	 <button type="submit" name="update_password" id="saveBtn">Save</button><hr>
            </form>

            <p><strong>Reg No:</strong> <?php echo $studentDetails['RegNo']; ?></p>
            <p><strong>Department:</strong> <?php echo $studentDetails['Department']; ?></p>
            <p><strong>Section:</strong> <?php echo $studentDetails['Section']; ?></p>
            <p><strong>Year:</strong> <?php echo $studentDetails['Year']; ?></p>
                
        <br><hr>
        <div class="recent-activity">
            <h2>Recent Activity</h2>
            <?php
            $quizIds = json_decode($quizIdsJS, true);
            $quizNames = json_decode($quizNamesJS, true);
            $averageScores = json_decode($averageScoresJS, true);
            
            //	echo '<script>console.log($quiz_labels)</script>';
        
            //echo $c." ".count($quizIds);
            if ($c) {
                $quiz_id_recent = $quizIds[count($quizIds) - 1];
                $recent_quiz_name = $quizNames[count($quizNames) - 1];
                $recent_quiz_avg = $averageScores[count($averageScores) - 1];

                // your recent quiz and score
                $yourrecent = $quizLabels[count($quizLabels) - 1];
                $yourrecentquizscore = $quizScores[count($quizScores) - 1];

                if ($yourrecent == $recent_quiz_name) {
                    if ($yourrecentquizscore >= $recent_quiz_avg) {
                        $performance_message = "Good job! Keep improving!";
                    } else {
                        $performance_message = "Needs improvement. Keep trying!";
                    }
                } else {
                    $performance_message = "You have not attended the recent quiz!";
                }
            ?>
                <div class="quiz-item-recent">
                    <p><strong>Recent Quiz:</strong> <?php echo $recent_quiz_name; ?></p>
                    <p><strong>Score:</strong> <?php echo $yourrecentquizscore; ?></p>
                    <p><strong>Status:</strong> <?php echo $performance_message; ?></p>
                </div>
            <?php
            } else {
            ?>
                <div class="quiz-item-recent">
                    <p>No Quiz</p>
                </div>
            <?php
            }
            ?>
        </div>

        </div>
        <form method="post" action="../auth/Student_logout.php">
            <center>
                <input type="submit" name="logout" value="Log Out" id="logout" class="logout">
            </center>
        </form>
    </div>        
    
    <div class="container">   
            
        <div class="content">
            <div class="greeting-block">
                <div class="greeting-text">
                    <h1>Welcome, </h1>
                    <h1><?php echo $studentDetails['Name']; ?></h1>
                    <p>Your current progress:
                    <span id="progressPercentageText"><?php echo $studentPercentage; ?>%</span>
                    </p>
                </div>
                <!-- <div class="progress-circle" id="progressCircle">
                    <canvas id="progressCanvas"></canvas>
                    <div class="progress-text" id="progressText">0%</div>
                </div> -->
            </div>   
          
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
                        <h3>' . htmlspecialchars($active_quiz['QuizName']) . '</h3>
                        <p>' . ($active_quiz['QuizType'] == '0' ? 'MCQ' : 'FillUp') . '</p>
                        <p>' . htmlspecialchars($activenoofques) . ' Questions | ' . $timeFormatted  . '</p>
                        <p>' . htmlspecialchars($activemarks) . ' Marks</p>';
                        if (!$flag) {
                            echo '<center><button class="active-btn" type="button" onclick="window.location.href=\'../public/Welcome.php\'">Start Quiz</button></center>';
                        } else {
                            echo '<center><button class="inactive-btn" type="button" disabled style="background-color: grey; cursor: not-allowed;">Already Attempted</button></center>';
                        }
                        
                        echo '</div></div>';                   
                } else{
                    echo '<div class="activequiz-item">
                    <div class="activequiz-icon"><i class="fas fa-bolt"></i></div>
                        <div class="activequiz-details"> <br>
                        <center>
                            <h2>The next challenge is 
                                <br>coming soon. 🚀</h2>
                        </center>
                        </div>
                    </div>';
                }           
                ?>
            </div>

            <!-- <div class="review"> -->
                <div class="card quiz">
                    <h2>All Quizzes</h2>
                        <div class="quiz-list">
                            <?php foreach ($quiz_labels as $index => $quiz_name): ?>
                                <div class="quiz-item">
                                    <h2 style="color:black;" title="<?php echo $quiz_name; ?>"><?php echo $quiz_name; ?></h2>
                                    <p>Percentage: <?php echo $scores[$index]; ?>%</p>
                                    <button onclick="window.location.href='../quiz/Answers.php?quiz_id=<?php echo $quiz_ids[$index]; ?>'" class="view-answers">View Answers</button>
                                </div>
                            <?php endforeach; ?>
                        </div>    
                <center><div class="pagination">
                        <button>&laquo; Prev</button>
                        <button>Next &raquo;</button> </div></center>            
                </div>

                <div class="card toppers">
                    <?php if (!empty($data['performers'])) { ?>

                    <h2>🏆 All-Time Toppers</h2>
                    <div class="topper-list">
                    <?php foreach ($data['performers'] as $topper) { ?>
                            <div class="topper-card">
                                <img src="<?php echo htmlspecialchars("../assets/".$topper['Avatar']); ?>" alt="Avatar"></img>
                                <div class="topper-details">
                                    <h3><?php echo htmlspecialchars($topper['Name']) . ' | ' . $topper['avg_percentage'] . '%'; ?></h3>                                  
                                    <p><strong>Reg No:</strong><?php echo htmlspecialchars($topper['RegNo']); ?></p>                              
                                </div>
                            </div>
                    <?php } ?>
                    </div>
                </div>
                <?php } else { ?>
                    <p>No toppers found.</p>
                <?php } ?>
            <!-- </div> -->
        </div>
    </div>
 

    <script>
         document.getElementById('toggleOldPassword').addEventListener('click', function() {
            let passwordInput = document.getElementById('old_password');
            let eyeIcon = document.getElementById('eyeIcon1');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.src = '../assets/icons/eye_hide.svg'; 
            } else {
                passwordInput.type = 'password';
                eyeIcon.src = '../assets/icons/eye_show.svg'; 
            }
        });


        document.getElementById('toggleNewPassword').addEventListener('click', function() {
            let passwordInput = document.getElementById('new_password');
            let eyeIcon = document.getElementById('eyeIcon2');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.src = '../assets/icons/eye_hide.svg'; 
            } else {
                passwordInput.type = 'password';
                eyeIcon.src = '../assets/icons/eye_show.svg'; 
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
        function displayEdit(){
        var form = document.getElementById("passform");
            form.style.display = (form.style.display === "none" || form.style.display === "") ? "block" : "none"; // Toggle visibility
        }

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
            xhr.open("POST", "../student/update_avatar.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                        console.log(xhr.responseText); 
                        alert(xhr.responseText);
                        location.reload();
                    }
                };
                xhr.send("avatar=" + encodeURIComponent("../assets/"+avatarSrc));
            }


        document.addEventListener('click', function(event) {
            const avatar = document.getElementById('avatar');
            const dropdown = document.getElementById('avatarDropdown');
            if (!avatar.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.style.display = 'none';
            }
        });
       
    </script>
</body>
</html>