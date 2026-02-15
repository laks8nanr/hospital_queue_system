<?php
// api/check_active_token.php
// Check if patient has an active token before allowing payment

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
require_once '../db.php';

$patient_phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';
$patient_id = isset($_SESSION['patient_id']) ? intval($_SESSION['patient_id']) : null;

// If logged in, check by patient_id; otherwise check by phone
if ($patient_id) {
    // Check by patient_id (account-based check)
    $activeCheckQuery = "SELECT 'opd' as token_type, token_number, status as token_status, 
                                (SELECT name FROM doctors WHERE id = tokens.doctor_id) as location_name
                         FROM tokens 
                         WHERE patient_id = ? AND DATE(created_at) = CURDATE() AND status IN ('waiting', 'consulting')
                         UNION ALL
                         SELECT 'lab' as token_type, token_number, token_status,
                                (SELECT test_name FROM lab_tests WHERE test_id = lab_tokens.test_id) as location_name
                         FROM lab_tokens 
                         WHERE patient_id = ? AND scheduled_date = CURDATE() AND token_status IN ('waiting', 'in_progress')
                         LIMIT 1";
    $stmt = $conn->prepare($activeCheckQuery);
    $stmt->bind_param("ii", $patient_id, $patient_id);
} else if (!empty($patient_phone)) {
    // Fallback to phone-based check for walk-ins
    $activeCheckQuery = "SELECT 'opd' as token_type, token_number, status as token_status, 
                                (SELECT name FROM doctors WHERE id = tokens.doctor_id) as location_name
                         FROM tokens 
                         WHERE patient_phone = ? AND DATE(created_at) = CURDATE() AND status IN ('waiting', 'consulting')
                         UNION ALL
                         SELECT 'lab' as token_type, token_number, token_status,
                                (SELECT test_name FROM lab_tests WHERE test_id = lab_tokens.test_id) as location_name
                         FROM lab_tokens 
                         WHERE patient_phone = ? AND scheduled_date = CURDATE() AND token_status IN ('waiting', 'in_progress')
                         LIMIT 1";
    $stmt = $conn->prepare($activeCheckQuery);
    $stmt->bind_param("ss", $patient_phone, $patient_phone);
} else {
    echo json_encode(['success' => false, 'message' => 'Phone number or login required']);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $activeToken = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    $location = $activeToken['token_type'] === 'opd' ? 'OPD' : 'Lab';
    $locationName = $activeToken['location_name'] ?? $location;
    
    echo json_encode([
        'success' => false,
        'has_active_token' => true,
        'token_number' => $activeToken['token_number'],
        'token_type' => $activeToken['token_type'],
        'token_status' => $activeToken['token_status'],
        'location' => $location,
        'location_name' => $locationName,
        'message' => 'You already have an active token (' . $activeToken['token_number'] . ') for ' . $locationName . '. Status: ' . ucfirst($activeToken['token_status']) . '. Please complete or cancel it before booking a new one.'
    ]);
    exit;
}

$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'has_active_token' => false,
    'message' => 'No active token found. You can proceed.'
]);
?>
