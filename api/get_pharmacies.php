<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include '../db.php';

// Get all active pharmacies with location info
$sql = "SELECT id, name, block, floor, wing, description, is_active, cash_counter, online_counter 
        FROM pharmacies 
        WHERE is_active = 1 
        ORDER BY id";

$result = $conn->query($sql);

$pharmacies = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pharmacies[] = [
            'id' => intval($row['id']),
            'name' => $row['name'],
            'block' => $row['block'],
            'floor' => $row['floor'],
            'wing' => $row['wing'],
            'description' => $row['description'],
            'cash_counter' => $row['cash_counter'] ?? 'Counter A',
            'online_counter' => $row['online_counter'] ?? 'Counter 1',
            'location' => "Block: {$row['block']}, Floor: {$row['floor']}, Wing: {$row['wing']}"
        ];
    }
}

echo json_encode([
    'success' => true,
    'data' => $pharmacies
]);

$conn->close();
?>
