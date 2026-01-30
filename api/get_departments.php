<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include '../db.php';

// Query to get all departments
$sql = "SELECT id, name, icon FROM departments ORDER BY name";
$result = $conn->query($sql);

$departments = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'data' => $departments
]);

$conn->close();
?>
