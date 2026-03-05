<?php
// api/book_lab_test.php
// Creates a lab booking and returns booking code (no token generation)
session_start();
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

$test_code = strtoupper(trim($data['test_code'] ?? ''));
$patient_name = trim($data['patient_name'] ?? '');
$patient_age = intval($data['patient_age'] ?? 0);
$patient_gender = strtolower(trim($data['patient_gender'] ?? ''));
$patient_phone = trim($data['patient_phone'] ?? '');
$appointment_date = $data['appointment_date'] ?? null;
$appointment_time = $data['appointment_time'] ?? null;
$payment_amount = floatval($data['payment_amount'] ?? 0);

// Get patient_id from session if logged in
$patient_id = isset($_SESSION['patient_id']) ? intval($_SESSION['patient_id']) : null;

// Validate required fields
if (empty($test_code) || empty($patient_name) || empty($patient_phone) || $patient_age <= 0 || empty($appointment_date) || empty($appointment_time)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit;
}

// Validate gender
if (!in_array($patient_gender, ['male', 'female', 'other'])) {
    $patient_gender = 'other';
}

// Validate appointment date (must be today or future)
$today = date('Y-m-d');
if ($appointment_date < $today) {
    echo json_encode([
        'success' => false,
        'message' => 'Appointment date must be today or a future date'
    ]);
    exit;
}

// Get test details
$testQuery = "SELECT test_id, test_name, fee, slot_duration, floor_block, wing, room_number FROM lab_tests WHERE test_code = ? AND status = 'active'";
$testStmt = $conn->prepare($testQuery);
$testStmt->bind_param("s", $test_code);
$testStmt->execute();
$testResult = $testStmt->get_result();

if ($testResult->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid test code or test not available'
    ]);
    exit;
}

$test = $testResult->fetch_assoc();
$testStmt->close();

// Check for existing booking on same date for same test & phone
$existingQuery = "SELECT booking_code FROM prebooked_lab_appointments 
                  WHERE patient_phone = ? 
                  AND test_id = ? 
                  AND appointment_date = ? 
                  AND booking_status IN ('booked', 'checked_in')";
$existingStmt = $conn->prepare($existingQuery);
$existingStmt->bind_param("sis", $patient_phone, $test['test_id'], $appointment_date);
$existingStmt->execute();
$existingResult = $existingStmt->get_result();

if ($existingResult->num_rows > 0) {
    $existing = $existingResult->fetch_assoc();
    echo json_encode([
        'success' => false,
        'message' => 'You already have a booking for this test on this date. Booking Code: ' . $existing['booking_code']
    ]);
    exit;
}
$existingStmt->close();

// Generate unique booking code
$bookingCodeQuery = "SELECT MAX(booking_id) as max_id FROM prebooked_lab_appointments";
$bookingCodeResult = $conn->query($bookingCodeQuery);
$maxId = $bookingCodeResult->fetch_assoc()['max_id'] ?? 0;
$newId = $maxId + 1;
$booking_code = 'LB' . str_pad($newId, 4, '0', STR_PAD_LEFT);

// Use test fee if payment amount not provided
if ($payment_amount <= 0) {
    $payment_amount = $test['fee'];
}

// Check slot availability
$slotCheckQuery = "SELECT slot_id, booked_count, total_capacity FROM lab_slot_availability 
                   WHERE test_id = ? AND slot_date = ? AND slot_time = ?";
$slotCheckStmt = $conn->prepare($slotCheckQuery);
$slotCheckStmt->bind_param("iss", $test['test_id'], $appointment_date, $appointment_time);
$slotCheckStmt->execute();
$slotResult = $slotCheckStmt->get_result();

$slotAvailable = true;
$slotId = null;

if ($slotResult->num_rows > 0) {
    $slot = $slotResult->fetch_assoc();
    if ($slot['booked_count'] >= $slot['total_capacity']) {
        $slotAvailable = false;
    } else {
        $slotId = $slot['slot_id'];
    }
}
$slotCheckStmt->close();

if (!$slotAvailable) {
    echo json_encode([
        'success' => false,
        'message' => 'Selected time slot is fully booked. Please choose another time.'
    ]);
    exit;
}

// Insert booking
$insertQuery = "INSERT INTO prebooked_lab_appointments 
                (booking_code, test_id, patient_id, patient_name, patient_age, patient_gender, patient_phone, 
                 appointment_date, appointment_time, payment_amount, payment_status, booking_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', 'booked')";
$insertStmt = $conn->prepare($insertQuery);
$insertStmt->bind_param("siisissssd", 
    $booking_code, 
    $test['test_id'], 
    $patient_id, 
    $patient_name, 
    $patient_age, 
    $patient_gender, 
    $patient_phone, 
    $appointment_date, 
    $appointment_time, 
    $payment_amount
);

if ($insertStmt->execute()) {
    // Update slot availability if slot exists
    if ($slotId) {
        $updateSlotQuery = "UPDATE lab_slot_availability SET booked_count = booked_count + 1 WHERE slot_id = ?";
        $updateSlotStmt = $conn->prepare($updateSlotQuery);
        $updateSlotStmt->bind_param("i", $slotId);
        $updateSlotStmt->execute();
        $updateSlotStmt->close();
    }
    
    // Format date and time for display
    $displayDate = date('D, M j, Y', strtotime($appointment_date));
    $displayTime = date('g:i A', strtotime($appointment_time));
    
    echo json_encode([
        'success' => true,
        'message' => 'Lab test booked successfully!',
        'data' => [
            'booking_code' => $booking_code,
            'test_name' => $test['test_name'],
            'test_code' => $test_code,
            'patient_name' => $patient_name,
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time,
            'display_date' => $displayDate,
            'display_time' => $displayTime,
            'amount' => $payment_amount,
            'location' => $test['floor_block'] ? 
                "Floor: {$test['floor_block']}, Wing: {$test['wing']}, Room: {$test['room_number']}" : 
                'Ground Floor, Lab Wing'
        ],
        'instructions' => 'Please arrive 15 minutes before your scheduled time. Present your Booking ID (' . $booking_code . ') at the lab counter to generate your token on the day of appointment.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create booking. Please try again.'
    ]);
}

$insertStmt->close();
$conn->close();
?>
