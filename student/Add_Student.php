<?php
include_once '../core/header.php';

if (!$_SESSION['logged'] || $_SESSION['logged'] === '') {
    header('Location: ../index.php');
    exit;
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $Name = $_POST['Name'];
    $RegNo = $_POST['RegNo'];
    $Department = $_POST['Department'];
    $Section = $_POST['Section'];
    $Year = $_POST['Year'];
    // $address = $_POST['email'];
        
    $Name = strtoupper($Name);  
    $RegNo = strtoupper($RegNo);  
    $Department = strtoupper($Department);
    $Section = strtoupper($Section);  
    $Year = strtoupper($Year);  
    
    $query = "INSERT INTO users (Name, RegNo, Department, Section, Year, Password) 
              VALUES ('$Name', '$RegNo', '$Department', '$Section', '$Year', '$RegNo')";
 
    if (mysqli_query($conn, $query)) {
        header("Location: ../dashboard/Student_Management.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Student</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/addStudent.css">
</head>
<body>
    <div class="outer">
        <h2>Add Student</h2>
        <form method="POST">
            <div class="input-field"><label>RegNo:</label> <input type="text" name="RegNo" class="form-control" required></div>
            <div class="input-field"><label>Name:</label> <input type="text" name="Name" class="form-control" required></div>
            <div class="input-field"><label>Department:</label> <input type="text" name="Department" class="form-control"></div>
            <div class="input-field"><label>Section:</label> <input type="text" name="Section" class="form-control"></div>
            <div class="input-field"><label>Year:</label> <input type="text" name="Year" class="form-control"></div>
            <button type="submit" class="btn btn-primary">Add Student</button>
        </form>
    </div> 
</body>
</html>
