<?php
include_once 'core_db.php';
session_start();

if(!$_SESSION['logged'] || $_SESSION['logged']===''){
    header('Location: index.php');
    exit;
}

if(($_SESSION['active']==='None' && $_SESSION['activeQuiz']==='None') || $_SESSION['QuizType']==1){
    header('Location: NoActiveQuiz.php');
    exit;
}

$quizId = $_SESSION['active'];
$activeQuiz = $_SESSION['activeQuiz'];

if($quizId !== 'None'){
    $query = "SELECT * FROM multiple_choices WHERE QuizId = $quizId";
    $result = $conn->query($query);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $questionNo = $_POST['question_no'];
    $question = $_POST['question_text'];
    $c1 = $_POST['choice1'];
    $c2 = $_POST['choice2'];
    $c3 = $_POST['choice3'];
    $c4 = $_POST['choice4'];
    $correct_choice = $_POST['correct_choice'];

    $stmt = $conn->prepare("UPDATE multiple_choices SET Question=?, Choice1=?, Choice2=?, Choice3=?, Choice4=?, Answer=?WHERE QuestionNo=? && QuizId = $quizId");
    $stmt->bind_param("ssssssi", $question, $c1, $c2, $c3, $c4, $correct_choice, $questionNo);

    if ($stmt->execute()) {
        http_response_code(200);
        $query = "SELECT * FROM multiple_choices WHERE QuestionNo = ? && QuizId = $quizId";
        $stmt_select = $conn->prepare($query);
        $stmt_select->bind_param("i", $questionNo);
        $stmt_select->execute();
        $result = $stmt_select->get_result();
        $row = $result->fetch_assoc();

        header('Content-Type: application/json');
        echo json_encode(array(
            "question" => $row['Question'],
            "choice1" => $row['Choice1'],
            "choice2" => $row['Choice2'],
            "choice3" => $row['Choice3'],
            "choice4" => $row['Choice4'],
            "correct_choice" => $row['Answer']
        ));
        exit;
    }
    else {
        http_response_code(500); 
        echo json_encode(array("message" => "Failed to update question."));
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Question</title>
    <link rel="stylesheet" type="text/css" href="css/navigation.css">
    <script src="inspect.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 20px;
            padding: 0;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #13274F;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
            color: #2c3e50;
        }
        th {
            background-color: #13274F;
            color: #ecf0f1;
        }
        td.edit-mode {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        button {
            margin-top: 10px;
            background-color: #13274F;
            color: #ecf0f1;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: inline-block;
        }
        button:hover {
            background-color: #0d1b37;
        }
        .message {
            margin-top: 10px;
            padding: 10px;
            background-color: #e74c3c;
            color: white;
            border-radius: 5px;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }

        p.no-quiz {
            color: #e74c3c;
            font-weight: bold;
            text-align: center;
            padding: 20px;
            font-size: large;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="admin.php" id="back" title="Back">
            <img src="icons\back.svg" alt="back">
        </a>
        <a href="logout.php" id="logout" title="Log Out">
            <img src="icons/exit.svg" alt="exit">
        </a>
    </div>
    <h1>Edit <?php echo $activeQuiz ?> Quiz</h1>
            <?php
            if ($result && $result->num_rows>0) {
        
                echo "<table>
                            <thead>
                                <tr>
                                    <th>Question No.</th>
                                    <th>Question</th>
                                    <th>Choice 1</th>
                                    <th>Choice 2</th>
                                    <th>Choice 3</th>
                                    <th>Choice 4</th>
                                    <th>Correct Choice</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr ondblclick='makeEditable(this)'>";
                    echo "<td data-field='question_no'>" . $row['QuestionNo'] . "</td>";
                    echo "<td data-field='question_text'>" . $row['Question'] . "</td>";
                    echo "<td data-field='choice1'>" . $row['Choice1'] . "</td>";
                    echo "<td data-field='choice2'>" . $row['Choice2'] . "</td>";
                    echo "<td data-field='choice3'>" . $row['Choice3'] . "</td>";
                    echo "<td data-field='choice4'>" . $row['Choice4'] . "</td>";
                    echo "<td data-field='correct_choice'>" . $row['Answer'] . "</td>";
                    echo "<td class='action-buttons'>
                            <button type='button' id='edit' onclick='makeEditable(this.parentNode.parentNode)'>Edit</button>
                            <button type='button' id='delete' onclick='deleteRow(this)'>Delete</button>
                            <button type='button' id='update' style='display: none;' onclick='updateRow(this)'>Update</button>
                          </td>";
                    echo "</tr>";
                }
            }

            else{
                echo "<p class='no-quiz'>No question found</p>";
            }
            ?>
        </tbody>
    </table>

    <script>
        let currentRow = null;
        let originalContent = {};

        function makeEditable(row) {
            if (currentRow && currentRow !== row) {
                revertChanges(currentRow);
            }
            currentRow = row;
            originalContent = {
                questionNo: row.cells[0].innerHTML,
                question: row.cells[1].innerHTML,
                choice1: row.cells[2].innerHTML,
                choice2: row.cells[3].innerHTML,
                choice3: row.cells[4].innerHTML,
                choice4: row.cells[5].innerHTML,
                correctChoice: row.cells[6].innerHTML
            };

            for (let i = 1; i < row.cells.length - 1; i++) {
                row.cells[i].contentEditable = 'true';
                row.cells[i].classList.add('edit-mode');
            }

            row.querySelector('#delete').style.display = 'none';
            row.querySelector('#edit').style.display = 'none';
            row.querySelector('#update').style.display = 'inline-block';
        }

        function revertChanges(row) {
            row.cells[1].innerHTML = originalContent.question;
            row.cells[2].innerHTML = originalContent.choice1;
            row.cells[3].innerHTML = originalContent.choice2;
            row.cells[4].innerHTML = originalContent.choice3;
            row.cells[5].innerHTML = originalContent.choice4;
            row.cells[6].innerHTML = originalContent.correctChoice;

            for (let i = 1; i < row.cells.length - 1; i++) {
                row.cells[i].contentEditable = 'false';
                row.cells[i].classList.remove('edit-mode');
            }

            row.querySelector('#update').style.display = 'none';
            row.querySelector('#delete').style.display = 'inline-block';
            row.querySelector('#edit').style.display = 'inline-block';
            
            currentRow = null;
        }

        function updateRow(button) {
            let row = button.parentNode.parentNode;
            let questionNo = row.cells[0].innerText;
            let question = row.cells[1].innerText;
            let choice1 = row.cells[2].innerText;
            let choice2 = row.cells[3].innerText;
            let choice3 = row.cells[4].innerText;
            let choice4 = row.cells[5].innerText;
            let correctChoice = row.cells[6].innerText;

            if (question == "" || choice1 == "" || choice2 == "" || choice3 == "" || choice4 == "" || correctChoice == "" ) {
                alert("All fields are required.");
                return;
            }

            if (correctChoice !== choice1 && correctChoice !== choice2 && correctChoice !== choice3 && correctChoice !== choice4) {
                alert("Correct choice must be one of the choices.");
                return;
            }

            let xhr = new XMLHttpRequest();
            xhr.open("POST", "Q_Edit.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        let responseData = JSON.parse(xhr.responseText);

                        row.cells[1].innerText = responseData.question;
                        row.cells[2].innerText = responseData.choice1;
                        row.cells[3].innerText = responseData.choice2;
                        row.cells[4].innerText = responseData.choice3;
                        row.cells[5].innerText = responseData.choice4;
                        row.cells[6].innerText = responseData.correct_choice;

                        alert('Question updated successfully.');

                        for (let i = 1; i < row.cells.length - 1; i++) {
                            row.cells[i].contentEditable = 'false';
                            row.cells[i].classList.remove('edit-mode');
                        }

                        row.querySelector('#update').style.display = 'none';
                        row.querySelector('#delete').style.display = 'inline-block';
                        row.querySelector('#edit').style.display = 'inline-block';

                        currentRow = null;

                    } else {
                        alert('Failed to update question.');
                    }
                }
            };

            let params = "question_no=" + encodeURIComponent(questionNo)
                + "&question_text=" + encodeURIComponent(question)
                + "&choice1=" + encodeURIComponent(choice1)
                + "&choice2=" + encodeURIComponent(choice2)
                + "&choice3=" + encodeURIComponent(choice3)
                + "&choice4=" + encodeURIComponent(choice4)
                + "&correct_choice=" + encodeURIComponent(correctChoice);
            xhr.send(params);
        }

        function deleteRow(button) {
            let row = button.parentNode.parentNode;
            let questionNo = row.cells[0].innerText;

            let ans = confirm("Are you sure you want to delete Question " + questionNo + "?");

            if (ans) {
                let xhr = new XMLHttpRequest();
                xhr.open("POST", "Q_Delete.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === XMLHttpRequest.DONE) {
                        if (xhr.status === 200) {
                            let response = JSON.parse(xhr.responseText);
                            alert(response.message);

                            if (response.questions) {
                                
                                let tbody = document.querySelector('table tbody');
                                tbody.innerHTML = '';

                                response.questions.forEach((question) => {
                                    let newRow = tbody.insertRow();

                                    newRow.innerHTML = `
                                        <td>${question.QuestionNo}</td>
                                        <td>${question.Question}</td>
                                        <td>${question.Choice1}</td>
                                        <td>${question.Choice2}</td>
                                        <td>${question.Choice3}</td>
                                        <td>${question.Choice4}</td>
                                        <td>${question.Answer}</td>
                                        <td>${question.Explanation}</td>
                                        <td class='action-buttons'>
                                            <button type='button' id='edit' onclick='makeEditable(this.parentNode.parentNode)'>Edit</button>
                                            <button type='button' id='delete' onclick='deleteRow(this)'>Delete</button>
                                            <button type='button' id='update' style='display: none;' onclick='updateRow(this)'>Update</button>
                                        </td>
                                    `;
                                });
                            }
                        } else {
                            alert('Failed to delete question.');
                        }
                    }
                };

                let params = "question_no=" + encodeURIComponent(questionNo);
                xhr.send(params);
            }
        }

    </script>
</body>
</html>
