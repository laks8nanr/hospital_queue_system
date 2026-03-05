<?php
// api/checkin_lab_booking.php
// Checks in a pre-booked lab appointment and generates a lab token
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include '../db.php';

// Get patient_id from session if logged in
$patient_id = isset($_SESSION['patient_id']) ? intval($_SESSION['patient_id']) : null;

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$booking_code = isset($data['booking_code']) ? strtoupper(trim($data['booking_code'])) : '';

if (empty($booking_code)) {
    echo json_encode([
        'success' => false,
        'message' => 'Booking code is required'
    ]);
    exit;
}

// Verify booking exists and is valid
$checkQuery = "SELECT pb.*, lt.test_name, lt.test_code, lt.fee, lt.slot_duration,
               lt.floor_block, lt.wing, lt.room_number
               FROM prebooked_lab_appointments pb
               JOIN lab_tests lt ON pb.test_id = lt.test_id
               WHERE pb.booking_code = ?
               AND pb.booking_status = 'booked'";

$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("s", $booking_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Check if already checked in or completed
    $usedQuery = "SELECT booking_code, booking_status, token_number FROM prebooked_lab_appointments WHERE booking_code = ?";
    $usedStmt = $conn->prepare($usedQuery);
    $usedStmt->bind_param("s", $booking_code);
    $usedStmt->execute();
    $usedResult = $usedStmt->get_result();
    
    if ($usedResult->num_rows > 0) {
        $usedBooking = $usedResult->fetch_assoc();
        if ($usedBooking['booking_status'] === 'checked_in' || $usedBooking['booking_status'] === 'in_progress') {
            echo json_encode([
                'success' => false,
                'message' => 'This booking has already been checked in. Token: ' . $usedBooking['token_number']
            ]);
        } elseif ($usedBooking['booking_status'] === 'completed') {
            echo json_encode([
                'success' => false,
                'message' => 'This booking has already been completed.'
            ]);
        } elseif ($usedBooking['booking_status'] === 'cancelled') {
            echo json_encode([
                'success' => false,
                'message' => 'This booking was cancelled.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid booking status.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid booking code. Please check and try again.'
        ]);
    }
    exit;
}

$booking = $result->fetch_assoc();
$stmt->close();

// Check if appointment date is today
$today = date('Y-m-d');
if ($booking['appointment_date'] !== $today) {
    $appointmentDate = date('D, M j, Y', strtotime($booking['appointment_date']));
    if ($booking['appointment_date'] > $today) {
        echo json_encode([
            'success' => false,
            'message' => 'Your appointment is scheduled for ' . $appointmentDate . '. Please check in on that day.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Your appointment date (' . $appointmentDate . ') has passed. Please book a new appointment.'
        ]);
    }
    exit;
}

// CHECK: Only ONE active token allowed at a time per patient (OPD or Lab)
$patient_phone = $booking['patient_phone'];

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
    $activeStmt->bind_param("ss", $patient_phone, $patient_phone);
}
$activeStmt->execute();
$activeResult = $activeStmt->get_result();

if ($activeResult->num_rows > 0) {
    $active = $activeResult->fetch_assoc();
    $tokenType = $active['token_type'] === 'opd' ? 'OPD consultation' : 'Lab test';
    echo json_encode([
        'success' => false,
        'message' => 'You already have an active ' . $tokenType . ' token (' . $active['token_number'] . '). Please complete or cancel it before checking in.',
        'existing_token' => $active['token_number']
    ]);
    $activeStmt->close();
    exit;
}
$activeStmt->close();

// Generate lab token number
$tokenQuery = "SELECT COUNT(*) as count FROM lab_tokens WHERE test_id = ? AND scheduled_date = CURDATE()";
$tokenStmt = $conn->prepare($tokenQuery);
$tokenStmt->bind_param("i", $booking['test_id']);
$tokenStmt->execute();
$tokenResult = $tokenStmt->get_result();
$tokenCount = $tokenResult->fetch_assoc()['count'];
$tokenNumber = $booking['test_code'] . '-' . str_pad($tokenCount + 1, 3, '0', STR_PAD_LEFT);
$tokenStmt->close();

// Calculate queue position
$queueQuery = "SELECT COUNT(*) as ahead FROM lab_tokens 
               WHERE test_id = ? 
               AND scheduled_date = CURDATE() 
               AND token_status IN ('waiting', 'in_progress')";
$queueStmt = $conn->prepare($queueQuery);
$queueStmt->bind_param("i", $booking['test_id']);
$queueStmt->execute();
$queueResult = $queueStmt->get_result();
$queuePosition = $queueResult->fetch_assoc()['ahead'] + 1;
$queueStmt->close();

// Estimated wait time (slot_duration per patient)
$waitTime = ($queuePosition - 1) * $booking['slot_duration'];

// Insert lab token
$insertQuery = "INSERT INTO lab_tokens 
                (token_number, test_id, patient_name, patient_age, patient_gender, patient_phone, patient_id,
                 patient_type, booking_id, scheduled_date, scheduled_time, payment_amount, token_status, queue_position) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'prebooked', ?, CURDATE(), ?, ?, 'waiting', ?)";

$insertStmt = $conn->prepare($insertQuery);
$insertStmt->bind_param("sisisssiisdi", 
    $tokenNumber, 
    $booking['test_id'],
    $booking['patient_name'],
    $booking['patient_age'],
    $booking['patient_gender'],
    $booking['patient_phone'],
    $patient_id,
    $booking['booking_id'],
    $booking['appointment_time'],
    $booking['payment_amount'],
    $queuePosition
);

if ($insertStmt->execute()) {
    $token_id = $conn->insert_id;
    
    // Update booking status to checked_in
    $updateBooking = "UPDATE prebooked_lab_appointments 
                      SET booking_status = 'checked_in', 
                          token_number = ?, 
                          check_in_time = NOW() 
                      WHERE booking_id = ?";
    $updateStmt = $conn->prepare($updateBooking);
    $updateStmt->bind_param("si", $tokenNumber, $booking['booking_id']);
    $updateStmt->execute();
    $updateStmt->close();
    
    // Format times for display
    $scheduledTime = date('g:i A', strtotime($booking['appointment_time']));
    
    // Build location string
    $location = '';
    if (!empty($booking['floor_block'])) {
        $location = "Floor: {$booking['floor_block']}, Wing: {$booking['wing']}, Room: {$booking['room_number']}";
    } else {
        $location = 'Ground Floor, Lab Wing';
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Check-in successful! Your token has been generated.',
        'data' => [
            'token_number' => $tokenNumber,
            'test_name' => $booking['test_name'],
            'test_code' => $booking['test_code'],
            'patient_name' => $booking['patient_name'],
            'scheduled_time' => $scheduledTime,
            'queue_position' => $queuePosition,
            'estimated_wait' => $waitTime . ' mins',
            'location' => $location
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to generate token. Please try again.'
    ]);
}

$insertStmt->close();
$conn->close();
?>
