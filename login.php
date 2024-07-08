<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);


session_start();

$host = "localhost:3390";
$user = "root";
$password = "";
$db = "quizz";

// Create connection
$conn = new mysqli($host, $user, $password, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$_SESSION['login'] = "";

$activeQuizQuery = "SELECT QuizName, Quiz_Id FROM quiz_details WHERE IsActive = 1 LIMIT 1";
$activeQuizResult = $conn->query($activeQuizQuery);
$activeQuizData = $activeQuizResult->fetch_assoc();

$activeQuiz = $activeQuizData['QuizName'] ?? 'None'; 
$activeQuizId = $activeQuizData['Quiz_Id'] ?? 'None'; 

$_SESSION['active'] = $activeQuizId;

$activeQuizId = $_SESSION['active'];

if (isset($_POST['Login_btn'])) {
    
    $Name = $conn->real_escape_string($_POST['name']);
    $RollNo = $conn->real_escape_string($_POST['rollno']);
    $dept = $conn->real_escape_string($_POST['dept']);
    $_SESSION['dept'] = $dept;

    $sql = "SELECT * FROM student WHERE RollNo='$RollNo' and QuizId= $activeQuizId";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        ?>
        <script>
            alert("You already attended the quiz");
        </script>
        <?php
    } 
    else {
        $sql = "INSERT INTO student (Name, RollNo, Department, QuizId) VALUES ('$Name', '$RollNo', '$dept', $activeQuizId)";
        if ($conn->query($sql)) {
            $_SESSION['login'] = TRUE;
            $_SESSION['logi'] = TRUE;
            $_SESSION['log'] = TRUE;
            $_SESSION['message'] = "You are logged in";
            $_SESSION['Name'] = $Name;
            $_SESSION['RollNo'] = $RollNo;
            header("Location: Welcome.php");
        } 
        else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
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
    } 
    else {
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
    <script type="text/javascript" src="inspect.js"></script>
    <style>
        body {
            background-repeat: no-repeat;
            background-size: 100%;
            background-color: white;  
            color: #13274F;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        #topbar {
            position: absolute;
            margin-top: 30px;
            top:0;
            float: left;
            width: 100%;
            padding: 20px 100px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 99;
        }

        h1{
            text-transform: uppercase;
            font-weight: 500;
            text-align: center;
            letter-spacing: 0.1em;
            margin-top: 50px;
        }


        #topbar #loginbut
         {
          
            width: 100px;
            height: 50px;
            margin-left: 40px;
            font-size: 1.1em;
            font-weight: 500;
            background: transparent;
            border-radius: 10px;
            border-color: #fff;
            cursor: pointer;
            color: #13274F;

        }

        #topbar #loginbut:hover
        {
            background: #13274F;
            color: #fff;
        }

        
        .container {
            position: relative;
            height: 450px;
            width: 400px;
            background-color: #13274F;
            color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-left: 500px;
            margin-top: 20px;
            backdrop-filter: blur(50px);
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            transform: scale(0);
            transition: transform 0.5s ease, height 0.2s ease;
        }

        .container .form-box {
            width: 100%;
            padding: 20px;

        }

        .container h1 {
            margin-top : -10px;
            margin-bottom: 20px;

        }
    
        .form-box h2 {
            font-size: 2em;
            text-align: center;

        }

        .container .form-box.login {
            transition: transform 0.18s ease;
            transform: translateX(0);
        }

        .container.active .form-box.login {
            transition: absolute;
            transform: translateX(-400px);
        }

        .container .form-box.register {
            position: absolute;
            transition: none;
            padding: 40px;
            transform: translateX(400px);
        }

        .container.active .form-box.register {
            transition: transform 0.18s ease;
            transform: translateX(0);
        }


        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;

        }

        .form-group input {
            width: 200px;
            background: transparent;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            color: #fff;

        }

        .form-group button {
            width: 100%;
            margin-top: 10px;
            padding: 10px;
            border: none;
            border-radius: 10px;
            background: transparent;
            color: #fff;
            font-size: 16px;
            cursor: pointer;            

        }

        .form-group button:hover {
            background-color: #fff;
            color: #13274F;
        }
        

        .login-register {
            font-weight: 500;
            text-align: center;
            font-weight: 500;
   

        }

        .login-register p a {
            text-decoration: none;
            display: flex;
            flex-direction: column;

        }

        .login-register p a:hover {
            color: #716f81;
        }

        .container .icon-close {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 20px;
            height: 20px;
            font-size: 1.5em;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            border-radius: 50%;
            z-index: 1;
        }
     

        .container.active-popup {
            transform: scale(1);
        }
        
        

        #reset{
                width: 100%;
            margin-top: 10px;
            padding: 10px;
            border: none;
            border-radius: 10px;
            background: transparent;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            
            }
            #reset:hover {
                background-color: #fff;
                color: #13274F;
        }
        .fixed-input {
        position: relative;
        display: inline-block;
        width: 200px; 
    }
        .fixed-text {
        position: absolute;
        left: 20px;
        top:8.5px;
        width: 20px; 
        text-align: center; 
        pointer-events: none; 
        color: white;
        font-size: 13.5px; 
    }
    .fixed-input input {
        width: calc(100% - 50px); 
        padding-left: 50px; 
        box-sizing: border-box; 
    }
    .close-button{
        width: 20px;
        border-radius: 50px;
    }
    .close-button:hover{    
    cursor: pointer;
    }


        
    </style>
</head>


<body oncontextmenu="return false;">

<center><h1>QUIZ</h1></center>


<div id="topbar">     
        <button id="loginbut">Login</button>
</div>

<div class="container">
    <div class="icon-close" data-closable>
        <button class="close-button" aria-label="Close alert" type="button" data-close>
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <div class="form-box login">
        <br>
    <center><h1>Student Login</h1> </center>
    <br>

    <form name="lg" method="post" action="login.php" onsubmit="return validateStudentLogin();" autocomplete="on">
    <div class="form-group" style="display:flex;flex-direction:row;justify-content:space-between">
                <label for="username">Name </label>
                <input type="text" id="username" name="name" >
            </div>
            <br>
            <div class="form-group" style="display:flex;flex-direction:row;justify-content:space-between" >
                <label for="rollno">Register Number</label>    
                        <input type="text" id="rollno" name="rollno"  placeholder="Ex: 9131" >
            </div>
            
            <br>
            <div class="form-group" style="display:flex;flex-direction:row">
                <label for="dept">Department   </label>
                 <select name="dept" style="width:100px;margin-left:40px;text-decoration:none;border-radius:5px;background-color:transparent;color:white">
                    <option style="color:black">select</option>
                    <option value="CSE"  style="color:black">CSE</option>
                    <option value="IT"  style="color:black">IT</option>
                    <option value="EEE"  style="color:black">EEE</option>
                    <option value="ECE"  style="color:black">ECE</option>
                    <option value="MECH"  style="color:black">MECH</option>
                    <option value="CIV"  style="color:black">CIV</option>
                </select>
        </div>     
        <br>   

                <div class="form-group" style="display:flex;flex-direction:row">
                <button type="submit" name="Login_btn" value="Login">Login</button>
                <input type="reset" name="Reset"   id="reset" value="Clear">

            </div>         
               
<br>
        <div class="login-register" >               
                    <a href="#" class="register-link" style="text-decoration:none;color:white">Admin Login ?</a>                
         </div>

</form>
</div>
<div class="form-box register">
        <center> <h1>Admin Login</h1></center>
        <br>
        <form name="lg" method="post" action="login.php" onsubmit="return validateAdminLogin();">
        <div class="form-group" style="display:flex;flex-direction:row; justify-content:space-between">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" >
            </div>
            <br>
            <div class="form-group" style="display:flex;flex-direction:row;justify-content:space-between">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" >
            </div>                                           
      <br>
            
            <div class="form-group" style="display:flex;flex-direction:row ;justify-content:space-between" >
            <button type="submit" name="logged" value="Login">Login</button>
            <input type="reset" id="reset" name="Reset" value="Clear">

            </div> 
    <br>
            <div class="login-register">               
                    <a href="#" class="login-link" style="text-decoration:none;color:white">Student Login?</a>
            </div>
        </form>
    </div>
</div>
  


<script>
    // Add event listener for login button
    const container = document.querySelector('.container');
    const loginLink = document.querySelector('.login-link');
    const registerLink = document.querySelector('.register-link');
    const btpopup = document.querySelector('#loginbut');
    const iconClose = document.querySelector('.icon-close');

    registerLink.addEventListener('click', () => {
        container.classList.add('active');
    });

    loginLink.addEventListener('click', () => {
        container.classList.remove('active');
    });
    btpopup.addEventListener('click', () => {
        container.classList.add('active-popup');
    });

    iconClose.addEventListener('click', () => {
        container.classList.remove('active-popup');
    });

    // Check if user is logged in
    const logout = document.getElementById("logout");
    logout.addEventListener("click", function() {
        window.location.href = "login_eg.php";
    });

    
    const others = document.querySelectorAll("#topbar .others");
    const logoutButton = document.querySelector("#topbar #logout");

    others.forEach(link => {
        link.style.display = "inline-block";
    });

    logoutButton.style.display = "inline-block";

    function validateStudentLogin() {
        var valid = true;

        // Retrieve form inputs
        var name = document.forms['lg']['name'].value.trim();
        var rollno = document.forms['lg']['rollno'].value.trim();
        var dept = document.forms['lg']['dept'].value;

        // Validate Name
        if (name === '' || !/^[a-zA-Z\s]*$/.test(name)) {
            alert('Please enter a valid name.');
            valid = false;
        }

        // Validate Roll Number
        if (rollno === '' || !/^\d+$/.test(rollno)) {
            alert('Please enter a valid roll number.');
            valid = false;
        }

        // Validate Department Selection
        if (dept === 'select') {
            alert('Please select a department.');
            valid = false;
        }

        return valid;
    }

    function validateAdminLogin() {
        var valid = true;

        // Retrieve form inputs
        var username = document.forms['lg']['username'].value.trim();
        var password = document.forms['lg']['password'].value.trim();

        // Validate Username
        if (username === '' || !/^[a-zA-Z0-9]+$/.test(username)) {
            alert('Please enter a valid username.');
            valid = false;
        }

        // Validate Password
        if (password === '') {
            alert('Please enter your password.');
            valid = false;
        }

        return valid;
    }
    
</script>


</body>
</html>


