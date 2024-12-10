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
$duration = $_SESSION['duration'];
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
    background-image: url("img3.jpg");
    background-repeat: no-repeat;
    background-attachment: fixed;
    background-position: center;
    font-family: 'Poppins', sans-serif;
    background-size: cover;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.head {
    text-align: center;
    margin-top: 20px;
    text-transform: uppercase;

}

h1 {
    font-size: 36px;
}

.quizContent {
    width: 900px;
    height: auto;
    background-color: white;
    padding: 40px;
    border-radius: 8px;
    color: #333;
    box-shadow: 1px 1px 20px rgba(0, 0, 0, 0.2);
    position: relative;
    -webkit-user-select: none;
    -ms-user-select: none;
    user-select: none;
    backdrop-filter: blur(10px);

}

h2.ques {
    color: #13274F;
    font-size: 24px;
    margin-bottom: 20px;
    font-weight: 600;
    line-height: 1.4;
    text-align: center;
}

.quizContent ul {
    list-style: none;
    padding: 0;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.quizContent ul li {
    color: #13274F;
    margin-bottom: 25px;
}

/* #optionsList {
    margin-top: 0;
    list-style: none;
    padding: 0;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between; /* Ensures even spacing */
    /* gap: 10px; /* Adjust spacing between items */
/* } */ 
.option {
    margin: 0 auto;
    align-items: center;
    text-align: center;
}

.option input[type="radio"] {
    display: none; /* Hide default radio buttons */
}

.option label {
    display: flex;
    justify-content: center; /* Centers content horizontally */
    align-items: center; /* Centers content vertically */
    background-color: #f9f9f9;
    color: #13274F;
    border-radius: 10px;
    padding: 10px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
    width:  300px;
    height: 50px;
}

.option label:hover {
    background-color: #dce4f7;
    transform: translateY(-5px);
}

.option input[type="radio"]:checked + label {
    background-color: #13274F;
    color: #fff;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
    transform: translateY(-10px);
}




.quizContent input[type="submit"] {
    background-color: #13274F;
    margin-top: 20px;
    color: #fff;
    padding: 12px 20px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    cursor: pointer;
    width: 100px;
    text-transform: uppercase;
    transition: background-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.quizContent input[type="submit"]:hover {
    background-color: #fff;
    color: #13274F;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
    transform: scale(1.05);
}

.quizContent input[type="submit"]:active {
    transform: scale(0.98);
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
}

#response {
    font-family: monospace;
    width: 100px;
    font-weight: bold;
    font-size: 35px;
    padding-right: 10px;
    margin-top: -20px;
    position: absolute;
    right: 20px;
    text-shadow: 1px 1px 5px #fff;
}

@keyframes blinker {
    50% {
        opacity: 0;
    }
}

.blink{
    animation: blinker 1s ease-in-out infinite;
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

#quizContent {
    display: none;
}

#quizForm {
    text-align: center;
}
#agreement {
    text-align: center;
    font-size: 16px;
    background-color: #ffffff;
    padding: 40px 30px;
    width: 50%;
    margin: 50px auto;
    color: #13274F;
    border-radius: 10px;
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    animation: fadeIn 1s ease-in-out;
}

#agreement h2 {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 20px;
}

.terms-content {
    font-size: 16px;
    line-height: 1.6;
    margin-bottom: 20px;
}

#agreebut {
    background-color: #13274F;
    color: #fff;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease-in-out;
}

#agreebut:hover {
    background-color: #fff;
    color: #13274F;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
    transform: translateY(-2px);
}

#agreebut:active {
    transform: scale(0.95);
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
}

@keyframes fadeIn {
    0% {
        opacity: 0;
        transform: translateY(-20px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgb(0, 0, 0);
    background-color: rgba(0, 0, 0, 0.4);
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 450px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
    transform: scale(0);
    transition: transform 0.5s ease;
}

.modal-content.show-modal {
    transform: scale(1); 
}

#msg {
    color: black;
    font-weight: bold;
    font-size: 20px;
    padding: 10px;
    padding-bottom: 25px;
    text-align: center;
    margin-top: 10px;
    font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
}

.button-container {
    display: flex;
    justify-content: center;
    gap: 30px; 
    margin-top: 10px;
}

.modal-content button {
    padding: 8px 10px;
    background-color: #13274F;
    color: #ecf0f1;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    width: 80px;
    height: 30px;
    transition: background-color 0.3s ease;
}

.modal-content button:hover {
    background-color: #0d1b37;
}

#remtime {
    font-size: 40px;
    color: red;
    font-weight: bold;
    font-family: 'poppins' sans-serif;
    opacity: 0;
    transition: opacity 1s ease-in-out;
}

#remtime.show {
    opacity: 1;
}

                
.question-container {
    width: 100%;
    padding: 10px;
}

#questionText {
    color: black;
    text-align: left; 
    font-size: 20px;
    margin: 10px 0;
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
<center><div id="remtime"></div></center>
<center>
        <div id="agreement">
            <h2>Terms of Quiz</h2>
            <h4>You are not allowed to switch screens during the quiz. Once you agree and start the quiz, you cannot attempt it again.</h4><br>
            <button onclick="agreeAndStart()" id="agreebut">I Agree</button>
        </div>
        </center>
        <div id="quizContent" class="quizContent">
        <br>
        <div id="response"></div>
        <center>
            <form id="quizForm">
                <br>
                <?php $index = 1; ?>
                <div class="question-container">
                    <h2 id="questionText" class="ques">
                    <?php echo $currentIndex + 1; ?> . <?php echo htmlspecialchars($result['Question']); ?>
                    </h2>
                </div>
                

                <br> 
                <ul id="optionsList">
                <?php foreach ($options as $option): ?>
                   
                    <div class="option">
                        <li>
                            <input type="radio" id="option_<?php echo htmlspecialchars($option); ?>" name="choice" value="<?php echo htmlspecialchars($option); ?>"  >
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
                <input type="submit" name="submit" value="Submit" id="submit">
                <?php $index+=1; ?>
            </form>
                </center>
        </div>
    
        <div class="modal" id="QuizModal">
    <div class="modal-content">
        <div id="msg">Are you sure you want to exit the quiz? You are not allowed to re-take the quiz again.</div>
        <div class="button-container">
            <button type="button" class="btn" id="yes">Yes</button>
            <button type="button" class="btn" id="no">No</button>
        </div>
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
var interval;

function startQuiz() {
    var durationStr = "<?php echo $question_duration; ?>"; 
    var durationParts = durationStr.split(":");
    var minutes = parseInt(durationParts[0], 10);
    var seconds = parseInt(durationParts[1], 10);
    var duration = (minutes * 60) + seconds; 

    var display = document.getElementById("response");
    if (!display) return; 

    if (interval) {
        clearInterval(interval);
    }

    var timer = duration;
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
            display.style.color = '#c94c4c';
            display.classList.add('blink');
        } else {
            display.style.color = '#82b74b';
            display.classList.remove('blink');
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('quizForm').addEventListener('submit', function (event) {
        event.preventDefault();

        var formData = new FormData(this); 

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
                                console.error('Output:', response.output); 
                            }
                        }
                    } catch (e) {
                        console.error('Error parsing JSON response:', e);
                    }
                } else {
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

    // Update question text and number
    var questionTextElement = document.getElementById('questionText');
    if (!questionTextElement) {
        console.error('Question text element not found');
        return;
    }

    // Correctly display question number (1-based index)
    questionTextElement.innerHTML = (questionData.currentIndex + 1) + ' . ' + questionData.question;

    // Clear existing options
    var optionsList = document.getElementById('optionsList');
    if (!optionsList) {
        console.error('Options list element not found');
        return;
    }
    optionsList.innerHTML = '';

    // Add new options
    questionData.options.forEach(function (option, index) {
        var listItem = document.createElement('li');
        var radioInput = document.createElement('input');
        var label = document.createElement('label');

        radioInput.type = 'radio';
        radioInput.name = 'choice';
        radioInput.value = option;
        radioInput.id = 'option_' + index;

        label.htmlFor = radioInput.id;
        label.textContent = option;

        listItem.appendChild(radioInput);
        listItem.appendChild(label);

        listItem.classList.add('option');
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

    var quesduration = "<?php echo $duration; ?>"; // Ensure this matches your variable name for question duration
    var quesDurationParts = quesduration.split(":");
    var quesDurationMillis = (parseInt(quesDurationParts[0]) * 60 + parseInt(quesDurationParts[1])) * 1000;

    if (remainingTime <= quesDurationMillis) {
        var remTimeElem = document.getElementById('remtime');
        
        var hours = Math.floor(remainingTime / 3600000);
        var minutes = Math.floor((remainingTime % 3600000) / 60000);
        var seconds = Math.floor((remainingTime % 60000) / 1000);

        if (hours > 0) {
            displayTime = hours + ' hr ' + minutes + ' min';
        } else if (minutes > 0) {
            displayTime = minutes + ' min ' + seconds + ' sec';
        } else {
            displayTime = seconds + ' sec';
        }

        remTimeElem.innerHTML = "Quiz Ends In " + displayTime;
        remTimeElem.classList.add('show');
    }
    setTimeout(function() {
            remTimeElem.classList.remove('show');
        }, 3000); // Show the message for 5 seconds

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