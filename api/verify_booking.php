<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include '../db.php';

// Get booking ID from query string
$booking_id = isset($_GET['booking_id']) ? strtoupper(trim($_GET['booking_id'])) : '';

if (empty($booking_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Booking ID is required'
    ]);
    exit;
}

// Query to get booking details with doctor and department info
$query = "SELECT 
    pb.booking_id,
    pb.patient_name,
    pb.patient_age,
    pb.patient_phone,
    pb.appointment_date,
    pb.appointment_time,
    pb.status,
    d.id as doctor_id,
    d.name as doctor_name,
    d.qualification as doctor_qualification,
    d.fees as doctor_fees,
    d.time_slot as doctor_time_slot,
    dept.id as department_id,
    dept.name as department_name
FROM prebooked_appointments pb
JOIN doctors d ON pb.doctor_id = d.id
JOIN departments dept ON pb.department_id = dept.id
WHERE pb.booking_id = ?
AND pb.status IN ('booked', 'confirmed')
AND pb.appointment_date >= CURDATE()";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Booking not found or already used. Please check your Booking ID.'
    ]);
    exit;
}

$booking = $result->fetch_assoc();

// Format date for display
$booking['appointment_date'] = date('d M Y', strtotime($booking['appointment_date']));

echo json_encode([
    'success' => true,
    'data' => $booking
]);

$stmt->close();
$conn->close();
?>
