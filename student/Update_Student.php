<?php
header('Content-Type: application/json');
include_once '../core/connection.php';

if (!isset($_SESSION['logged']) || $_SESSION['logged'] === '') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$response = ["success" => false];

if (!empty($data)) {
    foreach ($data as $student) {
        $regNo = $student['regNo'];
        $name = $student['name'];
        $department = $student['department'];
        $section = $student['section'];
        $year = $student['year'];

        $query = "UPDATE users SET Name='$name', Department='$department', Section='$section', Year='$year' WHERE RegNo='$regNo'";
        mysqli_query($conn, $query);
    }

    $response["success"] = true;
}

echo json_encode($response);
?>
