<?php
include_once 'core_db.php';
session_start();

if (!$_SESSION['logged'] || $_SESSION['logged'] === '') {
    header('Location: index.php');
    exit;
}

if (($_SESSION['active'] === 'None' && $_SESSION['activeQuiz'] === 'None') || $_SESSION['QuizType'] == 1) {
    header('Location: NoActiveQuiz.php');
    exit;
}

$quizId = $_SESSION['active'];

// Fetch questions from the database
$query = "SELECT * FROM multiple_choices WHERE QuizId = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $quizId);
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions</title>
    <style>
        body {
    font-family: Arial, sans-serif;
    background-color: #13274F;
    padding:10px;
    /* display: flex; */
    /* justify-content: center;
    align-items: center; */
    height: 100vh;
    margin: 0;
    /* margin-top: 230px; */
}
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        .editable {
            background-color: #f9f9f9;
            cursor: pointer;
        }
        .img{
            background-color : #fff;
        }
        h1{
            color: #fff;
        }
    </style>
</head>
<body>
    <center><h1>Manage Questions</h1></center>
    <table>
        <thead>
            <tr>
                <th>Question No.</th>
                <th>Question</th>
                <th>Choice 1</th>
                <th>Choice 2</th>
                <th>Choice 3</th>
                <th>Choice 4</th>
                <th>Correct Choice</th>
                <th>Image</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr data-id="<?php echo $row['QuestionNo']; ?>">
                <td class="editable" data-field="QuestionNo"><?php echo $row['QuestionNo']; ?></td>
                <td class="editable" data-field="Question"><?php echo htmlspecialchars($row['Question']); ?></td>
                <td class="editable" data-field="Choice1"><?php echo htmlspecialchars($row['Choice1']); ?></td>
                <td class="editable" data-field="Choice2"><?php echo htmlspecialchars($row['Choice2']); ?></td>
                <td class="editable" data-field="Choice3"><?php echo htmlspecialchars($row['Choice3']); ?></td>
                <td class="editable" data-field="Choice4"><?php echo htmlspecialchars($row['Choice4']); ?></td>
                <td class="editable" data-field="Answer"><?php echo htmlspecialchars($row['Answer']); ?></td>
                <td class = "img">
                    <!-- Display the current image -->
                    <?php if (!empty($row['img_path'])) { ?>
                        <img class = "img" src="<?php echo htmlspecialchars($row['img_path']); ?>" alt="Question Image" style="width: 100px; height: auto;" />
                    <?php } else { ?>
                        <span>No Image</span>
                    <?php } ?>
                    <br>
                    <!-- File input for uploading a new image -->
                    <input type="file" class="upload-image" id ="upload-image" accept=".png, .jpg, .jpeg" />
                    <!-- </span><span id="image-name<?php echo $row['QuestionNo']; ?>"><?php echo $row['img_path']; ?></span> -->
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        // Handle text updates
        const editableCells = document.querySelectorAll(".editable");
        editableCells.forEach(cell => {
            cell.addEventListener("blur", function () {
                const questionNo = this.closest("tr").dataset.id;
                const field = this.dataset.field;
                const value = this.textContent.trim();

                fetch("update_question.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ question_no: questionNo, field: field, value: value }),
                })
                .then(response => response.json())
                .then(data => alert(data.message))
                .catch(error => alert("Error: " + error.message));
            });
        });

        // Handle image uploads
        const uploadInputs = document.querySelectorAll(".upload-image");
        uploadInputs.forEach(input => {
            input.addEventListener("change", function () {
                const questionNo = this.closest("tr").dataset.id;
                const file = this.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append("question_no", questionNo);
                formData.append("upload_file", file);

                fetch("update_question.php", {
                    method: "POST",
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.message === "Image updated successfully.") location.reload();
                })
                .catch(error => alert("Error: " + error.message));
            });
        });
    });
</script>
</body>
</html>
