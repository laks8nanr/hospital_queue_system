<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include '../db.php';

// Get parameters
$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : 'today';

// Determine date to query
if ($date === 'today') {
    $query_date = date('Y-m-d');
} else {
    $query_date = date('Y-m-d', strtotime($date));
}

// Build query to get all tokens for the day (all statuses)
$sql = "SELECT 
            t.id,
            t.token_number,
            t.patient_name,
            COALESCE(t.token_type, t.type, 'walkin') as token_type,
            t.status,
            t.expected_time,
            t.doctor_id,
            t.department_id,
            TIME(t.created_at) as created_time
        FROM tokens t
        WHERE DATE(t.created_at) = ?";

$params = [$query_date];
$types = "s";

if ($doctor_id > 0) {
    $sql .= " AND t.doctor_id = ?";
    $params[] = $doctor_id;
    $types .= "i";
}

$sql .= " ORDER BY 
            CASE t.status 
                WHEN 'consulting' THEN 1
                WHEN 'waiting' THEN 2
                WHEN 'completed' THEN 3
                WHEN 'cancelled' THEN 4
                ELSE 5
            END,
            t.expected_time ASC,
            t.id ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$tokens = [];
while ($row = $result->fetch_assoc()) {
    $tokens[] = [
        'id' => $row['id'],
        'token_number' => $row['token_number'],
        'patient_name' => $row['patient_name'],
        'type' => $row['token_type'],
        'status' => $row['status'],
        'expected_time' => $row['expected_time'],
        'created_time' => $row['created_time']
    ];
}

echo json_encode([
    'success' => true,
    'data' => $tokens,
    'count' => count($tokens),
    'date' => $query_date
]);

$stmt->close();
$conn->close();
?>
