<?php
include_once 'core_db.php';
session_start();

if (!$_SESSION['logged'] || $_SESSION['logged'] === '') {
    header('Location: login.php');
    exit;
}

$error = '';

if (isset($_POST['submit'])) {
    $quizName = $_POST['quizName'];
    $isActive = $_POST['isActive'];
    $createdBy = $_POST['createdBy'];
    $quizMarks = $_POST['quizMarks'];
    $quizTime = $_POST['quizTime'];
    $noOfQuestions = $_POST['noOfQuestions'];
    $shuffle = $_POST['shuffle'];
    $startTime = $_POST['startTime'];
    $endTime = $_POST['endTime'];

    $sql = "SELECT * FROM quiz_details WHERE QuizName = '$quizName'";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        $error = "Quiz already exists";
    } else {
        $sql = "INSERT INTO quiz_details (QuizName, QuestionDuration, QuestionMark, IsActive, isShuffle, CreatedBy, startingtime, EndTime) VALUES ('$quizName', '$quizTime', '$quizMarks', '$isActive', '$shuffle', '$createdBy', '$startTime', '$endTime')";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $quizId = mysqli_insert_id($conn);
            $_SESSION['quiz'] = $quizId;
            $_SESSION['noOfQuestions'] = $noOfQuestions;
            if ($isActive === "1") {
                $conn->query("UPDATE quiz_details SET IsActive = 0");
                $conn->query("UPDATE quiz_details SET IsActive = 1 WHERE Quiz_Id = $quizId");
            }

            header('Location: Q_Add.php');
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
    <style>
        body {
            background-color: #2c3e50;
            margin: 30px 0;
            font-family: Arial, sans-serif;
            display: flex;
            color: #ccc;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .outer {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            min-height: 100vh;
            border: none;
            background-color: white;
            width: 60%;
            box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.4);
            border-radius: 10px;
            color: #333;
        }

        h1 {
            font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
            color: #34495e;
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
            background-color: #34495e;
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
            background-color: #2c3e50;
        }

        .error {
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
    <div class="outer">
        <h1>Add Quiz</h1>
        <?php if ($error): ?>
            <p class="error">
                <?php echo $error; ?>
            </p>
        <?php endif; 
        ?>
        <form action="Add_Quiz.php" id='quiz-form' method="post" class="one">
            <div class="input-field">
                <label for="quizName">Quiz Name:</label>
                <input type="text" id="quizName" name="quizName" required>
            </div>

            <div class="input-field">
                <label for="isActive" id="rad1">Want to make the Quiz Active:</label>
                <input type="radio" name="isActive" value="1" required>Yes  <span class="space"></span>
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
                <label for="quizTime">Quiz Time  (per Question):</label>
                <input type="time" id="quizTime" name="quizTime" required value="00:30">
            </div>

            <div class="input-field">
                <label for="noOfQuestions">No.of Questions:</label>
                <input type="number" id="noOfQuestions" name="noOfQuestions" value="1" required>
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
        });


        function setMinDateTime() {
            const now = new Date();
            
            // Format the current date and time in YYYY-MM-DDTHH:MM:SS format
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0'); // Months are zero-based
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');

            return `${year}-${month}-${day}T${hours}:${minutes}:00`;
        }

        const startTimeInput = document.getElementById('startTime');
        const endTimeInput = document.getElementById('endTime');
        
        startTimeInput.setAttribute('min', setMinDateTime());
        endTimeInput.setAttribute('min', setMinDateTime());
        
        // Update the min attribute dynamically just before form submission
        document.getElementById('quizForm').addEventListener('submit', function(event) {
            const startTime = new Date(startTimeInput.value);
            const endTime = new Date(endTimeInput.value);

            console.log(startTime, endTime);

            if (startTime <= Date.now()) {
                alert('Start time must be in the future.');
                event.preventDefault();
            } else if (endTime.getTime() <= startTime.getTime()) {
                alert('End time must be after the start time.');
                event.preventDefault();
            }
        });

    </script>

</body>
</html>
