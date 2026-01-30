<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include '../db.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

$token_id = intval($data['token_id'] ?? 0);
$status = $conn->real_escape_string($data['status'] ?? '');

if ($token_id == 0 || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Token ID and status are required']);
    exit;
}

// Valid statuses
$valid_statuses = ['waiting', 'consulting', 'completed'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// If setting to consulting, first set any current consulting to completed
if ($status == 'consulting') {
    $today = date('Y-m-d');
    
    // Get the department of the token being updated
    $dept_sql = "SELECT department_id FROM tokens WHERE id = ?";
    $dept_stmt = $conn->prepare($dept_sql);
    $dept_stmt->bind_param("i", $token_id);
    $dept_stmt->execute();
    $dept_result = $dept_stmt->get_result();
    
    if ($dept_result->num_rows > 0) {
        $dept_row = $dept_result->fetch_assoc();
        $department_id = $dept_row['department_id'];
        
        // Set current consulting tokens in same department to completed
        $reset_sql = "UPDATE tokens SET status = 'completed' WHERE DATE(created_at) = ? AND department_id = ? AND status = 'consulting'";
        $reset_stmt = $conn->prepare($reset_sql);
        $reset_stmt->bind_param("si", $today, $department_id);
        $reset_stmt->execute();
        $reset_stmt->close();
    }
    $dept_stmt->close();
}

// Update token status
$sql = "UPDATE tokens SET status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $status, $token_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Token status updated successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update token status']);
}

$stmt->close();
$conn->close();
?>
