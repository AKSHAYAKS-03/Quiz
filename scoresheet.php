<?php
include 'core_db.php';
include 'header.php';
session_start(); 
date_default_timezone_set('Asia/Kolkata');


if (!isset($_SESSION['login']) || empty($_SESSION['login']) ||
    !isset($_SESSION['logi']) || empty($_SESSION['logi']) ||
    !isset($_SESSION['RegNo']) || empty($_SESSION['RegNo']) ||
    !isset($_SESSION['Name']) || empty($_SESSION['Name']) ||
    !isset($_SESSION['dept']) || empty($_SESSION['dept']) ){
    header('Location: index.php');
    exit;
}

$RegNo = $_SESSION['RegNo'];
$total_time = $_SESSION['total_time'];

list($hours, $minutes, $seconds) = explode(':', $total_time);

if ($hours == 0 && $minutes == 0) {
    $display_time = intval($seconds) . ' sec';
} elseif ($hours == 0) {
    $display_time = intval($minutes) . ' min ' . intval($seconds) . ' sec';
} else {
    $display_time = intval($hours) . ' hr ' . intval($minutes) . ' min';
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$total = $_SESSION['Marks'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['Ok'])) {
    session_unset(); 
    session_destroy(); 
    header('Location: index.php');
    exit;
}

$percentage = floatval($_SESSION['percentage']);
$feedback_message = "";
$feedback_color = "";
$feedback_icon = "";
$progress_bar = 0;

if ($percentage >= 80) {
    $feedback_message = "You are doing great!";
    $feedback_color = "#28a745";  // Green
    $feedback_icon = "🎉";
} elseif ($percentage >= 60) {
    $feedback_message = "You can do better!";
} else {
    $feedback_message = "You need to work hard!";
    $feedback_color = "#dc3545";  // Red
    $feedback_icon = "😞";
}
?>

<!DOCTYPE html>
<html>
<head>    
    <title>Score Sheet</title>
    <script src="inspect.js"></script>
    <style>
    body {
        background: #13274F;
        font-family: 'Poppins', sans-serif;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        color: #fff;
    }

    .container {
        background: rgba(255, 255, 255, 0.95);
        color: #13274F;
        width: 90%;
        max-width: 550px;
        padding: 30px 25px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        text-align: center;
        animation: slideIn 0.8s ease-in-out;
    }

    .header {
        margin-bottom: 20px;
        text-align: center;
    }

    .header h1 {
        margin: 0;
        color: #13274F;
    }

    .score-details {
        margin: 20px 0;
        font-size: 18px;
        text-align: left;
        line-height: 1.8;
        padding: 0 15px;
    }

    .score-details strong {
        font-weight: 600;
        color: #13274F;
        display: inline-block;
        width: 150px;
    }

    .highlight {
        font-size: 22px;
        font-weight: bold;
        color: #28a745;
        text-align: center;
        margin: 15px 0;
    }

    .feedback {
        font-size: 22px;
        font-weight: bold;
        margin: 10px 0;
        padding: 10px;
        display: inline-block;
    }

    .footer {
        margin-top: 20px;
        text-align: center;
    }
    .submit-btn {
        background-color: #13274F;
        color: #fff;
        padding: 12px 25px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        transition: background-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
    }

    .submit-btn:hover {
        background-color: #fff;
        color: #13274F;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        transform: scale(1.05);
    }

    .submit-btn:active {
        transform: scale(0.98);
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
    }

    @keyframes slideIn {
        from {
            transform: translateY(30px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
</style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1> Congratulations🎉</h1>
            <div class="feedback">
                <?php echo $percentage."% - ".htmlspecialchars($feedback_message); ?>
        </div>
        </div>
        <div class="score-details">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['Name']); ?></p>
            <p><strong>Register No:</strong> <?php echo htmlspecialchars($_SESSION['RegNo']); ?></p>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($_SESSION['dept']); ?></p>
            <p><strong>Your Score:</strong> 
                <span class="highlight"><?php echo htmlspecialchars($_SESSION['score']); ?>/<?php echo $total; ?></span>
            </p>
            <p><strong>Time Taken:</strong> <?php echo htmlspecialchars($display_time); ?></p>
        </div>
        

        <div class="footer">
            <form method="post" action="scoresheet.php">
                <input type="submit" name="Ok" value="Logout" class="submit-btn" />
            </form>
        </div>
    </div>
</body>
</html>


<?php
$conn->close();
?>