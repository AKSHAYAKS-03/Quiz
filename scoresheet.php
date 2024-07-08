<?php
include 'core/db.php';
//session_start(); // Start session at the beginning

// Redirect to login page if not logged in or session variables are not set
if (!isset($_SESSION['login']) || empty($_SESSION['login']) ||
    !isset($_SESSION['logi']) || empty($_SESSION['logi']) ||
    !isset($_SESSION['RollNo']) || empty($_SESSION['RollNo']) ||
    !isset($_SESSION['Name']) || empty($_SESSION['Name']) ||
    !isset($_SESSION['dept']) || empty($_SESSION['dept']) ||
    !isset($_SESSION['score']) || empty($_SESSION['score']) ||
    !isset($_SESSION['total_time']) || empty($_SESSION['total_time'])) {
  
    header('Location: login.php');
    exit;
}

$rollno = $_SESSION['RollNo'];
$total_time = $_SESSION['total_time'];

list($minutes, $seconds) = explode(":", $total_time);

if($minutes == 0) {
$unit = "sec";
}
else {
$unit = "min";
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
    header('Location: login_eg.php');
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
            color: white;
            margin: 0;
            padding: 0;
        }
        .head {
            text-align: center;
            margin-top: 20px;
        }
        
        .cot {
            width: 500px;
            background-color: white;
            padding: 50px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            margin: 50px auto;
            color: #333;
            box-shadow: 1px 1px 10px black;
        }
        .score {
            padding-left: 400px;
        }

        .fr input[type="submit"] {
            background-color: #13274F;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .fr input[type="submit"]:hover {
            background: #fff;
            color: #13274F;
        }
        li{
            font-size: 20px;
            margin-bottom: 20px;
            text-decoration: none;
        }
        ul{

            margin-left:50px;
            list-style: none;
            padding: 20px;
        }
    </style>
</head>
<body oncontextmenu="return false;">
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
                <li><strong style="margin-right:70px;">Time Taken </strong><?php echo htmlspecialchars($_SESSION['total_time']); ?> <?php echo $unit; ?></li>
            </ul>
        </font>
        <div class="fr">
            <form method="post" action="scoresheet.php">
                <input type="submit" name="Ok" value="Logout" />
            </form>
    </div>
</div>
</body>
</html>

<?php
// Close MySQLi connection
$conn->close();
?>
