<?php
include_once 'core/connection.php';
include_once 'quiz/getActiveQuiz.php';

// student
$_SESSION['login'] = "";

$activeQuizId = $_SESSION['active'];

if (isset($_POST['Login_btn'])) {

    $RegNo = 9131 . $_POST['RegNo'];
    $Password = $_POST['pass'];
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE RegNo = ?");
    $stmt->bind_param("s", $RegNo);

    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        echo "<script>console.log(".$row['Password']." ".$Password.")</script>";
        if($row['Password']!=$Password){
            echo '<script>alert("Enter the correct password");</script>';
            header("Refresh: 0.5");
            exit();
        }
        $_SESSION['RegNo'] = $RegNo;
        $_SESSION['Name'] = $row['Name'];
        $_SESSION['dept'] = $row['Department'];
        $_SESSION['sec'] = $row['Section'];
        $_SESSION['year'] = $row['Year'];
        $_SESSION['login'] = true;
        
        header("Location: dashboard/dashboard.php");
    }
    else{
      echo '<script>alert("Register Number doesn\'t exist");</script>';
      header("Refresh: 0.5");
      exit();
    }
}


// admin
$_SESSION['logged'] = "";

if (isset($_POST['username'])) {
    $uname =$_POST['username'];
    $password = $_POST['password'];
   
    $stmt = $conn->prepare("SELECT * FROM admin WHERE Admin = ? AND Password = ?");
    $stmt->bind_param("ss", $uname, $password);
    $stmt->execute();
    
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $_SESSION['logged'] = TRUE;
        header("Location:dashboard/admin.php");
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
    <title>BrainBite</title>
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="icon" type="image/png" href="public/BB-logo.png">
    <!-- <script type="text/javascript" src="assets/scripts/inspect.js"></script> -->
  
</head>
<body>
<div class="full-container">    
        <div class="header">
            <center><h3>BRAINBITE</h3></center>
            <img src="assets/imgs/quiz4.jpg" class="quiz">
            <img src="assets/imgs/bulb3.gif" class="bulb-man">
        </div>

        <div class="container">
            
            <div class="form-box login" style="margin-top:-10px;">
                <br>
                <center><h1>Student Login</h1> </center>
                <br>
                <form name="lg" method="post" action="index.php" onsubmit="return validateStudentLogin();">
                  
                    <div class="form-group" >
                        <label for="RegNo" style="white-space: nowrap;">Register number </label><br>
                        <div class="fixed-input">
                            <span class="fixed-text">9131</span>
                            <input type="text" id="RegNo" name="RegNo" placeholder="22104001" style="width: 150px;">            
                        </div>           
                    </div>
                    <br>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="pass" name="pass">            
                    </div>
               
                    <br>
                    <div class="form-group">
                        <button type="submit" name="Login_btn" value="Login" id="Login_btn">Login</button>
                        <input type="reset" name="Reset" id="reset" value="Clear">
                    </div>
                    <br>
                    <div class="login-admin">               
                        <a href="#" class="admin-link" style="text-decoration:none">Admin Login ?</a>                
                    </div>
                </form>
            </div>
            <div class="form-box admin">
                <center> <h1>Admin Login</h1></center>
                <br>
                <form name="lg_admin" method="post" action="index.php" onsubmit="return validateAdminLogin();">
                    <div class="form-group" >
                        <label for="username_admin">Username</label>
                        <input type="text" id="username_admin" name="username">
                    </div>
                    <br>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password">
                    </div>
                    <br>
                    <div class="form-group">
                        <button type="submit" name="logged" value="Login" id="Login_btn">Login</button>
                        <input type="reset" id="reset" name="Reset" value="Clear">
                    </div>
                    <br>
                    <div class="login-admin">               
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
    const adminLink = document.querySelector('.admin-link');

    adminLink.addEventListener('click', () => {
        container.classList.add('active');
    });

    loginLink.addEventListener('click', () => {
        container.classList.remove('active');
    });

    function validateStudentLogin() {
        var RegNo = document.getElementById('RegNo').value.trim();
        var password = document.getElementById('pass').value.trim();

        if (RegNo === '' || !/^\d{8}$/.test(RegNo)) {
            alert('Please enter a valid roll number.');
            return false;
        }

        if(password === '') {
            alert('Please enter a password.');
            return false;
       }
        return true;
    }

    function validateAdminLogin() {
        var username = document.getElementById('username_admin').value.trim();
        var password = document.getElementById('password').value.trim();

        if (username === '') {
            alert('Please enter a username.');
             return false;
        }

        if (password === '') {
            alert('Please enter a password.');
             return false;
        }

        return true;
    }
</script>

</body>
</html>