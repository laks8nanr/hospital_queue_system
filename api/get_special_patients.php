<?php
/**
 * Get Special Patients API
 * Returns list of review or skipped patients for the given doctor
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

include '../db.php';

$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : '';

if (empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Status is required (review or skipped)']);
    exit;
}

if (!in_array($status, ['review', 'skipped'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status. Use "review" or "skipped"']);
    exit;
}

try {
    $today = date('Y-m-d');
    
    $sql = "SELECT t.id, t.token_number, t.patient_name, t.patient_phone, t.status, 
                   t.created_at, t.expected_time, t.token_type,
                   d.name as doctor_name, dept.name as department_name
            FROM tokens t
            LEFT JOIN doctors d ON t.doctor_id = d.id
            LEFT JOIN departments dept ON t.department_id = dept.id
            WHERE t.status = ? AND DATE(t.created_at) = ?";
    
    if ($doctor_id > 0) {
        $sql .= " AND t.doctor_id = ?";
    }
    
    $sql .= " ORDER BY t.created_at ASC";
    
    $stmt = $conn->prepare($sql);
    
    if ($doctor_id > 0) {
        $stmt->bind_param("ssi", $status, $today, $doctor_id);
    } else {
        $stmt->bind_param("ss", $status, $today);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $patients = [];
    while ($row = $result->fetch_assoc()) {
        $patients[] = [
            'id' => $row['id'],
            'token_number' => $row['token_number'],
            'patient_name' => $row['patient_name'],
            'patient_phone' => $row['patient_phone'],
            'token_type' => $row['token_type'],
            'created_at' => $row['created_at'],
            'expected_time' => $row['expected_time'],
            'doctor_name' => $row['doctor_name'],
            'department_name' => $row['department_name']
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'count' => count($patients),
        'patients' => $patients
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
