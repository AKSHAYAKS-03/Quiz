<?php
 session_start();
include 'core_db.php';
date_default_timezone_set('Asia/Kolkata');

// Redirect to login if session variables are not set
if (!isset($_SESSION['RollNo']) || empty($_SESSION['RollNo'])) {
    header('Location: login.php');
    exit;
}

$rollno = $_SESSION['RollNo'];
$quizid = $_SESSION['active'];
$currentIndex = isset($_SESSION['currentIndex']) ? $_SESSION['currentIndex'] : 0;
$question_duration = $_SESSION['question_duration'];
$questions = $_SESSION['shuffled_questions']; // Get shuffled questions

// Redirect to final page if all questions are answered
if ($currentIndex >= count($questions)) {
    header('Location: final.php');
    exit;
}

$currentQuestionNo = $questions[$currentIndex];

// Fetch the specific question from the database
$query = $conn->prepare("SELECT * FROM multiple_choices WHERE QuizId = ? AND QuestionNo = ?");
$query->bind_param("ii", $quizid, $currentQuestionNo);
$query->execute();
$result = $query->get_result()->fetch_assoc();


// Fetch options for the current question
$options = [
    $result['Choice1'],
    $result['Choice2'],
    $result['Choice3'],
    $result['Choice4']
];

// Shuffle options if required
if ($_SESSION['shuffle'] == 1) {
    shuffle($options);
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quizze</title>
    
    <style>
body {
    background-color: #13274F;
    font-family: "Poppins", sans-serif;
    color: white;
    margin: 0;
    padding: 0;
}

.head {
    text-align: center;
    margin-top: 20px;
}

h1 {
    font-size: 36px;
}

.cot {
    width: 900px;
    height: 500px;
    background-color: white;
    padding: 40px; /* Adjust padding for inner content */
    border-radius: 8px;
    margin: 50px auto;
    color: #333;
    box-shadow: 1px 1px 10px rgba(0, 0, 0, 0.2);
    position: relative;
    -webkit-user-select: none; /* Safari */
    -ms-user-select: none; /* IE 10 and IE 11 */
    user-select: none; /* Standard syntax */
}

h2.ques {
    color: #13274F;
    margin-bottom: 20px;
}

.cot ul {
    list-style: none;
    padding: 0;
}

.cot ul li {
    color: #13274F;
    margin-bottom: 15px;
}

#optionsList {
    list-style: none;
    padding: 70px;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between; /* Distribute items evenly */
}

.option {
    width: calc(50% - 20px); /* Adjust width to fit two items per row with spacing */
    margin-bottom: 20px; /* Adjust spacing between rows */
}

.option input[type="radio"] {
    display: none; /* Hide default radio buttons */
}

  .option label {
    
            display: block;
            width: 100%; /* Full width for label */
            background-color: #f1f1f1;
            color: #13274F;
            border-radius: 5px;
            padding: 15px; /* Adjust padding for label */
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease;
            box-shadow: 1px 1px 5px rgba(0, 0, 0, 0.2);
            text-align: center; /* Center align text */
        }

.option input[type="radio"]:checked + label {
    background-color: #13274F;
    color: white;
    box-shadow: 1px 1px 10px rgba(0, 0, 0, 0.4);
}

.cot input[type="submit"] {
    background-color: #13274F;
    color: #fff;
    padding: 12px 24px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin-top: -20px;
    font-size: 16px;
    position: relative;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.cot input[type="submit"]:hover {
    background: #fff;
    color: #13274F;
}

#response {
            font-family: monospace;
            width: 100px;
            font-weight: bold;
            font-size: 35px;
            padding-right: 10px;
            margin-top: -20px;
            position: absolute;
            right: 20px; /* Adjust position if necessary */
            text-shadow: 1px 1px 5px #fff;

        }
@keyframes pop {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
    }
}

.pop {
    animation: pop 0.5s ease-in-out;
}

/* Full screen styles */
:-webkit-full-screen {
    background-color: transparent;
}

:-ms-fullscreen {
    background-color: transparent;
}

:fullscreen {
    background-color: transparent;
}

#quizContent {
    display: none;
}

#quizForm {
    text-align: center;
}
#agreement{
    text-align: center;
    font-size:20px;
    padding: 100px;
}
#agreebut{
    background-color: #13274F;
    color: #fff;
    padding: 12px 24px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin-top: -20px;
    font-size: 16px;
    position: relative;
    transition: background-color 0.3s ease, color 0.3s ease;
}
#agreebut:hover {
    background: #fff;
    color: #13274F;
}


    </style>
    <script src='DisableKeys.js'></script>
</head>
<body oncontextmenu="return false;">
<div class="head">
    <div class="container">
        <h1><?php echo htmlspecialchars($_SESSION['quiz_name']); ?></h1>
    </div>
</div>
<div class="cot">
        <div id="agreement">
            <h1>Terms of Quiz</h1>
            <p>You are not allowed to switch screens during the quiz. Once you agree and start the quiz, you cannot attempt it again.</p><br>
            <button onclick="agreeAndStart()" id="agreebut">I Agree</button>
        </div>

        <div id="quizContent">
        <center><div id="remtime" style="text-align: center; font-size: 20px;color: red "></div></center>
        <br>
            <div id="response"></div>
            <form id="quizForm">
                <br>
                <h2 id="questionText" class="ques"><?php echo $currentIndex + 1; ?>: <?php echo htmlspecialchars($result['Question']); ?></h2>
                <ul id="optionsList">
                <?php foreach ($options as $option): ?>
                    <div class="option">
                        <li>
                            <input type="radio" id="option_<?php echo htmlspecialchars($option); ?>" name="choice" value="<?php echo htmlspecialchars($option); ?>" >
                            <label for="option_<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></label>
                        </li>
                    </div>
                <?php endforeach; ?>
            </ul>
                
                <input type="hidden" name="questionNo" id="questionNo" value="<?php echo $currentQuestionNo; ?>">
                <input type="hidden" name="question_start_time" id="question_start_time" value="<?php echo time()?>">
                <input type="hidden" name="total" id="total" value="<?php echo count($questions); ?>">
                <input type="hidden" name="timeout" id="timeout" value="0">
                <input type="hidden" name="currentIndex" id="currentIndex" value="<?php echo $currentIndex; ?>">
                <input type="submit" name="submit" value="Submit Answer" id="submit">
                
            </form>
        </div>
    
</div>

<script>
var timer;
var interval;

function agreeAndStart() {
    <?php $_SESSION['agreed'] = 1; ?>

    var elem = document.documentElement;
    if (elem.requestFullscreen) {
        elem.requestFullscreen();
    } else if (elem.mozRequestFullScreen) { // Firefox
        elem.mozRequestFullScreen();
    } else if (elem.webkitRequestFullscreen) { // Chrome, Safari and Opera
        elem.webkitRequestFullscreen();
    } else if (elem.msRequestFullscreen) { // IE/Edge
        elem.msRequestFullscreen();
    }

    document.getElementById('agreement').style.display = 'none';
    document.getElementById('quizContent').style.display = 'block';
    startQuiz(); // Call startQuiz function here
    checkTime();
}

function startQuiz() {
    var durationStr = "<?php echo $question_duration; ?>"; // duration in "MM:SS" format
    var durationParts = durationStr.split(":");
    var minutes = parseInt(durationParts[0], 10);
    var seconds = parseInt(durationParts[1], 10);
    var duration = (minutes * 60) + seconds; // convert total duration to seconds

    var display = document.getElementById("response");
    if (!display) return; // Check if display element exists

    if (interval) {
        clearInterval(interval);
    }

    timer = duration;
    var halfway = Math.floor(duration / 2);

    // Immediately update the display
    updateDisplay();

    interval = setInterval(function () {
        if (--timer < 0) {
            clearInterval(interval);
            console.log('Timeout reached, submitting form.');
            document.getElementById('submit').click();
            document.getElementById('quizForm').submit();
        } else {
            updateDisplay();
        }
    }, 1000);

    function updateDisplay() {
        var minutes = parseInt(timer / 60, 10);
        var seconds = parseInt(timer % 60, 10);

        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;

        display.textContent = minutes + ":" + seconds;

        if (timer <= halfway) {
            display.style.color = 'red';
        } else {
            display.style.color = 'green';
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('quizForm').addEventListener('submit', function (event) {
        event.preventDefault(); // Prevent default form submission

        var formData = new FormData(this); // Capture form data

        formData.append('submit', 'Submit Answer');
        var xhr = new XMLHttpRequest();

        xhr.onreadystatechange = function () {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    console.log('Quiz data saved successfully.');
                    try {
                        var response = JSON.parse(xhr.responseText);
                        console.log('Response:', response);

                        if (response.status === 'next_question') {
                            console.log('Next Question Data:', response.data);
                            handleNextQuestion(response.data);
                        } else if (response.status === 'final') {
                            handleFinalPage();
                        } else if (response.status === 'error') {
                            console.error('Error:', response.message);
                            if (response.output) {
                                console.error('Output:', response.output); // Debugging output
                            }
                        }
                    } catch (e) {
                        console.error('Error parsing JSON response:', e);
                    }
                } else {
                    // Handle errors (optional)
                    console.error('Error saving quiz data.');
                }
            }
        };

        xhr.open('POST', 'process.php', true);
        xhr.send(formData);
    });
});

function handleNextQuestion(questionData) {
    // Clear the interval for the previous question
    if (interval) {
        clearInterval(interval);
    }

    // Update question text
    var questionTextElement = document.getElementById('questionText');
    if (!questionTextElement) {
        console.error('Question text element not found');
        return;
    }
    questionTextElement.innerText = questionData.question;

    // Clear existing options
    var optionsList = document.getElementById('optionsList');
    if (!optionsList) {
        console.error('Options list element not found');
        return;
    }
    optionsList.innerHTML = '';

    // Add new options
    questionData.options.forEach(function (option,index) {
        var listItem = document.createElement('li');
        var radioInput = document.createElement('input');
        var label = document.createElement('label');

        radioInput.type = 'radio';
        radioInput.name = 'choice';
        radioInput.value = option;
        
        radioInput.id = 'option_' + index; // Unique ID for each radio input
        label.htmlFor = radioInput.id;
        label.textContent = option;

        listItem.appendChild(radioInput);
        listItem.appendChild(label);

        listItem.classList.add('option'); // Add 'option' class to the <li> element

        optionsList.appendChild(listItem);
    });

    // Update hidden fields
    document.getElementById('questionNo').value = questionData.questionNo;
    document.getElementById('question_start_time').value = questionData.question_start_time;
    document.getElementById('currentIndex').value = questionData.currentIndex;

    // Restart the quiz timer for the new question
    startQuiz();
    checkTime();
}

function handleFinalPage() {
    // Handle redirection or show final results
    window.location.href = 'final.php';
}

function checkTime() {
    var endingTime = new Date("<?php echo $_SESSION['endingtime']; ?>").getTime();
    var currentTime = new Date().getTime();
    var remainingTime = endingTime - currentTime;

    var quesduration = "<?php echo $question_duration; ?>"; // Ensure this matches your variable name for question duration
    var quesDurationParts = quesduration.split(":");
    var quesDurationMillis = (parseInt(quesDurationParts[0]) * 60 + parseInt(quesDurationParts[1])) * 1000;

    if (remainingTime <= quesDurationMillis) {
        document.getElementById('remtime').innerHTML = "You have " + formatTime(remainingTime) + " Left";
    }

    var interval = setInterval(function() {
        var currentTime = new Date().getTime();
        if (currentTime >= endingTime) {
            clearInterval(interval);
            window.location.href = 'final.php';
        }
    }, 1000);
}

function formatTime(ms) {
    var totalSeconds = Math.floor(ms / 1000);
    var hours = Math.floor(totalSeconds / 3600);
    var minutes = Math.floor((totalSeconds % 3600) / 60);
    var seconds = totalSeconds % 60;

    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
}
</script>
</body>
</html>