<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
date_default_timezone_set('Asia/Kolkata');
session_start();

$host = "localhost:3307";
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
    $Name = strtoupper($Name);
    $RollNo = 9131 . $_POST['rollno'];
    $dept = $conn->real_escape_string($_POST['dept']);
    $_SESSION['dept'] = $dept;
    $sec = $conn->real_escape_string($_POST['sec']);
    $_SESSION['sec'] = $sec;
    $year = $conn->real_escape_string($_POST['year']);
    $_SESSION['year'] = $year;

    $sql = "SELECT * FROM student WHERE RollNo='$RollNo' AND QuizId='$activeQuizId'";
    $result = $conn->query($sql);

    $sql2 = "SELECT * FROM stud WHERE regno='$RollNo' AND QuizId='$activeQuizId'";
    $result2 = $conn->query($sql2);

    if ($currentUnixTime > $endTime) {
        $sql1 = "SELECT * FROM student WHERE Name='$Name' AND RollNo='$RollNo' AND Department='$dept' AND Section='$sec' AND Year='$year' AND QuizId='$activeQuizId'";
        $result1 = $conn->query($sql1);

        if ($result1->num_rows > 0) {
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
        if($result->num_rows>0){
            $row = $result->fetch_assoc();
           // echo '<script>alert("'.$row['Name'].' '.$Name.' '.(trim($row['Name']) !== trim($Name)?1:0).''.'")</script>'; 

            if(trim($row['Name']) !== trim($Name) || $row['RollNo']!=$RollNo || $row['Department']!=$dept || $row['Section']!=$sec || $row['Year']!=$year || $row['Time'] !== NULL ){
                echo '<script>alert("You already attended the quiz!"); window.location.href = "index.php";;</script>'; 
                exit(); 
            }
        }
        else{
            $sql = "INSERT INTO student (Name, RollNo, Department,Section,Year, QuizId) VALUES ('$Name', '$RollNo', '$dept','$sec','$year', '$activeQuizId')";
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
        echo '<script>alert("You already attended the quiz");window.location.href = "index.php";</script>';
        exit();
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
            
            <div class="form-box login" style="width: 400px; height: 450px;">
                <br>
                <center><h1>Student Login</h1> </center>
                <br>
                <form name="lg" method="post" action="index.php" onsubmit="return validateStudentLogin();">
                    <div class="form-group" style="display:flex;flex-direction:row;justify-content:space-between">
                        <label for="username" >Name </label>
                        <input type="text" id="username" name="name" placeholder="eg: John D">
                    </div>
                    <div class="form-group" style="display:flex;flex-direction:row;justify-content:space-between" >
                        <label for="rollno" style="white-space: nowrap;">Register number </label><br>
                        <div class="fixed-input">
                            <span class="fixed-text">9131</span>
                            <input type="text" id="rollno" name="rollno" placeholder="22104001" style="width: 150px;">            
                        </div>           
                    </div>
                <div class="form-group">
                <label for="year" style="font-weight: bold; margin-bottom: 5px; color: #13274F;">Year</label>
                <select name="year" required 
                        style="width: 200px; padding: 5px; font-size: 14px; border: 1px solid #ccc; border-radius: 5px; 
                                 color: #13274F; outline: none; transition: border-color 0.3s;">
                    <option style="color: black;" disabled selected>Select</option>
                    <option value="I">I</option>
                    <option value="II">II</option>
                    <option value="III">III</option>
                    <option value="IV">IV</option>
                </select>
                </div>
                <div class="form-group">
                <label for="sec" style="font-weight: bold; margin-bottom: 5px; color: #13274F;">Section</label>
                <select name="sec" required 
                        style="width: 200px; padding: 5px; font-size: 14px; border: 1px solid #ccc; border-radius: 5px; 
                                color: #13274F; outline: none; transition: border-color 0.3s;">
                    <option style="color: black;" disabled selected>Select</option>
                    <option value="A" style="color: black;">A</option>
                    <option value="B" style="color: black;">B</option>
                    <option value="C" style="color: black;">C</option>

                </select>
                </div>

                <div class="form-group">
                <label for="dept" style="font-weight: bold; margin-bottom: 5px; color: #13274F;">Department</label>
                <select name="dept" required 
                        style="width: 200px; padding: 5px; font-size: 14px; border: 1px solid #ccc; border-radius: 5px; 
                                color: #13274F; outline: none; transition: border-color 0.3s;">
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
        <a href="about.html" style="text-decoration:none;color:white"><p>&copy; 2024 BrainBite Quiz Application | CSE 2022-26</p></a>
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
        var year = document.forms['lg']['year'];
        var dept = document.forms['lg']['dept'];
        var sec = document.forms['lg']['sec'];

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

        if (year === '') {
            alert('Please select a year.');
            valid = false;
        }
        if(sec === '') {
            alert('Please select a section.');
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