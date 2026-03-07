<?php
/**
 * Verify Today Patient API
 * Checks if a patient has a completed consultation token for today
 * Uses the logged-in patient's ID to automatically verify
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

include '../db.php';

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get parameters (works with both GET and POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $patient_db_id = isset($data['patient_db_id']) ? intval($data['patient_db_id']) : 0; // Integer ID from patients.id
    $patient_id = isset($data['patient_id']) ? trim($data['patient_id']) : ''; // PAT... string
    $patient_phone = isset($data['patient_phone']) ? trim($data['patient_phone']) : '';
} else {
    $patient_db_id = isset($_GET['patient_db_id']) ? intval($_GET['patient_db_id']) : 0;
    $patient_id = isset($_GET['patient_id']) ? trim($_GET['patient_id']) : '';
    $patient_phone = isset($_GET['patient_phone']) ? trim($_GET['patient_phone']) : '';
}

if ($patient_db_id <= 0 && empty($patient_id) && empty($patient_phone)) {
    echo json_encode(['success' => false, 'message' => 'Patient identification required']);
    exit();
}

$today = date('Y-m-d');

// Search for completed consultation token for today
// tokens.patient_id stores the integer ID from patients.id
$sql = "SELECT t.id, t.token_number, t.patient_name, t.patient_phone, t.patient_age,
               t.status, t.type, t.created_at,
               d.name as doctor_name, dept.name as department_name
        FROM tokens t
        LEFT JOIN doctors d ON t.doctor_id = d.id
        LEFT JOIN departments dept ON t.department_id = dept.id
        WHERE DATE(t.created_at) = ?
          AND t.status = 'completed'
          AND t.type IN ('walkin', 'prebooked')
          AND (t.patient_id = ? OR t.patient_phone = ? OR t.patient_phone = ?)
        ORDER BY t.created_at DESC
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("siss", $today, $patient_db_id, $patient_id, $patient_phone);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $token = $result->fetch_assoc();
    
    // Check if pharmacy token already exists for this consultation
    $check_sql = "SELECT id, token_number FROM pharmacy_tokens 
                  WHERE consultation_token_id = ? 
                  AND DATE(created_at) = ?
                  AND status NOT IN ('cancelled')";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $token['id'], $today);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    $existing_pharmacy_token = null;
    if ($check_result->num_rows > 0) {
        $existing_pharmacy_token = $check_result->fetch_assoc();
    }
    $check_stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Consultation verified successfully',
        'data' => [
            'consultation_token_id' => $token['id'],
            'token_number' => $token['token_number'],
            'patient_name' => $token['patient_name'],
            'patient_phone' => $token['patient_phone'],
            'patient_age' => $token['patient_age'],
            'doctor_name' => $token['doctor_name'],
            'department_name' => $token['department_name'],
            'consultation_time' => $token['created_at'],
            'status' => $token['status'],
            'already_has_pharmacy_token' => $existing_pharmacy_token !== null,
            'existing_pharmacy_token' => $existing_pharmacy_token ? $existing_pharmacy_token['token_number'] : null
        ]
    ]);
} else {
    // Check if token exists but not completed
    $pending_sql = "SELECT t.id, t.token_number, t.patient_name, t.status
                    FROM tokens t
                    WHERE DATE(t.created_at) = ?
                      AND t.type IN ('walkin', 'prebooked')
                      AND (t.patient_id = ? OR t.patient_phone = ? OR t.patient_phone = ?)
                    ORDER BY t.created_at DESC
                    LIMIT 1";
    
    $pending_stmt = $conn->prepare($pending_sql);
    $pending_stmt->bind_param("siss", $today, $patient_db_id, $patient_id, $patient_phone);
    $pending_stmt->execute();
    $pending_result = $pending_stmt->get_result();
    
    if ($pending_result->num_rows > 0) {
        $pending_token = $pending_result->fetch_assoc();
        $status_text = $pending_token['status'] === 'waiting' ? 'still waiting' : 
                      ($pending_token['status'] === 'consulting' ? 'currently consulting' : $pending_token['status']);
        
        echo json_encode([
            'success' => false,
            'message' => "Your consultation (Token: {$pending_token['token_number']}) is {$status_text}. Please complete your consultation first.",
            'status' => $pending_token['status']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No consultation found for today. You need to complete a consultation before visiting the pharmacy as a Today Patient.'
        ]);
    }
    
    $pending_stmt->close();
}

$stmt->close();
$conn->close();
?>
