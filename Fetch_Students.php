<?php
include_once 'core_db.php';
session_start();

if (!isset($_SESSION['logged']) || $_SESSION['logged'] === '') {
    echo json_encode([]);
    exit;
}

// Get filter values from POST request
$department = $_POST['department'] ?? 'all';
$section = $_POST['section'] ?? 'all';
$year = $_POST['year'] ?? 'all';

// Build SQL query
$query = "SELECT * FROM users WHERE 1";

if ($department !== 'all') {
    $query .= " AND Department = '" . mysqli_real_escape_string($conn, $department) . "'";
}
if ($section !== 'all') {
    $query .= " AND Section = '" . mysqli_real_escape_string($conn, $section) . "'";
}
if ($year !== 'all') {
    $query .= " AND Year = '" . mysqli_real_escape_string($conn, $year) . "'";
}

$query .= " ORDER BY Department, Section, Year";

$result = mysqli_query($conn, $query);

$students = [];
while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}

echo json_encode($students);
?>
