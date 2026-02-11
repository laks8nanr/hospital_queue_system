<?php
// api/cancel_lab_booking.php
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

// Update prebooked_lab_appointments status to cancelled
$updateQuery = "UPDATE prebooked_lab_appointments 
                SET booking_status = 'cancelled', 
                    updated_at = NOW() 
                WHERE booking_code = ? 
                AND booking_status IN ('booked', 'checked_in')";

$stmt = $conn->prepare($updateQuery);
$stmt->bind_param("s", $booking_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        // Also cancel any associated lab token
        $tokenQuery = "UPDATE lab_tokens 
                       SET token_status = 'cancelled', 
                           updated_at = NOW() 
                       WHERE token_number = ? 
                       AND token_status IN ('waiting', 'in_progress')";
        $tokenStmt = $conn->prepare($tokenQuery);
        $tokenStmt->bind_param("s", $booking_id);
        $tokenStmt->execute();
        $tokenStmt->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Lab booking cancelled successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Booking not found or already cancelled'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to cancel booking'
    ]);
}

$stmt->close();
$conn->close();
?>