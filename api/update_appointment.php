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
$new_date = isset($data['new_date']) ? trim($data['new_date']) : '';
$new_time = isset($data['new_time']) ? trim($data['new_time']) : '';

if (empty($booking_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Booking ID is required'
    ]);
    exit;
}

if (empty($new_date)) {
    echo json_encode([
        'success' => false,
        'message' => 'New appointment date is required'
    ]);
    exit;
}

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $new_date)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid date format. Use YYYY-MM-DD'
    ]);
    exit;
}

// Validate that the new date is not in the past
$today = date('Y-m-d');
if ($new_date < $today) {
    echo json_encode([
        'success' => false,
        'message' => 'Cannot reschedule to a past date'
    ]);
    exit;
}

// If new_time is provided, validate format (HH:MM:SS)
if (!empty($new_time) && !preg_match('/^\d{2}:\d{2}:\d{2}$/', $new_time)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid time format. Use HH:MM:SS'
    ]);
    exit;
}

try {
    // Get current booking details
    $queryCheck = "SELECT * FROM prebooked_appointments WHERE booking_id = ?";
    $stmtCheck = $conn->prepare($queryCheck);
    $stmtCheck->bind_param("s", $booking_id);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Booking ID not found'
        ]);
        $stmtCheck->close();
        exit;
    }

    $booking = $resultCheck->fetch_assoc();
    $stmtCheck->close();

    // Begin transaction
    $conn->begin_transaction();

    // Update appointment date (and time if provided)
    if (!empty($new_time)) {
        $updateQuery = "UPDATE prebooked_appointments SET appointment_date = ?, appointment_time = ? WHERE booking_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("sss", $new_date, $new_time, $booking_id);
    } else {
        // Keep the existing time if not provided
        $updateQuery = "UPDATE prebooked_appointments SET appointment_date = ? WHERE booking_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ss", $new_date, $booking_id);
    }

    if (!$updateStmt->execute()) {
        throw new Exception('Failed to update appointment: ' . $updateStmt->error);
    }

    $affected = $updateStmt->affected_rows;
    $updateStmt->close();

    // If a token exists with this booking, update its expected_time too
    if (!empty($new_time)) {
        $tokenUpdateQuery = "UPDATE tokens SET expected_time = ? WHERE booking_id = ?";
        $tokenUpdateStmt = $conn->prepare($tokenUpdateQuery);
        $tokenUpdateStmt->bind_param("ss", $new_time, $booking_id);
        $tokenUpdateStmt->execute();
        $tokenUpdateStmt->close();
    }

    $conn->commit();

    if ($affected > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Appointment rescheduled successfully',
            'data' => [
                'booking_id' => $booking_id,
                'old_date' => $booking['appointment_date'],
                'old_time' => $booking['appointment_time'],
                'new_date' => $new_date,
                'new_time' => !empty($new_time) ? $new_time : $booking['appointment_time']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No changes made to the appointment'
        ]);
    }
} catch (Exception $e) {
    if ($conn->connect_errno == 0) {
        $conn->rollback();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
