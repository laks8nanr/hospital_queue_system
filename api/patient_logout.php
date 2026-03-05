<?php
/**
 * Patient Logout API
 * Updates the session record with logout time and calculates session duration
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
include '../db.php';

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = ['success' => false, 'message' => 'Not logged in'];

// Check if there's a session record to update
if (isset($_SESSION['session_record_id'])) {
    $session_record_id = $_SESSION['session_record_id'];
    
    // Update the session record with logout time and calculate duration
    $stmt = $conn->prepare("UPDATE patient_sessions SET 
        logout_time = NOW(), 
        session_duration_minutes = TIMESTAMPDIFF(MINUTE, login_time, NOW()),
        status = 'logged_out' 
        WHERE id = ?");
    $stmt->bind_param("i", $session_record_id);
    
    if ($stmt->execute()) {
        $response = ['success' => true, 'message' => 'Logout recorded successfully'];
    } else {
        $response = ['success' => false, 'message' => 'Failed to record logout'];
    }
    $stmt->close();
}

// Also try to update by patient_id if session_record_id not available
if (!isset($_SESSION['session_record_id']) && isset($_SESSION['patient_id'])) {
    // Mark any active sessions for this patient as logged out
    $patient_type = isset($_SESSION['patient_type']) ? $_SESSION['patient_type'] : 'registered';
    
    // Get patient_id string (not the numeric id)
    $data = json_decode(file_get_contents('php://input'), true);
    $patient_id_str = isset($data['patient_id']) ? $data['patient_id'] : '';
    
    if (!empty($patient_id_str)) {
        $stmt = $conn->prepare("UPDATE patient_sessions SET 
            logout_time = NOW(), 
            session_duration_minutes = TIMESTAMPDIFF(MINUTE, login_time, NOW()),
            status = 'logged_out' 
            WHERE patient_id = ? AND status = 'active'");
        $stmt->bind_param("s", $patient_id_str);
        $stmt->execute();
        $stmt->close();
        $response = ['success' => true, 'message' => 'Logout recorded'];
    }
}

// Destroy the PHP session
session_destroy();

$conn->close();
echo json_encode($response);
?>
