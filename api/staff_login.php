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

// Query to check staff credentials - join with departments to get department name
// Supports both old schema (staff_id, department, doctor_id) and new schema (id as staff_id, email, dept_id, doc_id)
$sql = "SELECT s.id, s.name, s.email, s.role, s.dept_id, s.doc_id, d.name as department_name 
        FROM staff s 
        LEFT JOIN departments d ON s.dept_id = d.id
        WHERE s.id = ? AND s.password = ?";
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
            'staff_id' => $staff['id'],
            'name' => $staff['name'],
            'email' => $staff['email'],
            'role' => $staff['role'],
            'department' => $staff['department_name'],
            'department_id' => $staff['dept_id'],
            'doctor_id' => $staff['doc_id']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Staff ID or Password']);
}

$stmt->close();
$conn->close();
?>
