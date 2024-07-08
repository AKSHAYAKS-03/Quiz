<?php
include_once 'core_db.php';

session_start();

if(!$_SESSION['logged'] || $_SESSION['logged']===''){
    header('Location: login.php');
    exit;
}

$activeQuizQuery = "SELECT QuizName, Quiz_Id FROM quiz_details WHERE IsActive = 1 LIMIT 1";
$activeQuizResult = $conn->query($activeQuizQuery);
$activeQuizData = $activeQuizResult->fetch_assoc();

$activeQuiz = $activeQuizData['QuizName'] ?? 'None'; 
$activeQuizId = $activeQuizData['Quiz_Id'] ?? 'None'; 

$_SESSION['active'] = $activeQuizId;
$_SESSION['activeQuiz'] = $activeQuiz;
$_SESSION['quiz']='';


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['activeQuiz'])) {
    $newActiveQuizId = $_POST['activeQuiz'];

    $conn->query("UPDATE quiz_details SET IsActive = 0");

    $conn->query("UPDATE quiz_details SET IsActive = 1 WHERE Quiz_Id = $newActiveQuizId");

    $activeQuizRes = $conn->query("Select QuizName from quiz_details WHERE Quiz_Id = $newActiveQuizId");
    $activeQuiz = $activeQuizRes->fetch_assoc()['QuizName'];

    $activeQuizId = $newActiveQuizId;
    $_SESSION['active'] = $activeQuizId;
    $_SESSION['activeQuiz'] = $activeQuiz;
}

$query = "SELECT Quiz_Id, QuizName, NumberOfQuestions, TimeDuration, TotalMarks, IsActive FROM quiz_details";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin</title>

    <script src="inspect.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            overflow: hidden; 
        }

        .admin-nav {
            width: 20%;
            background-color: #13274F;
            padding: 20px;
            box-shadow: -2px 0 5px rgba(0,0,0,0.1);
            height: 100vh;
            box-sizing: border-box;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 500; 
        }

        .admin-nav h2 {
            color: #ecf0f1;
            margin-bottom: 40px;
        }

        .admin-nav ul {
            list-style-type: none;
            padding: 0;
        }

        .admin-nav ul li {
            margin: 10px 0;
        }

        .admin-nav ul li a {
            text-decoration: none;
            color: #ecf0f1;
            display: block;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }

        .admin-nav ul li a:hover {
            background-color: #34495e;
        }

        .content {
            width: 80%;
            padding: 20px;
            box-sizing: border-box;
            background-color: #ecf0f1;
            margin-left: 20%;
        }

        .content h1 {
            color: #13274F;
        }

        .quiz-details {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
            background-color: #fff;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .quiz-details th, .quiz-details td {
            border: 1px solid #ddd;
            padding: 12px;
        }

        .quiz-details th {
            background-color: #13274F;
            color: #ecf0f1;
        }

        .quiz-details td {
            color: #2c3e50;
        }

        .quiz-details tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .quiz-details tr:hover {
            background-color: #f1f1f1;
        }

        .btn {
            background-color: #13274F;
            color: #ecf0f1;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            box-shadow: 2px 2px 10px gray;
            padding: 10px;
        }

        .btn:hover {
            background-color: #2c3e50;
        }

        #logout {
            position: absolute;
            top: 10px;
            right: 20px;
        }

        body.blur .content,
        body.blur .admin-nav {
            filter: blur(5px);
        }

        .iframe-container {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            background-color: #ecf0f1;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }

        .iframe-container iframe {
            border: none;
            width: 500px;
            height: 400px;
        }

        .iframe-container .close-btn, .delete-btn{
            display: block;
            margin-top: 10px;
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 5px;
            width: 80px;
            background-color: #34495e;
            color: white;
            text-align: center;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .iframe-container .delete-btn {
            right: 130px;
        }
        .iframe-container .close-btn:hover, .delete-btn:hover {
            background-color: #2c3e50;
        }

        #no-quiz{
            font-size: large;
            font-weight: bold;
            text-align: center;
            padding: 20px;
            font-size: large;
        }

    </style>
</head>
<body>
    <nav class='admin-nav'>
        <h2>Admin</h2>
        <ul>
            <li><a href="Q_add.php">Add Question</a></li>
            <li><a href="Q_Edit.php">Edit/Delete Question</a></li>
            <li><a href="reset.php">Reset Quiz</a></li>
            <li><a href="ViewResult.php">View Result</a></li>
            <li><a href="#" onclick="redirectIframe('iframe1', 'Delete_Quiz.php')">Delete Quiz</a></li>
            <li><a href="Add_Quiz.php">Add New Quiz</a></li>
        </ul>
    </nav>
s
    <div class="content">
        <h1>Quiz Details</h1>
        <button class="btn" id="logout" onclick="logout()">Log Out</button>
        <p>Active Quiz: <strong><?php echo $activeQuiz; ?></strong></p>
        <?php if ($result && $result->num_rows > 0) {
            ?>
            <form method="post" action="">
            <table class="quiz-details">
                <thead>
                    <tr>
                        <th>Quiz No.</th>
                        <th>Quiz Name</th>
                        <th>Number of Questions</th>
                        <th>Time Duration</th>
                        <th>Total Marks</th>
                        <th>Active</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                        while ($row = $result->fetch_assoc()) {
                            $checked = $row['IsActive'] ? 'checked' : '';
                            echo "<tr>";
                            echo "<td>" . $row['Quiz_Id'] . "</td>";
                            echo "<td>" . $row['QuizName'] . "</td>";
                            echo "<td>" . $row['NumberOfQuestions'] . "</td>";
                            echo "<td>" . $row['TimeDuration'] . "</td>";
                            echo "<td>" . $row['TotalMarks'] . "</td>";
                            echo "<td><input type='radio' name='activeQuiz' value='" . $row['Quiz_Id'] . "' $checked></td>";
                            echo "</tr>";
                        }
                        echo "</tbody> </table>";
                    }
                     else {
                        echo "<p id='no-quiz'>No quiz found. Create New Quiz!</p>";
                    }
                ?>
            <button class='btn'  style="margin-top: 20px" type="submit">Update Active Quiz</button>
        </form>
    </div>

    <div class="iframe-container" id="iframeContainer">
        <iframe id="iframe1" src="about:blank"></iframe>
        <a href="#" class="delete-btn" onclick="DeleteAll()">Delete All</a>
        <a href="#" class="close-btn" onclick="closeIframe()">Close</a>
    </div>

    <script>
        function redirectIframe(iframeId, newUrl) {
            var iframeContainer = document.getElementById('iframeContainer');
            var iframe = document.getElementById(iframeId);
            iframe.src = newUrl;
            iframeContainer.style.display = 'block';
            document.body.classList.add('blur');  
        }

        function closeIframe() {
            var iframeContainer = document.getElementById('iframeContainer');
            window.location.reload();
            iframeContainer.style.display = 'none';
            document.body.classList.remove('blur');  
        }

        function DeleteAll() {
            if (confirm("Are you sure you want to delete all quizzes, this will delete all the informations?")) {
                
                let xhr = new XMLHttpRequest();
                xhr.open("POST", "DeleteAll.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                xhr.onreadystatechange = function () {
                    if (this.readyState === XMLHttpRequest.DONE) {
                        if (this.status === 200) {
                            try {
                                let response = JSON.parse(this.responseText);
                                alert(response.message);
                                // Optionally: Reload or update the quiz list on successful deletion
                                location.reload(); // Example: Reload the page
                            } catch (e) {
                                console.error("Error parsing JSON response: ", e);
                                alert("Failed to parse server response.");
                            }
                        } else {
                            alert("Failed to delete all quizzes. Server returned status: " + this.status);
                        }
                    }
                };
                xhr.send("deleteAll=true"); // Send a parameter to indicate delete all operation
            }
        }

        function logout() {
            window.location.href = "logout.php";
        }

    </script>
</html>
