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
    $c1 = trim($_POST['choice1']);
    $c2 = trim($_POST['choice2']);
    $c3 = trim($_POST['choice3']);
    $c4 = trim($_POST['choice4']);
    $correct_choice = trim($_POST['correct_choice']);

    $choices = [$c1, $c2, $c3, $c4];
    if (!in_array($correct_choice, $choices, true)) {
        echo json_encode(["message" => "Error: The correct answer must be one of the four choices."]);
        exit;
    }
    $uploadFile = 'NULL';
    if (isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] == 0) {     
        
        $target_dir = __DIR__ . "/uploads/" . $quizId . "/";

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true); // Create the folder and any necessary parent folders
        }

        $target_file = $target_dir . basename($_FILES["upload_file"]["name"]);
        $uploadOk = 1;

        $extension = strtolower(pathinfo($FILES["upload_file"]["name"], PATHINFO_EXTENSION));
        // echo "Uploading " . $relative_path;

        $relative_path = "uploads/" . $quizId . "/" .$questionNo . "." .$extension;

        $check = getimagesize($_FILES["upload_file"]["tmp_name"]);
        if ($check === false) {
            $uploadOk = 0;
        }

        
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
        echo json_encode(["message" => "Failed to insert question.", "error" => $stmt->error]);
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
    <link rel="stylesheet" type="text/css" href="css/navigation.css">
    <script src='DisableKeys_Fillup.js'></script>

    <style>
        body{
            margin: 50px;
        }
    </style>
</head>
<body oncontextmenu="return false;">
    <div class="header">
        <a href="admin.php" id="back" title="Back">
            <img src="icons\back_white.svg" alt="back">
        </a>
        <a href="logout.php" id="logout" title="Log Out">
            <img src="icons/exit_white.svg" alt="exit">
        </a>
    </div>
    <div class="cont" id="add-container">
        <div class="contain">
            
            <h1><?php echo $activeQuiz?> Quiz
            </h1>
            
            <div class="top">
                    <button type="button" id="upload" onclick="window.location.href = 'store_excel.php';">Upload Questions</button>  
                    <!-- <button type="button" id="upload" style="margin-top:60px;margin-bottom:50px" onclick="window.location.href = 'load_image.php';">Upload Image Questions</button>   -->

                    <h3>Add Question 
                        <span id="current-question-no"><?php echo $Q_NO; ?> </span>
                    </h3> <br>
            </div>

            <form id="question-form" method="post" >            
                <p class="form-group">
                    <label>Question Text</label>
                    <textarea cols="10" rows="5" name="question_text"></textarea>
                </p>   
                <p class="form-group">
                  <center>  <img id="preview_image" src="" style="display: none; max-width: 500px; max-height: 400px; margin-top: 10px;" /></center>
                </p>
                <p class="form-group">
                    <label>Upload Image (optional):</label>
                    <input type="file" name="upload_file" id="upload_file" accept=".png, .jpg, .jpeg"/>
                    <!-- <span class="tooltip-text">Double click to remove the image</span> -->

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

            const questionText = document.querySelector('[name="question_text"]').value.trim();
            const uploadFile = document.getElementById('upload_file').files[0];

            if (!questionText && !uploadFile) {
                alert("Please provide either a question text or upload an image.");
                return; 
            }

            var formData = new FormData(this);

            fetch('Q_Add.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())  // Get the response as text to debug

            .then(data => {
                console.log(data);

                if (data.message) {
                    alert(data.message);

                    var questionNo = parseInt(document.querySelector('input[name="question_no"]').value);
                    document.querySelector('input[name="question_no"]').value = questionNo + 1;
                    document.getElementById('current-question-no').textContent = questionNo + 1;
                    
                    this.reset();
                    //
                    document.getElementById("preview_image").src = "";
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
   
        document.getElementById('upload_file').addEventListener('change', function(event) {
            document.getElementById('preview_image').addEventListener('dblclick', function() {
                this.src = '';
                this.style.display = 'none';
                document.getElementById('upload_file').value = ''; 
            });

        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();

            reader.onload = function(e) {
                const imageUrl = e.target.result;
                
                // const textarea = document.querySelector('textarea[name="question_text"]');
                // textarea.value += `\n<img src="${imageUrl}" alt="Question Image" />`;

                document.getElementById('preview_image').src = imageUrl;
                document.getElementById('preview_image').style.display = 'block';
                document.getElementById('preview_image').style.maxWidth = '100%';
                document.getElementById('preview_image').style.height = 'auto';
            };

            reader.readAsDataURL(file);
        }
        });
        function removeImage() {
                document.getElementById('preview_image').addEventListener('dblclick', function() {
                this.src = '';
                this.style.display = 'none';
                document.getElementById('upload_file').value = ''; 
            });
        }
        
        document.getElementById('preview_image').addEventListener('mouseenter', function(event) {
            const tooltip = document.createElement('div');
            tooltip.innerText = 'Double click to remove the image';
            tooltip.style.position = 'absolute';
            tooltip.style.backgroundColor = 'black';
            tooltip.style.color = 'white';
            tooltip.style.padding = '5px 10px';
            tooltip.style.borderRadius = '5px';
            tooltip.style.fontSize = '14px';
            tooltip.style.pointerEvents = 'none';
            tooltip.style.zIndex = '1000';
            tooltip.id = 'tooltip';

            document.body.appendChild(tooltip);

            document.addEventListener('mousemove', moveTooltip);
        });

        document.getElementById('preview_image').addEventListener('mouseleave', function() {
            const tooltip = document.getElementById('tooltip');
            if (tooltip) {
                tooltip.remove();
                document.removeEventListener('mousemove', moveTooltip);
            }
        });

        function moveTooltip(event) {
            const tooltip = document.getElementById('tooltip');
            if (tooltip) {
                tooltip.style.left = event.pageX + 10 + 'px';  
                tooltip.style.top = event.pageY + 10 + 'px';
            }
        }

    </script>
</body>
</html>