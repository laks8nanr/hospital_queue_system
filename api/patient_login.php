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

// First, check registered patients table
$stmt = $conn->prepare("SELECT * FROM patients WHERE email = ? OR patient_id = ?");
$stmt->bind_param("ss", $login_input, $login_input);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Found in patients table
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
    $_SESSION['patient_type'] = 'registered';

    // Log the login session
    $ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '';
    $session_stmt = $conn->prepare("INSERT INTO patient_sessions (patient_id, patient_name, patient_type, login_time, ip_address, user_agent, status) VALUES (?, ?, 'registered', NOW(), ?, ?, 'active')");
    $session_stmt->bind_param("ssss", $patient['patient_id'], $patient['name'], $ip_address, $user_agent);
    $session_stmt->execute();
    $session_id = $conn->insert_id;
    $_SESSION['session_record_id'] = $session_id;
    $session_stmt->close();

    // Return patient data
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'data' => [
            'id' => $patient['id'],
            'patient_id' => $patient['patient_id'],
            'name' => $patient['name'],
            'email' => $patient['email'],
            'phone' => isset($patient['phone']) ? $patient['phone'] : '',
            'type' => 'registered'
        ]
    ]);
    $conn->close();
    exit();
}
$stmt->close();

// If not found in patients, check prebooked_appointments table
$stmt = $conn->prepare("SELECT * FROM prebooked_appointments WHERE (email = ? OR patient_id = ?) AND status != 'cancelled'");
$stmt->bind_param("ss", $login_input, $login_input);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Found in prebooked appointments
    $prebooked = $result->fetch_assoc();
    $stmt->close();

    // Check if password column exists and has value
    if (empty($prebooked['password'])) {
        echo json_encode(['success' => false, 'message' => 'Password not set. Please contact reception.']);
        exit();
    }

    // Verify password
    if (!password_verify($password, $prebooked['password'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect password']);
        exit();
    }

    // Set session
    $_SESSION['patient_id'] = $prebooked['id'];
    $_SESSION['patient_name'] = $prebooked['patient_name'];
    $_SESSION['patient_type'] = 'prebooked';
    $_SESSION['booking_id'] = $prebooked['booking_id'];

    // Log the login session
    $ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '';
    $session_stmt = $conn->prepare("INSERT INTO patient_sessions (patient_id, patient_name, patient_type, login_time, ip_address, user_agent, status) VALUES (?, ?, 'prebooked', NOW(), ?, ?, 'active')");
    $session_stmt->bind_param("ssss", $prebooked['patient_id'], $prebooked['patient_name'], $ip_address, $user_agent);
    $session_stmt->execute();
    $session_id = $conn->insert_id;
    $_SESSION['session_record_id'] = $session_id;
    $session_stmt->close();

    // Return patient data
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'data' => [
            'id' => $prebooked['id'],
            'patient_id' => $prebooked['patient_id'],
            'booking_id' => $prebooked['booking_id'],
            'name' => $prebooked['patient_name'],
            'email' => isset($prebooked['email']) ? $prebooked['email'] : '',
            'phone' => $prebooked['patient_phone'],
            'appointment_date' => $prebooked['appointment_date'],
            'appointment_time' => $prebooked['appointment_time'],
            'status' => $prebooked['status'],
            'type' => 'prebooked'
        ]
    ]);
    $conn->close();
    exit();
}
$stmt->close();

// Not found in either table
echo json_encode(['success' => false, 'message' => 'Patient ID or Email not found']);
$conn->close();
?>
