<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include '../db.php';

$data = json_decode(file_get_contents('php://input'), true);

$account_type = $data['account_type'] ?? '';
$email_phone = $conn->real_escape_string($data['email_phone'] ?? '');
$new_password = $data['new_password'] ?? '';

if (empty($account_type) || empty($email_phone) || empty($new_password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

// Hash password (in production, use password_hash)
$hashed_password = $new_password; // For demo, storing plain text like current system

if ($account_type === 'patient') {
    // Update patient password
    $sql = "UPDATE patients SET password = ? WHERE email = ? OR phone = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $hashed_password, $email_phone, $email_phone);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Account not found with this email/phone']);
    }
    $stmt->close();
} else if ($account_type === 'staff') {
    // Update staff password
    $sql = "UPDATE staff SET password = ? WHERE email = ? OR id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $hashed_password, $email_phone, $email_phone);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Account not found with this email/ID']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid account type']);
}

$conn->close();
?>
