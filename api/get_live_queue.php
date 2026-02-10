<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include '../db.php';

// Get doctor_id and current_token from query
$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$current_token = isset($_GET['current_token']) ? strtoupper(trim($_GET['current_token'])) : '';

// Combined query: Get tokens + prebooked appointments (not yet checked in)
// Queue ordered by: consulting first, then prebooked by appointment time, then walk-ins by token time
$query = "
    SELECT * FROM (
        -- Already checked-in PREBOOKED patients (use expected_time = appointment time)
        SELECT 
            t.id,
            t.token_number,
            t.patient_name,
            COALESCE(t.token_type, t.type, 'walkin') as token_type,
            t.status,
            t.doctor_id,
            COALESCE(t.expected_time, TIME(t.created_at)) as appointment_time,
            t.created_at,
            'checked_in' as checkin_status
        FROM tokens t
        WHERE DATE(t.created_at) = CURDATE()
        AND t.status IN ('waiting', 'consulting')
        AND (t.token_type = 'prebooked' OR t.type = 'prebooked' OR t.token_number LIKE 'PB%')
        
        UNION ALL
        
        -- Already checked-in WALK-IN patients (use created_at time)
        SELECT 
            t.id,
            t.token_number,
            t.patient_name,
            COALESCE(t.token_type, t.type, 'walkin') as token_type,
            t.status,
            t.doctor_id,
            TIME(t.created_at) as appointment_time,
            t.created_at,
            'checked_in' as checkin_status
        FROM tokens t
        WHERE DATE(t.created_at) = CURDATE()
        AND t.status IN ('waiting', 'consulting')
        AND COALESCE(t.token_type, t.type, 'walkin') != 'prebooked'
        AND t.token_number NOT LIKE 'PB%'
        
        UNION ALL
        
        -- Prebooked patients who haven't checked in yet (use appointment_time)
        SELECT 
            pb.id,
            pb.booking_id as token_number,
            pb.patient_name,
            'prebooked' as token_type,
            'waiting' as status,
            pb.doctor_id,
            pb.appointment_time as appointment_time,
            NULL as created_at,
            'not_checked_in' as checkin_status
        FROM prebooked_appointments pb
        WHERE pb.appointment_date = CURDATE()
        AND pb.status = 'booked'
        AND NOT EXISTS (
            SELECT 1 FROM tokens t 
            WHERE t.token_number = pb.booking_id 
            AND DATE(t.created_at) = CURDATE()
            AND t.status != 'cancelled'
        )
    ) AS combined_queue
    WHERE 1=1";

if ($doctor_id > 0) {
    $query .= " AND doctor_id = " . $doctor_id;
}

// Order: consulting first, then ALL prebooked by appointment time, then walk-ins by created time
$query .= " ORDER BY 
    CASE WHEN status = 'consulting' THEN 0 ELSE 1 END,
    CASE WHEN token_type = 'prebooked' OR token_number LIKE 'PB%' THEN 0 ELSE 1 END,
    appointment_time ASC,
    created_at ASC";

$result = $conn->query($query);

$queue = [];
$position = 1;
$current_position = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Case-insensitive comparison for token matching
        $row_token = strtoupper($row['token_number']);
        $is_current = ($row_token === $current_token);
        
        if ($is_current) {
            $current_position = $position;
        }
        
        $queue[] = [
            'position' => $position,
            'token_number' => $row['token_number'],
            'patient_name' => $row['patient_name'],
            'token_type' => $row['token_type'],
            'status' => $row['status'],
            'checkin_status' => $row['checkin_status'],
            'is_you' => $is_current
        ];
        
        $position++;
    }
}

// Get max 5 people centered around user's position
$display_queue = [];
$total = count($queue);

if (!empty($current_token) && $current_position > 0) {
    if ($total <= 5) {
        $display_queue = $queue;
    } else {
        $start = max(0, $current_position - 3);
        if ($start + 5 > $total) {
            $start = max(0, $total - 5);
        }
        $display_queue = array_slice($queue, $start, 5);
    }
} else {
    $display_queue = array_slice($queue, 0, 5);
}

$people_ahead = ($current_position > 0) ? $current_position - 1 : 0;

echo json_encode([
    'success' => true,
    'queue' => $display_queue,
    'total_in_queue' => $total,
    'current_position' => $current_position,
    'people_ahead' => $people_ahead
]);

$conn->close();
?>
