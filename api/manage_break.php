<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include '../db.php';

// Check if doctor_breaks table exists, create if not
$tableCheck = $conn->query("SHOW TABLES LIKE 'doctor_breaks'");
if ($tableCheck->num_rows == 0) {
    $createTable = "CREATE TABLE doctor_breaks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT NOT NULL,
        break_start DATETIME,
        break_end DATETIME,
        break_date DATE NOT NULL,
        status ENUM('on_break', 'available') DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_doctor_date (doctor_id, break_date)
    )";
    $conn->query($createTable);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get break status for a doctor
    $doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
    $today = date('Y-m-d');
    
    if ($doctor_id == 0) {
        echo json_encode(['success' => false, 'message' => 'Doctor ID is required']);
        exit;
    }
    
    $sql = "SELECT * FROM doctor_breaks WHERE doctor_id = ? AND break_date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $doctor_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $break = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'data' => [
                'status' => $break['status'],
                'break_start' => $break['break_start'],
                'break_end' => $break['break_end'],
                'is_on_break' => $break['status'] === 'on_break'
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => [
                'status' => 'available',
                'break_start' => null,
                'break_end' => null,
                'is_on_break' => false
            ]
        ]);
    }
    $stmt->close();
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set or end break
    $data = json_decode(file_get_contents('php://input'), true);
    
    $doctor_id = intval($data['doctor_id'] ?? 0);
    $action = $data['action'] ?? ''; // 'start_break' or 'end_break'
    
    if ($doctor_id == 0) {
        echo json_encode(['success' => false, 'message' => 'Doctor ID is required']);
        exit;
    }
    
    if (!in_array($action, ['start_break', 'end_break'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid action. Use start_break or end_break']);
        exit;
    }
    
    $today = date('Y-m-d');
    $now = date('Y-m-d H:i:s');
    
    if ($action === 'start_break') {
        // Start a break
        $sql = "INSERT INTO doctor_breaks (doctor_id, break_date, break_start, status) 
                VALUES (?, ?, ?, 'on_break')
                ON DUPLICATE KEY UPDATE break_start = ?, status = 'on_break', break_end = NULL";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $doctor_id, $today, $now, $now);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Break started successfully',
                'data' => [
                    'status' => 'on_break',
                    'break_start' => $now
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to start break']);
        }
        $stmt->close();
        
    } else {
        // End break
        $sql = "UPDATE doctor_breaks SET break_end = ?, status = 'available' 
                WHERE doctor_id = ? AND break_date = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sis", $now, $doctor_id, $today);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Break ended successfully',
                'data' => [
                    'status' => 'available',
                    'break_end' => $now
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to end break']);
        }
        $stmt->close();
    }
}

$conn->close();
?>
