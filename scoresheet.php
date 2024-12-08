<?php
include 'core/db.php';
//session_start(); // Start session at the beginning
date_default_timezone_set('Asia/Kolkata');


if (!isset($_SESSION['login']) || empty($_SESSION['login']) ||
    !isset($_SESSION['logi']) || empty($_SESSION['logi']) ||
    !isset($_SESSION['RollNo']) || empty($_SESSION['RollNo']) ||
    !isset($_SESSION['Name']) || empty($_SESSION['Name']) ||
    !isset($_SESSION['dept']) || empty($_SESSION['dept'])) {
    header('Location: login.php');
    exit;
}

$exited = isset($_GET['exitscore']) && $_GET['exitscore'] == 1;

if (!$exited) {
    if (!isset($_SESSION['score']) || empty($_SESSION['score']) ||
        !isset($_SESSION['total_time']) || empty($_SESSION['total_time'])) {
        header('Location: login.php');
        exit;
    }
}

$rollno = $_SESSION['RollNo'];
$total_time = $_SESSION['total_time'];

list($hours, $minutes, $seconds) = explode(':', $total_time);

// Determine the appropriate time format to display
if ($hours == 0 && $minutes == 0) {
    $display_time = intval($seconds) . ' sec';
} elseif ($hours == 0) {
    $display_time = intval($minutes) . ' min ' . intval($seconds) . ' sec';
} else {
    $display_time = intval($hours) . ' hr ' . intval($minutes) . ' min';
}

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch total number of questions for the quiz
$query_total = $conn->query("SELECT * FROM multiple_choices WHERE QuizId = '" . $_SESSION['active'] . "'");
$total = $query_total->num_rows;

// Handle logout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['Ok'])) {
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Score Sheet</title>
    <style>
    body {
        background-color: #13274F;
        font-family: "Poppins", sans-serif;
        color: #13274F;
        margin: 0;
        padding: 0;
        background-image: url("img3.jpg");
        background-repeat: no-repeat;
        background-attachment: fixed;
        background-position: center;
        background-size: cover; /* This ensures the background image covers the entire page */
    }

    .head {
        text-align: center;
        margin-top: 20px;
    }

    .cot {
        width: 500px;
        height: 350px;            
        font-family: 'Poppins', sans-serif;
        background-size: cover;
        background-color: white;
        padding: 10px;
        border-radius: 8px;
        margin: 50px auto;
        color: #13274F;
        box-shadow: 1px 1px 20px 10px rgba(0, 0, 0, 0.2);
        top: 60%;
        left: 50%;
        z-index: 1; /* Ensure the box appears above the background image */
        animation: blinkJump 3s ease-in-out infinite; /* Apply a scale animation */
    }

    /* Blink and Jump Animation */
    @keyframes blinkJump {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }

    .score {
        padding-left: 400px;
    }

    
    li {
        font-size: 20px;
        margin-bottom: 20px;
        text-decoration: none;
    }

    ul {
        margin-left: 50px;
        list-style: none;
        padding: 40px;
    }

    /* Lights animation for a glowing effect */
    @keyframes lights {
        0% {
            box-shadow: 0 0 10px 5px transparent;
        }
        50% {
            box-shadow: 0 0 20px 10px transparent, 0 0 40px 20px #AFDBF5;
        }
        100% {
            box-shadow: 0 0 10px 5px transparent;
        }
    }
    .submit-btn {
    background-color: #13274F;
    color: #fff;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s ease, color 0.3s ease; /* For smooth hover transition */
}

.submit-btn:hover {
    background: #fff;
    color: #13274F;
}

.fr {
    text-align: center; /* Ensures the form is centered */
    margin-top: -30px; /* Keeps the original margin */
}

</style>

</head>
<body oncontextmenu="return false;">
    <br>
<div class="head">
        <h1>Score Sheet</h1>
</div>
<div class="cot">
        <font size='05'>
            <ul>
                <li><strong style="margin-right:127px;">Name  </strong> <?php echo htmlspecialchars($_SESSION['Name']); ?></li>
                <li><strong style="margin-right:70px;">Register No  </strong> <?php echo htmlspecialchars($_SESSION['RollNo']); ?></li>
                <li><strong style="margin-right:70px;">Department </strong> <?php echo htmlspecialchars($_SESSION['dept']); ?></li>
                <li><strong style="margin-right:75px;">Your Score  </strong> <?php echo htmlspecialchars($_SESSION['score']); ?>/<?php echo $total; ?></li>
                <li><strong style="margin-right:70px;">Time Taken </strong><?php echo htmlspecialchars($display_time); ?></li>
                </ul>
        </font>
        <div class="fr" style="text-align: center;">
    <form method="post" action="scoresheet.php">
        <input type="submit" name="Ok" value="Logout" class="submit-btn" />
    </form>
</div>

</div>
</body>
</html>

<?php
// Close MySQLi connection
$conn->close();
?>