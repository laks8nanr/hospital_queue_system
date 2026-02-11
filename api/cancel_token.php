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

$token_number = isset($data['token']) ? strtoupper(trim($data['token'])) : '';
$token_id = isset($data['token_id']) ? intval($data['token_id']) : 0;
$booking_id = isset($data['booking_id']) ? strtoupper(trim($data['booking_id'])) : $token_number;
$cancellation_reason = isset($data['reason']) ? trim($data['reason']) : 'due to being late';
$notify_patient = isset($data['notify']) ? (bool)$data['notify'] : true;

if (empty($token_number) && $token_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Token number or ID is required']);
    exit;
}

// Begin transaction for data consistency
$conn->begin_transaction();

try {
    $today = date('Y-m-d');
    
    // Cancel token in tokens table
    if ($token_id > 0) {
        $sql = "UPDATE tokens SET status = 'cancelled' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $token_id);
    } else {
        // Try both token_number and booking_id columns
        $sql = "UPDATE tokens SET status = 'cancelled' WHERE (token_number = ? OR booking_id = ?) AND DATE(created_at) = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $token_number, $booking_id, $today);
    }
    $stmt->execute();
    $tokens_affected = $stmt->affected_rows;
    $stmt->close();
    
    // Always update prebooked_appointments if booking_id looks like PB format
    if (strpos($booking_id, 'PB') === 0) {
        $updateBooking = "UPDATE prebooked_appointments SET status = 'cancelled' WHERE booking_id = ?";
        $bookingStmt = $conn->prepare($updateBooking);
        $bookingStmt->bind_param("s", $booking_id);
        $bookingStmt->execute();
        $booking_affected = $bookingStmt->affected_rows;
        $bookingStmt->close();
    }
    
    // Also try with token_number if different from booking_id
    if ($token_number !== $booking_id && strpos($token_number, 'PB') === 0) {
        $updateBooking2 = "UPDATE prebooked_appointments SET status = 'cancelled' WHERE booking_id = ?";
        $bookingStmt2 = $conn->prepare($updateBooking2);
        $bookingStmt2->bind_param("s", $token_number);
        $bookingStmt2->execute();
        $bookingStmt2->close();
    }
    
    $conn->commit();
    
    // Log notification for cancelled token
    if ($notify_patient) {
        try {
            // Check if notification_logs table exists
            $tableCheck = $conn->query("SHOW TABLES LIKE 'notification_logs'");
            if ($tableCheck->num_rows > 0) {
                $notifyMsg = "Your token has been cancelled " . $cancellation_reason . ". Please contact reception.";
                $logSql = "INSERT INTO notification_logs (token_number, booking_id, notification_type, message, delivery_status) VALUES (?, ?, 'cancelled', ?, 'pending')";
                $logStmt = $conn->prepare($logSql);
                $logStmt->bind_param("sss", $token_number, $booking_id, $notifyMsg);
                $logStmt->execute();
                $logStmt->close();
            }
        } catch (Exception $logError) {
            // Don't fail cancellation if logging fails
            error_log("Notification logging failed: " . $logError->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Token cancelled successfully',
        'notified' => $notify_patient,
        'reason' => $cancellation_reason
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to cancel token: ' . $e->getMessage()]);
}

$conn->close();
?>
