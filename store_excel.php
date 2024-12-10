<?php
include_once 'core_db.php';
session_start();


if(!$_SESSION['logged'] || $_SESSION['logged']===''){
    header('Location: login.php');
    exit;
}

$quizId = $_SESSION['quiz'] === '' ? $_SESSION['active'] : $_SESSION['quiz'];
$activeQuiz = $_SESSION['activeQuiz'];

if ($quizId !== $_SESSION['active']) {
    $query = 'SELECT QuizName FROM quiz_details WHERE quiz_id = ' . $quizId;
    $result = $conn->query($query);
    $activeQuiz = $result->fetch_assoc()['QuizName'];
    $_SESSION['quiz_over'] = false;

} else if ($_SESSION['active'] === 'None' && $_SESSION['activeQuiz'] === 'None') {
    header('Location: NoActiveQuiz.php');
    exit;
}

$Q_NoResult = $conn->query("SELECT count(QuestionNo) FROM multiple_choices WHERE QuizId = $quizId"); 
$questionNo = ($Q_NoResult->fetch_assoc()['count(QuestionNo)']) + 1;


if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_FILES['file']['name']) && !empty($_FILES['file']['name'])) {
        $fileTmpName = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];
        $fileType = $_FILES['file']['type'];
        $fileError = $_FILES['file']['error'];
        $fileSize = $_FILES['file']['size'];

        if ($fileError !== UPLOAD_ERR_OK) {
            echo "File upload error!";
            exit;
        }

        $allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];

        if (!in_array($fileType, $allowedTypes)) {
            echo "Please upload a valid CSV or Excel file!";
            exit;
        }

        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $questions = [];

        if ($fileExt === 'csv') {
            if (($handle = fopen($fileTmpName, 'r')) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                    $questions[] = $data;
                }
                fclose($handle);
            }
        } elseif ($fileExt === 'xls' || $fileExt === 'xlsx') {
            // Convert Excel to CSV using PhpSpreadsheet
            require 'vendor/autoload.php';

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($fileTmpName);
            $spreadsheet = $reader->load($fileTmpName);

            // Create a temporary CSV file
            $tempCsvFile = tempnam(sys_get_temp_dir(), 'questions_') . '.csv';
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Csv');
            $writer->save($tempCsvFile);

            // Now, read the CSV content
            if (($handle = fopen($tempCsvFile, 'r')) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                    $questions[] = $data;
                }
                fclose($handle);
            }

            // Delete the temporary CSV file after reading
            unlink($tempCsvFile);
        }

        // Store questions in session
        $_SESSION['questions'] = $questions;
        $_SESSION['current_question'] = 0;
        $_SESSION['quiz_over'] = false;
                
    } else {
        // If "Next" button is clicked, insert current question into DB
        if (isset($_POST['next'])) {
            $questions = $_SESSION['questions'];
            $currentIndex = $_SESSION['current_question'];
            $correct_choice = isset($_POST['correct_choice']) ? $_POST['correct_choice'] : '';
            $explanation = isset($_POST['explanation']) ? $_POST['explanation'] : '';

            if($_SESSION['current_question'] + 1  > count($_SESSION['questions'])) {
                $_SESSION['quiz_over'] = true;  
                header('Location: Q_Add.php');
                exit;  // Make sure the script stops after the redirect                
            }
            
            if ($currentIndex < count($questions)) {
                $data = $questions[$currentIndex];
                $question = $data[0];
                $choice1 = $data[1];
                $choice2 = $data[2];
                $choice3 = $data[3];
                $choice4 = $data[4];

                $query = "INSERT INTO multiple_choices (QuizId, QuestionNo, Question, Choice1, Choice2, Choice3, Choice4, Answer, Explanation)
                          VALUES ('$quizId', '$questionNo', '$question', '$choice1', '$choice2', '$choice3', '$choice4', '$correct_choice', '$explanation')";

                          

                if (mysqli_query($conn, $query)) {
                    // Increment question number and move to the next question
                    $_SESSION['question_no'] = $questionNo + 1;
                    $_SESSION['current_question'] = $currentIndex + 1;
                    $questionNo++;
                    
                    $stmt2 = $conn->prepare("UPDATE quiz_details SET NumberOfQuestions = NumberOfQuestions + 1 WHERE Quiz_Id = ?");
                    $stmt2->bind_param("i", $quizId);
                    if (!$stmt2->execute()) {
                        echo "Error updating quiz details: " . $stmt2->error;
                    }
                    } else {
                        echo "Error inserting question: " . mysqli_error($conn);
                    }
            }
            else {
                $_SESSION['quiz_over'] = true;
                
            }
                      
        }   


        if (isset($_POST['previous'])) {
            // Decrease the current question index if "Previous" button is clicked
            $currentIndex = $_SESSION['current_question'];
            if ($currentIndex > 0) {
                $_SESSION['current_question'] = $currentIndex - 1;
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Question - Upload CSV/Excel</title>
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

        .Curr_Q {
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
        button[type="submit"]:nth-of-type(2) {
            background-color: #e74c3c; /* Red for Previous */
        }

        button[type="submit"]:nth-of-type(1) {
            background-color: #2ecc71; /* Green for Next */
        }

    </style>
</head>
<body>   
    <body oncontextmenu="return false;">
    <script type="text/javascript" src="inspect.js"></script>
    <div class="cont" id="add-container">
        <div class="contain">
            <h2>Add Question - Quiz ID: <?php echo htmlspecialchars($quizId); ?>, Question No: <?php echo $questionNo; ?></h2>

            <!-- Form for uploading file -->
            <form action="store_excel.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="quizId" value="<?php echo $quizId; ?>">
                <label for="file">Upload CSV or Excel File:</label>
                <input type="file" name="file" accept=".csv, .xls, .xlsx" required><br><br>
                <input type="submit" value="Upload File">
            </form>
            <button type="button" onclick="window.location.href = 'admin.php'">Back</button>

            </div>
    <!-- </div> -->

            <?php if (isset($_SESSION['quiz_over']) && $_SESSION['quiz_over'] === true): ?>
                <h3>Question Over</h3>
                <script>
                    document.getElementById('question-form')?.style.display = 'none'; // Ensure form is hidden if quiz is over
                </script>
            <?php else: ?>


                <?php if (isset($_SESSION['questions']) && !empty($_SESSION['questions'])): ?>
                    <h3>Question <?php echo $_SESSION['current_question'] + 1; ?> of <?php echo count($_SESSION['questions']); ?></h3>

                <!-- Form for displaying current question -->
                <form action="store_excel.php" method="POST" id="question-form">
                    <?php
                    $currentQuestion = $_SESSION['questions'][$_SESSION['current_question']];
                    ?>
                    <label for="question">Question:</label>
                    <input type="text" id="question" name="question" value="<?php echo htmlspecialchars($currentQuestion[0]); ?>" required><br><br>

                    <label for="choice1">Choice 1:</label>
                    <input type="text" id="choice1" name="choice1" value="<?php echo htmlspecialchars($currentQuestion[1]); ?>" required><br><br>

                    <label for="choice2">Choice 2:</label>
                    <input type="text" id="choice2" name="choice2" value="<?php echo htmlspecialchars($currentQuestion[2]); ?>" required><br><br>

                    <label for="choice3">Choice 3:</label>
                    <input type="text" id="choice3" name="choice3" value="<?php echo htmlspecialchars($currentQuestion[3]); ?>" required><br><br>

                    <label for="choice4">Choice 4:</label>
                    <input type="text" id="choice4" name="choice4" value="<?php echo htmlspecialchars($currentQuestion[4]); ?>" required><br><br>

                    <label for="correct_choice">Correct Answer:</label>
                    <input type="text" id="correct_choice" name="correct_choice" required><br><br>

                    <label for="explanation">Explanation:</label>
                    <textarea id="explanation" name="explanation">NO EXPLANATION</textarea><br><br>

                    <input type="submit" name="next" value="Next">
                    <button type="submit" name="previous" value="Previous">Previous</button>
                    <button type="button" onclick="window.location.href = 'admin.php'">Back</button>


                </form>
            <?php else: ?>
                <p>No questions uploaded yet.</p>
            <?php endif; ?>
    </div>
    <?php endif; ?>

    <script>
    document.getElementById('explanation').addEventListener('focus', function() {
        if (this.value === 'NO EXPLANATION') {
            this.value = '';
        }
    });

    document.getElementById('explanation').addEventListener('blur', function() {
        if (this.value.trim() === '') {
            this.value = 'NO EXPLANATION';
        }
    });
</script>

</body>
</html>
