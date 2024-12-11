<?php
include_once 'core_db.php';
session_start();

if(!$_SESSION['logged'] || $_SESSION['logged']===''){
    header('Location: login.php');
    exit;
}

$ActiveQuizId = $_SESSION['quiz']==='' ? $_SESSION['active'] : $_SESSION['quiz'];
$activeQuiz = $_SESSION['activeQuiz'];
$quizname = $_SESSION['quiz_name'];

if($ActiveQuizId!==$_SESSION['active']){
    $query = 'SELECT QuizName FROM quiz_details where quiz_id = '.$ActiveQuizId;
    $result = $conn->query($query);
    $activeQuiz = $result->fetch_assoc()['QuizName'];
}

else if($_SESSION['active']==='None' && $_SESSION['activeQuiz']==='None'){
    header('Location: NoActiveQuiz.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the raw POST data and decode the JSON into an array
    $data = json_decode(file_get_contents('php://input'), true);

    // Check if the data is valid
    if ($data) {

        foreach ($data as $question) {
            $quizId = $question['quizId'];         
            $questionNo = $question['questionNo']; 
            $questionText = $question['question'];
            $quesType = $question['quesType'];     
            $options = $question['options'];       
            $rangeStart = $question['rangeStart'];
            $rangeEnd = $question['rangeEnd'];     

            // Insert the question into the database
            $stmt = $conn->prepare("INSERT INTO fillup (QuizId, QuestionNo, Question, Ques_Type) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iisi", $quizId, $questionNo, $questionText, $quesType);
            if (!$stmt->execute()) {
                echo json_encode(array("message" => "Error inserting question."));
                exit;
            }

            foreach ($options as $index => $option) {
                $ansId = uniqid(); 

                $stmt2 = $conn->prepare("INSERT INTO answers (QuizId, ans_Id, answer, Q_Id) VALUES (?, ?, ?, ?)");
                $stmt2->bind_param("isis", $quizId, $ansId, $option, $questionNo);
                if (!$stmt2->execute()) {
                    echo json_encode(array("message" => "Error inserting answers."));
                    exit;
                }
            }
            // if ($rangeStart !== null && $rangeEnd !== null) {
            //     // Range start
            //     $ansIdRangeStart = uniqid(); // Generate unique ID for range start
            //     $stmt3 = $conn->prepare("INSERT INTO answers (QuizId, ans_Id, answer, Q_Id) VALUES (?, ?, ?, ?)");
            //     $stmt3->bind_param("isis", $quizId, $ansIdRangeStart, "Range Start: " . $rangeStart, $questionNo);
            //     if (!$stmt3->execute()) {
            //         echo json_encode(array("message" => "Error inserting range start answer."));
            //         exit;
            //     }

            //     // Range end
            //     $ansIdRangeEnd = uniqid(); // Generate unique ID for range end
            //     $stmt4 = $conn->prepare("INSERT INTO answers (QuizId, ans_Id, answer, Q_Id) VALUES (?, ?, ?, ?)");
            //     $stmt4->bind_param("isis", $quizId, $ansIdRangeEnd, "Range End: " . $rangeEnd, $questionNo);
            //     if (!$stmt4->execute()) {
            //         echo json_encode(array("message" => "Error inserting range end answer."));
            //         exit;
            //     }
            // }        
        }

        // Return success response after all questions are processed
        echo json_encode(array("message" => "Questions and answers added successfully."));
    } else {
        // Return error if data is invalid
        echo json_encode(array("message" => "Invalid data."));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fillup Add Questions</title>
    <style>
      body {
    font-family: Arial, sans-serif;
    background-color: #13274F;
    margin: 0;
    padding: 0;
    color: #333;
}

.outer {
    background-color: #ecf0f1;
    width: 60%;
    box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.4);
    border-radius: 10px;
    color: #333;
    padding: 30px;
    text-align: center;
    margin: 50px auto;
}

.question-container {
    border: 1px solid #ccc;
    padding: 20px;
    margin-bottom: 30px;
    border-radius: 5px;
    background-color: white;
    box-shadow: 1px 1px 10px rgba(0, 0, 0, 0.1);
    color: black;
    display: flex;
    flex-direction: column;
    gap: 15px; 
}

.question-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

textarea {
    width: 100%;
    resize: vertical;
    padding: 8px;
    border-radius: 5px;
    border: 1px solid #ccc;
    font-size: 14px;
}

.options > div {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
}

input[type="text"], input[type="number"] {
    flex: 1;
    padding: 8px;
    border-radius: 5px;
    border: 1px solid #ccc;
    font-size: 14px;
}

.range-fields {
    display: none;
    margin-top: 10px;
    gap: 10px; 
}

button {
    padding: 8px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
}

.btn-delete {
    background : transparent;
    color: white;
    padding: 8px 12px;
}

.btn-option {
    background: transparent;
    color: white;
    padding: 8px 12px;
}

.btn-add {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    background-color: #13274F;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    color: #fff;
    transition: background-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
}

.btn-add:hover {
    background-color: #fff;
    color: #13274F;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
    transform: scale(1.05);
}

.btn-add:active {
    transform: scale(0.98);
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
}
#back{
    margin-top: 20px;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    background-color: #13274F;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    color: #fff;
    transition: background-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
}

#back:hover {
    background-color: #fff;
    color: #13274F;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
    transform: scale(1.05);
}

#back:active {
    transform: scale(0.98);
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
}

#submit{
    margin-top: 20px;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    background-color: #13274F;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    color: #fff;
    transition: background-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
}

#submit:hover {
    background-color: #fff;
    color: #13274F;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
    transform: scale(1.05);
}

#submit:active {
    transform: scale(0.98);
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
}

    </style>
</head>
<body>
    <div class="outer">
    <div id="form-container">
        <h1 style="text-align: center;text-transform: uppercase;">Fillup Add Questions - <?php echo $quizname; ?></h1>       
        <button class="btn btn-add" onclick="addQuestion()">Add Question</button>
        <form id="quiz-form" method="post">
        <div id="question-container"></div>
        <button type="submit" id="submit">Submit Questions</button>
        </form>
    </div>

    <button type="button" onclick="window.location.href = 'admin.php'" id="back">Back</button>
    </div>
    <script>
        // Add a new question dynamically
function addQuestion() {
    const form = document.getElementById("quiz-form");
    const questionCount = document.querySelectorAll(".question-container").length + 1;

    // Create a new question container
    const questionDiv = document.createElement("div");
    questionDiv.className = "question-container";

    questionDiv.innerHTML = `
        <div class="question-header">
            <label>Question ${questionCount}:</label>
            <button type="button" class="btn btn-delete" style="font-size: 20px;" onclick="deleteQuestion(this)">❌</button>
        </div>
        <textarea name="question-${questionCount}" rows="2" placeholder="Enter your question" required></textarea>
        <div class="options" id="options-${questionCount}">
            <div>
                <input type="text" name="option-${questionCount}-1" placeholder="Option 1" required>
                <button type="button" class="btn btn-option" style="color:black;font-size: 25px;" onclick="addOption(${questionCount})">+</button>
            </div>
        </div>
        <label>
            <input type="checkbox" name="range-${questionCount}" onclick="toggleRange(this, ${questionCount})"> Enable range choice
        </label>
        <div class="range-fields" id="range-fields-${questionCount}">
            <input type="number" name="range-start-${questionCount}" placeholder="Start range">
            <input type="number" name="range-end-${questionCount}" placeholder="End range">
        </div>
    `;

    form.appendChild(questionDiv);
    questionDiv.scrollIntoView({ behavior: "smooth", block: "end" }); 
    updateQuestionNumbers();
}

// Delete a question and update the question numbers
function deleteQuestion(button) {
    const questionDiv = button.closest(".question-container");
    questionDiv.remove();
    updateQuestionNumbers();
}

// Add an option to a specific question
function addOption(questionId) {
    const optionsDiv = document.getElementById(`options-${questionId}`);
    const optionCount = optionsDiv.children.length + 1;

    const optionDiv = document.createElement("div");
    optionDiv.innerHTML = `
        <input type="text" name="option-${questionId}-${optionCount}" placeholder="Option ${optionCount}" required>
        <button type="button" class="btn btn-delete" style="color:black;font-size: 25px;" onclick="this.parentElement.remove()">-</button>
    `;

    optionsDiv.appendChild(optionDiv);
}

// Update question numbers dynamically
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

// Toggle range fields visibility
function toggleRange(checkbox, questionId) {
    const rangeFields = document.getElementById(`range-fields-${questionId}`);
    rangeFields.style.display = checkbox.checked ? "block" : "none";
}

document.querySelector("#quiz-form").addEventListener("submit", function(event) {
    event.preventDefault(); 

    let formData = new FormData(this);
    let questionsData = [];

    // Collect data for each question
    document.querySelectorAll(".question-container").forEach((questionDiv, index) => {
        let questionNo = index + 1;
        let questionText = questionDiv.querySelector("textarea").value;
        let quesType = questionDiv.querySelector("input[type=checkbox]").checked ? 1 : 0;

        // Collect options
        let options = [];
        questionDiv.querySelectorAll(".options input").forEach(optionInput => {
            options.push(optionInput.value);
        });

        let rangeStart = questionDiv.querySelector(`input[name="range-start-${questionNo}"]`)?.value;
        let rangeEnd = questionDiv.querySelector(`input[name="range-end-${questionNo}"]`)?.value;

        questionsData.push({
            quizId: <?php echo $_SESSION['quizId']; ?>, // Get QuizId from session
            questionNo: questionNo,
            question: questionText,
            quesType: quesType,
            options: options,
            rangeStart: rangeStart,
            rangeEnd: rangeEnd
        });
    });

    // Send data to server (AJAX or form submission)
    fetch('process_fillup.php', {
        method: 'POST',
        body: JSON.stringify(questionsData),
        headers: {
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.message) {
            alert(data.message); // Display success message
            // You can redirect to another page or perform further actions here
        } else {
            alert("Failed to add questions.");
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("An error occurred while processing your request.");
    });
});


</script>
</body>
</html>