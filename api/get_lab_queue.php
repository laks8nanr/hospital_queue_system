<?php
// api/get_lab_queue.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include '../db.php';

$test_type = isset($_GET['test_type']) ? strtoupper(trim($_GET['test_type'])) : '';
$current_token = isset($_GET['current_token']) ? strtoupper(trim($_GET['current_token'])) : '';

if (empty($test_type)) {
    echo json_encode([
        'success' => false,
        'message' => 'Test type is required'
    ]);
    exit;
}

// Get test_id from test_code
$testQuery = "SELECT test_id, test_name, duration_minutes FROM lab_tests WHERE test_code = ?";
$testStmt = $conn->prepare($testQuery);
$testStmt->bind_param("s", $test_type);
$testStmt->execute();
$testResult = $testStmt->get_result();

if ($testResult->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid test type'
    ]);
    exit;
}

$testInfo = $testResult->fetch_assoc();
$test_id = $testInfo['test_id'];
$test_name = $testInfo['test_name'];
$duration = $testInfo['duration_minutes'];

// Combined query for lab queue
$query = "
    SELECT * FROM (
        -- Checked-in lab tokens (from lab_tokens table)
        SELECT 
            lt.token_id as id,
            lt.token_number,
            lt.patient_name,
            lt.patient_type,
            lt.token_status as status,
            lt.scheduled_time,
            lt.created_at,
            'checked_in' as checkin_status,
            ? as test_name
        FROM lab_tokens lt
        WHERE lt.test_id = ?
        AND lt.scheduled_date = CURDATE()
        AND lt.token_status IN ('waiting', 'in_progress')
        
        UNION ALL
        
        -- Prebooked lab appointments not yet checked in
        SELECT 
            pla.booking_id as id,
            pla.booking_code as token_number,
            pla.patient_name,
            'prebooked' as patient_type,
            pla.booking_status as status,
            pla.appointment_time as scheduled_time,
            pla.created_at,
            'not_checked_in' as checkin_status,
            ? as test_name
        FROM prebooked_lab_appointments pla
        WHERE pla.test_id = ?
        AND pla.appointment_date = CURDATE()
        AND pla.booking_status = 'booked'
        AND NOT EXISTS (
            SELECT 1 FROM lab_tokens lt2 
            WHERE lt2.booking_id = pla.booking_id 
            AND lt2.scheduled_date = CURDATE()
            AND lt2.token_status != 'cancelled'
        )
    ) AS combined_queue
    ORDER BY 
        CASE WHEN status = 'in_progress' THEN 0 ELSE 1 END,
        scheduled_time ASC,
        created_at ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("sisi", $test_name, $test_id, $test_name, $test_id);
$stmt->execute();
$result = $stmt->get_result();

$queue = [];
$position = 1;
$current_position = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row_token = strtoupper($row['token_number']);
        $is_current = ($row_token === $current_token);
        
        if ($is_current) {
            $current_position = $position;
        }
        
        $queue[] = [
            'position' => $position,
            'token_number' => $row['token_number'],
            'patient_name' => $row['patient_name'],
            'patient_type' => $row['patient_type'],
            'status' => $row['status'],
            'test_name' => $row['test_name'],
            'checkin_status' => $row['checkin_status'],
            'is_you' => $is_current
        ];
        
        $position++;
    }
}

// Calculate people ahead
$people_ahead = ($current_position > 0) ? $current_position - 1 : 0;

echo json_encode([
    'success' => true,
    'queue' => $queue,
    'total_in_queue' => count($queue),
    'current_position' => $current_position,
    'people_ahead' => $people_ahead,
    'test_name' => $test_name
]);

$stmt->close();
$testStmt->close();
$conn->close();
?>