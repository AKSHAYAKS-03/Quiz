<?php
include_once 'core_db.php';
session_start();

if(!$_SESSION['logged'] || $_SESSION['logged']===''){
    header('Location: login.php');
    exit;
}

if ($conn->connect_errno) {
    echo "Failed to connect to MySQL: " . $conn->connect_error;
    exit();
}

$activeQuizId= $_SESSION['active'];
$activeQuiz = $_SESSION['activeQuiz'];
$noRecords = false; 

if($activeQuizId !== 'None'){
    $sql = "SELECT * FROM student WHERE QuizId = $activeQuizId ORDER BY Score DESC, `Time` LIMIT 20";
    $records = $conn->query($sql);
}
else {
  $sql = "SELECT * FROM student ORDER BY Score DESC, `Time`";
  $records = $conn->query($sql);
}

if (isset($_POST['Delete'])) {
    if (isset($_POST['quizId'])) {
        $activeQuizId = $_POST['quizId'];
    }
    $conn->query("DELETE FROM student WHERE QuizId = $activeQuizId");
    $conn->query("DELETE FROM stud WHERE QuizId = $activeQuizId");
}

if (isset($_POST['DeleteAll'])) {
  $conn->query("DELETE FROM student");
  $conn->query("DELETE FROM stud WHERE QuizId = $activeQuizId");
}

if (isset($_POST['display'])) {
    if (isset($_POST['quizId'])) {
        $activeQuizId = $_POST['quizId'];
    }

    if($activeQuizId !== 'None' && $activeQuizId !== 'all'){
        $sql = "SELECT * FROM student WHERE QuizId = $activeQuizId ORDER BY Score DESC, `Time`";
    }
    else
    {
      $sql = "SELECT * FROM student ORDER BY Score DESC, `Time`";
    }
    $records = $conn->query($sql);
} 


if (isset($_POST['export'])) {
    if (isset($_POST['quizId'])) {
      $activeQuizId = $_POST['quizId'];
    }
    header("Location: export.php?quizId=$activeQuizId");
    exit;
}

if (isset($_POST['Back'])) {
    header("Location: admin.php");
    exit; 
}

?>

<!DOCTYPE html>
<html>
<head>
  <title>Score table</title>

  <script src="inspect.js"></script>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f2f2f2;
      margin: 0;
    }
    .header {
      background-color: #13274F;
      color: #fff;
      text-align: center;
      padding: 10px 0;
    }
    .container {
      width: 80%;
      margin: 0 auto;
    }
    h1 {
      margin: 0;
    }

    .select{
        text-align: center;
        margin-top: 20px;        
    }

    select {
        width: 180px; 
        padding: 7px;
        border: 1px solid #34495e;
        border-radius: 5px;
        background-color: #fff;
        color: #34495e;
        font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
        font-size: 16px;
        margin-top: 20px;
        cursor: pointer;
        box-shadow: 3px 3px 6px rgba(0, 0, 0, 0.2);
        margin-right: 30px;
        transition: all 0.3s ease;
    }

    select:last-child {
      margin-right: 0; 
    } 
    select:focus {
        outline: none;
        border-color: #13274F;
        box-shadow: 0 0 5px rgba(44, 62, 80, 0.5);
    }

    option {
        padding: 10px;
        background-color: #fff;
        color: #13274F;
    }

    option:hover {
        background-color: #ecf0f1;
    }

    #score {
      width: 80%;
      margin: 20px auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.4);
    }
    table th, table td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: center;
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
        background-color: #DEDEDE;
    }
    .back {
      text-align: center;
      margin: 30px 0;
    }
    .back input[type="submit"], .back select {
      background: #13274F;
      color: #fff;
      padding: 10px 20px;
      border: 0;
      border-radius: 5px;
      margin: 5px;
      cursor: pointer;
    }
    .back input[type="submit"]:hover, .back select:hover {
      background-color: #0d1b37;
    }
  </style>
</head>
<body oncontextmenu="return false;">
<script type="text/javascript" src="inspect.js"></script>
<div class="header">
  <div class="container">
    <h1>Score table</h1>
  </div>
</div>

<div class="select">
    Quiz Name:
    <select id="quiz">
      <option value="all">All</option>
      <?php
        $sql = "SELECT Quiz_Id, QuizName FROM quiz_details";
        $options = $conn->query($sql);
        if ($options->num_rows > 0) {
          while ($row = $options->fetch_assoc()) {
            $id = $row['Quiz_Id'];
            $selected = $id == $activeQuizId ? "selected" : "";
            echo "<option value='$id' $selected>" . $row['QuizName'] . "</option>";
          }
        }
      ?>
    </select>

    Limit:
    <select id="limit">
      <option value="10">Top 10</option>
      <option value="20" selected>Top 20</option>
      <option value="50">Top 50</option>
      <option value="100">Top 100</option>
      <option value="all">All</option>
    </select>

    Department:
    <select id="department">
      <option value="all">All Departments</option>
      <option value="CSE">CSE</option>
      <option value="IT">IT</option>
      <option value="ECE">ECE</option>
      <option value="EEE">EEE</option>
      <option value="MECH">MECH</option>
      <option value="CIVIL">CIV</option>
      <!-- Add other departments here -->
    </select>
</div>
<br>

<div id="score">
  <?php if ($records && $records->num_rows > 0) { ?>
    <div>
        <table>
        <tr>
          <th>SNO</th>
          <th>NAME</th>
          <th>REGISTER NO</th>
          <th>DEPARTMENT</th>
          <th>SCORE</th>
          <th>TIME (in MIN)</th>
        </tr>

        <?php
        $_SESSION['Sno'] = 0;
        while ($student = $records->fetch_assoc()) {
          $_SESSION['Sno']++;
          echo "<tr>";
          echo "<td>" . $_SESSION['Sno'] . "</td>";
          echo "<td>" . $student['Name'] . "</td>";
          echo "<td>" . $student['RollNo'] . "</td>";
          echo "<td>" . $student['Department'] . "</td>";
          echo "<td>" . $student['Score'] . "</td>";
          echo "<td>" . $student['Time'] . "</td>";
          echo "</tr>";
        }
        ?>
      </table>
    </div>

  <?php } else { ?>
    <p>No records found. Select Quiz to view</p>
  <?php } ?>
  </div>

  <div class="back">
      <form method="post" action="ViewResult.php">
        <input type="hidden" id="selectedQuizId" name="quizId" value="<?php echo $activeQuizId; ?>">
        <input type="hidden" id="selectedQuizName" value="<?php echo $activeQuiz; ?>">
        <input type="submit" name="Back" value="Back" />
        <input type="submit" name="display" value="Display All" />
        <input type="submit" name="Delete" value="Delete scoresheet" onclick ='return deleteConfirm()' />
        <input type="submit" name="DeleteAll" value="Delete All" onclick ='confirm("Are you sure you want to delete all scoresheets?")'/>
        <input type="submit" name="export" value="Export" />
      </form>
    </div>

<script>
  function fetchScores() {
    const quizId = document.getElementById("quiz").value;
    const limit = document.getElementById("limit").value;
    const department = document.getElementById("department").value;  // Correctly get the selected department value
    
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "FetchScores.php", true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            document.getElementById("score").innerHTML = xhr.responseText;
        }
    };
    xhr.send("quizId=" + quizId + "&limit=" + limit + "&department=" + department);  // Send the department value correctly

    document.getElementById("selectedQuizName").value = document.getElementById("quiz").options[document.getElementById("quiz").selectedIndex].text;
    document.getElementById("selectedQuizId").value = quizId;
}

document.getElementById("quiz").addEventListener('change', fetchScores);
document.getElementById("limit").addEventListener('change', fetchScores);
document.getElementById("department").addEventListener('change', fetchScores);

  function deleteConfirm() {
    const activeQuiz = document.getElementById("selectedQuizName").value;
    if (confirm("Are you sure you want to delete scoresheet for " + activeQuiz + "?")) {
      document.getElementById("score").innerHTML = 'No records Found! Select Quiz to view';
      const index = document.getElementById("quiz").selectedIndex;
      document.getElementById("quiz").selectedIndex = index;
      return true;
    } else {
      return false;
    }
  }
</script>
</body>
</html>
