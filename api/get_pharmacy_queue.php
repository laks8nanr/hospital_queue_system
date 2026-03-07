<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include '../db.php';

$pharmacy_id = isset($_GET['pharmacy_id']) ? intval($_GET['pharmacy_id']) : 0;
$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';

$today = date('Y-m-d');

// Build query
$sql = "SELECT pt.id, pt.token_number, pt.patient_name, pt.patient_phone, pt.patient_id,
               pt.status, pt.payment_method, pt.payment_status, pt.counter_number, pt.counter_name,
               pt.prescription_number, pt.notes, pt.created_at, pt.updated_at,
               p.name as pharmacy_name, p.block, p.floor, p.wing
        FROM pharmacy_tokens pt
        JOIN pharmacies p ON pt.pharmacy_id = p.id
        WHERE DATE(pt.created_at) = ?";

$params = [$today];
$types = "s";

if ($pharmacy_id > 0) {
    $sql .= " AND pt.pharmacy_id = ?";
    $params[] = $pharmacy_id;
    $types .= "i";
}

if (!empty($status)) {
    $sql .= " AND pt.status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY pt.created_at ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$queue = [];
$stats = [
    'total' => 0,
    'waiting' => 0,
    'processing' => 0,
    'completed' => 0,
    'cancelled' => 0
];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['created_time'] = date('h:i A', strtotime($row['created_at']));
        $row['updated_time'] = date('h:i A', strtotime($row['updated_at']));
        $queue[] = $row;
        
        $stats['total']++;
        if (isset($stats[$row['status']])) {
            $stats[$row['status']]++;
        }
    }
}

// Get currently processing token
$current_sql = "SELECT pt.token_number, pt.patient_name, p.name as pharmacy_name
                FROM pharmacy_tokens pt
                JOIN pharmacies p ON pt.pharmacy_id = p.id
                WHERE DATE(pt.created_at) = ? AND pt.status = 'processing'";
if ($pharmacy_id > 0) {
    $current_sql .= " AND pt.pharmacy_id = ?";
}
$current_sql .= " ORDER BY pt.created_at ASC LIMIT 1";

$current_stmt = $conn->prepare($current_sql);
if ($pharmacy_id > 0) {
    $current_stmt->bind_param("si", $today, $pharmacy_id);
} else {
    $current_stmt->bind_param("s", $today);
}
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
