<?php 

include_once 'core_db.php';
include 'header.php';
session_start();

if(!$_SESSION['logged'] || $_SESSION['logged']===''){
    header('Location: index.php');
    exit;
}

$query = 'SELECT QuizName, CreatedBy FROM quiz_details';
$result = $conn->query($query); 

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $quizName = $_POST['quizName'];
    $createdBy = $_POST['createdBy'];

    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT Quiz_Id, QuizType FROM quiz_details WHERE QuizName = ? AND CreatedBy = ?");
    $stmt->bind_param("ss", $quizName, $createdBy);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $quizId = $row['Quiz_Id'];
    $quizType = $row['QuizType'];
    $stmt->close();

    $Quiz = ($quizType === 1) ? 'fillup' : 'multiple_choices';
    if($quizType === 1){
        $stmt = $conn->prepare("DELETE FROM answer_fillup WHERE QuizId = ?");
        $stmt->bind_param("s", $quizId);
        if (!$stmt->execute()) {
            error_log("Error deleting from answer_fillup: " . $stmt->error);
            throw new Exception("Error deleting from answer_fillup: " . $stmt->error);
        }
        $stmt->close();
    }    

    try {
        $stmt1 = $conn->prepare("DELETE FROM stud WHERE QuizId = ?");
        $stmt1->bind_param("s", $quizId);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conn->prepare("DELETE FROM student WHERE QuizId = ?");
        $stmt2->bind_param("s", $quizId);
        $stmt2->execute();
        $stmt2->close();

        $stmt3 = $conn->prepare("DELETE FROM ".$Quiz." WHERE QuizId = ?");
        $stmt3->bind_param("s", $quizId);
        $stmt3->execute();
        $stmt3->close();

        $stmt4 = $conn->prepare("DELETE FROM quiz_details WHERE Quiz_Id = ?");
        $stmt4->bind_param("s", $quizId);
        if (!$stmt4->execute()) {
            error_log("Error deleting from quiz_details: " . $stmt->error);
            throw new Exception("Error deleting from quiz_details: " . $stmt->error);
        }
        $stmt4->close();
        
        $conn->commit();
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(array("message" => "Quiz deleted successfully"));
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(array("message" => "Failed to delete quiz"));
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Delete Quiz</title>

        <script src="inspect.js"></script>
        <style>
           body {
                font-family: Arial, sans-serif;
                background-color: #f0f2f5;
                margin: 0;
                padding: 10px;
            }
            .table-container {
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh; 
            }
            table {
                border-collapse: collapse;
                width: 90%;
                max-width: 800px;
                margin: 20px;
                background-color: #fff;
                border-radius: 5px;
                overflow: hidden;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            }
            table th, table td {
                border: 1px solid #ddd;
                padding: 12px;
                position: relative;
            }
            table th {
                background-color: #13274F;
                color: #ecf0f1;
            }
            table td {
                color: #2c3e50;
                cursor: pointer;
            }
            table tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            table tr:hover {
                background-color: #f1f1f1;
            }

            .tooltip {
                visibility: hidden;
                background-color: black;
                color: #fff;
                text-align: center;
                border-radius: 5px;
                padding: 3px;
                position: absolute;
                z-index: 1;
                bottom: 110%; 
                left: 50%;
                margin-left: -60px; 
                opacity: 0;
                transition: opacity 0.3s;
                width: 170px;
            }
            table td:hover .tooltip {
                visibility: visible;
                opacity: 0.8;
            }

            .no-quiz{
                font-size: large;
                font-weight: bold;
                text-align: center;
                padding: 20px;
                font-size: large;
            }

            h2{
                color: #13274F;
            }
        </style>
    </head>

    <body>
        <h2>Delete Quiz</h2>
            <?php
                if($result && $result->num_rows > 0){
                    echo "
                        <table>
                            <thead>
                              <tr>
                                  <th>Quiz Name</th>
                                  <th>Created By</th>
                              </tr>
                          </thead>";
                          while ($row = $result->fetch_assoc()) {
                            echo "<tr ondblclick='Delete(this)'>
                                    <td data-quizname='".$row['QuizName']."'>".$row['QuizName']." <span class='tooltip'>Double-click to delete</span> </td>
                                    <td data-createdby='".$row['CreatedBy']."'>". $row['CreatedBy']." <span class='tooltip'>Double-click to delete</span> </td>
                                </tr>";
                        }
                    echo "</table>";
                    } else {
                        echo "<p class='no-quiz'>No quiz found</p>";
                    }
                ?>
            </table>
        </div>

        <script>
            function Delete(row) {
                let quizName = row.children[0].dataset.quizname;
                let createdBy = row.children[1].dataset.createdby;

                if (confirm("Are you sure you want to delete " + quizName + " by " + createdBy + "?")) {
                    let xhr = new XMLHttpRequest();
                    xhr.open("POST", "Delete_Quiz.php", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                    xhr.onreadystatechange = function () {
                        if (this.readyState == 4) {
                            let response = JSON.parse(this.responseText);
                            alert(response.message);
                            if (this.status == 200) {
                                row.parentNode.removeChild(row);
                            }
                        }
                    };
                    xhr.send("quizName=" + encodeURIComponent(quizName) + "&createdBy=" + encodeURIComponent(createdBy));
                }
            }
        </script>
    </body>
</html>
