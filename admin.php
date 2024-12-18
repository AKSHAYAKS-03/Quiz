<?php
include_once 'core_db.php';

session_start();

if (!$_SESSION['logged'] || $_SESSION['logged'] === '') {
    header('Location: index.php');
    exit;
}

$activeQuizQuery = "SELECT QuizName, Quiz_Id, QuizType FROM quiz_details WHERE IsActive = 1 LIMIT 1";
$activeQuizResult = $conn->query($activeQuizQuery);
$activeQuizData = $activeQuizResult->fetch_assoc();

$activeQuiz = $activeQuizData['QuizName'] ?? 'None';
$activeQuizId = $activeQuizData['Quiz_Id'] ?? 'None';
$quizType = $activeQuizData['QuizType'] ?? 'None';

$_SESSION['active'] = $activeQuizId;
$_SESSION['activeQuiz'] = $activeQuiz;
$_SESSION['QuizType'] = $quizType;

echo '<script>console.log("Active Quiz type: ' . $quizType . '");</script>';
$_SESSION['quiz'] = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['activeQuiz'])) {
    $newActiveQuizId = $_POST['activeQuiz'];

    $conn->query("UPDATE quiz_details SET IsActive = 0");

    $conn->query("UPDATE quiz_details SET IsActive = 1 WHERE Quiz_Id = $newActiveQuizId");

    $activeQuizRes = $conn->query("SELECT QuizName FROM quiz_details WHERE Quiz_Id = $newActiveQuizId");
    $activeQuiz = $activeQuizRes->fetch_assoc()['QuizName'];

    $activeQuizId = $newActiveQuizId;
    $_SESSION['active'] = $activeQuizId;
    $_SESSION['activeQuiz'] = $activeQuiz;
}

$query = "SELECT Quiz_Id, QuizName, QuizType, NumberOfQuestions,Active_NoOfQuestions, TimeDuration, TotalMarks, IsActive FROM quiz_details";
$result = $conn->query($query);
 
$endTime='';
$startTime='';
$quizDuration='';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin</title>
    <link href="css/admin.css" rel="stylesheet">
    <!-- <script src="inspect.js"></script> -->
   
</head>
<body>
    <nav class='admin-nav'>
        <h2>Admin</h2>
        <ul>
            <li><a href="Add_Quiz.php">Add New Quiz</a></li>
            <li><a href="#" onclick="toggleSubmenu('multipleChoicesMenu')">Multiple Choices</a></li>
            <ul id="multipleChoicesMenu" style="display: none;background-color:#2c3e50">
                <li><a href="Q_add.php">Add Question</a></li>
                <li><a href="Q_Edit.php">Edit/Delete Question</a></li>
            </ul>
            <li><a href="#" onclick="toggleSubmenu('FillupMenu')">Fill Up</a></li>
            <ul id="FillupMenu" style="display: none;background-color:#2c3e50">
                <li><a href="Fillup_Q_add.php">Add/Edit Question </a></li>
            </ul>
            <li><a href="#" onclick="redirectIframe('iframe1', 'Delete_Quiz.php')">Delete Quiz</a></li>
            <li><a href="reset.php">Reset Options</a></li>
            <li><a href="ViewResult.php">View Result</a></li>
            
        </ul>
    </nav>

    <div class="content">
        <h1>Quiz Details</h1>
        <div class="header-right">
            <a href="about.html" title="About">
                <img src="icons/about.svg" alt="about">
            </a>
            <a href="logout.php" id="logout" title="Log Out">
                <img src="icons/exit.svg" alt="exit">
            </a>
        </div>

        <p>Active Quiz: <strong><?php echo $activeQuiz; ?></strong></p>
        <?php if ($result && $result->num_rows > 0) { ?>
            <form method="post" action="">
            <table class="quiz-details">
                <thead>
                    <tr>
                        <th>Quiz No.</th>
                        <th>Quiz Name</th>
                        <th>Quiz Type</th>
                        <th>Number of Questions</th>
                        <th>Active No.of Questions</th>
                        <th>Time Duration</th>
                        <th>Total Marks</th>
                        <th>Active</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()) {
                    $checked = $row['IsActive'] ? 'checked' : '';
                    echo "<tr>";
                    echo "<td>" . $row['Quiz_Id'] . "</td>";
                    echo "<td>" . $row['QuizName'] . "</td>";
                    echo "<td>" . ($row['QuizType']==0?"Multiple Choice":"Fill Up"). "</td>";
                    echo "<td>" . $row['NumberOfQuestions'] . "</td>";
                    echo "<td>" . ($row['Active_NoOfQuestions']==0? $row['NumberOfQuestions']:$row['Active_NoOfQuestions']) . "</td>";
                    echo "<td>" . $row['TimeDuration'] . "</td>";
                    echo "<td>" . $row['TotalMarks'] . "</td>";
                    echo "<td><input type='radio' name='activeQuiz' value='" . $row['Quiz_Id'] . "' $checked></td>";
                    echo "</tr>";
                } ?>
                </tbody>
            </table>
            <button class='btn' style="margin-top: 20px" type="submit">Update Active Quiz</button>
        </form>
        <?php } else { ?>
            <p id='no-quiz'>No quiz found. Create New Quiz!</p>
        <?php } ?>
    </div>

    <div class="modal" id="timeModal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h2 id='quizHeader'></h2>
            <form id="timeForm">
                <div id="timeMessage"></div>
                <label for="startTime">Start Time:</label>
                <input type="datetime-local" id="startTime" name="startTime" value="<?= $startTime ?>" required>
                <br>
                <label for="endTime">End Time:</label>
                <input type="datetime-local" id="endTime" name="endTime" value="<?= $endTime ?>" required>
                <br>
                <input type="hidden" id="quizDuration" name="quizDuration">
                <input type="hidden" id="quizId" name="quizId">

                <button type="button" class="btn" onclick="saveTime()">Save</button>
            </form>
        </div>
    </div>

    <div class="iframe-container" id="iframeContainer">
        <iframe id="iframe1" src="about:blank"></iframe>
        <a href="#" class="delete-btn" onclick="DeleteAll()">Delete All</a>
        <a href="#" class="close" onclick="closeIframe()">Close</a>
    </div>

    <script>
        function toggleSubmenu(menuId) {
            const menu = document.getElementById(menuId);
            if (menu.style.display === "none") {
                menu.style.display = "block";
            } else {
                menu.style.display = "none";
            }
        }

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
                                location.reload();
                            } catch (e) {
                                console.error("Error parsing JSON response: ", e);
                                alert("Failed to parse server response.");
                            }
                        } else {
                            alert("Failed to delete all quizzes. Server returned status: " + this.status);
                        }
                    }
                };
                xhr.send("deleteAll=true");
            }
        }

        document.querySelector('form').addEventListener('submit', function(event) {
            event.preventDefault();
            openModal();
        });

        function openModal() {
            var activeQuizId = document.querySelector('input[name="activeQuiz"]:checked');
            if (activeQuizId) {
                activeQuizId = activeQuizId.value;
                document.getElementById('quizId').value = activeQuizId;

                fetch(`UpdateQuizTime.php?quizId=${activeQuizId}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('quizHeader').innerText = data.quizHeader;
                        document.getElementById('startTime').value = data.startTime;
                        document.getElementById('endTime').value = data.endTime;
                        document.getElementById('quizDuration').value = data.quizDuration;
                        var modal = document.getElementById('timeModal');
                        modal.style.display = 'block'; 
                        setTimeout(() => {
                            modal.querySelector('.modal-content').classList.add('show-modal'); 
                        }, 10);
                    })
                    .catch(error => console.error('Error:', error));
            } else {
                alert('Please select an active quiz to update.');
            }
        }

        function closeModal() {
            document.getElementById('timeMessage').innerHTML = '';
            document.querySelector('.modal-content').classList.remove('show-modal'); 
            setTimeout(() => {
                document.getElementById('timeModal').style.display = 'none';
            }, 500); 
        }

        function saveTime() {
            var startTime = document.getElementById('startTime').value;
            var endTime = document.getElementById('endTime').value;
            var quizId = document.getElementById('quizId').value;

            if (!startTime || !endTime) {
                document.getElementById('timeMessage').innerHTML = 'Please fill out both start and end times.';
                return;
            }

            var quizDuration = document.getElementById('quizDuration').value;
            var [durationMinutes, durationSeconds] = quizDuration.split(':').map(Number);
            var startDateTime = new Date(startTime);
            var endDateTime = new Date(endTime);
            var minEndDateTime = new Date(startDateTime.getTime() + durationMinutes * 60000 + durationSeconds * 1000);

            if(startDateTime < new Date()) {
                document.getElementById('timeMessage').innerHTML = 'Start time must be in the future.';
                return;
            } 
            else if (endDateTime < minEndDateTime) {
                document.getElementById('timeMessage').innerHTML = 'End time must be at least ' + quizDuration + ' after the start time.';
                return;
            }

            else {
                fetch('updateQuizTime.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ startTime, endTime, quizId })
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    closeModal();
                    location.reload(); 
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    closeModal();
                });
            }
        }
    </script>
</body>
</html>