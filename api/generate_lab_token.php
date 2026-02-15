<?php
// api/generate_lab_token.php
session_start();
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

$test_code = strtoupper(trim($data['test_code'] ?? ''));
$patient_name = trim($data['patient_name'] ?? '');
$patient_age = intval($data['patient_age'] ?? 0);
$patient_gender = strtolower(trim($data['patient_gender'] ?? ''));
$patient_phone = trim($data['patient_phone'] ?? '');
$scheduled_time = $data['scheduled_time'] ?? null;
$payment_amount = floatval($data['payment_amount'] ?? 0);

// Get patient_id from session if logged in
$patient_id = isset($_SESSION['patient_id']) ? intval($_SESSION['patient_id']) : null;

// Validate required fields
if (empty($test_code) || empty($patient_name) || empty($patient_phone) || $patient_age <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: test_code, patient_name, patient_age, patient_phone'
    ]);
    exit;
}

// Validate gender
if (!in_array($patient_gender, ['male', 'female', 'other'])) {
    $patient_gender = 'other';
}

// Get test details
$testQuery = "SELECT test_id, test_name, fee, slot_duration FROM lab_tests WHERE test_code = ? AND status = 'active'";
$testStmt = $conn->prepare($testQuery);
$testStmt->bind_param("s", $test_code);
$testStmt->execute();
$testResult = $testStmt->get_result();

if ($testResult->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid test code or test not available'
    ]);
    exit;
}

$test = $testResult->fetch_assoc();
$testStmt->close();

// CHECK: Only ONE active token allowed at a time per patient (OPD or Lab)
// If logged in, check by patient_id first; otherwise check by phone
if ($patient_id) {
    $activeCheckQuery = "SELECT 'opd' as token_type, token_number, status as token_status FROM tokens 
                         WHERE patient_id = ? AND DATE(created_at) = CURDATE() AND status IN ('waiting', 'consulting')
                         UNION ALL
                         SELECT 'lab' as token_type, token_number, token_status FROM lab_tokens 
                         WHERE patient_id = ? AND scheduled_date = CURDATE() AND token_status IN ('waiting', 'in_progress')
                         LIMIT 1";
    $activeStmt = $conn->prepare($activeCheckQuery);
    $activeStmt->bind_param("ii", $patient_id, $patient_id);
    $activeStmt->execute();
    $activeResult = $activeStmt->get_result();

    if ($activeResult->num_rows > 0) {
        $activeToken = $activeResult->fetch_assoc();
        $activeStmt->close();
        $location = $activeToken['token_type'] === 'opd' ? 'OPD' : 'Lab';
        echo json_encode([
            'success' => false,
            'message' => 'You already have an active token (' . $activeToken['token_number'] . ') from your account in ' . $location . '. Only one token per account is allowed. Please complete, cancel, or wait for it to expire.',
            'existing_token' => $activeToken['token_number']
        ]);
        exit;
    }
    $activeStmt->close();
} else {
    $activeCheckQuery = "SELECT 'opd' as token_type, token_number, status as token_status FROM tokens 
                         WHERE patient_phone = ? AND DATE(created_at) = CURDATE() AND status IN ('waiting', 'consulting')
                         UNION ALL
                         SELECT 'lab' as token_type, token_number, token_status FROM lab_tokens 
                         WHERE patient_phone = ? AND scheduled_date = CURDATE() AND token_status IN ('waiting', 'in_progress')
                         LIMIT 1";
    $activeStmt = $conn->prepare($activeCheckQuery);
    $activeStmt->bind_param("ss", $patient_phone, $patient_phone);
    $activeStmt->execute();
    $activeResult = $activeStmt->get_result();

    if ($activeResult->num_rows > 0) {
        $activeToken = $activeResult->fetch_assoc();
        $activeStmt->close();
        $location = $activeToken['token_type'] === 'opd' ? 'OPD' : 'Lab';
        echo json_encode([
            'success' => false,
            'message' => 'You already have an active token (' . $activeToken['token_number'] . ') in ' . $location . '. Status: ' . ucfirst($activeToken['token_status']) . '. Please complete, cancel, or wait for it to expire.',
            'existing_token' => $activeToken['token_number']
        ]);
        exit;
    }
    $activeStmt->close();
}

// Generate token number
$today = date('Y-m-d');
$prefix = $test_code;

$countQuery = "SELECT COUNT(*) as count FROM lab_tokens WHERE scheduled_date = ? AND test_id = ?";
$countStmt = $conn->prepare($countQuery);
$countStmt->bind_param("si", $today, $test['test_id']);
$countStmt->execute();
$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();
$tokenCount = $countRow['count'] + 1;
$countStmt->close();

$token_number = $prefix . '-' . str_pad($tokenCount, 3, '0', STR_PAD_LEFT);

// Calculate queue position
$queueQuery = "SELECT COUNT(*) as ahead FROM lab_tokens 
               WHERE test_id = ? 
               AND scheduled_date = CURDATE() 
               AND token_status IN ('waiting', 'in_progress')";
$queueStmt = $conn->prepare($queueQuery);
$queueStmt->bind_param("i", $test['test_id']);
$queueStmt->execute();
$queueResult = $queueStmt->get_result();
$queueRow = $queueResult->fetch_assoc();
$queue_position = $queueRow['ahead'] + 1;
$wait_time = $queueRow['ahead'] * $test['slot_duration'];
$queueStmt->close();

// Use payment amount from request or default to test fee
if ($payment_amount <= 0) {
    $payment_amount = $test['fee'];
}

// Insert lab token
$insertQuery = "INSERT INTO lab_tokens 
                (token_number, test_id, patient_name, patient_age, patient_gender, patient_phone, patient_id,
                 patient_type, scheduled_date, scheduled_time, payment_amount, token_status, queue_position, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'walkin', CURDATE(), ?, ?, 'waiting', ?, NOW())";

$insertStmt = $conn->prepare($insertQuery);
$insertStmt->bind_param("sisississdi", 
    $token_number, 
    $test['test_id'], 
    $patient_name, 
    $patient_age, 
    $patient_gender, 
    $patient_phone,
    $patient_id,
    $scheduled_time,
    $payment_amount,
    $queue_position
);

if ($insertStmt->execute()) {
    $token_id = $conn->insert_id;
    
    echo json_encode([
        'success' => true,
        'message' => 'Lab token generated successfully',
        'data' => [
            'token_id' => $token_id,
            'token_number' => $token_number,
            'test_name' => $test['test_name'],
            'patient_name' => $patient_name,
            'queue_position' => $queue_position,
            'people_ahead' => $queue_position - 1,
            'wait_time' => $wait_time,
            'scheduled_time' => $scheduled_time
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to generate token: ' . $conn->error
    ]);
}

$insertStmt->close();
$conn->close();
?>
