<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include '../db.php';

// Get department ID from query parameter
$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;
$department_name = isset($_GET['department']) ? $conn->real_escape_string($_GET['department']) : '';

$doctors = [];
$today = date('Y-m-d');

if ($department_id > 0) {
    // Query by department ID
    $sql = "SELECT d.id, d.name, d.time_slot, d.qualification, d.fees, dep.name as department, dep.id as department_id
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
            // Calculate wait time based on patients in queue for this doctor
            $wait_sql = "SELECT COUNT(*) as count FROM tokens WHERE doctor_id = ? AND DATE(created_at) = ? AND status = 'waiting'";
            $wait_stmt = $conn->prepare($wait_sql);
            $wait_stmt->bind_param("is", $row['id'], $today);
            $wait_stmt->execute();
            $wait_result = $wait_stmt->get_result();
            $wait_row = $wait_result->fetch_assoc();
            $row['wait_time'] = $wait_row['count'] * 10; // 10 mins per patient
            $row['patients_waiting'] = $wait_row['count'];
            $wait_stmt->close();
            
            $doctors[] = $row;
        }
        $stmt->close();
    }
} else if (!empty($department_name)) {
    // Query by department name
    $sql = "SELECT d.id, d.name, d.time_slot, d.qualification, d.fees, dep.name as department, dep.id as department_id
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
            // Calculate wait time based on patients in queue for this doctor
            $wait_sql = "SELECT COUNT(*) as count FROM tokens WHERE doctor_id = ? AND DATE(created_at) = ? AND status = 'waiting'";
            $wait_stmt = $conn->prepare($wait_sql);
            $wait_stmt->bind_param("is", $row['id'], $today);
            $wait_stmt->execute();
            $wait_result = $wait_stmt->get_result();
            $wait_row = $wait_result->fetch_assoc();
            $row['wait_time'] = $wait_row['count'] * 10; // 10 mins per patient
            $row['patients_waiting'] = $wait_row['count'];
            $wait_stmt->close();
            
            $doctors[] = $row;
        }
        $stmt->close();
    }
} else {
    // Return all doctors
    $sql = "SELECT d.id, d.name, d.time_slot, d.qualification, d.fees, dep.name as department, dep.id as department_id
            FROM doctors d 
            JOIN departments dep ON d.department_id = dep.id 
            ORDER BY dep.name, d.name";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Calculate wait time based on patients in queue for this doctor
            $wait_sql = "SELECT COUNT(*) as count FROM tokens WHERE doctor_id = ? AND DATE(created_at) = ? AND status = 'waiting'";
            $wait_stmt = $conn->prepare($wait_sql);
            $wait_stmt->bind_param("is", $row['id'], $today);
            $wait_stmt->execute();
            $wait_result = $wait_stmt->get_result();
            $wait_row = $wait_result->fetch_assoc();
            $row['wait_time'] = $wait_row['count'] * 10; // 10 mins per patient
            $row['patients_waiting'] = $wait_row['count'];
            $wait_stmt->close();
            
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
