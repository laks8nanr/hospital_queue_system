<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include '../db.php';

// Get all active pharmacies with location info
$sql = "SELECT id, name, block, floor, wing, description, is_active 
        FROM pharmacies 
        WHERE is_active = TRUE 
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
