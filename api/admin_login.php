<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include '../db.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

$admin_id = $conn->real_escape_string($data['admin_id'] ?? '');
$password = $data['password'] ?? '';

if (empty($admin_id) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Admin ID and Password are required']);
    exit;
}

// Query to check admin credentials
$sql = "SELECT id, admin_id, name FROM admin WHERE admin_id = ? AND password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $admin_id, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'data' => [
            'id' => $admin['id'],
            'admin_id' => $admin['admin_id'],
            'name' => $admin['name']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Admin ID or Password']);
}

$stmt->close();
$conn->close();
?>
