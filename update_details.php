<?php
session_start();
include 'core_db.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $regno =$_SESSION['RollNo'];
    $dept = $_POST['dept'];
    $sec = $_POST['sec'];
    $year = $_POST['year'];

    // Update the student details in the database
    $query = "UPDATE student SET Name = ?, Department = ?,Section = ?,Year = ?  WHERE RollNo = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssssi', $name, $dept, $sec, $year, $regno);

    if ($stmt->execute()) {
        // If update is successful, reload the page to reflect changes
        header('Location: dashboard.php');
    } else {
        echo "Error updating details.";
    }

    $stmt->close();
    $conn->close();
}
?>
