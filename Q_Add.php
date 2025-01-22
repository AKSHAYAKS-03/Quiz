<?php
include_once 'core_db.php';
session_start();

if(!$_SESSION['logged'] || $_SESSION['logged']===''){
    header('Location: index.php');
    exit;
}

$ActiveQuizId = $_SESSION['quiz']==='' ? $_SESSION['active'] : $_SESSION['quiz'];
$activeQuiz = $_SESSION['activeQuiz'];

if($_SESSION['QuizType']==1){  
    header('Location: NoActiveQuiz.php');
    exit;
}

else if($ActiveQuizId!==$_SESSION['active']){
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

    $uploadFile = 'NULL';
    if (isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] == 0) {
        $target_dir = __DIR__ . "/uploads/" . $quizId . "/";

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true); // Create the folder and any necessary parent folders
        }

        $target_file = $target_dir . basename($_FILES["upload_file"]["name"]);
        $uploadOk = 1;
        $imageFileType = pathinfo($target_file, PATHINFO_EXTENSION);

        $allowedExtensions = array("jpg", "jpeg", "png", "gif");

        if (!in_array(strtolower($imageFileType), $allowedExtensions)) {
            echo "Sorry, only JPG, JPEG, PNG, and GIF files are allowed.";
            $uploadOk = 0;
        }

        $check = getimagesize($_FILES["upload_file"]["tmp_name"]);
        if ($check === false) {
            $uploadOk = 0;
        }

        $relative_path = "uploads/" . $quizId . "/" . basename($_FILES["upload_file"]["name"]);
        
        if ($uploadOk && move_uploaded_file($_FILES["upload_file"]["tmp_name"], $target_file)) {
            $uploadFile = $relative_path;
        }
        else {
            $uploadFile = 'NULL';  
        }
    }

    $stmt = $conn->prepare("INSERT INTO multiple_choices (QuizId, QuestionNo, Question, Choice1, Choice2, Choice3, Choice4, Answer, img_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?,?)");
    
    $stmt->bind_param("iisssssss", $quizId, $questionNo, $question, $c1, $c2, $c3, $c4, $correct_choice, $uploadFile);
    
    ob_clean();
    if ($stmt->execute()) {
        $stmt2 = $conn->prepare("UPDATE quiz_details SET NumberOfQuestions = NumberOfQuestions + 1 WHERE Quiz_Id = ?");
        $stmt2->bind_param("i", $quizId);
        $stmt2->execute();
        $stmt2->close();

        http_response_code(200);
        echo json_encode(['message' => 'New question added successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Sorry, Failed to insert question."));
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
    <link rel="stylesheet" type="text/css" href="css/Q_Add.css">
 
</head>
<body oncontextmenu="return false;">
    <!-- <script type="text/javascript" src="inspect.js"></script> -->
    <script type="text/javascript" src="validate.js"></script>
    <div class="cont" id="add-container">
        <div class="contain">
            
            <h1><?php echo $activeQuiz?> Quiz
            </h1>
            
            <div class="top">
                    <button type="button" id="upload" onclick="window.location.href = 'store_excel.php';">Upload Questions</button>  
                    <button type="button" id="upload" style="margin-top:60px;margin-bottom:50px" onclick="window.location.href = 'load_image.php';">Upload Image Questions</button>  

                    <h3>Add Question 
                        <span id="current-question-no"><?php echo $Q_NO; ?> </span>
                    </h3> <br>
            </div>

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
                    <label>Upload Image (optional):</label>
                    <input type="file" name="upload_file" />
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

        document.getElementById('question-form').addEventListener('submit', function(event) {
            event.preventDefault(); 

            var formData = new FormData(this);

            fetch('Q_Add.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())  // Get the response as text to debug

            .then(data => {
                console.log(data.message);

                if (data.message) {
                    alert(data.message);

                    var questionNo = parseInt(document.querySelector('input[name="question_no"]').value);
                    document.querySelector('input[name="question_no"]').value = questionNo + 1;
                    document.getElementById('current-question-no').textContent = questionNo + 1;
                    
                    this.reset();
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