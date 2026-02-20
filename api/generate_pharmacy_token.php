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

$pharmacy_id = isset($data['pharmacy_id']) ? intval($data['pharmacy_id']) : 0;
$patient_id = isset($data['patient_id']) ? intval($data['patient_id']) : null;
$patient_name = isset($data['patient_name']) ? trim($data['patient_name']) : '';
$patient_phone = isset($data['patient_phone']) ? trim($data['patient_phone']) : '';
$payment_method = isset($data['payment_method']) ? trim($data['payment_method']) : 'cash';
$notes = isset($data['notes']) ? trim($data['notes']) : '';

// Validate required fields
if ($pharmacy_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid pharmacy selected']);
    exit();
}

if (empty($patient_name)) {
    echo json_encode(['success' => false, 'message' => 'Patient name is required']);
    exit();
}

// Check if pharmacy exists
$check_sql = "SELECT id, name, block, floor, wing FROM pharmacies WHERE id = ? AND is_active = TRUE";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $pharmacy_id);
$check_stmt->execute();
$pharmacy_result = $check_stmt->get_result();

if ($pharmacy_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Selected pharmacy not found or inactive']);
    exit();
}

$pharmacy = $pharmacy_result->fetch_assoc();
$check_stmt->close();

// Generate token number based on pharmacy
// Format: PH{pharmacy_id}-{sequence}
$today = date('Y-m-d');
$prefix = "PH{$pharmacy_id}-";

// Get next token number for this pharmacy today
$count_sql = "SELECT COUNT(*) as count FROM pharmacy_tokens 
              WHERE pharmacy_id = ? AND DATE(created_at) = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("is", $pharmacy_id, $today);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$next_number = $count_row['count'] + 1;
$count_stmt->close();

$token_number = $prefix . str_pad($next_number, 3, '0', STR_PAD_LEFT);

// Insert token
$insert_sql = "INSERT INTO pharmacy_tokens (token_number, patient_name, patient_phone, patient_id, pharmacy_id, payment_method, notes, status) 
               VALUES (?, ?, ?, ?, ?, ?, ?, 'waiting')";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param("sssiiss", $token_number, $patient_name, $patient_phone, $patient_id, $pharmacy_id, $payment_method, $notes);

if ($insert_stmt->execute()) {
    $token_id = $conn->insert_id;
    
    // Get position in queue
    $position_sql = "SELECT COUNT(*) as position FROM pharmacy_tokens 
                     WHERE pharmacy_id = ? AND DATE(created_at) = ? AND status = 'waiting' AND id < ?";
    $position_stmt = $conn->prepare($position_sql);
    $position_stmt->bind_param("isi", $pharmacy_id, $today, $token_id);
    $position_stmt->execute();
    $position_result = $position_stmt->get_result();
    $position_row = $position_result->fetch_assoc();
    $patients_ahead = $position_row['position'];
    $position_stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Token generated successfully',
        'data' => [
            'token_id' => $token_id,
            'token_number' => $token_number,
            'patient_name' => $patient_name,
            'pharmacy' => [
                'id' => $pharmacy['id'],
                'name' => $pharmacy['name'],
                'block' => $pharmacy['block'],
                'floor' => $pharmacy['floor'],
                'wing' => $pharmacy['wing'],
                'location' => "Block: {$pharmacy['block']}, Floor: {$pharmacy['floor']}, Wing: {$pharmacy['wing']}"
            ],
            'patients_ahead' => $patients_ahead,
            'estimated_wait' => ($patients_ahead * 3) . ' mins' // ~3 mins per patient
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to generate token: ' . $conn->error]);
}

$insert_stmt->close();
$conn->close();
?>
