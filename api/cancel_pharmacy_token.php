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

$token_number = isset($data['token_number']) ? strtoupper(trim($data['token_number'])) : '';
$token_id = isset($data['token_id']) ? intval($data['token_id']) : 0;
$cancellation_reason = isset($data['reason']) ? trim($data['reason']) : 'Cancelled by patient';

if (empty($token_number) && $token_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Token number or ID is required']);
    exit;
}

$today = date('Y-m-d');

// Find the token first
if ($token_id > 0) {
    $find_sql = "SELECT id, token_number, patient_name, status, pharmacy_id FROM pharmacy_tokens WHERE id = ?";
    $find_stmt = $conn->prepare($find_sql);
    $find_stmt->bind_param("i", $token_id);
} else {
    $find_sql = "SELECT id, token_number, patient_name, status, pharmacy_id FROM pharmacy_tokens WHERE token_number = ? AND DATE(created_at) = ?";
    $find_stmt = $conn->prepare($find_sql);
    $find_stmt->bind_param("ss", $token_number, $today);
}

$find_stmt->execute();
$result = $find_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Token not found']);
    exit;
}

$token = $result->fetch_assoc();
$find_stmt->close();

// Check if already cancelled or completed
if ($token['status'] === 'cancelled') {
    echo json_encode(['success' => false, 'message' => 'Token is already cancelled']);
    exit;
}

if ($token['status'] === 'completed') {
    echo json_encode(['success' => false, 'message' => 'Cannot cancel a completed token']);
    exit;
}

// Update the token status to cancelled
$update_sql = "UPDATE pharmacy_tokens SET status = 'cancelled', cancelled_at = NOW(), cancellation_reason = ? WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("si", $cancellation_reason, $token['id']);

if ($update_stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Token cancelled successfully',
        'data' => [
            'token_id' => $token['id'],
            'token_number' => $token['token_number'],
            'patient_name' => $token['patient_name'],
            'previous_status' => $token['status'],
            'new_status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s'),
            'reason' => $cancellation_reason
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel token: ' . $conn->error]);
}

$update_stmt->close();
$conn->close();
?>
