<?php
include_once 'core_db.php';
include 'header.php';
session_start();

if (!$_SESSION['logged'] || $_SESSION['logged'] === '') {
    header('Location: index.php');
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
    
    $query = "INSERT INTO users (Name, RegNo, Department, Section, Year) 
              VALUES ('$Name', '$RegNo', '$Department', '$Section', '$Year')";
 
    if (mysqli_query($conn, $query)) {
        header("Location: Student_Management.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Student</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">
    <h2>Add Student</h2>
    <form method="POST">
        <div class="mb-3"><label>RegNo:</label> <input type="text" name="RegNo" class="form-control" required></div>
        <div class="mb-3"><label>Name:</label> <input type="text" name="Name" class="form-control" required></div>
        <div class="mb-3"><label>Department:</label> <input type="text" name="Department" class="form-control"></div>
        <div class="mb-3"><label>Section:</label> <input type="text" name="Section" class="form-control"></div>
        <div class="mb-3"><label>Year:</label> <input type="text" name="Year" class="form-control"></div>
        <!-- <div class="mb-3"><label>Address:</label> <textarea name="address" class="form-control"></textarea></div> -->
        <button type="submit" class="btn btn-primary">Add Student</button>
    </form>
</body>
</html>
