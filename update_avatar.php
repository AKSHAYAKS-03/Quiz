<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

session_start();
include 'core_db.php';

// Check if the user is logged in
if (!isset($_SESSION['RegNo'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['avatar'])) {
    $RegNo = $_SESSION['RegNo'];
    $avatar = $_POST['avatar'];

    $sql = "UPDATE users SET Avatar = ? WHERE RegNo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $avatar, $RegNo);

    if ($stmt->execute()) {
        echo "Avatar updated successfully!";    
    } else {
        echo "Error updating avatar.";
    }

    $stmt->close();
    $conn->close();
}
?>
