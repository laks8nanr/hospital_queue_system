<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include '../db.php';

// Get department ID from query parameter
$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;
$department_name = isset($_GET['department']) ? $conn->real_escape_string($_GET['department']) : '';

$doctors = [];

if ($department_id > 0) {
    // Query by department ID
    $sql = "SELECT d.id, d.name, d.time_slot, dep.name as department 
            FROM doctors d 
            JOIN departments dep ON d.department_id = dep.id 
            WHERE d.department_id = ? 
            ORDER BY d.name";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $department_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $doctors[] = $row;
        }
        $stmt->close();
    }
} else if (!empty($department_name)) {
    // Query by department name
    $sql = "SELECT d.id, d.name, d.time_slot, dep.name as department 
            FROM doctors d 
            JOIN departments dep ON d.department_id = dep.id 
            WHERE dep.name = ? 
            ORDER BY d.name";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $department_name);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $doctors[] = $row;
        }
        $stmt->close();
    }
} else {
    // Return all doctors
    $sql = "SELECT d.id, d.name, d.time_slot, dep.name as department 
            FROM doctors d 
            JOIN departments dep ON d.department_id = dep.id 
            ORDER BY dep.name, d.name";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $doctors[] = $row;
        }
    }
}

echo json_encode([
    'success' => true,
    'data' => $doctors
]);

$conn->close();
?>
