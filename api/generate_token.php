<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include '../db.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

$patient_name = $conn->real_escape_string($data['patient_name'] ?? 'Walk-in Patient');
$patient_age = intval($data['patient_age'] ?? 0);
$patient_phone = $conn->real_escape_string($data['patient_phone'] ?? '');
$department = $conn->real_escape_string($data['department'] ?? '');
$doctor_id = intval($data['doctor_id'] ?? 0);
$type = $conn->real_escape_string($data['type'] ?? 'walkin');

// Initialize department_id
$department_id = 0;

// Get department ID from name
if (!empty($department)) {
    $dept_sql = "SELECT id FROM departments WHERE name = ?";
    $dept_stmt = $conn->prepare($dept_sql);
    $dept_stmt->bind_param("s", $department);
    $dept_stmt->execute();
    $dept_result = $dept_stmt->get_result();
    if ($dept_result->num_rows > 0) {
        $dept_row = $dept_result->fetch_assoc();
        $department_id = $dept_row['id'];
    }
    $dept_stmt->close();
}

// If department_id still 0 and we have doctor_id, get from doctor
if ($department_id == 0 && $doctor_id > 0) {
    $doc_sql = "SELECT department_id FROM doctors WHERE id = ?";
    $doc_stmt = $conn->prepare($doc_sql);
    $doc_stmt->bind_param("i", $doctor_id);
    $doc_stmt->execute();
    $doc_result = $doc_stmt->get_result();
    if ($doc_result->num_rows > 0) {
        $doc_row = $doc_result->fetch_assoc();
        $department_id = $doc_row['department_id'];
    }
    $doc_stmt->close();
}

// Check if we have valid department
if ($department_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid department']);
    exit;
}

// Generate token number based on department prefix and daily count
$prefix = strtoupper(substr($department, 0, 1));
if (empty($prefix)) {
    $prefix = 'T'; // Default prefix
}
$today = date('Y-m-d');

// Get count of tokens for today for this department
$count_sql = "SELECT COUNT(*) as count FROM tokens WHERE DATE(created_at) = ? AND department_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("si", $today, $department_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$token_count = $count_row['count'] + 1;
$count_stmt->close();

$token_number = $prefix . str_pad($token_count, 3, '0', STR_PAD_LEFT);

// Calculate expected time (10 minutes per patient ahead)
$waiting_sql = "SELECT COUNT(*) as waiting FROM tokens WHERE DATE(created_at) = ? AND department_id = ? AND status = 'waiting'";
$waiting_stmt = $conn->prepare($waiting_sql);
$waiting_stmt->bind_param("si", $today, $department_id);
$waiting_stmt->execute();
$waiting_result = $waiting_stmt->get_result();
$waiting_row = $waiting_result->fetch_assoc();
$patients_ahead = $waiting_row['waiting'];
$waiting_stmt->close();

$wait_minutes = $patients_ahead * 10;
$expected_time = date('H:i:s', strtotime("+$wait_minutes minutes"));

// Get currently consulting token
$current_sql = "SELECT token_number FROM tokens WHERE DATE(created_at) = ? AND department_id = ? AND status = 'consulting' LIMIT 1";
$current_stmt = $conn->prepare($current_sql);
$current_stmt->bind_param("si", $today, $department_id);
$current_stmt->execute();
$current_result = $current_stmt->get_result();
$current_token = 'None';
if ($current_result->num_rows > 0) {
    $current_row = $current_result->fetch_assoc();
    $current_token = $current_row['token_number'];
}
$current_stmt->close();

// Insert new token
$insert_sql = "INSERT INTO tokens (token_number, patient_name, patient_age, patient_phone, department_id, doctor_id, token_type, expected_time, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'waiting', NOW())";
$insert_stmt = $conn->prepare($insert_sql);

if (!$insert_stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$insert_stmt->bind_param("ssississ", $token_number, $patient_name, $patient_age, $patient_phone, $department_id, $doctor_id, $type, $expected_time);

if ($insert_stmt->execute()) {
    $token_id = $conn->insert_id;
    echo json_encode([
        'success' => true,
        'message' => 'Token generated successfully',
        'data' => [
            'id' => $token_id,
            'token_number' => $token_number,
            'patient_name' => $patient_name,
            'expected_time' => date('h:i A', strtotime($expected_time)),
            'patients_ahead' => $patients_ahead,
            'current_token' => $current_token,
            'department' => $department,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to generate token: ' . $insert_stmt->error]);
}

$insert_stmt->close();
$conn->close();
?>
