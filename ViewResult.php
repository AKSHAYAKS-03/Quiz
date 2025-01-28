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
    $sql = "SELECT * FROM student WHERE QuizId = $activeQuizId ORDER BY CAST(RollNo as UNSIGNED)";
    $records = $conn->query($sql);
}
else {
  $sql = "SELECT * FROM student ORDER BY CAST(RollNo as UNSIGNED)";
  $records = $conn->query($sql);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  echo '<script>console.log("POST method");</script>';
  if (isset($_POST['Delete'])) {
      if (isset($_POST['quizId'])) {
          $activeQuizId = $_POST['quizId'];
      }
      echo 'activeQuizId: ' . $activeQuizId;
      $conn->query("DELETE FROM student WHERE QuizId = $activeQuizId");
      echo '<script>console.log("deleted from student");</script>';
      $conn->query("DELETE FROM stud WHERE QuizId = $activeQuizId");
      echo '<script>console.log("deleted from stud");</script>';
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
          $sql = "SELECT * FROM student WHERE QuizId = $activeQuizId ORDER BY CAST(RollNo as UNSIGNED)";
      }
      else
      {
        $sql = "SELECT * FROM student ORDER BY CAST(RollNo as UNSIGNED)";
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
  <link rel="stylesheet" type="text/css" href="css/navigation.css">
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
        padding: 5px;
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
    .arrow{
      cursor: pointer;
      font-size: 10px;
      float: right;
    }
    table th{
      cursor: pointer;
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
              $name = $row['QuizName'];
              $selected = $id == $activeQuizId ? "selected" : "";
              echo "<option value='$id' $selected>" . $name . "</option>";
            }
          }
        ?>
      </select>

      <div class="category-label">Limit</div>
      <select id="limit">
        <option value="all" selected>All</option>
        <option value=10>10</option>
        <option value=20>20</option>
        <option value=50>50</option>
        <option value=100>100</option>
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
        <option value="toppers">Toppers (85 & above)</option>
        <option value="aboveAverage">Above Average (60 - 84)</option>
        <option value="average">Average (40 - 59)</option>
        <option value="belowAverage">Below Average (Below 40)</option>
      </select>
    </div>
  </div>
</aside>
<br>

<div id="score">
<h3 id="noRecordsMessage"  style="font-weight: bold; color: red; display: none">** No records found. Select Quiz to view **</h3>
  <div id="scoreTable">
      <div>
          <table>
          <tr>
            <th onclick="sortTable(0)">SNO <span class="arrow"></span></th>
            <th onclick="sortTable(1)">NAME <span class="arrow"></span></th>
            <th onclick="sortTable(2)">REGISTER NO <span class="arrow"></span></th>
            <th onclick="sortTable(3)">DEPARTMENT <span class="arrow"></span></th>
            <th onclick="sortTable(4)">SECTION <span class="arrow"></span></th>
            <th onclick="sortTable(5)">YEAR <span class="arrow"></span></th>
            <th onclick="sortTable(6)">SCORE <span class="arrow"></span></th>
            <th onclick="sortTable(7)">PERCENTAGE <span class="arrow"></span></th>
            <th onclick="sortTable(8)">TIME TAKEN <span class="arrow"></span></th>
          </tr>
          <tbody id="tableBody">
          <?php
              if ($records && $records->num_rows > 0) { 
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
          <?php } else { 
            echo '<script>
              document.getElementById("noRecordsMessage").style.display = "block";
              document.getElementById("scoreTable").style.display = "none";
            </script>';
          } ?>
          </tbody>
        </table>
      </div>
  </div>

  <div class="back" id="options">
      <form method="post" action="ViewResult.php" method='post'>
        <input type="hidden" id="selectedQuizId" name="quizId" value="<?php echo $activeQuizId; ?>">
        <input type="hidden" id="selectedQuizName" value="<?php echo $activeQuiz; ?>">
        <input type="hidden" id="selectedDepartment" name="department">
        <input type="hidden" id="selectedSection" name="section">
        <input type="hidden" id="selectedYear" name="year">
        <input type="hidden" id="selectedLimit" name="limit">
        <input type="hidden" id="selectedPerformance" name="performance" value="all">

        <input type="submit" name="Back" value="Back" />
        <input type="submit" name="display" value="Display All" />
        <input type="submit" name="Delete" value="Delete scoresheet" onclick ='confirm("Are you sure you want to delete scoresheet for " + document.getElementById("selectedQuizName").value + "?")' />
        <input type="submit" name="DeleteAll" value="Delete All" onclick ='confirm("Are you sure you want to delete all scoresheets?")'/>
        <input type="button" value="Export" id="export" onclick="exportData()" />
      </form>
    </div>
</div>
<script>
  if (document.getElementById("scoreTable").style.display === "none") {
    document.getElementById("options").style.display = "none";
} else {
    document.getElementById("options").style.display = "block"; // Show if not hidden
}
  function setHiddenValues() {
    document.getElementById("selectedQuizId").value = document.getElementById("quiz").value;
    document.getElementById("selectedQuizName").value = document.getElementById("quiz").options[document.getElementById("quiz").selectedIndex].text;
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
  
  function fetchScores() {
      var tableBody = document.getElementById("tableBody");
      if(tableBody){
        document.getElementById("tableBody").innerHTML = "";
      }
      const quizId = document.getElementById("quiz").value;
      const department = document.getElementById("department").value;
      const section = document.getElementById("section").value;
      const year = document.getElementById("year").value;
      const performance = document.getElementById("performance").value;
      console.log("insideee");
      const xhr = new XMLHttpRequest();
      xhr.open("POST", "FetchScores.php", true);
      xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
      xhr.onreadystatechange = function() {
        console.log("inside on ready state change");
          if (xhr.readyState === 4 && xhr.status === 200) {
              if(xhr.responseText.trim() === 'empty'){
                console.log('here');
                document.getElementById('export').disabled = true;
                document.getElementById('scoreTable').style.display = 'none';
                document.getElementById("options").style.display = 'none';
                document.getElementById('noRecordsMessage').style.display = 'block'
              }
              else{
                document.getElementById('export').disabled = false;
                document.getElementById('scoreTable').style.display = 'block';
                document.getElementById("options").style.display = 'block';
                document.getElementById('noRecordsMessage').style.display = 'none';
                document.getElementById("tableBody").innerHTML = xhr.responseText;
                if(currentSortColumn>0){
                    currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
                    sortTable(currentSortColumn);
                }
              }
              limitChange();
          }
      };
      xhr.send("quizId=" + quizId + "&department=" + department + "&section=" + section + "&year=" + year+"&performance="+performance); 

      document.getElementById("selectedQuizName").value = document.getElementById("quiz").options[document.getElementById("quiz").selectedIndex].text;
      document.getElementById("selectedQuizId").value = quizId;
  }

  function limitChange() {
    console.log("limit");
    const limit = document.getElementById("limit").value;
    const tableBody = document.getElementById("tableBody"); 
    const rows = Array.from(tableBody.getElementsByTagName("tr")); 

    rows.forEach((row, index) => {
        row.style.display = index < limit || limit=='all'? "" : "none"; 
        console.log(index+" "+row.style.display);
    });

    var dis = (limit==='all')?'':'none';
    addedRows.forEach(row => {
        row.style.display = dis;
    });

    addedRows.slice(0, limit).forEach(row => {
        row.style.display = ""; 
    });
    document.getElementById("selectedLimit").value = limit;
    
  }
  
  document.getElementById("limit").addEventListener('change', fetchScores);

  document.getElementById("quiz").addEventListener('change', fetchScores);
  document.getElementById("department").addEventListener('change', fetchScores);
  document.getElementById("section").addEventListener('change', fetchScores);
  document.getElementById("year").addEventListener('change', fetchScores);
  document.getElementById("performance").addEventListener('change', fetchScores);

  function exportData() {
      setHiddenValues();
      const quizName = document.getElementById("selectedQuizName").value;
      const department = document.getElementById("selectedDepartment").value;
      const section = document.getElementById("selectedSection").value;
      const year = document.getElementById("selectedYear").value;
      const limit = document.getElementById("selectedLimit").value;
      const performance = document.getElementById("selectedPerformance").value;

      var fileName = generateCSVFilename(quizName, department, section, year, performance);

      const table = document.getElementById("scoreTable").getElementsByTagName('table')[0];
      const rows = table.getElementsByTagName('tr');

      let csvData = [];
      const headerRow = rows[0];
      const headerCells = headerRow.getElementsByTagName('th');
      let headerData = [];
      for (let j = 0; j < headerCells.length; j++) {
          headerData.push(headerCells[j].innerText.trim());
      }
      csvData.push(headerData.join(',')); 
      
      for (let i = 1; i < rows.length; i++) {
          let row = rows[i];
          const cells = row.getElementsByTagName('td');
          
          if (i === 0 || cells.length === 0) 
            continue;

          let rowData = [];
          for (let j = 0; j < cells.length; j++) {
            rowData.push(cells[j].innerText.trim());
          }
          csvData.push(rowData.join(','));
      }
      
      let csvContent = "data:text/csv;charset=utf-8," + csvData.join("\n");

      const encodedUri = encodeURI(csvContent);
      const link = document.createElement('a');
      link.setAttribute('href', encodedUri);
      link.setAttribute('download', fileName);

      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }


  function generateCSVFilename(quizName, department, section, year, performance) {
      let filename = `quiz_${new Date().toISOString().split('T')[0]}`;

      if (quizName!=='all') {
          filename = `${quizName}_${new Date().toISOString().split('T')[0]}`;
      }
      if (department && department !== 'all') {
          filename += `_${department}`;
      }
      if (year && year !== 'all') {
          filename += `_${year}`;
      }
      if (section && section !== 'all') {
          filename += `_${section}`;
      }
      if (performance && performance !== 'all') {
          let performanceLabel = '';
          if (performance === 'toppers') performanceLabel = 'Toppers';
          else if (performance === 'aboveAverage') performanceLabel = 'aboveAverage';
          else if (performance === 'average') performanceLabel = 'Average';
          else if (performance === 'belowAverage') performanceLabel = 'belowAverage';
          
          if (performanceLabel) {
              filename += `_${performanceLabel}`;
          }
      }
      filename += '.csv';  
      return filename;
  }

  let currentSortColumn = -1;
  let currentSortDirection = 'asc';
  const addedRows = [];

  function sortTable(columnIndex) {
    const tbody = document.getElementById('tableBody'); 
    const rows = Array.from(tbody.rows); 
      let sortedRows;
      const isNumeric = columnIndex === 6 || columnIndex === 7 || columnIndex === 8;

      if (currentSortColumn === columnIndex) {
        currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        currentSortColumn = columnIndex;
        currentSortDirection = 'asc';
      }

      tbody.innerHTML = "";
      sortedRows =rows.sort((rowA, rowB) => {
        const cellA = rowA.cells[columnIndex].textContent.trim();
        const cellB = rowB.cells[columnIndex].textContent.trim();

        let comparison = 0;

        if (isNumeric) {
          comparison = parseFloat(cellA.replace(/[^\d.-]/g, '')) - parseFloat(cellB.replace(/[^\d.-]/g, ''));
        } else {
          comparison = cellA.localeCompare(cellB);
        }

        return currentSortDirection === 'asc' ? comparison : -comparison;
      });
      
      tbody.innerHTML = "";

// Append the sorted rows to the tbody
sortedRows.forEach(row => {
    tbody.appendChild(row);
});
      currentSortColumn = columnIndex;
      updateSortIcons();
  }

  function updateSortIcons() {
    const headers = document.querySelectorAll('th');
    console.log("Headers: " + headers);
    headers.forEach((header, index) => {
      const icon = header.querySelector('span');
      if (index === currentSortColumn) {
        icon.innerHTML = currentSortDirection === 'asc'? '&#9650;': '&#9660;'; 
        icon.style.color = '#lightgray'; 
      }
      else{
        icon.innerHTML = '';
      }
    });
  }

</script>
</body>
</html>
