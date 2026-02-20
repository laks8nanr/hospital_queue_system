<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
include '../db.php';

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

$login_input = isset($data['login_input']) ? trim($data['login_input']) : '';
$password = isset($data['password']) ? $data['password'] : '';

// Validate
if (empty($login_input) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email/Patient ID and password are required']);
    exit();
}

// Check credentials
$stmt = $conn->prepare("SELECT * FROM patients WHERE email = ? OR patient_id = ?");
$stmt->bind_param("ss", $login_input, $login_input);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Patient ID or Email not found']);
    exit();
}

$patient = $result->fetch_assoc();
$stmt->close();

// Verify password
if (!password_verify($password, $patient['password'])) {
    echo json_encode(['success' => false, 'message' => 'Incorrect password']);
    exit();
}

// Set session
$_SESSION['patient_id'] = $patient['id'];
$_SESSION['patient_name'] = $patient['name'];

// Return patient data
echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'data' => [
        'id' => $patient['id'],
        'patient_id' => $patient['patient_id'],
        'name' => $patient['name'],
        'email' => $patient['email'],
        'phone' => isset($patient['phone']) ? $patient['phone'] : ''
    ]
]);

$conn->close();
?>
