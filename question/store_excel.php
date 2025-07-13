<?php
include_once '../core/header.php';

if(!$_SESSION['logged'] || $_SESSION['logged']===''){
    header('Location: ../index.php');
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
    header('Location: ../quiz/NoActiveQuiz.php');
    exit;
}

$QuizName = $conn->query("SELECT QuizName FROM quiz_details WHERE Quiz_Id = $quizId")->fetch_assoc()['QuizName'];

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
            require '../vendor/autoload.php';

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($fileTmpName);
            $spreadsheet = $reader->load($fileTmpName);

            $tempCsvFile = tempnam(sys_get_temp_dir(), 'questions_') . '.csv';
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Csv');
            $writer->save($tempCsvFile);

            if (($handle = fopen($tempCsvFile, 'r')) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                    $questions[] = $data;
                }
                fclose($handle);
            }

            unlink($tempCsvFile);
        }

        $_SESSION['questions'] = $questions;
        $_SESSION['current_question'] = 0;
        $_SESSION['quiz_over'] = false;
                
    } else {
        $questions = $_SESSION['questions'];
        $currentIndex = $_SESSION['current_question'];

        if (isset($_POST['next']) || isset($_POST['submit'])) {
            if ($currentIndex < count($questions)) {
                $data = $questions[$currentIndex];
                $question = $_POST['question'] ?? $data[0];
                $choice1 = $_POST['choice1'] ?? $data[1];
                $choice2 = $_POST['choice2'] ?? $data[2];
                $choice3 = $_POST['choice3'] ?? $data[3];
                $choice4 = $_POST['choice4'] ?? $data[4];
                $correct_choice = $_POST['correct_choice'] ?? $data[5];
            
            $stmt = $conn->prepare("INSERT INTO multiple_choices (QuizId, QuestionNo, Question, Choice1, Choice2, Choice3, Choice4, Answer)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iissssss", $quizId, $questionNo, $question, $choice1, $choice2, $choice3, $choice4, $correct_choice);
                
                if ($stmt->execute()) {
                    $_SESSION['question_no'] = ++$questionNo;
                    $_SESSION['current_question'] = ++$currentIndex;

                    $stmt2 = $conn->prepare("UPDATE quiz_details SET NumberOfQuestions = NumberOfQuestions + 1 WHERE Quiz_Id = ?");
                    if ($stmt2) {
                        $stmt2->bind_param("i", $quizId);
                        $stmt2->execute();
                        $stmt2->close();
                    }
                } else {
                    echo "Error inserting question: " . $stmt->error;
                    exit;
                }

                $stmt->close();
            } else {
                echo "Error preparing statement: " . $conn->error;
                exit;
            }
            
            if(isset($_POST['submit'])){
                $_SESSION['quiz_over'] = true;
                header('Location: ../dashboard/admin.php');
                exit;
            }
            if ($_SESSION['current_question'] >= count($questions)) {
                $_SESSION['quiz_over'] = true;
                header('Location: Q_Add.php');
                exit;
            }
        }
        }

        if (isset($_POST['previous'])) {
            $currentIndex = $_SESSION['current_question'];
            if ($currentIndex > 0) {
                $_SESSION['current_question'] = --$currentIndex;
                $questionNo = $_SESSION['question_no']-1;
            
                $stmt = $conn->prepare("DELETE FROM  multiple_choices WHERE QuestionNo=? && QuizId = ?"); 
                $stmt->bind_param("ii", $questionNo, $quizId);

                $stmt->execute();
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
    <link rel="stylesheet" href="../assets/css/store_excel.css">
</head>
<body>   
    <body oncontextmenu="return false;">
    <script type="text/javascript" src="../assets/scripts/inspect.js"></script>
    <div class="cont" id="add-container">
        <h2 style="text-align: center;text-transform: uppercase"><?php echo htmlspecialchars($QuizName); ?></h2>        
        <div class="contain">
            <h2>Add Question -  Question No: <?php echo $questionNo; ?></h2>

            <form action="store_excel.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="quizId" value="<?php echo $quizId; ?>">
                <div class="file-form" style= "display: flex;">
                    <label for="file" style="font-weight: bold;width:auto;margin-right: 10px">Upload CSV or Excel File:  </label>
                    <input type="file" name="file" accept=".csv, .xls, .xlsx" required><br><br>
                </div>      
                <div class="button-container">
                    <input type="submit" value="Upload File" id="upload"> 
                    <button type="button" onclick="window.location.href = '../dashboard/admin.php'" id="back">Back</button>
                </div>
                
            </form>
        </div>

            <?php if (isset($_SESSION['quiz_over']) && $_SESSION['quiz_over'] === true): ?>
                <h3>Question Over</h3>
                <script>
                    document.getElementById('question-form')?.style.display = 'none'; 
                </script>
                </div>

            <?php else: ?>


                <?php if (isset($_SESSION['questions']) && !empty($_SESSION['questions'])): ?>
                    <h3>Question <?php echo $_SESSION['current_question'] + 1; ?> of <?php echo count($_SESSION['questions']); ?></h3>

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
                    <input type="text" id="correct_choice" name="correct_choice" value="<?php echo htmlspecialchars($currentQuestion[5]); ?>"><br><br>
                    
                    <input type="submit" name="next" value="Next">
                    <button type="submit" name="submit">Submit</button>
                </form>
            <?php else: ?>
                <p>No questions uploaded yet.</p>
            <?php endif; ?>
    </div>
    <?php endif; ?>

</body>
</html>