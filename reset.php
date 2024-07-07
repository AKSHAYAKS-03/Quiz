<?php
include_once 'core_db.php';
session_start();

if (!$_SESSION['logged'] || $_SESSION['logged'] === '') {
    header('Location: login.php');
    exit(); // Ensure no further code execution after redirect
}

$msg = ''; // Initialize message variable
$activeQuizId = $_SESSION['active'];
$activeQuiz = $_SESSION['activeQuiz'];

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
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #2c3e50;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .content {
            max-width: 55%;
            margin: 0 auto;
            padding: 20px;
            background-color: #ecf0f1;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .container {
            padding: 20px;
        }

        .container h2 {
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: inline-block;
            width: 150px;
            font-weight: bold;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="password"],
        .form-group input[type="submit"] {
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 8px;
            font-size: 16px;
            width: 200px;
        }

        .form-group input[type="submit"] {
            background: #2c3e50;
            color: #fff;
            font-size: 14px;
            padding: 7px 10px;
            border: 0;
            border-radius: 5px;
            margin-top: 20px;
            width: auto;
        }

        .form-group input[type="submit"]:hover {
            cursor: pointer;
            font-weight: bolder;
            background-color: #34495e;
        }

        .message {
            margin-top: 20px;
            padding: 10px;
            border-radius: 5px;
        }

        .message.success {
            background-color: #D4EDDA;
            border-color: #C3E6CB;
            color: #155724;
        }

        .message.error {
            background-color: #F8D7DA;
            border-color: #F5C6CB;
            color: #721C24;
        }

        .up {
            margin-top: 20px;
            text-align: center;
        }

        .up input[type="submit"] {
            background-color: #000000;
            color: #cccccc;
            padding: 7px 10px;
            border: 0;
            border-radius: 5px;
            margin-top: 20px;
            width: 100px;
        }

        .up input[type="submit"]:hover {
            background-color: #333333;
        }

    </style>
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
        <label for="min">Timer (per Question):</label>
        <input type="number" id="min" name="min" placeholder="Minutes" min="0" max="59" />
        <input type="number" id="sec" name="sec" placeholder="Seconds" min="0" max="59" />
        <input type="submit" name="Timer" value="Set" />
      </div>

      <?php } ?>

      <?php if (!empty($msg)): ?>
        <div class="message <?php echo strpos($msg, 'successfully') !== false ? 'success' : 'error'; ?>">
          <?php echo $msg; ?>
        </div>
      <?php endif; ?>
    </form>
    <div class="up">
      <form method="post" action="reset.php">
        <input type="submit" name="Back" value="Back" />
      </form>
    </div>
  </div>
</div>
</body>
</html>
