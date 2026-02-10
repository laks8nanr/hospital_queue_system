<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include '../db.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$booking_id = isset($data['booking_id']) ? strtoupper(trim($data['booking_id'])) : '';

if (empty($booking_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Booking ID is required'
    ]);
    exit;
}

// Verify booking exists and is valid
$checkQuery = "SELECT pb.*, d.name as doctor_name, d.time_slot as doctor_time_slot
               FROM prebooked_appointments pb
               JOIN doctors d ON pb.doctor_id = d.id
               WHERE pb.booking_id = ?
               AND pb.status IN ('booked', 'confirmed')
               AND pb.appointment_date >= CURDATE()";

$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("s", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid or expired booking'
    ]);
    exit;
}

$booking = $result->fetch_assoc();
$stmt->close();

// Use booking_id as token number (e.g., PB001)
$token_number = $booking_id;

// Use the original appointment_time for queue ordering (this is the booked time slot)
$appointment_time = $booking['appointment_time'];

// Calculate patients ahead based on appointment time (prebooked with earlier time slots)
$aheadQuery = "SELECT COUNT(*) as count FROM (
    SELECT 1 FROM tokens t
    WHERE t.doctor_id = ? 
    AND DATE(t.created_at) = CURDATE() 
    AND t.status IN ('waiting', 'consulting')
    AND (t.token_type = 'prebooked' OR t.token_number LIKE 'PB%')
    AND COALESCE(t.expected_time, TIME(t.created_at)) < ?
    
    UNION ALL
    
    SELECT 1 FROM prebooked_appointments pb
    WHERE pb.doctor_id = ?
    AND pb.appointment_date = CURDATE()
    AND pb.status = 'booked'
    AND pb.appointment_time < ?
    AND pb.booking_id != ?
) AS ahead";

$aheadStmt = $conn->prepare($aheadQuery);
$aheadStmt->bind_param("isiss", 
    $booking['doctor_id'], 
    $appointment_time,
    $booking['doctor_id'],
    $appointment_time,
    $booking_id
);
$aheadStmt->execute();
$aheadResult = $aheadStmt->get_result();
$aheadRow = $aheadResult->fetch_assoc();
$patients_ahead = $aheadRow['count'];
$wait_time = $patients_ahead * 10; // 10 minutes per patient
$aheadStmt->close();

// Use the booked appointment_time as expected_time (preserves time slot order)
$expected_time = $appointment_time;

// Insert token into tokens table
$insertQuery = "INSERT INTO tokens (doctor_id, department_id, patient_name, patient_age, patient_phone, 
                token_number, token_type, status, expected_time, booking_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'prebooked', 'waiting', ?, ?, NOW())";

$insertStmt = $conn->prepare($insertQuery);
$insertStmt->bind_param("iisiisss", 
    $booking['doctor_id'],
    $booking['department_id'],
    $booking['patient_name'],
    $booking['patient_age'],
    $booking['patient_phone'],
    $token_number,
    $expected_time,
    $booking_id
);

if (!$insertStmt->execute()) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to generate token: ' . $conn->error
    ]);
    exit;
}

$token_id = $conn->insert_id;
$insertStmt->close();

// Update booking status to 'checked_in'
$updateQuery = "UPDATE prebooked_appointments SET status = 'checked_in' WHERE booking_id = ?";
$updateStmt = $conn->prepare($updateQuery);
$updateStmt->bind_param("s", $booking_id);
$updateStmt->execute();
$updateStmt->close();

echo json_encode([
    'success' => true,
    'message' => 'Check-in successful',
    'data' => [
        'token_id' => $token_id,
        'token_number' => $token_number,
        'expected_time' => $expected_time,
        'patients_ahead' => $patients_ahead,
        'wait_time' => $wait_time,
        'patient_name' => $booking['patient_name'],
        'doctor_name' => $booking['doctor_name']
    ]
]);

$conn->close();
?>
