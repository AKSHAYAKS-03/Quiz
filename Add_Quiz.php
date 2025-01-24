<?php
include_once 'core_db.php';
session_start();

if (!$_SESSION['logged'] || $_SESSION['logged'] === '') {
    header('Location: index.php');
    exit;
}

$error = '';

if (isset($_POST['submit'])) {
    $quizName = $_POST['quizName'];
    $QuizType = $_POST['QuizType'];
    $isActive = $_POST['isActive'];
    $createdBy = $_POST['createdBy'];
    $TimerType = $_POST['timertype'];
    $quizMarks = $_POST['quizMarks'];
    $quizTime = $_POST['quizTime'];
    $shuffle = $_POST['shuffle'];
    $startTime = $_POST['startTime'];
    $endTime = $_POST['endTime'];

    $sql = "SELECT * FROM quiz_details WHERE QuizName = '$quizName'";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        $error = "Quiz already exists with the same name.";
    } else {
        $sql = "INSERT INTO quiz_details (QuizName, QuizType, QuestionDuration, TimerType , QuestionMark, IsActive, isShuffle, CreatedBy, startingtime, EndTime) VALUES ('$quizName', '$QuizType', '$quizTime','$TimerType', '$quizMarks', '$isActive', '$shuffle', '$createdBy', '$startTime', '$endTime')";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $quizId = mysqli_insert_id($conn);
            $_SESSION['quiz'] = $quizId;
            if ($isActive === "1") {
                $conn->query("UPDATE quiz_details SET IsActive = 0");
                $conn->query("UPDATE quiz_details SET IsActive = 1 WHERE Quiz_Id = $quizId");
            }
            
            if($_SESSION['QuizType']=='1'){
                header('Location: Fillup_Q_Add.php');
                exit;
            }
            else{
                header('Location: Q_Add.php');
            }
            exit;
        } else {
            $error = "Error: " . $sql . "<br>" . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Quiz</title>
    <script src="inspect.js"></script>
    <link rel="stylesheet" type="text/css" href="css/navigation.css">    
    <style>
        body {
            background-color: #13274F;
            margin: 30px 0;
            font-family: Arial, sans-serif;
            display: flex;
            color: #ccc;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding: 50px 0;
        }

        .outer {
            background-color: white;
            width: 60%;
            box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.4);
            border-radius: 10px;
            color: #333;
            padding: 30px;
            text-align: center;
            margin: 50px auto;
        }
        h1 {
            font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
            color: #13274F;
            margin-bottom: 40px;
        }

        .one {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .input-field {
            display: inline;
            margin-bottom: 20px;
            margin-right: 40px;
        }

        .input-field label {
            display: inline-block;
            width: 250px;
            font-weight: bold;
            margin-bottom: 5px;
            text-align: left;
        }

        #quizType{
            margin-left: 10px;
        }
        #timertype {
            margin-left: 10px;
        }
        
        .input-field input {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            width: 300px;
            font-size: 14px;
        }

        .input-field input:focus {
            outline: none;
            border-color: #34495e;
        }

        .input-field input[type="radio"] {
            margin-left: 0;
            margin-right: 10px;
            width: auto;
        }

        .input-field input[type="submit"] {
            background-color: #13274F;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            margin-top: 10px;
        }

        .input-field input[type="submit"]:hover {
            background-color: #0d1b37;
        }

        .error , #message {
            color: red;
            font-weight: bold;
            margin-bottom: 20px;
            
        }

        #rad1, #rad2{
            margin-right: 90px;
        }

        .space{
            margin-left: 65px;
        }
    </style>
</head>
<body>
    <div class="header">
            <a href="admin.php" id="back" title="Back">
                <img src="icons\back_white.svg" alt="back">
            </a>
            <a href="logout.php" id="logout" title="Log Out">
                <img src="icons\exit_white.svg" alt="exit">
            </a>
    </div>    
    <div class="outer" id="quizFormContainer">
        <h1>Add Quiz</h1>
        <?php if ($error): ?>
            <p class="error">
                <?php echo $error; ?>
            </p>

        <?php endif; 
        ?>
        <div id="message"></div>
        <form action="Add_Quiz.php" id='quiz-form' method="post" class="one" onsubmit="return validateTime();">
            <div class="input-field">
                <label for="quizName">Quiz Name:</label>
                <input type="text" id="quizName" name="quizName" required>
            </div>

            <div class="input-field">
                <label for="quizType" id="quizType">Quiz Type:</label>
                <input type="radio" name="QuizType" value="0" required>Multiple Choice <span class="space"></span>
                <input type="radio" name="QuizType" value="1" required>Fill Up<span class="space"></span>
                
            </div>

            <div class="input-field">
                <label for="isActive" id="rad1">Want to make the Quiz Active:</label>
                <input type="radio" name="isActive" value="1" required>Yes <span class="space"></span>
                <input type="radio" name="isActive" value="0" required>No <span class="space"></span>
            </div>

            <div class="input-field">
                <label for="createdBy">Created By:</label>
                <input type="text" id="createdBy" name="createdBy" required>
            </div>

            <div class="input-field">
                <label for="startTime">Quiz Start Time:</label>
                <input type="datetime-local" id="startTime" name="startTime" required>
            </div>

            <div class="input-field">
                <label for="endTime">Quiz End Time:</label>
                <input type="datetime-local" id="endTime" name="endTime" required>
            </div>

            <div class="input-field">
                <label for="quizMarks">Quiz Marks (per Question):</label>
                <input type="text" id="quizMarks" name="quizMarks" value="1" required>
            </div>    

            <div class="input-field">
                <label for="timertype" id="timertype" style="margin-left : 55px">Timer Type:</label>
                <input type="radio" name="timertype" value="0" required>Each Question<span class="space"></span>
                <input type="radio" name="timertype" value="1" required>For Full Quiz<span class="space"></span>
                
            </div>

            <div class="input-field">
                <label for="quizTime">Quiz Time (per Question):</label>
                <input type="time" id="quizTime" name="quizTime" required value="00:30">
            </div>

            <div class="input-field">
                <label for="shuffle" id="rad2">Want to shuffle the Questions & options during the Quiz:</label> 
                <input type="radio" name="shuffle" value="1" required checked>Yes <span class="space"></span>
                <input type="radio" name="shuffle" value="0" required>No <span class="space"></span>
            </div>

            <div class="input-field">
                <input type="submit" value="Submit" name="submit">
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input');
            const errorMessage = document.querySelector('.error');

            inputs.forEach(input => {
                input.addEventListener('focus', () => {
                    if (errorMessage) {
                        errorMessage.style.display = 'none';
                    }
                });
            });

            if (errorMessage) {
                scroll();
            }
        });

        function scroll() {
            const quizFormContainer = document.getElementById('quizFormContainer');
            quizFormContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function validateTime() {
            var startTime = document.getElementById('startTime').value;
            var endTime = document.getElementById('endTime').value;

            if (!startTime || !endTime) {
                document.getElementById('message').innerHTML = 'Please fill out both start and end times.';
                scroll();
                return false;
            }

            var startDateTime = new Date(startTime);
            var endDateTime = new Date(endTime);
            var currentDate = new Date();

            if (startDateTime >= endDateTime) {
                document.getElementById('message').innerHTML = 'Start time must be before end time.';
                scroll();
                return false;
            } else if (startDateTime < currentDate || endDateTime < currentDate) {
                document.getElementById('message').innerHTML = 'Start time and end time must be in the future.';
                scroll();
                return false;
            }

            return true;
        }
    </script>
</body>
</html>