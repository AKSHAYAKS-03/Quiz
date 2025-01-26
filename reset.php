<?php
include_once 'core_db.php';
session_start();

if (!$_SESSION['logged'] || $_SESSION['logged'] === '') {
    header('Location: index.php');
    exit(); 
}

$msg = ''; 
$activeQuizId = $_SESSION['active'];
$activeQuiz = $_SESSION['activeQuiz'];

if($activeQuizId !== 'None'){
    $activeNoOfQuestions = $conn->query("Select NumberOfQuestions from quiz_details where quiz_id = $activeQuizId")->fetch_assoc()['NumberOfQuestions'];
    $TimerType = $conn->query("Select TimerType from quiz_details where quiz_id = $activeQuizId")->fetch_assoc()['TimerType'];
}

if(isset($_POST['name'])){
    if(empty($_POST['quizName']))
        $msg = "Enter Quiz Name & Try Again";
    else{
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $quizName = $_POST['quizName'];
        $result = $conn->query("Select quiz_details where QuizName = '$quizName'")->fetch_assoc();
        if($result){
            $msg = "Quiz Already Exists with this name";
        }
        else{
            $query = "UPDATE quiz_details SET QuizName='$quizName' WHERE Quiz_id = $activeQuizId";
            if ($conn->query($query) === true) {
                $msg = "Quiz Name Updated successfully";
                $_SESSION['activeQuiz'] = $quizName;
                $activeQuiz = $quizName;
                header("Refresh:0"); 
            } else {
                $msg = "Error updating record: " . $conn->error;
            }
        }
    }
}

if (isset($_POST['Reset'])) {
    if (empty($_POST['RollNo'])) {
        $msg = 'RollNo is required';
    } else {
        $roll = $_POST['RollNo'];

        $check = "SELECT * FROM student where RollNo = '$roll'";
        $result = $conn->query($check)->fetch_assoc();

        // Fetch student data to display in the modal
        $studentQuery = "SELECT * FROM student WHERE RollNo = '$roll' and QuizId = $activeQuizId";
        $studentData = $conn->query($studentQuery)->fetch_assoc();

        $studQuery = "SELECT count(*) as QuestionsAttended FROM stud WHERE regno = '$roll' and QuizId = $activeQuizId";
        $studData = $conn->query($studQuery)->fetch_assoc();

        if ($studentData) {
            echo "
                <div id='confirmationModal' class='modal'>
                    <div class='modal-content'>
                        <span class='close-btn' onclick='closeModal()'>&times;</span>
                        <h3>Student Information</h3>
                        <p><strong>Register Number:</strong> $roll</p>
                        <p><strong>Name:</strong> {$studentData['Name']}</p>
                        <p><strong>Class:</strong>  {$studentData['Year']} {$studentData['Department']} {$studentData['Section']}</p>
                        <p><strong>Questions Attended:</strong> {$studData['QuestionsAttended']}</p>
                        <form method='post'>
                            <input type='hidden' name='confirmReset' value='1' />
                            <input type='hidden' name='RollNo' value='$roll' />
                            <input type='submit' name='ResetConfirmed' value='Confirm Reset' />
                            <button type='button' id='cancelReset'>Cancel</button>
                        </form>

                        <p id='note'>**Note:This action cannot be undone. </p>
                    </div>
                </div>
                <script>
                    document.getElementById('confirmationModal').style.display = 'block';
                    document.getElementById('cancelReset').addEventListener('click', function() {
                        closeModal();
                    });
                    function closeModal() {
                        document.getElementById('confirmationModal').style.display = 'none';
                    }
                </script>
            ";
        } else {
            $msg = "Student not found!";
        }
    }
}

if (isset($_POST['ResetConfirmed'])) {
    $roll = $_POST['RollNo'];
    $msg = 'Reset successfully';

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Reset student details in both tables
    $conn->query("DELETE FROM student WHERE RollNo= '$roll' and QuizId= $activeQuizId ");
    $conn->query("DELETE FROM stud WHERE regno= '$roll' and QuizId= $activeQuizId");

    $conn->close();
}

if(isset($_POST['Questions'])){
    if(empty($_POST['ques']))
        $msg = "Enter active No.of Questions & Try Again";
    else{
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $quesCount = $_POST['ques'];
        $query = "UPDATE quiz_details SET Active_NoOfQuestions='$quesCount' WHERE Quiz_id = $activeQuizId";
        if ($conn->query($query) === true) {
            $msg = "Active No of Questions was Updated successfully as $quesCount <br> **Note: Recommended to delete the existing scoreTable for fair evaluation";
            $activeNoOfQuestions = $quesCount;
        } else {
            $msg = "Failed to update Active Question Count for $activeQuiz Quiz";
        }
        $conn->close();
    }
}

if (isset($_POST['Timer'])) {
    if (empty($_POST['sec']) && empty($_POST['min'])) {
        $msg = 'Enter time';
    } else {
        $sec = $_POST['sec'];
        $min = $_POST['min'];

        if (strlen($sec) > 2 || strlen($min) > 2) {
            $msg = "Enter valid time";
        } 
        else 
        {
            $sec = str_pad($sec, 2, '0', STR_PAD_LEFT);
            $min = str_pad($min, 2, '0', STR_PAD_LEFT);

            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            $query = "UPDATE quiz_details SET QuestionDuration='$min:$sec' WHERE Quiz_id = $activeQuizId";
            if ($conn->query($query) === true) {
                $msg = "Time updated successfully as $min:$sec for $activeQuiz Quiz";
            } else {
                $msg = "Failed to update time for $activeQuiz Quiz";
            }

            $conn->close();
        }
    }
}

if (isset($_POST['pass'])) {
    if (empty($_POST['password'])) {
        $msg = 'Enter password';
    } else {
        $msg = 'Password Changed successfully';
        $pass = $_POST['password'];

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $conn->query("UPDATE admin SET pwd='$pass'");

        $conn->close();
    }
}

if(isset($_POST['TimerType'])){
    if(empty($_POST['TimerType']))
        $msg = "Enter Timer Type & Try Again";
    else{
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $TimerType = $_POST['TimerType'];    
        $query = "UPDATE quiz_details SET TimerType='$TimerType' WHERE Quiz_id = $activeQuizId";
        if ($conn->query($query) === true) {
            $msg = "Timer Type was Updated successfully ";
        } else {
            $msg = "Failed to update Timer Type for $activeQuiz Quiz";
        }
        $conn->close();
    }
}

if (isset($_POST['Back'])) {
    header("Location: admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quiz Settings</title>

    <!-- <script src="inspect.js"></script> -->
    <link rel="stylesheet" type="text/css" href="css/reset.css">
    <link rel="stylesheet" type="text/css" href="css/navigation.css">
    <style>
        body{
            margin: 50px 0px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 480px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }

        .modal-content input[type="submit"], button {
            background: #13274F;
            color: #fff;
            font-size: 14px;
            padding: 7px 10px;
            border: 0;
            border-radius: 5px;
            margin-top: 20px;
            width: auto;
        }

        .modal-content input[type="submit"]:hover, button:hover {
            cursor: pointer;
            font-weight: bolder;
            background-color: #0d1b37;
        }
        .form-group select{
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 8px;
            font-size: 16px;
            width: 220px;
        }
        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-btn:hover, .close-btn:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        #note{
            color: #be0d0dec;
            font-weight: bold;
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
<div class="content">    
  <div class="container">
    <h1 style="color: #13274F; margin-bottom: 30px; text-align: center;">Reset Settings</h1>
    <form method="post" action="reset.php">

      <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" placeholder="Enter new password" />
        <input type="submit" name="pass" value="Reset password" />
      </div><br>

      <?php 
        if ($activeQuiz !== 'None'){
      ?>
      <h3 style="font-weight: bold;">For Quiz <?php echo $activeQuiz; ?>,</h3>
      <div class="form-group">
        <label for="RollNo">RollNo:</label>
        <input type="text" id="RollNo" name="RollNo" placeholder="Enter RollNo" />
        <input type="submit" name="Reset" value="Reset" />
      </div>

      <div class="form-group">
        <label for="quizName">New Name:</label>
        <input type="text" id="quizName" name="quizName" placeholder="<?php echo $activeQuiz; ?>" />
        <input type="submit" name="name" value="Update" />
      </div>

      <div class="form-group">
        <label for="ques">Active No.of Questions:</label>
        <input type="number" id="ques" name="ques" min="1" max= "<?php echo $activeNoOfQuestions ?>"/>
        <input type="submit" name="Questions" value="Update" />
      </div>

      <div class="form-group">
        <label for="tType">Timer type:</label>
        <select id="tType" name="tType">
            <option <?php echo $TimerType == '0' ? 'selected' : ''; ?> value="0">For each Question</option>
            <option <?php echo $TimerType == '0' ? 'selected' : ''; ?> value="1">For Full Quiz</option>
        </select>
        <input type="submit" name="TimerType" value="Change" />
      </div>

      <div class="form-group">
        <label for="min">Timer (per Question):</label>
        <input type="number" id="min" name="min" placeholder="Minutes" min="0" max="59" />
        <input type="number" id="sec" name="sec" placeholder="Seconds" min="0" max="59" />
        <input type="submit" name="Timer" value="Set" />
      </div>

      <div class="form-group">
        <label for="startTime">Start Time:</label>
        <input type="datetime-local" id="startTime" name="startTime" /> <br>
        <label for="endTime">End Time:</label>
        <input type="datetime-local" id="endTime" name="endTime" />
        <input type="hidden" id="quizDuration" name="quizDuration">
        <button type="button" class="btn" onclick="saveTime()">Save</button>
      </div>

      <?php } ?>

      <?php if (!empty($msg)): ?>
        <div class="message <?php echo strpos($msg, 'successfully') !== false ? 'success' : 'error'; ?>">
          <?php echo $msg; ?>
        </div>
      <?php endif; ?>

      <div id="message"></div>
    </form>
    <div class="up">
      <form method="post" action="reset.php">
        <input type="submit" name="Back" value="Back" />
      </form>
    </div>
  </div>
</div>

<script>

    document.addEventListener('DOMContentLoaded', function() {
        defaultTime();
    });

    function defaultTime() {
        var activeQuizId = <?= $activeQuizId ?>;

        fetch(`UpdateQuizTime.php?quizId=${activeQuizId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('startTime').value = data.startTime;
                document.getElementById('endTime').value = data.endTime;
                document.getElementById('quizDuration').value = data.quizDuration;
            })
        .catch(error => console.error('Error:', error));
    }

    function saveTime() {
        var startTime = document.getElementById('startTime').value;
        var endTime = document.getElementById('endTime').value;
        var quizId = <?= $activeQuizId ?>;
        var messageElement = document.getElementById('message');

        if (!startTime || !endTime) {
            showMessage('Please fill out both start and end times.', 'error');
            return;
        }

        var quizDuration = document.getElementById('quizDuration').value;
        var [durationMinutes, durationSeconds] = quizDuration.split(':').map(Number);
        var startDateTime = new Date(startTime);
        var endDateTime = new Date(endTime);
        var minEndDateTime = new Date(startDateTime.getTime() + durationMinutes * 60000 + durationSeconds * 1000);

        if(endDateTime < minEndDateTime) {
            showMessage('End time must be at least ' + quizDuration + ' after the start time.', 'error');
            return;
        } else {
            fetch('updateQuizTime.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ startTime, endTime, quizId })
            })
                .then(response => response.json())
                .then(data => {
                    showMessage(data.message, 'success');
                    defaultTime();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
        }
    }

    function showMessage(message, type) {
        var messageElement = document.getElementById('message');
        if (messageElement) {
            messageElement.innerHTML = message;
            messageElement.className = 'message ' + type;
        } else {
            alert(message); 
        }
    }
</script>
</body>
</html> 
