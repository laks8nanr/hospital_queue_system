<?php
/**
 * Check Token Status API
 * Returns the current status of a token - used for polling from patient's device
 * to detect cancellations even when page is open
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

include '../db.php';

$token_number = isset($_GET['token']) ? strtoupper(trim($_GET['token'])) : '';
$booking_id = isset($_GET['booking_id']) ? strtoupper(trim($_GET['booking_id'])) : '';

if (empty($token_number) && empty($booking_id)) {
    echo json_encode(['success' => false, 'message' => 'Token or booking ID is required']);
    exit;
}

try {
    $today = date('Y-m-d');
    $status = null;
    $reason = null;
    $tokenData = null;
    
    // First check tokens table
    if (!empty($token_number)) {
        $sql = "SELECT t.*, d.name as doctor_name, dept.name as department_name,
                d.floor_block, d.wing, d.room_number
                FROM tokens t 
                LEFT JOIN doctors d ON t.doctor_id = d.id 
                LEFT JOIN departments dept ON t.department_id = dept.id 
                WHERE (t.token_number = ? OR t.booking_id = ?) AND DATE(t.created_at) = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $token_number, $token_number, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $tokenData = $result->fetch_assoc();
            $status = $tokenData['status'];
            
            // Determine cancellation reason
            if ($status === 'cancelled') {
                $reason = 'due to being late';
            }
        }
        $stmt->close();
    }
    
    // Check prebooked_appointments if booking_id provided or token not found
    if (empty($status) && !empty($booking_id)) {
        $bookingSql = "SELECT pa.*, d.name as doctor_name, dept.name as department_name 
                       FROM prebooked_appointments pa 
                       LEFT JOIN doctors d ON pa.doctor_id = d.id 
                       LEFT JOIN departments dept ON pa.department_id = dept.id 
                       WHERE pa.booking_id = ?";
        $bookingStmt = $conn->prepare($bookingSql);
        $bookingStmt->bind_param("s", $booking_id);
        $bookingStmt->execute();
        $bookingResult = $bookingStmt->get_result();
        
        if ($bookingResult->num_rows > 0) {
            $bookingData = $bookingResult->fetch_assoc();
            $status = $bookingData['status'];
            
            if ($status === 'cancelled') {
                $reason = 'due to being late';
            }
            
            if (!$tokenData) {
                $tokenData = $bookingData;
            }
        }
        $bookingStmt->close();
    }
    
    // Also check using booking_id in tokens table
    if (empty($status) && !empty($booking_id)) {
        $tokenBySql = "SELECT t.*, d.name as doctor_name, dept.name as department_name 
                       FROM tokens t 
                       LEFT JOIN doctors d ON t.doctor_id = d.id 
                       LEFT JOIN departments dept ON t.department_id = dept.id 
                       WHERE t.booking_id = ? AND DATE(t.created_at) = ?";
        $tokenByStmt = $conn->prepare($tokenBySql);
        $tokenByStmt->bind_param("ss", $booking_id, $today);
        $tokenByStmt->execute();
        $tokenByResult = $tokenByStmt->get_result();
        
        if ($tokenByResult->num_rows > 0) {
            $tokenData = $tokenByResult->fetch_assoc();
            $status = $tokenData['status'];
            
            if ($status === 'cancelled') {
                $reason = 'due to being late';
            }
        }
        $tokenByStmt->close();
    }
    
    if ($status) {
        // Calculate patients ahead and current consulting token
        $patients_ahead = 0;
        $current_token = 'None';
        $expected_time = null;
        
        if ($tokenData && isset($tokenData['doctor_id']) && ($status === 'waiting' || $status === 'consulting')) {
            $doctor_id = $tokenData['doctor_id'];
            $my_token_id = $tokenData['id'];
            
            // Get current consulting token for this doctor
            $currentSql = "SELECT token_number FROM tokens WHERE doctor_id = ? AND status = 'consulting' AND DATE(created_at) = ? LIMIT 1";
            $currentStmt = $conn->prepare($currentSql);
            $currentStmt->bind_param("is", $doctor_id, $today);
            $currentStmt->execute();
            $currentResult = $currentStmt->get_result();
            if ($currentResult->num_rows > 0) {
                $currentRow = $currentResult->fetch_assoc();
                $current_token = $currentRow['token_number'];
            }
            $currentStmt->close();
            
            // Count patients ahead (waiting tokens with lower id than mine for the same doctor)
            $aheadSql = "SELECT COUNT(*) as ahead FROM tokens WHERE doctor_id = ? AND status = 'waiting' AND id < ? AND DATE(created_at) = ?";
            $aheadStmt = $conn->prepare($aheadSql);
            $aheadStmt->bind_param("iis", $doctor_id, $my_token_id, $today);
            $aheadStmt->execute();
            $aheadResult = $aheadStmt->get_result();
            if ($aheadResult->num_rows > 0) {
                $aheadRow = $aheadResult->fetch_assoc();
                $patients_ahead = $aheadRow['ahead'];
            }
            $aheadStmt->close();
            
            // Use stored expected_time if available, otherwise calculate
            if (isset($tokenData['expected_time']) && $tokenData['expected_time']) {
                $expected_time = date('h:i A', strtotime($tokenData['expected_time']));
            } else {
                // Estimate expected time (approx 10 min per patient)
                $minutes_to_wait = $patients_ahead * 10;
                $expected_time = date('h:i A', strtotime("+{$minutes_to_wait} minutes"));
            }
        }
        
        $response = [
            'success' => true,
            'status' => $status,
            'reason' => $reason,
            'token_number' => $tokenData ? $tokenData['token_number'] : $token_number,
            'booking_id' => $tokenData && isset($tokenData['booking_id']) ? $tokenData['booking_id'] : $booking_id,
            'doctor_id' => $tokenData && isset($tokenData['doctor_id']) ? $tokenData['doctor_id'] : null,
            'doctor_name' => $tokenData && isset($tokenData['doctor_name']) ? $tokenData['doctor_name'] : null,
            'department_name' => $tokenData && isset($tokenData['department_name']) ? $tokenData['department_name'] : null,
            'patients_ahead' => $patients_ahead,
            'current_token' => $current_token,
            'expected_time' => $expected_time,
            'location' => [
                'floor_block' => $tokenData && isset($tokenData['floor_block']) ? $tokenData['floor_block'] : '',
                'wing' => $tokenData && isset($tokenData['wing']) ? $tokenData['wing'] : '',
                'room_number' => $tokenData && isset($tokenData['room_number']) ? $tokenData['room_number'] : ''
            ]
        ];
        
        echo json_encode($response);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Token not found',
            'status' => 'not_found'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
