<?php
include_once 'core_db.php';

session_start();

if (!$_SESSION['logged'] || $_SESSION['logged'] === '') {
    header('Location: index.php');
    exit;
}

$regNo = $_POST['RegNo'];

if (empty($regNo)) {
    echo json_encode(["success" => false, "message" => "Reg No is required."]);
    exit;
}

$query = "DELETE FROM users WHERE RegNo = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $regNo);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Database delete failed."]);
}

mysqli_stmt_close($stmt);
?>
