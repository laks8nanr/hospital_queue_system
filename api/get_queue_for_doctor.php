<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include '../db.php';

$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;

$today = date('Y-m-d');

// Build query - Order by: consulting first, then prebooked, then review, then walkin
// Within each type, order by expected_time
$sql = "SELECT t.id, t.token_number, t.patient_name, t.type, t.status, t.expected_time,
               CASE 
                   WHEN t.status = 'consulting' THEN 0
                   WHEN t.type = 'prebooked' THEN 1
                   WHEN t.type = 'review' THEN 2
                   ELSE 3
               END as priority_order
        FROM tokens t
        WHERE DATE(t.created_at) = ?
        AND t.status IN ('waiting', 'consulting')";

$params = [$today];
$types = "s";

if ($doctor_id > 0) {
    $sql .= " AND t.doctor_id = ?";
    $params[] = $doctor_id;
    $types .= "i";
}

if ($department_id > 0) {
    $sql .= " AND t.department_id = ?";
    $params[] = $department_id;
    $types .= "i";
}

$sql .= " ORDER BY priority_order ASC, t.expected_time ASC, t.id ASC LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$queue = [];
$position = 1;
while ($row = $result->fetch_assoc()) {
    $queue[] = [
        'position' => $position,
        'id' => $row['id'],
        'token_number' => $row['token_number'],
        'patient_name' => $row['patient_name'],
        'type' => $row['type'],
        'status' => $row['status'],
        'expected_time' => $row['expected_time']
    ];
    $position++;
}

echo json_encode([
    'success' => true,
    'data' => $queue
]);

$stmt->close();
$conn->close();
?>
