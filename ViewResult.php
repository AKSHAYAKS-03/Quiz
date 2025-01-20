<?php
include_once 'core_db.php';
session_start();

if(!$_SESSION['logged'] || $_SESSION['logged']===''){
    header('Location: index.php');
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
    $sql = "SELECT * FROM student WHERE QuizId = $activeQuizId ORDER BY Score DESC, `Time`";
    $records = $conn->query($sql);
}
else {
  $sql = "SELECT * FROM student ORDER BY Score DESC, `Time`";
  $records = $conn->query($sql);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['Delete'])) {
      if (isset($_POST['quizId'])) {
          $activeQuizId = $_POST['quizId'];
      }
      $conn->query("DELETE FROM student WHERE QuizId = $activeQuizId");
      $conn->query("DELETE FROM stud WHERE QuizId = $activeQuizId");
  }

  elseif (isset($_POST['DeleteAll'])) {
    $conn->query("DELETE FROM student");
    $conn->query("DELETE FROM stud WHERE QuizId = $activeQuizId");
  }

  elseif (isset($_POST['display'])) {
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

  elseif (isset($_POST['Back'])) {
      header("Location: admin.php");
      exit; 
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Score table</title>
  <script src="inspect.js"></script>
  <link rel="stylesheet" type="text/css" href="css/viewResult.css">
  <style>
    aside {
      position: fixed;
      left: 0;
      top: 70px;
      width: 250px; 
      height: 90%;
      background-color:rgba(228, 225, 225, 0.59);
      border: 1px solid #ddd;
      border-radius: 8px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      z-index: 10; 
      text-align: center;
    }
    aside select {
        width: 220px; 
        border: 1px solid #34495e;
        border-radius: 5px;
        background-color: #fff;
        color: #34495e;
        font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
        font-size: 16px;
        margin-left: 20px;
        margin: 0;
        cursor: pointer;
        box-shadow: 3px 3px 6px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }
    aside .category-label {
      cursor: pointer;
      font-weight: bold;
      padding: 5px;
      margin-top: 20px;
      display: block;
      width: max-content;
      font-size: large;
    }
    #score {
      float: right;
      width: 80%; 
      margin-right: 10px; 
      margin-top: 50px;
    }
    .header{
      position: fixed;
      width: 100%;
      top: 0;
    }
    .header a {
        text-decoration: none;
        padding: 2px;
        display: inline-block;
        position: absolute;
        top: 5px;
        right: 10px;
        padding: 8px;
    }
    .header #back{
        left: 10px;
        color: #fff;
    }
    .header a img {
        cursor: pointer;
        width: 26px;  
        height: 32px; 
    }
    #back{
        right: 98%;
    }
  </style>
</head>
<body oncontextmenu="return false;">
  <div class="header">
    <a href="admin.php" id="back" title="Back">
        <img src="icons\back_white.svg" alt="back">
    </a>
    <div class="container">
      <h1>Score table</h1>
    </div>
    <a href="logout.php" id="logout" title="Log Out">
        <img src="icons/exit_white.svg" alt="exit">
    </a>
  </div>

<aside class="row"  style="padding:5px">
  <div class="select">
  <h2 style="color: #13274F;">FILTERS</h2><hr/>
  <div class="category-label">Quiz Name</div>
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

      <div class="category-label">Limit</div>
      <select id="limit">
        <option value="all" selected>All</option>
        <option value="10">Top 10</option>
        <option value="20">Top 20</option>
        <option value="50">Top 50</option>
        <option value="100">Top 100</option>
      </select>

      <div class="category-label">Department</div>
      <select id="department">
        <option value="all" selected>All Departments</option>
        <option value="CSE">CSE</option>
        <option value="IT">IT</option>
        <option value="ECE">ECE</option>
        <option value="EEE">EEE</option>
        <option value="MECH">MECH</option>
        <option value="CIV">CIV</option>
      </select>

      <div class="category-label">Section</div>
      <select id="section">
        <option value="all" selected>All Sections</option>
        <option value="A">A</option>
        <option value="B">B</option>
        <option value="C">C</option>
      </select>

      <div class="category-label">Year</div>
      <select id="year">
        <option value="all" selected>All Years</option>
        <option value="I">I</option>
        <option value="II">II</option>
        <option value="III">III</option>
        <option value="IV">IV</option>
      </select>

      <div class="category-label">Performance</div>
      <select id="performance">
        <option value="all" selected>All</option>
        <option value="1">Toppers (85 & above)</option>
        <option value="2">Average (60 - 84)</option>
        <option value="3">Below Average (40 - 59)</option>
        <option value="4">Bottom (Below 40)</option>
      </select>
    </div>
  </div>
</aside>
<br>

<div id="score">
  <div id="scoreTable">
    <?php if ($records && $records->num_rows > 0) { ?>
      <div>
          <table>
          <tr>
            <th>SNO</th>
            <th>NAME</th>
            <th>REGISTER NO</th>
            <th>DEPARTMENT</th>
            <th>SECTION</th>
            <th>YEAR</th>
            <th>SCORE</th>
            <th>PERCENTAGE</th>
            <th>TIME TAKEN</th>
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
            echo "<td>" . $student['Section'] . "</td>";
            echo "<td>" . strtoupper($student['Year']) . "</td>";
            echo "<td>" . $student['Score'] . "</td>";
            echo "<td>" . $student['percentage'] . "</td>";
            echo "<td>" . $student['Time'] . "</td>";
            echo "</tr>";
          }
          ?>
        </table>
      </div>

    <?php } else { ?>
      <p style="font-weight: bold;">No records found. Select Quiz to view</p>
    <?php } ?>
  </div>

  <div class="back">
      <form method="post" action="ViewResult.php">
        <input type="hidden" id="selectedQuizId" name="quizId" value="<?php echo $activeQuizId; ?>">
        <input type="hidden" id="selectedQuizName" value="<?php echo $activeQuiz; ?>">
        <input type="hidden" id="selectedDepartment" name="department">
        <input type="hidden" id="selectedSection" name="section">
        <input type="hidden" id="selectedYear" name="year">
        <input type="hidden" id="selectedLimit" name="limit">
        <input type="hidden" id="selectedPerformance" name="performance" value="all">

        <input type="submit" name="Back" value="Back" />
        <input type="submit" name="display" value="Display All" />
        <input type="submit" name="Delete" value="Delete scoresheet" onclick ='return deleteConfirm()' />
        <input type="submit" name="DeleteAll" value="Delete All" onclick ='confirm("Are you sure you want to delete all scoresheets?")'/>
        <input type="button" value="Export" onclick="exportData()" />
      </form>
    </div>
</div>
<script>
  function setHiddenValues() {
    document.getElementById("selectedQuizId").value = document.getElementById("quiz").value;
    document.getElementById("selectedDepartment").value = document.getElementById("department").value;
    document.getElementById("selectedSection").value = document.getElementById("section").value;
    document.getElementById("selectedYear").value = document.getElementById("year").value;
    document.getElementById("selectedLimit").value = document.getElementById("limit").value;
    document.getElementById("selectedPerformance").value = document.getElementById("performance").value;
  }

  // Call the function when the user submits the form or changes the selection
  document.querySelector('form').addEventListener('submit', setHiddenValues);

  document.getElementById("quiz").addEventListener('change', setHiddenValues);
  document.getElementById("department").addEventListener('change', setHiddenValues);
  document.getElementById("section").addEventListener('change', setHiddenValues);
  document.getElementById("year").addEventListener('change', setHiddenValues);
  document.getElementById("limit").addEventListener('change', setHiddenValues);
  document.getElementById("performance").addEventListener('change', setHiddenValues);
  
  function exportData() {
    const quizId = document.getElementById("quiz").value;
    const department = document.getElementById("selectedDepartment").value;
    const section = document.getElementById("selectedSection").value;
    const year = document.getElementById("selectedYear").value;
    const limit = document.getElementById("selectedLimit").value;
    const performance = document.getElementById("selectedPerformance").value;

    const url = `export.php?quizId=${quizId}&department=${department}&section=${section}&year=${year}&performance=${performance}`;
    window.location.href = url;
  }
  
  function fetchScores() {
      const quizId = document.getElementById("quiz").value;
      const limit = document.getElementById("limit").value;
      const department = document.getElementById("department").value;
      const section = document.getElementById("section").value;
      const year = document.getElementById("year").value;
      const performance = document.getElementById("performance").value;

      const xhr = new XMLHttpRequest();
      xhr.open("POST", "FetchScores.php", true);
      xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
      xhr.onreadystatechange = function() {
          if (xhr.readyState === 4 && xhr.status === 200) {
              document.getElementById("scoreTable").innerHTML = xhr.responseText;
          }
      };
      xhr.send("quizId=" + quizId + "&limit=" + limit + "&department=" + department + "&section=" + section + "&year=" + year+"&performance="+performance); 

      document.getElementById("selectedQuizName").value = document.getElementById("quiz").options[document.getElementById("quiz").selectedIndex].text;
      document.getElementById("selectedQuizId").value = quizId;
  }

    document.getElementById("quiz").addEventListener('change', fetchScores);
    document.getElementById("limit").addEventListener('change', fetchScores);
    document.getElementById("department").addEventListener('change', fetchScores);
    document.getElementById("section").addEventListener('change', fetchScores);
    document.getElementById("year").addEventListener('change', fetchScores);
    document.getElementById("performance").addEventListener('change', fetchScores);

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
