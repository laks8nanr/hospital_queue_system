<?php
// api/verify_lab_booking.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include '../db.php';

// Get patient_id from session if logged in
$patient_id = isset($_SESSION['patient_id']) ? intval($_SESSION['patient_id']) : null;

// Get booking ID from query string
$booking_id = isset($_GET['booking_id']) ? strtoupper(trim($_GET['booking_id'])) : '';

if (empty($booking_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Lab Booking ID is required'
    ]);
    exit;
}

// Query to get lab booking details
$query = "SELECT 
    pla.booking_id,
    pla.booking_code,
    pla.patient_name,
    pla.patient_age,
    pla.patient_gender,
    pla.patient_phone,
    pla.appointment_date,
    pla.appointment_time,
    pla.booking_status,
    pla.payment_status,
    pla.payment_amount,
    pla.token_number,
    lt.test_id,
    lt.test_code,
    lt.test_name,
    lt.duration_minutes
FROM prebooked_lab_appointments pla
JOIN lab_tests lt ON pla.test_id = lt.test_id
WHERE pla.booking_code = ?
AND pla.booking_status IN ('booked', 'checked_in', 'in_progress')
AND pla.appointment_date >= CURDATE()";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Booking not found or already completed. Please check your Lab Booking ID.'
    ]);
    exit;
}

$booking = $result->fetch_assoc();

// CHECK: Only ONE active token allowed at a time per patient (OPD or Lab)
// If logged in, check by patient_id first; otherwise check by phone
if ($patient_id) {
    $activeCheckQuery = "SELECT 'opd' as token_type, token_number, status as token_status FROM tokens 
                         WHERE patient_id = ? AND DATE(created_at) = CURDATE() AND status IN ('waiting', 'consulting')
                         UNION ALL
                         SELECT 'lab' as token_type, token_number, token_status FROM lab_tokens 
                         WHERE patient_id = ? AND scheduled_date = CURDATE() AND token_status IN ('waiting', 'in_progress')
                         LIMIT 1";
    $activeStmt = $conn->prepare($activeCheckQuery);
    $activeStmt->bind_param("ii", $patient_id, $patient_id);
} else {
    $activeCheckQuery = "SELECT 'opd' as token_type, token_number, status as token_status FROM tokens 
                         WHERE patient_phone = ? AND DATE(created_at) = CURDATE() AND status IN ('waiting', 'consulting')
                         UNION ALL
                         SELECT 'lab' as token_type, token_number, token_status FROM lab_tokens 
                         WHERE patient_phone = ? AND scheduled_date = CURDATE() AND token_status IN ('waiting', 'in_progress')
                         LIMIT 1";
    $activeStmt = $conn->prepare($activeCheckQuery);
    $activeStmt->bind_param("ss", $booking['patient_phone'], $booking['patient_phone']);
}
$activeStmt->execute();
$activeResult = $activeStmt->get_result();

if ($activeResult->num_rows > 0) {
    $active = $activeResult->fetch_assoc();
    $tokenType = $active['token_type'] === 'opd' ? 'OPD consultation' : 'Lab test';
    $msg = $patient_id ? 'You already have an active ' . $tokenType . ' token (' . $active['token_number'] . ') from your account. Only one token per account is allowed.'
                       : 'You already have an active ' . $tokenType . ' token (' . $active['token_number'] . '). You can only have one token at a time.';
    echo json_encode([
        'success' => false,
        'message' => $msg . ' Please complete, cancel, or wait for it to expire before checking in.',
        'existing_token' => $active['token_number']
    ]);
    $activeStmt->close();
    exit;
}
$activeStmt->close();

// Calculate queue position and wait time
$queueQuery = "SELECT COUNT(*) as ahead FROM (
    -- Checked-in lab tokens with earlier position
    SELECT 1 FROM lab_tokens lt2
    WHERE lt2.test_id = ?
    AND lt2.scheduled_date = CURDATE()
    AND lt2.token_status IN ('waiting', 'in_progress')
    
    UNION ALL
    
    -- Prebooked appointments with earlier time that aren't checked in yet
    SELECT 1 FROM prebooked_lab_appointments pla2
    WHERE pla2.test_id = ?
    AND pla2.appointment_date = CURDATE()
    AND pla2.booking_status = 'booked'
    AND pla2.appointment_time < ?
    AND pla2.booking_code != ?
) AS ahead_queue";

$queueStmt = $conn->prepare($queueQuery);
$queueStmt->bind_param("iiss", 
    $booking['test_id'],
    $booking['test_id'],
    $booking['appointment_time'],
    $booking_id
);
$queueStmt->execute();
$queueResult = $queueStmt->get_result();
$queueRow = $queueResult->fetch_assoc();
$people_ahead = $queueRow['ahead'];
$wait_time = $people_ahead * $booking['duration_minutes'];

// Calculate expected time
$scheduled_time = $booking['appointment_time'];
$expected_time = date('H:i:s', strtotime($scheduled_time) + ($wait_time * 60));

echo json_encode([
    'success' => true,
    'data' => [
        'booking_id' => $booking['booking_code'],
        'test_id' => $booking['test_id'],
        'test_code' => $booking['test_code'],
        'test_name' => $booking['test_name'],
        'test_type' => $booking['test_code'],
        'patient_name' => $booking['patient_name'],
        'patient_age' => $booking['patient_age'],
        'patient_phone' => $booking['patient_phone'],
        'appointment_date' => $booking['appointment_date'],
        'scheduled_time' => $booking['appointment_time'],
        'expected_time' => $expected_time,
        'booking_status' => $booking['booking_status'],
        'payment_status' => $booking['payment_status'],
        'people_ahead' => $people_ahead,
        'wait_time' => $wait_time,
        'position' => $people_ahead + 1
    ]
]);

$stmt->close();
$queueStmt->close();
$conn->close();
?>