<?php
session_start();
include 'core_db.php';
include 'header.php';

if (!isset($_SESSION['RegNo'])) {
    header('Location: index.php');
    exit();
}

    $RegNo = $_SESSION['RegNo'];
    $name = $_SESSION['Name'];
    $activeQuizId = $_SESSION['active'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
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

    $active_no_of_questions = isset($active_quiz["Active_NoOfQuestions"]) ? (int)$active_quiz["Active_NoOfQuestions"] : 0;
    $total_no_of_questions = isset($active_quiz["NumberOfQuestions"]) ? (int)$active_quiz["NumberOfQuestions"] : 0;
    $question_mark = isset($active_quiz['QuestionMark']) ? (int)$active_quiz['QuestionMark'] : 0;
    $eachquestimer = isset($active_quiz['QuestionDuration']) ? (string)$active_quiz['QuestionDuration'] : '0:00:00';


    $timeParts = explode(':', $eachquestimer);

    if (count($timeParts) == 2) {
        $hours = 0;
        list($minutes, $seconds) = $timeParts;
    } elseif (count($timeParts) == 3) {
        list($hours, $minutes, $seconds) = $timeParts;
    } else {

        $hours = $minutes = $seconds = 0;
    }

    $eachquestimer_in_seconds = ($hours * 3600) + ($minutes * 60) + $seconds;
    $activenoofques = ($active_no_of_questions == 0) 
        ? $total_no_of_questions 
        : min($active_no_of_questions, $total_no_of_questions);

    $activemarks = $activenoofques * $question_mark;
    $active_time = $activenoofques * $eachquestimer_in_seconds;

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

    if($activeQuizId === 'None'){
        $flag = true;
        $student_active_quiz_query = "SELECT * FROM student WHERE RegNo = $RegNo AND QuizId = $activeQuizId";
        $student_active_quiz = $conn->query($student_active_quiz_query);
        $flag = false;
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

        $averageScore = $totalScore / count($quizScores);
        $averageScoreJS = json_encode($averageScore);

        //leaderboard
        $leaderboardQuery = mysqli_query($conn, "
            SELECT q.Quiz_id, q.QuizName, u.Name, s.percentage, u.Avatar
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
                'percentage' => $row['percentage'],
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
            s.RegNo LIKE '913122104%'
        GROUP BY 
            s.QuizId, q.QuizName;
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
    <link rel="stylesheet" href="css/navigation.css"/>

    <style>
       body { 
            font-family: 'Arial', 'Helvetica', 'Verdana', sans-serif;
            margin: 0; 
            background-color:rgba(16, 16, 83, 0.49);  
            /* background-color:black;  */
            color:black;
            display: flex; 
            position: relative;
            /* bacjkground-color: #fff; */
            overflow-x : hidden;
        }

        .container { 
            margin: -160px 0px;
            margin-left:170px;
            padding :50px 15px;
            background-color: rgb(236, 236, 238);
            height:1780px;
            scale:0.81;            
            border-radius: 8px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);

        }
        .fullcontainer{
            background-color: pink;
        }       
        .greeting-block {
            display: flex;
            align-items: center;     
            background: linear-gradient(170deg,rgba(35, 121, 124, 0.85),rgb(52, 82, 140));
            color: white; 
            /* padding: 15px; */
            width: 1040px;
            height:250px; 
            border-radius: 8px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
            .slider-container {        
            position: relative;
            width: 1080px;
            height: 300px; 
            border-radius: 10px;

            overflow: hidden;
            }
            .greeting-text {
                /* background-color:pink; */
                /* padding:20px; */
            flex: 1;
            margin-left:100px;
            font-size : 20px;
            margin-right: 40px;
            }
            .greeting-text h1 {
            margin: 0 0 10px;
            }
        
            .slides {
            display: flex;
            width: 300%; 
            height: 100%;
            transition: transform 0.5s ease-in-out;
            border-radius: 10px;

            }
            .slide {
            width: 100vw;
            height: 100vh;
            flex-shrink: 0;
            padding: 20px;
            /* background: linear-gradient(180deg,rgba(35, 121, 124, 0.85),rgb(52, 82, 140)); */
            background: #fff;
            border-radius: 10px;

            }
            
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
            min-width:90px; 
            max-width:250px;
            height: 708px;
            margin-top: -52px;
            margin-left:10px; 
            background-color : rgb(255, 255, 255);

            padding: 20px;
            color: black;
            position: fixed; 
            font-size:14px;
            top: 70px; 
            left: 0; 
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            font-family: 'Arial', 'Helvetica', 'Verdana', sans-serif;
        }

        .profile-header {
            /* display :flex; */
            padding:0 20;
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



        .student-info p {
            /* background: #fff; */
            padding: 10px;
            /* border-radius: 8px;     */
            /* background-color : #f9f9f9; */
            color:black;
            margin: 8px 0;
            /* box-shadow: 0 2px 5px rgba(0,0,0,0.1); */
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
            display: none;  
            position: absolute;
            top: 90px;      
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
            margin-left:-460px;
            padding: 20px;
            width: 400px;
            height: auto;
            border-radius: 10px;
            text-align: center;
            align-items: center;
        }


        .nav-buttons{
            margin-top:-50px;
            display: flex;
            justify-content: center;
            margin-bottom: 15px;
        }


        .nav-buttons button{
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
        #saveBtn{
            background: linear-gradient(330deg,rgb(79, 136, 197),rgba(93, 128, 129, 0.85));
            color: white;
            border: none;
            padding: 7px 10px;
            margin-bottom:10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .nav-buttons button:hover{
            background-color:rgb(255, 255, 255);
            color : #13274F
        }

        .quiz-container {
            position: relative;
            overflow: hidden;
        }

        .quiz-leaderboard {
            display: none; 
            height:90%;
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
            height: 400px !important;    
            
        }
        #scoreBarChart {
            width: 600px  !important;
            height: 380px !important;
            /* background-color: yellow; */

        }
        #timePieChart {
            width: 300px !important;
            height: 300px !important;
        }
        #avgChart{
            
        }
        .charts {
            display: flex;
            width: 100%;
            /* margin-left:20px; */
            background:transparent;
            justify-content: space-between;
            align-items: center;

        }
        .badges{
                width: 560px;
                height: 350px;
                margin-top:-380px;
                margin-left: 950px;
                background-color: green;
                background:#fff; 
                /* #f9f9f9; */
                padding: 15px; 
                border-radius: 8px; 
                box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
                font-size: 16px;
                }

        .barc {
            width: 600px;
            margin-left: 20px;
            height: 450px;

            overflow-x: auto;  
            white-space: nowrap; 
        }

        .piec {
            width: 400px;
            height: 450px;
        }
        .line_chart{
            width: 1050px;
            height: auto;
        }        

        .quiz-list {
            display: flex;
            overflow:hidden;

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


        .quiz{
            margin-top: 20px;
            width: 900px;
            /* margin-left:450px; */
            height: 350px;
        }

            .progress-circle {
            position: relative;
            width:300px;
            /* background-color: pink; */
            margin-right: 50px
            }
            /* Center text over the canvas */
            .progress-text {
            position: absolute;
            top: 50%;
            left: 58%;
            transform: translate(-50%, -50%);
            font-weight: bold;
            font-size: 20px;
            }
            #progressCanvas{
                margin-left:-30px;
                width: 400px !important;
                height: 200px !important;
                    
            }
        
            .recent-activity{
                color:black;
            }
            .recent-activity p{
                font-weight: bold;
                margin-bottom: 10px;
                color:black;
            } 
            .active-btn, .logout{
                background-color: #13274F;
                margin-top: 0px;
                color: #fff;
                padding: 10px 15px;
                border: none;
                border-radius: 8px;
                font-weight: 500;
                cursor: pointer;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            }
            .active-btn{
                transition: background-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
                animation: pulse 2s infinite ease-in-out; 
                font-size: 18px;
            }

            .logout{
                font-size: 14px;
                margin-top: 30px;
            }
        
            .active-btn:hover , .logout:hover{
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
            .inactive-btn{
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
                
            }

            .active_quiz{
                /* background-color:rgb(211, 35, 35); */
                width: 400px;
                margin-left:-455px;
                /* height: 250px; */
                color:black;
                
            }    .toppers { 
            /* background-color:rgb(255, 0, 0); */
            padding: 20px;
            display:flex;
            height:445px;
            margin-left:20px;
            width:400px;

            flex-direction: column;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .toppers h2 {
            font-size: 1.8em;
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }


        .topper-card {
        display : flex;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-bottom:20px;
        font-size:15px;
        }

        .topper-card h3 {
            font-size: 1.2em;
            color: #555;
        }

        .topper-details p {
            font-size: 1em;
            color: #666;
        }

        .topper-details strong {
            color: #333;
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
        .topper-card img{
            margin:10px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        #editBtn{
            font-size:20px;
            border : none;
            background : transparent;
            cursor:  pointer;
        }
        .view-answers{
            background-color:#fff;
            color:black

        }
        .view-answers:hover{
            font-weight: bold;
        }
    </style>
</head>
<body>
        <div class="sidebar">
            <div class="profile-header">

            <!-- <div class="imgg" style="    background: linear-gradient(170deg,rgba(22, 77, 79, 0.85),rgb(95, 114, 151)); -->
            <img src="<?php echo htmlspecialchars($avatar); ?>" id="avatar" alt="Avatar" onclick="toggleAvatarDropdown()">
                <div id="avatarDropdown" class="avatar-dropdown">
                    <img src="avatar/boy1.png" onclick="selectAvatar('avatar/boy1.png')">
                    <img src="avatar/boy2.png" onclick="selectAvatar('avatar/boy2.png')">
                    <img src="avatar/boy4.png" onclick="selectAvatar('avatar/boy4.png')">
                    <img src="avatar/boy5.png" onclick="selectAvatar('avatar/boy5.png')">
                    <img src="avatar/boy6.png" onclick="selectAvatar('avatar/boy6.png')">
                    <img src="avatar/boy3.png" onclick="selectAvatar('avatar/boy3.png')">
                    <img src="avatar/girl1.png" onclick="selectAvatar('avatar/girl1.png')">
                    <img src="avatar/girl2.png" onclick="selectAvatar('avatar/girl2.png')">
                    <img src="avatar/girl3.png" onclick="selectAvatar('avatar/girl3.png')">
                    <img src="avatar/girl4.png" onclick="selectAvatar('avatar/girl4.png')">
                    <img src="avatar/girl7.png" onclick="selectAvatar('avatar/girl7.png')">
                            
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
                            <img id="eyeIcon1" src="icons/eye_show.svg" alt="Show Password" width="18" height="14">
                        </span>
                    </div>

                    <div style="position: relative; width: 220px;">
                        <input type="password" id="new_password" name="new_password" placeholder="New password" 
                            style="width:100%; height:30px; padding-right: 30px;" required>
                            <span id="toggleNewPassword" 
                                style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                <img id="eyeIcon2" src="icons/eye_show.svg" alt="Show Password" width="18" height="14">
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
            
            $quiz_id_recent = $quizIds[count($quizIds) - 1];
            $recent_quiz_name = $quizNames[count($quizNames) - 1];
            $recent_quiz_avg = $averageScores[count($averageScores) - 1];
            
            // your recent quiz and score
            $yourrecnt = $quizLabels[count($quizLabels) - 1];
            $yourrecentquizscore = $quizScores[count($quizScores) - 1];
            
            if ($yourrecnt == $recent_quiz_name) {
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
        </div>    

        </div>
        <form method="post" action="welcome.php">
            <center>
                <input type="submit" name="logout" value="Log Out" id="logout" class="logout">
            </center>
        </form>
    </div>        
    
    <div class="container">   
            
            <div class="content">
          
            <div class="slider-container">
                <div class="slides">
                <div class="slide">
                    <div class="greeting-block">
                    <div class="greeting-text">
                        <h1>Welcome, </h1>
                        <h1><?php echo $studentDetails['Name']; ?></h1>
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
                    
                </div>
                <!-- Slide 3: More Details-->
                <div class="slide">
                 
                </div>
                </div>-->
                <!-- Navigation Arrows -->
                
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
                <h3>' . htmlspecialchars($active_quiz['QuizName']) . '</h3>
                <p>' . ($active_quiz['QuizType'] == '0' ? 'MCQ' : 'FillUp') . '</p>
                <p>' . htmlspecialchars($activenoofques) . ' Questions | ' . $timeFormatted  . '</p>
                <p>' . htmlspecialchars($activemarks) . ' Marks</p>';
                if (!$flag) {
                    echo '<center><button class="active-btn" type="button" onclick="window.location.href=\'Welcome.php\'">Start Quiz</button></center>';
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
        <div class="line_chart card">
                <h2>Average Performance</h2>
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
       
       <div class="charts">
                
        <div class="card piec">
            <h2>Time of Completion</h2>
            <canvas id="timePieChart"></canvas>
        </div>         
        <div class="card barc">
                <h2>Scores</h2>
                    <canvas id="scoreBarChart"></canvas>
        </div>
        <div class="card toppers">
                    <?php if (!empty($data['performers'])) { ?>
  
                    <h2>🏆 All-Time Toppers</h2>
                    <div class="topper-list">
                       <?php foreach ($data['performers'] as $topper) { ?>
                            <div class="topper-card">
                                <img src="<?php echo htmlspecialchars($topper['Avatar']); ?>" alt="Avatar"></img>
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
        </div>
        </div>
        <div class="card quiz">
            <h2>All Quizzes</h2>
                <div class="quiz-list">
                    <?php foreach ($quiz_labels as $index => $quiz_name): ?>
                        <div class="quiz-item">
                            <h2 style="color:black;"><?php echo $quiz_name; ?></h2>
                            <p>Score: <?php echo $scores[$index]; ?>%</p>
                            <button onclick="window.location.href='Answers.php?quiz_id=<?php echo $quiz_ids[$index]; ?>'" class="view-answers">View Answers</button>
                        </div>
                    <?php endforeach; ?>
                </div>    
		<center><div class="pagination">
                <button>&laquo; Prev</button>
                <button>Next &raquo;</button> </div></center>            
 
        </div>


    <div class="badges">
                <h2>Badges</h2>
                <p>🏆 High Scorer | 🕒 Fast Finisher | 📚 Quiz Master</p>
        </div>
    </div>
 

    <script>
        document.getElementById('toggleOldPassword').addEventListener('click', function() {
            let passwordInput = document.getElementById('old_password');
            let eyeIcon = document.getElementById('eyeIcon1');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.src = 'icons/eye_hide.svg'; 
            } else {
                passwordInput.type = 'password';
                eyeIcon.src = 'icons/eye_show.svg'; 
            }
        });


        document.getElementById('toggleNewPassword').addEventListener('click', function() {
            let passwordInput = document.getElementById('new_password');
            let eyeIcon = document.getElementById('eyeIcon2');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.src = 'icons/eye_hide.svg'; 
            } else {
                passwordInput.type = 'password';
                eyeIcon.src = 'icons/eye_show.svg'; 
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
	
        // Progress Circle
        const progressPercent = <?php echo $studentPercentage; ?>;
        const duration = 1000; 
        
        const canvas = document.getElementById('progressCanvas');
        const ctx_new = canvas.getContext('2d');
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


        // Line Chart
         
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


        //leaderboard
    
        const leaderboardData = <?php echo json_encode($leaderboardData); ?>;
        const quizIds = Object.keys(leaderboardData);
        let currentQuizIndex = 0;

        function renderChart(quizId) {
            const canvas = document.getElementById(`chart-${quizId}`);
            
            if (canvas.chart) {
                canvas.chart.destroy();
            }

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
            const scores = topStudents.map(student => student.percentage);
            const avatars = topStudents.map(student => student.Avatar);
            const ctx_bar = canvas.getContext('2d');
            var yAxisMax = Math.max(...scores) + 25;


            var gradient1 = ctx_bar.createLinearGradient(0, 0, 0, 400);
            gradient1.addColorStop(0, 'rgba(22, 77, 79, 0.85)');
            gradient1.addColorStop(1, 'rgba(22, 77, 79, 0.4)');

            var gradient2 = ctx_bar.createLinearGradient(0, 0, 0, 400);
            gradient2.addColorStop(0, 'rgba(77, 81, 91, 0.85)');
            gradient2.addColorStop(1, 'rgba(77, 81, 91, 0.4)');

            var gradient3 = ctx_bar.createLinearGradient(0, 0, 0, 400);
            gradient3.addColorStop(0, 'rgba(65, 89, 130, 0.85)');
            gradient3.addColorStop(1, 'rgba(108, 145, 210, 0.4)');

            const newChart = new Chart(ctx_bar, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Percentage',
                        data: scores,
                        backgroundColor: [gradient1, gradient2, gradient3],
                        borderColor: '#222',
                        borderWidth: 1,
                        borderRadius: 5, 
                        hoverBackgroundColor: ['#174D4F', '#4D515B', '#6C91D2'], 
                        barThickness: 70, 
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



        // piechart
        let pieChartInstance = null;

        function renderPieChart() {
            var quizNames = <?php echo json_encode($quizNames); ?>;
            var totalTimes = <?php echo json_encode($totalTimes); ?>;

            function timeToMinutes(timeStr) {
                const [hours, minutes, seconds] = timeStr.split(':').map(Number);
                return hours * 60 + minutes + (seconds / 60); 
            }

            const totalTimesInMinutes = totalTimes.map(time => timeToMinutes(time));

            var colors = ['#e7eaf6', '#a2a8d3', '#38598b', '#113f67', 'rgba(22, 77, 79, 0.85)', 'rgba(77, 81, 91, 0.85)', 'rgba(77, 81, 91, 0.4)', 'rgba(108, 145, 210, 0.85)', 'rgba(108, 145, 210, 0.4)'];

            function getRandomColor() {
                return colors[Math.floor(Math.random() * colors.length)];
            }

            const ctx_pie = document.getElementById('timePieChart').getContext('2d');

            function getRandomGradient(ctx) {
                const gradient = ctx.createLinearGradient(0, 0, 0, 400);
                const color1 = getRandomColor();
                const color2 = getRandomColor();

                gradient.addColorStop(0, color1);
                gradient.addColorStop(1, color2);

                return gradient;
            }

            var barGradients = totalTimesInMinutes.map(() => getRandomGradient(ctx_pie));

            // Destroy previous chart if it exists
            if (pieChartInstance) {
                pieChartInstance.destroy();
            }

            // Create a new chart and store the instance
            pieChartInstance = new Chart(ctx_pie, {
                type: 'pie',
                data: {
                   
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
                        	      const quizName = quizNames[context.dataIndex]; 
                           
			                    return `${quizName}: ${percentage}% (${originalTime})`; 
                			}
            			},
       
       			     bodyFont: {
			                size: 16, 
			                weight: 'bold'
			            },
 
   
                    },
                    animation: { 
                        animateRotate: true, 
                        duration: 1500, 
                        easing: 'easeOutBounce' 
                    }
                }
}
            });
        }
        let scoreChartInstance = null;

    function renderScoreChart() {
        var quizLabels = <?php echo json_encode($quizLabels); ?>;
        var quizScores = <?php echo json_encode($quizScores); ?>;
        var colors2 = ['#e7eaf6', '#a2a8d3', '#38598b', '#113f67', 
            'rgba(22, 77, 79, 0.85)', 'rgba(77, 81, 91, 0.85)', 'rgba(77, 81, 91, 0.4)',
            'rgba(108, 145, 210, 0.85)', 'rgba(108, 145, 210, 0.4)'];

        function getRandomColor2() {
            return colors2[Math.floor(Math.random() * colors2.length)];
        }

        function getRandomGradient2(ctx) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            const color1 = getRandomColor2();
            const color2 = getRandomColor2();
            gradient.addColorStop(0, color1);
            gradient.addColorStop(1, color2);
            return gradient;
        }

        var ctx = document.getElementById('scoreBarChart').getContext('2d');

        // Destroy previous chart if it exists
        if (scoreChartInstance) {
            scoreChartInstance.destroy();
        }

        // Create an array of gradients for each bar
        var barGradients = quizScores.map(() => getRandomGradient2(ctx));

        // Create a new chart and store the instance

        scoreChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: quizLabels,
            datasets: [{
                label: 'Scores',
                data: quizScores,
                backgroundColor: barGradients,
                borderWidth: 1,
                borderColor: 'black',
                borderRadius: 5,
                barThickness: 50,    // Adjust bar width for better spacing
                maxBarThickness: 60, // Limit max width
                tension: 0.4
                    }]
                },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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
                }
            }
        });

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
       

        //for scroll animation
        let pieChartRendered = false;
        let scoreChartRendered = false;

        window.addEventListener('scroll', () => {
            const rect = document.getElementById('timePieChart')?.getBoundingClientRect();
            const rect2 = document.getElementById('scoreBarChart')?.getBoundingClientRect();

            if (rect && rect.top < window.innerHeight && rect.bottom > 0 && !pieChartRendered) {
                renderPieChart();
                pieChartRendered = true; 
            }

            if (rect2 && rect2.top < window.innerHeight && rect2.bottom > 0 && !scoreChartRendered) {
                renderScoreChart();
                scoreChartRendered = true; 
            }
        });

        
       </script>

</body>
</html>