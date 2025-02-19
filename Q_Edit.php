<?php
include_once 'core_db.php';
include 'header.php';
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
    <link rel="stylesheet" href="css/navigation.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            color:black;
            background-color: #f9f9f9;
        }

        th {
            background-color:rgb(211, 211, 211); 
            color: black;
            padding: 10px;
            text-align: left;
        }

        td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }

        .editable {
            background-color: #f9f9f9;
            cursor: pointer;
            padding: 5px;
            border: 1px solid #ccc;
        }

        .editable input, .editable textarea {
            width: 100%;
            font-size: inherit;
            padding: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .action-buttons button {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .save-btn {
            background-color: #13274F;
            color: white;
        }

        .delete-btn {
            background-color: #13274F;
            color: white;
        }

        .save-btn:hover, .delete-btn:hover {
            opacity: 0.8;
        }

        .img {
            width: 100%;
            text-align: center;
        }

        .img img {
            max-width: 100%;
            height: auto;
        }

        .img span {
            display: block;
            padding: 10px;
        }

        .img input {
            margin-top: 10px;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #13274F;
            color: white;
            padding: 10px;
            margin: 0;
        }

        h1 {
            text-align: center;
        }

    </style>
</head>
<body>

    <center><h1>Manage Questions</h1></center>
    <div class="header">
        <a href="admin.php" id="back" title="Back">
            <img src="icons\back_white.svg" alt="back">
        </a>
        <a href="logout.php" id="logout" title="Log Out">
            <img src="icons/exit_white.svg" alt="exit">
        </a>
    </div>
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
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr data-id="<?php echo $row['QuestionNo']; ?>">
                    <td class="editable qno" data-field="QuestionNo"><?php echo $row['QuestionNo']; ?></td>
                    <td class="editable" data-field="Question"><?php echo htmlspecialchars($row['Question']); ?></td>
                    <td class="editable" data-field="Choice1"><?php echo htmlspecialchars($row['Choice1']); ?></td>
                    <td class="editable" data-field="Choice2"><?php echo htmlspecialchars($row['Choice2']); ?></td>
                    <td class="editable" data-field="Choice3"><?php echo htmlspecialchars($row['Choice3']); ?></td>
                    <td class="editable" data-field="Choice4"><?php echo htmlspecialchars($row['Choice4']); ?></td>
                    <td class="editable" data-field="Answer"><?php echo htmlspecialchars($row['Answer']); ?></td>
                    <td class="img">
                        <?php if (!empty($row['img_path'])) { ?>
                            <img src="<?php echo htmlspecialchars($row['img_path']); ?>" alt="Question Image" />
                        <?php } else { ?>
                            <span>No Image</span>
                        <?php } ?>
                        <br>
                        <input type="file" class="upload-image" accept=".png, .jpg, .jpeg" />
                    </td>                  
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const tableRows = document.querySelectorAll("tbody tr");

            tableRows.forEach(row => {
                row.addEventListener("dblclick", function () {
                    makeRowEditable(this);
                });
            });

            function makeRowEditable(row) {
            if (row.classList.contains("editing")) return;
            row.classList.add("editing");

            const cells = row.querySelectorAll(".editable");
            cells.forEach(cell => {
                let input = document.createElement("textarea");
                input.type = "text";
                input.value = cell.textContent.trim();
                input.classList.add("editable");

                const cellStyle = window.getComputedStyle(cell);
                input.style.width = cellStyle.width; 
                input.style.height = cellStyle.height;
                input.style.fontSize = cellStyle.fontSize;
                input.style.padding = cellStyle.padding;
                input.style.border = "1px solid #ccc"; 
                input.style.resize = "none"; 

                cell.textContent = ""; 
                cell.appendChild(input); 
            });

            let actionCell = row.insertCell(-1);
            actionCell.classList.add("action-buttons");
            actionCell.innerHTML = `
                <button class="btn save-btn">Save</button>
                <button class="btn delete-btn">Delete</button>
            `;

            row.querySelector(".save-btn").addEventListener("click", function () {
                saveChanges(row);
            });

            row.querySelector(".delete-btn").addEventListener("click", function () {
                deleteRow(row);
            });
        }

        function saveChanges(row) {
                const questionNo = row.dataset.id;
                const fields = ["Question", "Choice1", "Choice2", "Choice3", "Choice4", "Answer"];
                let formData = new FormData();

                formData.append("question_no", questionNo);
                formData.append("quiz_id", "<?php echo $_SESSION['active']; ?>");

                fields.forEach((field, index) => {
                    let inputElement = row.cells[index + 1].querySelector("textarea");
                    if (inputElement) {
                        formData.append(field, inputElement.value.trim());
                    }
                });

                let imageInput = row.querySelector(".upload-image");
                if (imageInput && imageInput.files.length > 0) {
                    formData.append("image", imageInput.files[0]);
                }
            
                fetch("update_question.php", {
                    method: "POST",
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    location.reload();
                })
                .catch(error => alert("Error: " + error.message));
            }
            function deleteRow(row) {
                const questionNo = row.dataset.id;

                if (!confirm("Are you sure you want to delete this question?")) return;

                fetch("Q_Delete.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ question_no: questionNo }),
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    row.remove();
                    location.reload();

                })
                .catch(error => console.log("Error: " + error.message));
            }
        });
    </script>
</body>
</html>
