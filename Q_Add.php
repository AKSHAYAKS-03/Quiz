<?php
include_once 'core_db.php';
session_start();

if(!$_SESSION['logged'] || $_SESSION['logged']===''){
    header('Location: login.php');
    exit;
}

$ActiveQuizId = $_SESSION['quiz']==='' ? $_SESSION['active'] : $_SESSION['quiz'];
$activeQuiz = $_SESSION['activeQuiz'];

if($ActiveQuizId!==$_SESSION['active']){
    $query = 'SELECT QuizName FROM quiz_details where quiz_id = '.$ActiveQuizId;
    $result = $conn->query($query);
    $activeQuiz = $result->fetch_assoc()['QuizName'];
}

else if($_SESSION['active']==='None' && $_SESSION['activeQuiz']==='None'){
    header('Location: NoActiveQuiz.php');
    exit;
}

$Q_NoResult= $conn->query("SELECT count(QuestionNo) FROM multiple_choices WHERE QuizId = $ActiveQuizId"); 
$Q_NO = ($Q_NoResult->fetch_assoc()['count(QuestionNo)']) + 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $quizId = $ActiveQuizId;
    $questionNo = $_POST['question_no'];
    $question = $_POST['question_text'];
    $c1 = $_POST['choice1'];
    $c2 = $_POST['choice2'];
    $c3 = $_POST['choice3'];
    $c4 = $_POST['choice4'];
    $correct_choice = $_POST['correct_choice'];
    $exp = $_POST['exp'];

    $stmt = $conn->prepare("INSERT INTO multiple_choices (QuizId, QuestionNo, Question, Choice1, Choice2, Choice3, Choice4, Answer, Explanation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("iisssssss", $quizId, $questionNo, $question, $c1, $c2, $c3, $c4, $correct_choice, $exp);
    
    if ($stmt->execute()) {
        $stmt2 = $conn->prepare("UPDATE quiz_details SET NumberOfQuestions = NumberOfQuestions + 1 WHERE Quiz_Id = ?");
        $stmt2->bind_param("i", $quizId);
        $stmt2->execute();
        $stmt2->close();

        http_response_code(200);
        echo json_encode(array("message" => "New question added successfully."));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Sorry, failed to insert."));
    }
    
    $stmt->close();
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quizze</title>

    <script src="inspect.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #13274F;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            margin-top: 230px;
        }

        .cont {
            background-color: #ecf0f1;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 60%;
        }

        .cont h1, h3 {
            color: #13274F;
            margin-bottom: 30px;
            text-align: center;
        }

        .cont p {
            margin: 15px 0;
            text-transform: capitalize;
        }

        .Curr_Q{
            margin: 30px;
            padding: 20px;
        }

        label {
            display: inline-block;
            width: 140px;
            text-align: left;
            padding-left: 70px;
        }

        input[type='text'], textarea, input[type='number'] {
            width: calc(100% - 280px);
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
        }

        input[type='submit'], button {
            background: #13274F;
            color: #fff;
            padding: 10px 20px;
            border: 0;
            border-radius: 5px;
            margin-top: 20px;
            width: 100px;

        }

        input[type='submit']:hover, button:hover {
            cursor: pointer;
            font-weight: bolder;
            background-color: #0d1b37;
        }


        a {
            text-decoration: none;
            border: 2px solid #333;
            padding: 2px 12px;
            color: white;
            border-radius: 6px;
            background: #333;
            display: inline-block;
            margin-top: 20px;
        }

        a:hover {
            font-weight: bolder;
            color: #000;
        }

        .form-group {
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }

        .form-group textarea {
            resize: vertical;
        }

    </style>
</head>
<body oncontextmenu="return false;">
    <script type="text/javascript" src="inspect.js"></script>
    <script type="text/javascript" src="validate.js"></script>
    <div class="cont" id="add-container">
        <div class="contain">
            
            <h1><?php echo $activeQuiz?> Quiz</h1> 
            <h3>Add Question 
                <span id="current-question-no"><?php echo $Q_NO; ?> </span>  
            </h3> <br>

            <form id="question-form" method="post">            
                <p class="form-group">
                    <label>Question Text</label>
                    <textarea cols="80" rows="10" name="question_text" required></textarea>
                </p>
                <p class="form-group">
                    <label>Choice 1:</label>
                    <input type="text" name="choice1" required/>
                </p>
                <p class="form-group">
                    <label>Choice 2:</label>
                    <input type="text" name="choice2" required />
                </p>
                <p class="form-group">
                    <label>Choice 3:</label>
                    <input type="text" name="choice3" required />
                </p>
                <p class="form-group">
                    <label>Choice 4:</label>
                    <input type="text" name="choice4" required />
                </p>
                <p class="form-group">
                    <label><b>Correct Answer:</b></label>
                    <input type="text" name="correct_choice" required/>
                </p>
                <p class="form-group">
                    <label>Explanation</label>
                    <textarea cols="80" rows="10" name="exp" id="exp-textarea">NO EXPLANATION</textarea>
                </p>
                <p>
                    <input type="submit" name="submit" value="Next" />
                    <button type="button" onclick="window.location.href = 'admin.php'">Back</button>
                </p>
                <input type="hidden" name="question_no" value="<?php echo $Q_NO; ?>" />
            </form>
        </div>
    </div>

    <script>
        document.getElementById('exp-textarea').addEventListener('focus', function() {
            if (this.value === 'NO EXPLANATION') {
                this.value = '';
            }
        });

        document.getElementById('exp-textarea').addEventListener('blur', function() {
            if (this.value.trim() === '') {
                this.value = 'NO EXPLANATION';
            }
        });

        document.getElementById('question-form').addEventListener('submit', function(event) {
            event.preventDefault(); 

            var formData = new FormData(this);

            fetch('Q_Add.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    alert(data.message);

                    var questionNo = parseInt(document.querySelector('input[name="question_no"]').value);
                    document.querySelector('input[name="question_no"]').value = questionNo + 1;
                    document.getElementById('current-question-no').textContent = questionNo + 1;
                    
                    this.reset();
                    document.getElementById('exp-textarea').value = 'NO EXPLANATION';
                    scroll();
                } else {
                    alert('Failed to insert.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to insert.');
            });
        });

        function scroll() {
            const addContainer = document.getElementById('add-container');
            addContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

    </script>
</body>
</html>