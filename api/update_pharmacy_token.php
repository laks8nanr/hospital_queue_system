<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include '../db.php';

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

$token_id = isset($data['token_id']) ? intval($data['token_id']) : 0;
$status = isset($data['status']) ? trim($data['status']) : '';

// Validate
if ($token_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid token ID']);
    exit();
}

$valid_statuses = ['waiting', 'processing', 'completed', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status. Must be: ' . implode(', ', $valid_statuses)]);
    exit();
}

// Check if token exists
$check_sql = "SELECT pt.*, p.name as pharmacy_name 
              FROM pharmacy_tokens pt 
              JOIN pharmacies p ON pt.pharmacy_id = p.id 
              WHERE pt.id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $token_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Token not found']);
    exit();
}

$token = $result->fetch_assoc();
$check_stmt->close();

// Update token status
$update_sql = "UPDATE pharmacy_tokens SET status = ?, updated_at = NOW() WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("si", $status, $token_id);

if ($update_stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Token status updated to ' . ucfirst($status),
        'data' => [
            'token_id' => $token_id,
            'token_number' => $token['token_number'],
            'patient_name' => $token['patient_name'],
            'old_status' => $token['status'],
            'new_status' => $status,
            'pharmacy' => $token['pharmacy_name']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update token: ' . $conn->error]);
}

$update_stmt->close();
$conn->close();
?>
