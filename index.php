<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
date_default_timezone_set('Asia/Kolkata');
session_start();

$host = "localhost:3390";
$user = "root";
$password = "";
$db = "quizz";

$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$_SESSION['login'] = "";

$activeQuizQuery = "SELECT QuizName, Quiz_Id,TimeDuration, startingtime, EndTime FROM quiz_details WHERE IsActive = 1 LIMIT 1";
$activeQuizResult = $conn->query($activeQuizQuery);
$activeQuizData = $activeQuizResult->fetch_assoc();

$activeQuiz = $activeQuizData['QuizName'] ?? 'None';
$activeQuizId = $activeQuizData['Quiz_Id'] ?? 'None';

$_SESSION['active'] = $activeQuizId;

$activeQuizId = $_SESSION['active'];

if($activeQuizId === 'None'){
    echo '<script>alert("No Active Quiz");</script>';
} else {
    $totalduration = $activeQuizData["TimeDuration"];
    $startTime = strtotime($activeQuizData["startingtime"]);
    $endTime = strtotime($activeQuizData["EndTime"]);
    $currentUnixTime = time(); 


    $timeParts = explode(':', $totalduration);
    $durationInSeconds = ((int)$timeParts[0] * 60) + $timeParts[1];  

    $quizEndTime = $startTime + $durationInSeconds;

    $formattedEndTime = date('H:i:s', $quizEndTime);

    // echo "Quiz ends at: " . $formattedEndTime;

}
// echo $totalduration;
if (isset($_POST['Login_btn'])) {
    $Name = $conn->real_escape_string($_POST['name']);
    $RollNo = 9131 . $_POST['rollno'];
    $dept = $conn->real_escape_string($_POST['dept']);
    $_SESSION['dept'] = $dept;

    $sql = "SELECT * FROM student WHERE RollNo='$RollNo' AND QuizId='$activeQuizId'";
    $result = $conn->query($sql);

    $Name = strtoupper($Name);
    if ($currentUnixTime > $endTime) {
        $sql = "SELECT * FROM student WHERE Name='$Name' AND RollNo='$RollNo' AND Department='$dept' AND QuizId='$activeQuizId'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $_SESSION['login'] = TRUE;
            $_SESSION['logi'] = TRUE;
            $_SESSION['log'] = TRUE;
            $_SESSION['message'] = "You are logged in";
            $_SESSION['Name'] = $Name;
            $_SESSION['RollNo'] = $RollNo;
            header("Location: Answers.php");
            exit();
        } else {
        echo '<script>alert("Quiz is over"); window.location.href = "index.php";</script>';
        exit();
        }
    }


    if($result->num_rows <= 0 || $result2->num_rows<=0){
        if($result->num_rows<=0){
            $sql = "INSERT INTO student (Name, RollNo, Department, QuizId) VALUES ('$Name', '$RollNo', '$dept', '$activeQuizId')";
            $conn->query($sql);
        }
        $_SESSION['login'] = TRUE;
        $_SESSION['logi'] = TRUE;
        $_SESSION['log'] = TRUE;
        $_SESSION['message'] = "You are logged in";
        $_SESSION['Name'] = $Name;
        $_SESSION['RollNo'] = $RollNo;
        header("Location: Welcome.php");
    }
    else{
        echo '<script>alert("You already attended the quiz");</script>'; 
    }
}


$_SESSION['logged'] = "";

if (isset($_POST['username'])) {
    $uname = $conn->real_escape_string($_POST['username']);
    $pwd = $conn->real_escape_string($_POST['password']);
    $sql = "SELECT * FROM admin WHERE Admin='$uname' AND Pwd='$pwd'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $_SESSION['logged'] = TRUE;
        header("Location:admin.php");
        exit();
    } else {
        ?>
        <script>alert("Enter the correct password");</script>
        <?php
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Quiz Login</title>
    <link rel="stylesheet" href="css/index.css">
    <script type="text/javascript" src="inspect.js"></script>
  
</head>
<body oncontextmenu="return false;">
<div class="full-container">    
        <div class="header">
            <center><h3>BRAINBITE</h3></center>
            <img src="imgs/quiz4.jpg" class="quiz">
            <img src="imgs/bulb3.gif" class="bulb-man">
        </div>

        <div class="container">
            
            <div class="form-box login">
                <br>
                <center><h1>Student Login</h1> </center>
                <br>
                <form name="lg" method="post" action="index.php" onsubmit="return validateStudentLogin();">
                    <div class="form-group" style="display:flex;flex-direction:row;justify-content:space-between">
                        <label for="username">Name </label>
                        <input type="text" id="username" name="name">
                    </div>
                    <br>
                    <div class="form-group" style="display:flex;flex-direction:row;justify-content:space-between" >
                        <label for="rollno">Register number </label><br>
                        <div class="fixed-input">
                            <span class="fixed-text">9131</span>
                            <input type="text" id="rollno" name="rollno" placeholder="22104001">            
                        </div>           
                    </div>
                    <br>
                    <div class="form-group" style="display: flex; flex-direction: row;">
                        <label for="year">Year</label>
                        <select name="year" 
                                style="width: 150px; text-decoration: none; border-radius: 5px; background-color: transparent; 
                                    color: #13274F; padding: 5px;margin-left:-70px" required>
                            <option style="color: black;" disabled selected>Select</option>
                            <option value="I" style="color: black;">I</option>
                            <option value="II" style="color: black;">II</option>
                            <option value="III" style="color: black;">III</option>
                            <option value="IV" style="color: black;">IV</option>
                        </select>
                    </div> 

                    <div class="form-group" style="display: flex; flex-direction: row;">
                        <label for="dept">Department</label>
                        <select name="dept" 
                                style="width: 150px; text-decoration: none; border-radius: 5px; background-color: transparent; 
                                    color: #13274F; padding: 5px;margin-left:-70px">
                            <option value="" style="color: black;" disabled selected>Select</option>
                            <option value="CSE" style="color: black;">CSE</option>
                            <option value="IT" style="color: black;">IT</option>
                            <option value="EEE" style="color: black;">EEE</option>
                            <option value="ECE" style="color: black;">ECE</option>
                            <option value="MECH" style="color: black;">MECH</option>
                            <option value="CIV" style="color: black;">CIV</option>
                        </select>
                    </div> 
                    <br>
                    <div class="form-group" style="display:flex;flex-direction:row">
                        <button type="submit" name="Login_btn" value="Login" id="Login_btn">Login</button>
                        <input type="reset" name="Reset" id="reset" value="Clear">
                    </div>
                    <br>
                    <div class="login-register">               
                        <a href="#" class="register-link" style="text-decoration:none">Admin Login ?</a>                
                    </div>
                </form>
            </div>
            <div class="form-box register">
                <center> <h1>Admin Login</h1></center>
                <br>
                <form name="lg_admin" method="post" action="index.php" onsubmit="return validateAdminLogin();">
                    <div class="form-group" style="display:flex;flex-direction:row; justify-content:space-between">
                        <label for="username_admin">Username</label>
                        <input type="text" id="username_admin" name="username">
                    </div>
                    <br>
                    <div class="form-group" style="display:flex;flex-direction:row;justify-content:space-between">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password">
                    </div>
                    <br>
                    <div class="form-group" style="display:flex;flex-direction:row ;justify-content:space-between">
                        <button type="submit" name="logged" value="Login" id="Login_btn">Login</button>
                        <input type="reset" id="reset" name="Reset" value="Clear">
                    </div>
                    <br>
                    <div class="login-register">               
                        <a href="#" class="login-link" style="text-decoration:none">Student Login?</a>
                    </div>
                </form>
            </div>
            
        </div>
</div>

<div>
<footer>
        <a href="about.html" style="text-decoration:none;color:white"><p>&copy; 2024 BrainBite Quiz Application | Developed by Akshaya K S  &  Suriya Lakshmi M (CSE 2022-26)</p></a>
</footer>
</div>
<script>
    const container = document.querySelector('.container');
    const loginLink = document.querySelector('.login-link');
    const registerLink = document.querySelector('.register-link');

    registerLink.addEventListener('click', () => {
        container.classList.add('active');
    });

    loginLink.addEventListener('click', () => {
        container.classList.remove('active');
    });

    function validateStudentLogin() {
        var valid = true;

        var name = document.forms['lg']['name'].value.trim();
        var rollno = document.forms['lg']['rollno'].value.trim();
        var dept = document.forms['lg']['dept'].value;

        if (name === '' || !/^[a-zA-Z\s.]*$/.test(name)) {
            alert('Please enter a valid name.');
            valid = false;
        }

        if (rollno === '' || !/^\d+$/.test(rollno) || rollno.length !== 8) {
            alert('Please enter a valid roll number.');
            valid = false;
        }

        if (dept === '') {
            alert('Please select a department.');
            valid = false;
        }

        return valid;
    }

    function validateAdminLogin() {
        var valid = true;

        var username = document.forms['lg_admin']['username'].value.trim();
        var password = document.forms['lg_admin']['password'].value.trim();

        if (username === '') {
            alert('Please enter a username.');
            valid = false;
        }

        if (password === '') {
            alert('Please enter a password.');
            valid = false;
        }

        return valid;
    }
</script>

</body>
</html>