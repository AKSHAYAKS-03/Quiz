<?php
include_once '../core/header.php';


if (!isset($_SESSION['login']) || empty($_SESSION['login']) ||
    !isset($_SESSION['logi']) || empty($_SESSION['logi']) ||
    !isset($_SESSION['RegNo']) || empty($_SESSION['RegNo']) ||
    !isset($_SESSION['Name']) || empty($_SESSION['Name']) ||
    !isset($_SESSION['dept']) || empty($_SESSION['dept']) ){
    header('Location: ../index.php');
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['logout'])) {
        header('Location:../auth/Student_logout.php'); 
        exit;
    }

    if (isset($_POST['dashboard'])) {
        header('Location: ../dashboard/dashboard.php');
        exit;
    }
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
    <script src="../assets/scripts/inspect.js"></script>
    <link rel="stylesheet" type="text/css" href="../assets/css/scoresheet.css">
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
                <div class="bts">
                
                    <input type="submit" name="dashboard" value="Dashboard" class="submit-btn" />
                    <input type="submit" name="logout" value="Logout" class="submit-btn" />
                </div>
            </form>

        </div>
    </div>
</body>
</html>


<?php
$conn->close();
?>