<?php 
include_once '../core/header.php';
$quiz = $_SESSION['QuizType']==0 ? "Fill Up" : "Multiple Choice"; 
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Quizze</title>
        <script src="../assets/scripts/inspect.js"></script>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(155deg, #325e80, #13274F);
                color: white;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                text-align: center;
            }
            .message {
                background-color: #ecf0f1;
                color: #2c3e50;
                padding: 20px;
                border-radius: 5px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
                margin-bottom: 20px;                
            }
        </style>
    </head>
    <body>
        <div class='message'>
            <h1>No active or selected <?php echo $quiz?> Quiz found. Please select or create a quiz.</h1>
            <p>You will be redirected to the admin page within few seconds.</p>
        </div>
        <script>
            setTimeout(function() {
                window.location.href = '../dashboard/admin.php';
            }, 5000);
        </script>
    </body>
</html>