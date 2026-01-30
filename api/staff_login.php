<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include '../db.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

$staff_id = $conn->real_escape_string($data['staff_id'] ?? '');
$password = $data['password'] ?? '';

if (empty($staff_id) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Staff ID and Password are required']);
    exit;
}

// Query to check staff credentials
$sql = "SELECT id, staff_id, name, department FROM staff WHERE staff_id = ? AND password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $staff_id, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $staff = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'data' => [
            'id' => $staff['id'],
            'staff_id' => $staff['staff_id'],
            'name' => $staff['name'],
            'department' => $staff['department']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Staff ID or Password']);
}

$stmt->close();
$conn->close();
?>
