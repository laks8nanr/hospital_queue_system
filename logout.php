<?php
session_start();
include 'db.php';

// Update session record if exists
if (isset($_SESSION['session_record_id'])) {
    $session_record_id = $_SESSION['session_record_id'];
    
    $stmt = $conn->prepare("UPDATE patient_sessions SET 
        logout_time = NOW(), 
        session_duration_minutes = TIMESTAMPDIFF(MINUTE, login_time, NOW()),
        status = 'logged_out' 
        WHERE id = ?");
    $stmt->bind_param("i", $session_record_id);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
session_destroy();
header("Location: home.html");
exit();
?>
