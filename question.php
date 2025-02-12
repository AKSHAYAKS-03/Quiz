<?php
session_start();
include 'core_db.php';
date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['RegNo']) || empty($_SESSION['RegNo'])) {
    header('Location: index.php');
    exit;
}

$RegNo = $_SESSION['RegNo'];
$name = $_SESSION['Name'];
$quizid = $_SESSION['active'];

$currentIndex = isset($_SESSION['currentIndex']) ? $_SESSION['currentIndex'] : 0;
$question_duration = $_SESSION['question_duration'];
$duration = $_SESSION['duration'];
$timertype = $_SESSION['TimerType'];

// echo $_SESSION["duration"]." ".$_SESSION['question_duration']." ".$_SESSION['TimerType'];

// echo $question_duration." ". $duration;
$questions = $_SESSION['shuffled_questions']; 

$activeQuestions = $_SESSION['Active_NoOfQuestions'];
if ($currentIndex >= $activeQuestions) {
    header('Location: final.php');
    exit;
}

$currentQuestionNo = $questions[$currentIndex];

if($_SESSION['QuizType'] ===0){
    $query = $conn->prepare("SELECT * FROM multiple_choices WHERE QuizId = ? AND QuestionNo = ?");
    $query->bind_param("ii", $quizid, $currentQuestionNo);
    $query->execute();
    $result = $query->get_result()->fetch_assoc();
    $options = [
        $result['Choice1'],
        $result['Choice2'],
        $result['Choice3'],
        $result['Choice4']
    ];

    if ($_SESSION['shuffle'] == 1) {
        shuffle($options);
    }
}
else{
    $query = $conn->prepare("SELECT * FROM FillUp WHERE QuizId = ? AND QuestionNo = ?");
    $query->bind_param("ii", $quizid, $currentQuestionNo);
    $query->execute();
    $result = $query->get_result()->fetch_assoc();
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quizze</title>
    <link rel="stylesheet" type="text/css" href="css/question.css">
    <!-- <script src='inspect.js'></script>
    <script src='DisableKeys.js'></script> -->
    <style>
        #questionImage{
            width: auto;
            min-width: 500px;
            max-width: 100%; 
            height:auto; 
            min-height: 350px;
            max-height: 100%;
            display: block; 
            margin: 0 auto;
        }
        body {
            background-color: #13274F;
            font-family: "Poppins", sans-serif;
            color: white;
            margin: 0;
            padding: 100px;
            font-family: 'Poppins', sans-serif;
            background-size: cover;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin-top: 60px;
        }

    .head {
        text-align: center;
        margin-top: -100px;
        text-transform: uppercase;
        letter-spacing: 2px; /* Added letter-spacing for better readability */
        font-size: 36px;
        font-weight: 700;
        color: #fff;
    }

    h1 {
        font-size: 36px;
    }

    .quizContent {
        width: auto;
        max-width: 900px;
        height: auto;
        /* max-height: 900px; */
        background-color: #fff;
        padding: 40px;
        border-radius: 15px;
        color: #333;
        box-shadow: 1px 1px 20px rgba(0, 0, 0, 0.2);
        position: relative;
        -webkit-user-select: none;
        -ms-user-select: none;
        user-select: none;
        /* backdrop-filter: blur(10px);
        background-color: #c0c0c0;
        background-color: #979dac;
        background-color: #AAB7C4; */
    }

    h2.ques {
        color: #13274F;
        font-size: 26px;
        margin-bottom: 25px;
        font-weight: 600;
        line-height: 1.4;
        text-align: center;
        text-shadow: 1px 1px 5px rgba(0, 0, 0, 0.2);
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
        font-size: 18px;
    }

    .option {
        width: auto;
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
        padding: 12px;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
        width:  300px;
        height: 55px;
        font-size: 17px;
        font-weight: 500;
    }

    .option label:hover {
        background-color: #dce4f7;
        transform: translateY(-5px);
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
    }

    .option input[type="radio"]:checked + label {
        background-color: #13274F;
        color: #fff;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        transform: translateY(-10px);
    }

    .quizContent input[type="submit"] {
        background-color: #13274F;
        margin-top: 30px;
        color: #fff;
        padding: 14px 25px;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        cursor: pointer;
        width: 120px;
        text-transform: uppercase;
        transition: background-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    .quizContent input[type="submit"]:hover {
        background-color: #fff;
        color: #13274F;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        transform: scale(1.07);
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
        margin-top: -45px;
        position: absolute;
        margin-right: 40px;
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
        background-color: #f7f7f7;
        padding: 20px;
        border-radius: 8px;
        width: 80%;
        max-width: 600px;
        margin: 30px auto;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    #agreement h2 {
        text-align: center;
        color: #2c3e50;
        font-family: Arial, sans-serif;
        margin-bottom: 20px;
    }

    .terms-box {
        background-color: #ffffff;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #ddd;
        margin-bottom: 20px;
        font-family: Arial, sans-serif;
        color: #333;
        line-height: 1.6;
        text-align: justify; /* Aligns text inside the terms box to justify */
    }

    .terms-box h3 {
        font-size: 18px;
        font-weight: bold;
        color: #e74c3c;
        margin-bottom: 15px;
        text-align: justify; /* Aligns h3 to justify */
    }

    .terms-box p {
        font-size: 16px;
        margin: 10px 0;
        text-align: justify; /* Aligns paragraphs to justify */
    }

    #agreebut{
        background-color: #13274F;
        margin-top: 0px;
        color: #fff;
        padding: 10px 15px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        transition: background-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
        }
        
        #agreebut:hover {
        background-color: #fff;
        color: #13274F;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        transform: scale(1.05);
        }
        
        #agreebut:active {
        transform: scale(0.98);
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
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

    /* .modal {
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
        color: #000;
        max-width: 350px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        transform: scale(0);
        transition: transform 0.5s ease;
    }

    .modal-content.show-modal {
        transform: scale(1); 
    } */

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.7); /* Darker overlay */
        padding: 20px;
    }

    .modal-content {
        background-color: #ffffff;
        margin: 15% auto;
        padding: 30px;
        border-radius: 12px;
        width: 80%;
        max-width: 400px;
        text-align: center;
        color: #333333; /* Darker text for better readability */
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5); /* Soft shadow */
        transform: scale(0);
        transition: transform 0.4s ease, opacity 0.3s ease;
        opacity: 0;
    }

    .modal-content.show-modal {
        transform: scale(1); 
        opacity: 1;
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

    /* .modal-content button {
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
    } */

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
        min-width: 900px;    
        max-width: 1000px;
        margin-left:-10px;
        /* background-color: #e74c3c; */
        padding: 10px; 
    }

    #questionText {
        color: black;
        text-align: left; 
        font-size: 20px;
        margin: 10px 0;
    } 

    .answer-container {
        text-align: left;
        display: flex;
        margin-left: 50px;
        padding: 10px 0;
    }

    .answer-container label {
        font-size: 16px;
        margin-right: 10px;
        white-space: nowrap;  
        flex-shrink: 0;  
    }

    .answer-container input[type="text"] {
        padding: 5px 10px;
        font-size: 16px;
        border: none;
        border-bottom: 2px solid #000;  
        background: transparent;
        outline: none;
        text-align: left;
        flex-grow: 1; 
        width: 600px;  
    }

    .answer-container input[type="text"]:focus {
        border-color: #1f3f81;
    }

    .option{
        margin-top: -10px;
        /* background-color: #13274F; */
        margin-bottom:-20px;
    }
    </style>
</head>
<body oncontextmenu="return false;" data-rollno="<?php echo htmlspecialchars($RegNo); ?>" data-quizid="<?php echo htmlspecialchars($quizid); ?>">
<div class="head" id="head">
    <div class="container">
        <h1><?php echo htmlspecialchars($_SESSION['quiz_name']); ?></h1>
    </div>
</div>
<center>
    <!-- <?php echo $_SESSION['duration']; ?>
    <?php echo $_SESSION['question_duration']; ?> -->

        <div id="agreement">
            <h2>Terms of Quiz</h2>
            <div class="terms-box">
                <!-- <h3 style="color: red;text-align: justify;">You are not allowed to switch screens during the quiz. Once you agree to start the quiz, you cannot attempt it again.</h3> -->
                <h3 style="color: red;text-align: justify;">Attempting to exit fullscreen mode is considered as a violation. If this occurs, your quiz will be automatically terminated. Once you agree to start the quiz, you cannot attempt it again.</h3>
                <p style="text-align: justify;">Each question is allocated a specific amount of time. If the timer runs out before you submit your answer, the question will be skipped automatically.</p>
                <p style="text-align: justify;">If you do not select an answer for a question and move to the next one, it is not validated.</p>
                <p style="text-align: justify;">Once you move to the next question, you cannot go back to the previous question. Make sure to review your answer before proceeding.</p>
            </div>
            <button onclick="agreeAndStart()" id="agreebut">I Agree</button>
        </div>

        </center>
        <center><div id="remtime"></div></center>

        <div id="quizContent" class="quizContent">
        <br>
        <div id="response"></div> 
        <div id="fullTimerDisplay"></div> 

        <center>
            <form id="quizForm">
                <br>
                <?php $index = 1; ?>
                <div class="question-container" id="questionContainer">
                    <h2 id="questionText" class="ques">
                        <?php echo $currentIndex + 1; ?> .
                        <?php if (!empty($result['Question'])){
                             echo htmlspecialchars($result['Question']); 
                        }                        
                        if (!empty($result['img_path']) && $result['img_path']!='NULL') {
                            echo '<br/><center>
                                    <img id="questionImage" src="' . htmlspecialchars($result['img_path']) . '" alt="Question Image">
                                    </center>';
                        } ?>
                    </h2>
                </div>
                <?php if($_SESSION['QuizType'] ===0): ?>                    
                    <center><div class="option-container" style="display: block; ">
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
                <div><center>
                <?php else: ?>
                    
                    <div class="answer-container" id="answer" style="display: block;">
                    <strong><label for="option">Your Answer: </label></strong>
                    <input type="text" id="option" name="choice" placeholder="Type your answer here">
                </div>
                <?php endif; ?>
                
                <input type="hidden" name="questionNo" id="questionNo" value="<?php echo $currentQuestionNo; ?>">
                <input type="hidden" name="question_start_time" id="question_start_time" value="<?php echo time()?>">
                <input type="hidden" name="total" id="total" value="<?php echo $activeQuestions; ?>">
                <input type="hidden" name="timeout" id="timeout" value="0">
                <input type="hidden" name="currentIndex" id="currentIndex" value="<?php echo $currentIndex; ?>">
                <input type="submit" name="submit" value="Submit" id="submit" style="margin-top: -20px;">
                <?php $index+=1; ?>
            </form>
                </center>
        </div>

    <!-- Modal for warning -->
    <div id="QuizModal" class="modal" style="display: none;">
        <div class="modal-content">
            <p id="msg">Attempting to exit fullscreen mode is considered as a violation of the quiz rules. The quiz will now be terminated.</p>
        </div>
    </div>

</div>

<script>

var fullInterval; 
var timerType;

console.log("start loaded");
function agreeAndStart() {
    scrollToQuestion();
 
    let regNo = "<?php echo $RegNo; ?>";
    let quizId = "<?php echo $quizid; ?>";

    fetch('check_quiz_attempt.php?regNo=' + regNo + '&quizId=' + quizId)
        .then(response => response.json())
        .then(data => {
            if (data.status === "exists") {
                alert("You have already attended the quiz!");
                window.location.href = 'index.php';
            } 
            else if (data.status === "not_exists") {
                console.log("inserted");
            } 
            //else {
            //     alert("Error checking quiz status. Try again.");
            // }
        })
        .catch(error => console.error("Error:", error));

    <?php $_SESSION['agreed'] = 1; ?>

    var elem = document.documentElement;
    if (elem.requestFullscreen) {
        elem.requestFullscreen();
    } else if (elem.mozRequestFullScreen) {
        elem.mozRequestFullScreen();
    } else if (elem.webkitRequestFullscreen) {
        elem.webkitRequestFullscreen();
    } else if (elem.msRequestFullscreen) {
        elem.msRequestFullscreen();
    }

    document.getElementById('agreement').style.display = 'none';
    document.getElementById('quizContent').style.display = 'block';

    timerType = "<?php echo $_SESSION['TimerType']; ?>";
    
    if (timerType === "1") {
        startFullTimer(); 
    } else if (timerType === "0") {
        startQuiz();
    }
    checkTime();
}

var interval;

function startQuiz() {
    var durationStr = "<?php echo $question_duration; ?>"; 
    var durationParts = durationStr.split(":");
  
    var hours = 0, minutes = 0, seconds = 0;
    if (durationParts.length === 3) {
        hours = parseInt(durationParts[0], 10);
        minutes = parseInt(durationParts[1], 10);
        seconds = parseInt(durationParts[2], 10);
    } else if (durationParts.length === 2) {
        minutes = parseInt(durationParts[0], 10);
        seconds = parseInt(durationParts[1], 10);
    }

    var duration = (hours * 3600) + (minutes * 60) + seconds;
    var display = document.getElementById("response");
    if (!display) return; 

    if (interval) {
        clearInterval(interval);
    }

    var timer = duration;
    var criticalTime = Math.floor(timer * 0.10); 

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
        var displayHours = Math.floor(timer / 3600);
        var displayMinutes = parseInt(timer / 60, 10);
        var displaySeconds = parseInt(timer % 60, 10);

        displayHours = displayHours < 10 ? "0" + displayHours : displayHours;
        displayMinutes = displayMinutes < 10 ? "0" + displayMinutes : displayMinutes;
        displaySeconds = displaySeconds < 10 ? "0" + displaySeconds : displaySeconds;

        display.textContent = displayHours + ":" + displayMinutes + ":" + displaySeconds;

        if (timer <= criticalTime) {
            display.style.color = '#c94c4c';
            display.classList.add('blink');
        } else {
            display.style.color = '#82b74b';
            display.classList.remove('blink');
        }
    }
}

function startFullTimer() {

    if (interval) {
        clearInterval(interval);
    }
    if (fullInterval) {
        clearInterval(fullInterval);
    }

    var fullDurationStr = "<?php echo $duration; ?>"; 
    var durationParts = fullDurationStr.split(":");

    var endingTime = new Date("<?php echo $_SESSION['endingtime']; ?>").getTime();

    var hours = 0, minutes = 0, seconds = 0;
    if (durationParts.length === 3) {
        hours = parseInt(durationParts[0], 10);
        minutes = parseInt(durationParts[1], 10);
        seconds = parseInt(durationParts[2], 10);
    } else if (durationParts.length === 2) {
        minutes = parseInt(durationParts[0], 10);
        seconds = parseInt(durationParts[1], 10);
    }

    var fullTimer = (hours * 3600) + (minutes * 60) + seconds;
    var display = document.getElementById("response");
    var criticalTime = Math.floor(fullTimer * 0.10); 
    console.log(formatTime(criticalTime));


    if (!display) return;
  
    updateFullDisplay();

    fullInterval = setInterval(function () {
        if (--fullTimer < 0) {
            clearInterval(fullInterval);
            console.log('Full quiz timeout reached, submitting form.');
                document.getElementById('submit').click();
                // document.getElementById('quizForm').submit();         
                handleFinalPage();

        } else {
            updateFullDisplay();
        }
    }, 1000);

    function updateFullDisplay() {
        if(interval){
        clearInterval(interval);
    }
    var displayHours = Math.floor(fullTimer / 3600);
        var displayMinutes = Math.floor((fullTimer % 3600) / 60);
        var displaySeconds = fullTimer % 60;

        displayHours = displayHours < 10 ? "0" + displayHours : displayHours;
        displayMinutes = displayMinutes < 10 ? "0" + displayMinutes : displayMinutes;
        displaySeconds = displaySeconds < 10 ? "0" + displaySeconds : displaySeconds;

        display.textContent = displayHours + ":" + displayMinutes + ":" + displaySeconds;

        if (fullTimer <= criticalTime) {
            display.style.color = '#c94c4c';
            display.classList.add('blink'); 
        } else {
            display.style.color = '#82b74b';
            display.classList.remove('blink');
        }
    
    }    
    checkTime();

}

document.addEventListener('DOMContentLoaded', function () {

    document.getElementById('quizForm').addEventListener('submit', function (event) {
        event.preventDefault();

        var formData = new FormData(this); 
        console.log('Form Data:', formData);

        var quizType = <?php echo $_SESSION['QuizType']; ?>;
        if (quizType === 1) { 
            var userAnswer = document.getElementById('option').value;
            formData.set('choice', userAnswer);
        }

        formData.append('submit', 'Submit Answer');
        var xhr = new XMLHttpRequest();

        xhr.onreadystatechange = function () {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    console.log('Quiz data saved successfully.');
                    try {
                        var response = JSON.parse(xhr.responseText);
                        console.log('Response:', response);
                        if(response.status === 'reset') {
                            console.log('Reset Data:', response.data);
                            handleReset();
                        }
                        else if (response.status === 'next_question') {
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

function handleReset(){
    var modal = document.getElementById('QuizModal');
    modal.style.display = 'block';
    setTimeout(() => {
        modal.querySelector('.modal-content').classList.add('show-modal'); 
    }, 10);
    var message = document.getElementById('msg');
    message.innerHTML = `
        Your Quiz has been reset by admin. You cannot further continue this attempt. 
        Please <a href="resetErrorPage.php">click here</a> to try again.
    `;
    document.getElementById('quizForm').style.pointerEvents = 'none';
}

function handleNextQuestion(questionData) {
    scrollToQuestion();
    console.log(questionData)

    var questionTextElement = document.getElementById('questionText');
    if (!questionTextElement) {
        console.error('Question text element not found');
        return;
    }

    questionTextElement.innerHTML = (questionData.currentIndex + 1) + ' . ' + questionData.question;

    var questionImageElement = document.getElementById('questionImage');
    
    console.log(questionData.questionImage);
    if(questionData.questionImage!=='NULL' && questionData.questionImage!=='') {
        if(!questionImageElement) {
            var questionImageElement = document.createElement('img');
            questionImageElement.id = 'questionImage';
            document.getElementById('questionText').appendChild(questionImageElement);
        }
        questionImageElement.src = questionData.questionImage;
    }else{
        const existingImg = document.getElementById('questionImage');
        console.log("img");
        if (existingImg) {
            existingImg.remove();
            console.log("img removed");
        }
        else{
            console.log("img not removed");
        }
    }

    var quizType = <?php echo $_SESSION['QuizType']; ?>;
    if (quizType === 0) {
        var optionsList = document.getElementById('optionsList');
        if (!optionsList) {
            console.error('Options list element not found');
            return;
        }
        optionsList.innerHTML = '';

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
    } else {
        var answer = document.getElementById('answer');
        if (!answer) {
            console.error('Answer element not found');
            return;
        }
        answer.textContent = '';

        var inputBox = document.createElement('input');
        var label = document.createElement('label');

        inputBox.type = 'text';
        inputBox.name = 'choice';
        inputBox.placeholder = 'Type your answer here';
        inputBox.id = 'option';

        label.htmlFor = inputBox.id;
        label.textContent = 'Your Answer: ';

        answer.appendChild(label);
        answer.appendChild(inputBox);
    }

    document.getElementById('questionNo').value = questionData.questionNo;
    document.getElementById('question_start_time').value = questionData.question_start_time;
    document.getElementById('currentIndex').value = questionData.currentIndex;

    if (timerType === "0") {
        startQuiz();
    }

    checkTime(); 
}

function handleFinalPage() {
    window.location.href = 'final.php';
}


function checkTime() {
    var endingTime = new Date("<?php echo $_SESSION['endingtime']; ?>").getTime();
    var currentTime = new Date().getTime();
    console.log("Ending Time: " + endingTime);
    console.log("Current Time: " + currentTime);
    var remainingTime = endingTime - currentTime;

    var quesduration = "<?php echo $duration; ?>"; 
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
        }, 3000);

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
function scrollToQuestion() {
        const questionContainer = document.getElementById('head');
        if (questionContainer) {
            questionContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
}
</script>
</body>
</html>