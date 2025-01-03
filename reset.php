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

$activeNoOfQuestions = $conn->query("Select NumberOfQuestions from quiz_details where quiz_id = $activeQuizId")->fetch_assoc()['NumberOfQuestions'];

if (isset($_POST['Reset'])) {
    if (empty($_POST['RollNo'])) {
        $msg = 'RollNo is required';
    } else {
        $roll = $_POST['RollNo'];
        $msg = 'Reset successfully';

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $conn->query("DELETE FROM student WHERE RollNo= '$roll' and QuizId= $activeQuizId ");
        $conn->query("DELETE FROM stud WHERE regno= '$roll' and QuizId= $activeQuizId");

        $conn->close();
    }
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

if (isset($_POST['Back'])) {
    header("Location: admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quiz Settings</title>

    <script src="inspect.js"></script>
    <link rel="stylesheet" type="text/css" href="css/reset.css">
</head>
<body>
<div class="content">
  <div class="container">
    <h2>Reset Settings</h2>
    <form method="post" action="reset.php">

      <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" placeholder="Enter new password" />
        <input type="submit" name="pass" value="Reset password" />
      </div><br>

      <?php 
        if ($activeQuiz !== 'None'){
      ?>
      <h3>For Quiz <?php echo $activeQuiz; ?>,</h3>
      <div class="form-group">
        <label for="RollNo">RollNo:</label>
        <input type="text" id="RollNo" name="RollNo" placeholder="Enter RollNo" />
        <input type="submit" name="Reset" value="Reset" />
      </div>

      <div class="form-group">
        <label for="ques">Active No.of Questions:</label>
        <input type="number" id="ques" name="ques" min="1" max= "<?php echo $activeNoOfQuestions ?>"/>
        <input type="submit" name="Questions" value="Update" />
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