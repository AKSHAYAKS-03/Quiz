<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

include_once 'core_db.php';
session_start();

if (!isset($_SESSION['logged']) || $_SESSION['logged'] === '') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

// Get updated values from the request
$regNo = $_POST['RegNo'];
$name = $_POST['Name'];
$department = $_POST['Department'];
$section = $_POST['Section'];
$year = $_POST['Year'];

// Validate input (basic validation)
if (empty($regNo) || empty($name) || empty($department) || empty($section) || empty($year)) {
    echo json_encode(["success" => false, "message" => "All fields are required."]);
    exit;
}

// Update the database
$query = "UPDATE users SET Name = ?, Department = ?, Section = ?, Year = ? WHERE RegNo = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ssssi", $name, $department, $section, $year, $regNo);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Database update failed."]);
}

mysqli_stmt_close($stmt);
?>
