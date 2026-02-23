<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include '../db.php';

$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;
$include_not_arrived = isset($_GET['include_not_arrived']) ? $_GET['include_not_arrived'] === 'true' : true;

$today = date('Y-m-d');

// First, get all checked-in tokens (both prebooked and walkin)
$sql = "SELECT 
            t.id,
            t.token_number,
            t.patient_name,
            COALESCE(t.token_type, t.type, 'walkin') as type,
            t.status,
            t.expected_time,
            t.doctor_id,
            t.department_id,
            'checked_in' as checkin_status,
            CASE 
                WHEN t.status = 'consulting' THEN 0
                WHEN COALESCE(t.token_type, t.type) = 'prebooked' OR t.token_number LIKE 'PB%' THEN 1
                WHEN COALESCE(t.token_type, t.type) = 'review' THEN 2
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

$sql .= " ORDER BY 
    CASE WHEN status = 'consulting' THEN 0 ELSE 1 END,
    priority_order ASC, 
    expected_time ASC, 
    id ASC 
    LIMIT 20";

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
        'expected_time' => $row['expected_time'],
        'checkin_status' => 'checked_in'
    ];
    $position++;
}
$stmt->close();

// Optionally include not-yet-arrived prebooked appointments (for display only)
$expected = [];
if ($include_not_arrived) {
    $pb_sql = "SELECT 
            pb.id as prebook_id,
            pb.booking_id as token_number,
            pb.patient_name,
            'prebooked' as type,
            'not_arrived' as status,
            pb.appointment_time as expected_time,
            pb.doctor_id,
            pb.department_id
        FROM prebooked_appointments pb
        WHERE pb.appointment_date = ?
        AND pb.status IN ('booked', 'confirmed')
        AND NOT EXISTS (
            SELECT 1 FROM tokens t 
            WHERE (t.token_number = pb.booking_id OR t.booking_id = pb.booking_id)
            AND DATE(t.created_at) = ?
            AND t.status != 'cancelled'
        )";
    
    $pb_params = [$today, $today];
    $pb_types = "ss";
    
    if ($doctor_id > 0) {
        $pb_sql .= " AND pb.doctor_id = ?";
        $pb_params[] = $doctor_id;
        $pb_types .= "i";
    }
    
    $pb_sql .= " ORDER BY pb.appointment_time ASC LIMIT 10";
    
    $pb_stmt = $conn->prepare($pb_sql);
    $pb_stmt->bind_param($pb_types, ...$pb_params);
    $pb_stmt->execute();
    $pb_result = $pb_stmt->get_result();
    
    while ($row = $pb_result->fetch_assoc()) {
        $expected[] = [
            'prebook_id' => $row['prebook_id'],
            'token_number' => $row['token_number'],
            'patient_name' => $row['patient_name'],
            'type' => 'prebooked',
            'status' => 'not_arrived',
            'expected_time' => $row['expected_time'],
            'checkin_status' => 'not_checked_in'
        ];
    }
    $pb_stmt->close();
}

echo json_encode([
    'success' => true,
    'data' => $queue,
    'expected_arrivals' => $expected,
    'total_waiting' => count($queue),
    'total_expected' => count($expected)
]);

$conn->close();
?>
