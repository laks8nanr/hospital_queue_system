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

// Begin transaction
$conn->begin_transaction();

try {
    // Update booking status to cancelled in prebooked_appointments
    $query = "UPDATE prebooked_appointments SET status = 'cancelled' WHERE booking_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $booking_id);
    $stmt->execute();
    $booking_affected = $stmt->affected_rows;
    $stmt->close();
    
    // Also cancel any token with this booking_id (in case they already checked in)
    $tokenQuery = "UPDATE tokens SET status = 'cancelled' WHERE booking_id = ? OR token_number = ?";
    $tokenStmt = $conn->prepare($tokenQuery);
    $tokenStmt->bind_param("ss", $booking_id, $booking_id);
    $tokenStmt->execute();
    $tokenStmt->close();
    
    $conn->commit();
    
    if ($booking_affected > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Booking cancelled successfully'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Booking cancelled'
        ]);
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Failed to cancel booking: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
