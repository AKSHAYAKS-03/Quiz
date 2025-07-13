<?php
header("Content-Type: application/json");
include_once '../core/connection.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['logged']) || $_SESSION['logged'] === '') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$column = $data['column'];
$value = $data['value'];
$regNos = $data['regNos']; 

if ($column && $value && !empty($regNos)) {
    // Create a dynamic query to update only the selected Reg Nos
    
    $placeholders = implode(",", array_fill(0, count($regNos), "?"));
    $sql = "UPDATE users SET `$column` = ? WHERE RegNo IN ($placeholders)";

    $stmt = $conn->prepare($sql);
    
    $params = array_merge([$value], $regNos);
    $types = str_repeat("s", count($params)); 
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "error" => "Invalid data"]);
}

$conn->close();
?>
