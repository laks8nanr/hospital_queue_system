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

$staff_id = isset($data['staff_id']) ? trim($data['staff_id']) : '';
$password = isset($data['password']) ? trim($data['password']) : '';

// Validate
if (empty($staff_id) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Staff ID and password are required']);
    exit();
}

// Check credentials
$sql = "SELECT ps.*, p.name as pharmacy_name, p.block, p.floor, p.wing 
        FROM pharmacy_staff ps
        JOIN pharmacies p ON ps.pharmacy_id = p.id
        WHERE ps.staff_id = ? AND ps.password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $staff_id, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid staff ID or password']);
    exit();
}

$staff = $result->fetch_assoc();
$stmt->close();

echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'data' => [
        'staff_id' => $staff['staff_id'],
        'name' => $staff['name'],
        'pharmacy_id' => intval($staff['pharmacy_id']),
        'pharmacy' => [
            'name' => $staff['pharmacy_name'],
            'block' => $staff['block'],
            'floor' => $staff['floor'],
            'wing' => $staff['wing'],
            'location' => "Block: {$staff['block']}, Floor: {$staff['floor']}, Wing: {$staff['wing']}"
        ]
    ]
]);

$conn->close();
?>
