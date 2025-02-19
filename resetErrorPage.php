<?php
session_start();
include 'header.php';
// Check if session data exists
if (!isset($_SESSION['Name'])) {
    echo "No reset data found.";
    exit;
}

// Retrieve details from the session
$user_name = $_SESSION['Name'];
$user_roll = $_SESSION['RollNo'];
$user_dept = $_SESSION['dept'];
$user_year = $_SESSION['year'];
$user_section = $_SESSION['sec'];  

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['Logout'])) {
        $stmt = $conn->prepare("DELETE FROM student WHERE RollNo = ? AND QuizId = ?");
        $stmt->bind_param("si", $rollno, $activeQuizId);
        if ($stmt->execute()) {
            header("Location: index.php");
            $_SESSION['login'] = FALSE;
            $_SESSION['logi'] = FALSE;
            $_SESSION['log'] = FALSE;
            exit;
        } else {
            echo "Error deleting record: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Reset Notification</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg,rgb(42, 64, 108), #13274F);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .container {
            background: #ffffff;
            padding: 30px 50px;
            border-radius: 15px;
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
            text-align: center;
            max-width: 500px;
            width: 90%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .container h1 {
            color: #13274f;
            font-size: 30px;
            margin-bottom: 20px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .container p {
            font-size: 16px;
            color: #4b4b4b;
            margin: 12px 0;
            line-height: 1.8;
            text-align: start;
            margin-left: 30px;
        }

        .container p strong {
            color: #2c3e50;
        }

        .btn.Logout {
            border: none;
            display: inline-block;
            padding: 12px 30px;
            margin-top: 25px;
            background-color: #13274f;
            color: #fff;
            text-transform: uppercase;
            font-weight: 600;
            font-size: 15px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn.Logout:hover {
            background-color: #ffffff;
            color: #13274f;
            border: 1px solid #13274f;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px 30px;
            }

            .container h1 {
                font-size: 26px;
            }

            .btn.Logout {
                font-size: 14px;
                padding: 10px 20px;
            }
        }

    </style>
</head>
<body>
    <div class="container">
        <h1>Quiz Attempt Reset</h1>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($user_name); ?></p>
        <p><strong>Roll Number:</strong> <?php echo htmlspecialchars($user_roll); ?></p>
        <p><strong>Department:</strong> <?php echo htmlspecialchars($user_dept); ?></p>
        <p><strong>Year & Sec:</strong> <?php echo htmlspecialchars($user_year)." ".htmlspecialchars($user_section); ?></p>      <br>
        <p style="font-weight: bold;"> **If you believe this reset was a mistake, please contact your instructor</a> or the quiz admin for clarification.</p>
        <form method="post" action="welcome.php">
            <input type="submit" name="Logout" value="Logout" class="btn Logout">
        </form>
    </div>
    
</body>
</html>
