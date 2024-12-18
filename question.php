<?php
 session_start();
include 'core_db.php';
date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['RollNo']) || empty($_SESSION['RollNo'])) {
    header('Location: login.php');
    exit;
}

$rollno = $_SESSION['RollNo'];
$quizid = $_SESSION['active'];
$currentIndex = isset($_SESSION['currentIndex']) ? $_SESSION['currentIndex'] : 0;
$question_duration = $_SESSION['question_duration'];
$duration = $_SESSION['duration'];
$questions = $_SESSION['shuffled_questions']; 

$activeQuestions = $_SESSION['active_NoOfQuestions'];
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
    <script src='inspect.js'></script>
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
            <div class="terms-box">
                <h3 style="color: red;text-align: justify;">You are not allowed to switch screens during the quiz. Once you agree to start the quiz, you cannot attempt it again.</h3>
                <p style="text-align: justify;">Each question is allocated a specific amount of time. If the timer runs out before you submit your answer, the question will be skipped automatically.</p>
                <p style="text-align: justify;">If you do not select an answer for a question and move to the next one, it is not validated.</p>
                <p style="text-align: justify;">Once you move to the next question, you cannot go back to the previous question. Make sure to review your answer before proceeding.</p>
            </div>
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

                <?php if($_SESSION['QuizType'] ===0): ?>
                <div class="option-container" style="display: block; ">
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
                <div>
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
    startQuiz();
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
    if (interval) {
        clearInterval(interval);
    }

    var questionTextElement = document.getElementById('questionText');
    if (!questionTextElement) {
        console.error('Question text element not found');
        return;
    }

    questionTextElement.innerHTML = (questionData.currentIndex + 1) + ' . ' + questionData.question;
    
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

    startQuiz();
    checkTime();    
}

function handleFinalPage() {
    window.location.href = 'final.php';
}

function checkTime() {
    var endingTime = new Date("<?php echo $_SESSION['endingtime']; ?>").getTime();
    var currentTime = new Date().getTime();
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
</script>
</body>
</html>