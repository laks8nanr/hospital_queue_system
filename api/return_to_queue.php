<?php
/**
 * Return to Queue API
 * Returns review/skipped patients back to the queue at a specific position
 * - Review patients: After 2 patients
 * - Skipped patients: After 3 patients
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include '../db.php';

$data = json_decode(file_get_contents('php://input'), true);

$token_id = intval($data['token_id'] ?? 0);
$action = $data['action'] ?? ''; // 'return' for review patients, 'recall' for skipped patients

if ($token_id == 0 || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Token ID and action are required']);
    exit;
}

if (!in_array($action, ['return', 'recall'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid action. Use "return" or "recall"']);
    exit;
}

try {
    $today = date('Y-m-d');
    
    // Get the token details
    $tokenSql = "SELECT * FROM tokens WHERE id = ?";
    $tokenStmt = $conn->prepare($tokenSql);
    $tokenStmt->bind_param("i", $token_id);
    $tokenStmt->execute();
    $tokenResult = $tokenStmt->get_result();
    
    if ($tokenResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Token not found']);
        exit;
    }
    
    $token = $tokenResult->fetch_assoc();
    $doctor_id = $token['doctor_id'];
    $tokenStmt->close();
    
    // Position to insert: after 2 for review, after 3 for recall
    $insertAfter = ($action === 'return') ? 2 : 3;
    
    // Get the waiting tokens for this doctor, ordered by queue_position/id
    $queueSql = "SELECT id, queue_position FROM tokens 
                 WHERE doctor_id = ? AND status = 'waiting' AND DATE(created_at) = ?
                 ORDER BY COALESCE(queue_position, id) ASC";
    $queueStmt = $conn->prepare($queueSql);
    $queueStmt->bind_param("is", $doctor_id, $today);
    $queueStmt->execute();
    $queueResult = $queueStmt->get_result();
    
    $waitingTokens = [];
    while ($row = $queueResult->fetch_assoc()) {
        $waitingTokens[] = $row;
    }
    $queueStmt->close();
    
    // Calculate new expected time (add ~20-30 mins for review, ~30-40 mins for recall)
    $baseMinutes = ($action === 'return') ? ($insertAfter + 1) * 10 : ($insertAfter + 1) * 10;
    $newExpectedTime = date('H:i:s', strtotime("+{$baseMinutes} minutes"));
    
    // Update all tokens after the insert position to shift their position
    // We need to give the returning patient a queue_position
    if (count($waitingTokens) <= $insertAfter) {
        // Not enough patients, just add to end
        $newPosition = count($waitingTokens) + 1;
    } else {
        // Insert after $insertAfter patients
        // Get the position of the patient at $insertAfter index
        $targetPosition = $waitingTokens[$insertAfter]['queue_position'] ?? ($insertAfter + 1);
        
        // Shift all tokens after this position
        $shiftSql = "UPDATE tokens SET queue_position = queue_position + 1, 
                     expected_time = DATE_ADD(expected_time, INTERVAL 10 MINUTE)
                     WHERE doctor_id = ? AND status = 'waiting' AND DATE(created_at) = ?
                     AND COALESCE(queue_position, id) >= ?";
        $shiftStmt = $conn->prepare($shiftSql);
        $shiftStmt->bind_param("isi", $doctor_id, $today, $targetPosition);
        $shiftStmt->execute();
        $shiftStmt->close();
        
        $newPosition = $targetPosition;
    }
    
    // Update the returning/recalled token
    $updateSql = "UPDATE tokens SET status = 'waiting', queue_position = ?, expected_time = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("isi", $newPosition, $newExpectedTime, $token_id);
    
    if ($updateStmt->execute()) {
        $actionWord = ($action === 'return') ? 'returned' : 'recalled';
        echo json_encode([
            'success' => true,
            'message' => "Patient {$actionWord} to queue at position " . ($insertAfter + 1),
            'new_position' => $insertAfter + 1,
            'expected_time' => date('h:i A', strtotime($newExpectedTime))
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update token']);
    }
    
    $updateStmt->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
