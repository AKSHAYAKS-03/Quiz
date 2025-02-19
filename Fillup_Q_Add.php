<?php
include_once 'core_db.php';
include 'header.php';
session_start();

if (!isset($_SESSION['logged']) || empty($_SESSION['logged'])) {
    header('Location: index.php');
    exit;
}

$ActiveQuizId = empty($_SESSION['quiz']) ? $_SESSION['active'] : $_SESSION['quiz'];
$activeQuiz = $_SESSION['activeQuiz'];

if($_SESSION['QuizType']==0){  
    header('Location: NoActiveQuiz.php');
    exit;
}
else if ($ActiveQuizId !== $_SESSION['active']) {
    $query = 'SELECT QuizName FROM quiz_details WHERE quiz_id = ?';
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $ActiveQuizId);
    $stmt->execute();
    $result = $stmt->get_result();
    $activeQuiz = $result->fetch_assoc()['QuizName'];
} elseif ($_SESSION['active'] === 'None' && $_SESSION['activeQuiz'] === 'None') {
    header('Location: NoActiveQuiz.php');
    exit;    
}

if (empty($ActiveQuizId)) {
    // echo "ActiveQuizId is not set or is invalid.";
}

$query = "SELECT MAX(QuestionNo) AS max_question_no FROM fillup WHERE QuizId = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $ActiveQuizId);
$stmt->execute();
$stmt->bind_result($maxQuestionNo);
$stmt->fetch();
$stmt->close();

$Q_NO = ($maxQuestionNo === null) ? 1 : $maxQuestionNo + 1;

$query = "SELECT QuestionNo, Question, Ques_Type FROM fillup WHERE QuizId = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $ActiveQuizId);
$stmt->execute();
$questionsResult = $stmt->get_result();

$stmt->free_result(); 

$json_data = file_get_contents('php://input');

$questions = [];
while ($question = $questionsResult->fetch_assoc()) {
    $questionNo = $question['QuestionNo'];
    $optionsQuery = "SELECT answer FROM answer_fillup WHERE QuizId = ? AND Q_Id = ?";
    $stmtOptions = $conn->prepare($optionsQuery);
    $stmtOptions->bind_param("ii", $ActiveQuizId, $questionNo);
    $stmtOptions->execute();
    $optionsResult = $stmtOptions->get_result();

    $options = [];
    while ($option = $optionsResult->fetch_assoc()) {
        $options[] = $option['answer'];
    }

    $rangeStart = null;
    $rangeEnd = null;
    $stmtRange = $conn->prepare("SELECT answer FROM answer_fillup WHERE QuizId = ? AND Q_Id = ? ORDER BY answer");
    $stmtRange->bind_param("ii", $ActiveQuizId, $questionNo);
    $stmtRange->execute();
    $result = $stmtRange->get_result();

    $answers = [];
    while ($row = $result->fetch_assoc()) {
        $answers[] = $row['answer'];  
    }
    if (count($answers) > 0) {
        $rangeStart = $answers[0]; 
    }
    if (count($answers) > 1) {
        $rangeEnd = $answers[1]; 
    }
    $stmtRange->close();

    $questions[] = [
        'questionNo' => $questionNo,
        'question' => $question['Question'],
        'quesType' => $question['Ques_Type'],
        'options' => $options, 
        'range' => [
            'start' => $rangeStart,
            'end' => $rangeEnd
        ]
    ];

    $stmtOptions->free_result(); 
    $stmtOptions->close();  
}

$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean();
    
    header('Content-Type: application/json');
    $data = json_decode($json_data, true);  

    if ($data === null) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid JSON input']);
        exit;
    }

    $deleteAnswersQuery = "DELETE FROM answer_fillup WHERE QuizId = ?";
    $stmtDeleteAnswers = $conn->prepare($deleteAnswersQuery);
    $stmtDeleteAnswers->bind_param("i", $ActiveQuizId);
    $stmtDeleteAnswers->execute();
    $stmtDeleteAnswers->close();

    $deleteQuestionsQuery = "DELETE FROM fillup WHERE QuizId = ?";
    $stmtDeleteQuestions = $conn->prepare($deleteQuestionsQuery);
    $stmtDeleteQuestions->bind_param("i", $ActiveQuizId);
    $stmtDeleteQuestions->execute();
    $stmtDeleteQuestions->close();

    $newQuestionsAdded = 0;
    $questionNo = 1;


    foreach ($data as $question) {
        $questionText = $question['question'];
        $quesType = $question['quesType'];
        $options = $question['options'] ?? [];
        $range = $question['range'] ?? null;
        $quizId = $ActiveQuizId;

        $stmtInsert = $conn->prepare("INSERT INTO fillup (QuizId, QuestionNo, Question, Ques_Type) VALUES (?, ?, ?, ?)");
        $stmtInsert->bind_param("iisi", $quizId, $questionNo, $questionText, $quesType);
        if ($stmtInsert->execute()) {
            $newQuestionsAdded++;

            if ($quesType == 0 && !empty($options)) {
                foreach ($options as $option) {
                    if ($option != '') {
                        $stmtOption = $conn->prepare("INSERT INTO answer_fillup (QuizId, answer, Q_Id) VALUES (?, ?, ?)");
                        $stmtOption->bind_param("iss", $quizId, $option, $questionNo);
                        $stmtOption->execute();
                        $stmtOption->close();
                    }
                }
            }

            if ($quesType == 1 && $range !== null) {
                $rangeStart = $range['start'];
                $rangeEnd = $range['end'];

                $stmtRange1 = $conn->prepare("INSERT INTO answer_fillup (QuizId, Q_Id, answer) VALUES (?, ?, ?)");
                $stmtRange1->bind_param("iis", $quizId, $questionNo, $rangeStart);
                $stmtRange1->execute();
                $stmtRange1->close();

                $stmtRange2 = $conn->prepare("INSERT INTO answer_fillup (QuizId, Q_Id, answer) VALUES (?, ?, ?)");
                $stmtRange2->bind_param("iis", $quizId, $questionNo, $rangeEnd);
                $stmtRange2->execute();
                $stmtRange2->close();
            }

            $questionNo++;

        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error inserting question."]);
            exit;
        }
    }

    if ($newQuestionsAdded > 0) {
        
        $stmtUpdateQuiz = $conn->prepare("UPDATE quiz_details SET NumberOfQuestions = ? WHERE Quiz_id = ?");
        $stmtUpdateQuiz->bind_param("ii", $newQuestionsAdded, $quizId);

        if ($stmtUpdateQuiz->execute()) {
            http_response_code(200); 
            echo json_encode(["message" => "Questions and answers added successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error updating quiz details."]);
        }
        $stmtUpdateQuiz->close();
    }

    $stmtInsert->close();   
    exit;

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fillup Add Questions</title>
    <link rel="stylesheet" href="css/fillup_q_add.css">
    <link rel="stylesheet" href="css/navigation.css">
    <script src="inspect.js"></script>
    
</head>
<body>
    <div class="header">
        <a href="admin.php" id="back" title="Back">
            <img src="icons\back_white.svg" alt="back">
        </a>
    </div>
<div id="questions" data-questions='<?= htmlspecialchars(json_encode($questions), ENT_QUOTES, 'UTF-8') ?>'></div>

<div class="outer">

    <div id="form-container">
        <h1 style="text-align: center; text-transform: uppercase;">Fillup Add Questions</h1>
        <button class="btn btn-add" onclick="addQuestion()">Add Question</button>
        <form id="quiz-form">

            <div id="questions-container">
            </div>
            <strong><p style="color: #ff0000;">*Kindly submit the form after doing minor changes to see the results.</p></strong>

            <div style="display: flex; justify-content: center; gap: 30px">
                <button type="button" onclick="window.location.href = 'admin.php'" id="back">Back</button>
                <button type="submit" class="submit" id="submit">Submit</button>
            </div>
        </form>
    </div>
</div>


    <script>
        var questionsData = document.getElementById('questions').getAttribute('data-questions');

        try {
            var questions = JSON.parse(questionsData);
        } catch (error) {
            console.error('Error parsing JSON:', error);
        }


            
    function displayQuestions() {
    const questionsContainer = document.getElementById('questions-container');

    questions.forEach((question) => {
        const questionDiv = document.createElement('div');
        questionDiv.classList.add('question-container');
        questionDiv.dataset.id = question.questionNo;

        let questionHTML = `
            <div class="question-header">
                <label>Question ${question.questionNo}:</label>
                <button type="button" class="btn btn-delete" onclick="deleteQuestion(this, ${question.questionNo})">❌</button>
            </div>
            <textarea name="question-${question.questionNo}" rows="2" placeholder="Enter your question" required>${question.question}</textarea>
        `;

        if (question.quesType == 1) {  
            questionHTML += `
                <label>
                    <input type="checkbox" id="checkbox-${question.questionNo}" onclick="toggleRange(this, ${question.questionNo})" checked> Enable range choice
                </label>
                <div class="range-fields" id="range-fields-${question.questionNo}" style="display: block;">
                    <input type="number" step="0.00001" name="range-start-${question.questionNo}" placeholder="Start range" value="${question.range['start'] || ''}">
                    <input type="number" step="0.00001" name="range-end-${question.questionNo}" placeholder="End range" value="${question.range['end'] || ''}">
                </div>
            `;
        } else {
            const optionsHTML = question.options.map((option, index) => {
                return `
                    <div>
                        <input type="text" name="option-${question.questionNo}-${index + 1}" placeholder="Option ${index + 1}" value="${option}">
                        <button type="button" class="btn btn-delete" style="font-size: 16px;color: #13274F;"onclick="removeOption(this, ${question.questionNo}, '${option}')">-</button>
                    </div>
                `;
            }).join(''); 

            questionHTML += `
                <div class="options" id="options-${question.questionNo}">
                    ${optionsHTML}
                    <button type="button" class="btn btn-option" style="font-size: 16px;color: #13274F;" onclick="addOption(${question.questionNo})">+</button>
                </div>
            `;
        }

        questionDiv.innerHTML = questionHTML;
        questionsContainer.appendChild(questionDiv);

        const checkbox = document.getElementById(`checkbox-${question.questionNo}`);
        if (checkbox) {
            checkbox.checked = question.quesType === 1; 
        }
    });
}

displayQuestions();


        function addQuestion() {
            const questionsContainer = document.getElementById("questions-container");
            const questionCount = questionsContainer.children.length + 1;

            const questionDiv = document.createElement("div");
            questionDiv.classList.add("question-container");
            questionDiv.dataset.id = questionCount;
            questionDiv.innerHTML = `
                <div class="question-header">
                    <label>Question ${questionCount}:</label>
                    <button type="button" class="btn btn-delete" onclick="deleteQuestion(this, ${questionCount})">❌</button>
                </div>
                <textarea name="question-${questionCount}" rows="2" placeholder="Enter your question" required></textarea>
                <label>
                    <input type="checkbox" onclick="toggleRange(this, ${questionCount})"> Enable range choice
                </label>
                <div class="range-fields" id="range-fields-${questionCount}" style="display: none;">
                    <input type="number" step="0.01" name="range-start-${questionCount}" placeholder="Start range">
                    <input type="number" step="0.01" name="range-end-${questionCount}" placeholder="End range">
                </div>
                <div class="options" id="options-${questionCount}">
                    <div>
                        <input type="text" name="option-${questionCount}-1" placeholder="Option 1">
                        <button type="button" style="font-size: 16px;color: #13274F;" textclass="btn btn-option" onclick="addOption(${questionCount})">+</button>
                    </div>
                </div>
            `;
            questionsContainer.appendChild(questionDiv);

            questionDiv.scrollIntoView({ behavior: 'smooth' });
            updateQuestionNumbers();
        }

        function addOption(questionId) {
            const optionsDiv = document.getElementById(`options-${questionId}`);
            const optionCount = optionsDiv.children.length + 1;

            const optionDiv = document.createElement("div");
            optionDiv.innerHTML = `
                <input type="text" name="option-${questionId}-${optionCount}" placeholder="Option ${optionCount}">
                <button type="button" style="font-size: 16px;color: #13274F;" class="btn btn-delete" onclick="removeOption(this, ${questionId}, ${optionCount})">-</button>
            `;
            optionsDiv.appendChild(optionDiv);
        }

            function toggleRange(checkbox, questionId) {
                const rangeFields = document.getElementById(`range-fields-${questionId}`);
                rangeFields.style.display = checkbox.checked ? "block" : "none";

                const optionsDiv = document.getElementById(`options-${questionId}`);
                optionsDiv.style.display = checkbox.checked ? "none" : "block";
            }

            function updateQuestionNumbers() {
            const questionContainers = document.querySelectorAll(".question-container");
            questionContainers.forEach((container, index) => {
                const label = container.querySelector(".question-header label");
                label.textContent = `Question ${index + 1}:`;
                const textarea = container.querySelector("textarea");
                textarea.name = `question-${index + 1}`;
                const optionsDiv = container.querySelector(".options");
                optionsDiv.id = `options-${index + 1}`;
                const inputs = optionsDiv.querySelectorAll("input");
                inputs.forEach((input, i) => {
                    input.name = `option-${index + 1}-${i + 1}`;
                });
                const rangeFields = container.querySelector(".range-fields");
                if (rangeFields) {
                    rangeFields.id = `range-fields-${index + 1}`;
                    rangeFields.querySelectorAll("input").forEach((input, i) => {
                        input.name = i === 0
                            ? `range-start-${index + 1}`
                            : `range-end-${index + 1}`;
                    });
                }
            });
        }


            function deleteQuestion(button, questionId) {
                const questionDiv = button.closest(".question-container");

                if (questionId) {
                    fetch("Fillup_Q_Del.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: new URLSearchParams({
                            questionId: questionId 
                        })
                    })
                    .then(response => response.text())
                    .then(data => {
                        alert('Question and associated answers deleted successfully');
                        console.log(data);
                    })
                    .then(responseText => {
                        console.log(responseText); 
                        questionDiv.remove(); 
                    })
                    .catch(error => console.error("Error deleting question:", error));
                } else {
                    questionDiv.remove(); 
                }

                updateQuestionNumbers(); 
            }
            function removeOption(button) {

                    const questionId = button.getAttribute('data-question-id');
                    const optionValue = button.getAttribute('data-option');

                    const optionDiv = button.parentElement; 
                    optionDiv.remove(); 

                    const removedOption = {
                        questionId: questionId,
                        optionValue: optionValue
                    };

                    fetch('Fillup_Option_Del.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(removedOption)
                    })
                    .then(response => response.json())
                    .then(data => {
                        
                        alert('Option removed successfully');
                        console.log(data);
                        
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                }



    document.getElementById("quiz-form").addEventListener("submit", function (event) {
        event.preventDefault();

        const questionsData = [];
        document.querySelectorAll(".question-container").forEach((questionDiv, index) => {
            const questionNo = index + 1;
            const questionText = questionDiv.querySelector("textarea").value;

            const checkbox = questionDiv.querySelector("input[type=checkbox]");
            const quesType = checkbox ? (checkbox.checked ? 1 : 0) : 0;

            if (quesType === 0) {
                const options = Array.from(questionDiv.querySelectorAll(".options input"))
                    .map(optionInput => optionInput.value)
                    .filter(option => option.trim() !== ""); 

                questionsData.push({
                    questionNo: questionNo,
                    question: questionText,
                    quesType: quesType,
                    options: options, 
                    range: null, 
                });
            } else if (quesType === 1) {
                const rangeFields = questionDiv.querySelector(".range-fields");
                const rangeStart = rangeFields.querySelector("input[name^='range-start']").value;
                const rangeEnd = rangeFields.querySelector("input[name^='range-end']").value;

                questionsData.push({
                    questionNo: questionNo,
                    question: questionText,
                    quesType: quesType,
                    options: null, 
                    range: { start: rangeStart, end: rangeEnd }, 
                });
            }
        });

        fetch("Fillup_Q_Add.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(questionsData),
        })
        
        .then(response => {
            if (!response.ok) throw new Error("Network response was not ok");
            return response.json();
        })
        .then(data => {
            console.log(data);
            alert(data.message);
        })
        .catch(error => {
            console.error("Error during fetch:", error);
        });
        console.log(questions);  

    });

    </script>
</body>
