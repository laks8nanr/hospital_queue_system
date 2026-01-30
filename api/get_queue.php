<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include '../db.php';

// Get optional filters
$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;
$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';

$today = date('Y-m-d');

// Build query based on filters
$sql = "SELECT t.id, t.token_number, t.patient_name, t.patient_age, t.patient_phone, t.status, t.type, t.expected_time, t.created_at,
               d.name as doctor_name, dep.name as department_name
        FROM tokens t
        LEFT JOIN doctors d ON t.doctor_id = d.id
        LEFT JOIN departments dep ON t.department_id = dep.id
        WHERE DATE(t.created_at) = ?";

$params = [$today];
$types = "s";

if ($department_id > 0) {
    $sql .= " AND t.department_id = ?";
    $params[] = $department_id;
    $types .= "i";
}

if ($doctor_id > 0) {
    $sql .= " AND t.doctor_id = ?";
    $params[] = $doctor_id;
    $types .= "i";
}

if (!empty($status)) {
    $sql .= " AND t.status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY t.created_at ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$queue = [];
$stats = [
    'total' => 0,
    'waiting' => 0,
    'consulting' => 0,
    'completed' => 0
];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['expected_time'] = date('h:i A', strtotime($row['expected_time']));
        $row['created_at'] = date('h:i A', strtotime($row['created_at']));
        $queue[] = $row;
        
        $stats['total']++;
        $stats[$row['status']]++;
    }
}

// Get current consulting token
$current_sql = "SELECT t.token_number, d.name as doctor_name, dep.name as department_name
                FROM tokens t
                LEFT JOIN doctors d ON t.doctor_id = d.id
                LEFT JOIN departments dep ON t.department_id = dep.id
                WHERE DATE(t.created_at) = ? AND t.status = 'consulting'
                LIMIT 1";
$current_stmt = $conn->prepare($current_sql);
$current_stmt->bind_param("s", $today);
$current_stmt->execute();
$current_result = $current_stmt->get_result();
$current_token = null;
if ($current_result->num_rows > 0) {
    $current_token = $current_result->fetch_assoc();
}
$current_stmt->close();

echo json_encode([
    'success' => true,
    'data' => [
        'queue' => $queue,
        'stats' => $stats,
        'current_token' => $current_token
    ]
]);

$stmt->close();
$conn->close();
?>
