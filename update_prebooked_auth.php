<?php
include 'db.php';

echo "Updating prebooked_appointments table...\n\n";

// Check if columns already exist
$result = $conn->query('SHOW COLUMNS FROM prebooked_appointments LIKE "patient_id"');
if ($result->num_rows == 0) {
    // Add new columns
    $conn->query('ALTER TABLE prebooked_appointments ADD COLUMN patient_id VARCHAR(20) NULL AFTER id');
    echo "Added patient_id column.\n";
    
    $conn->query('ALTER TABLE prebooked_appointments ADD COLUMN email VARCHAR(100) NULL AFTER patient_phone');
    echo "Added email column.\n";
    
    $conn->query('ALTER TABLE prebooked_appointments ADD COLUMN password VARCHAR(255) NULL AFTER email');
    echo "Added password column.\n";
} else {
    echo "Columns already exist.\n";
}

// Update existing records with patient_id
$conn->query('UPDATE prebooked_appointments SET patient_id = CONCAT("PRE", LPAD(id, 3, "0")) WHERE patient_id IS NULL');
echo "Patient IDs updated.\n";

// Set default password (hashed 'password')
$hashedPassword = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$stmt = $conn->prepare('UPDATE prebooked_appointments SET password = ? WHERE password IS NULL');
$stmt->bind_param('s', $hashedPassword);
$stmt->execute();
echo "Passwords set (default: 'password').\n";

// Set sample emails
$conn->query('UPDATE prebooked_appointments SET email = CONCAT(LOWER(REPLACE(patient_name, " ", ".")), "@example.com") WHERE email IS NULL');
echo "Emails updated.\n";

// Show sample data
echo "\n========================================\n";
echo "Sample data from prebooked_appointments:\n";
echo "========================================\n";
$result = $conn->query('SELECT id, patient_id, patient_name, email FROM prebooked_appointments LIMIT 10');
while ($row = $result->fetch_assoc()) {
    echo $row['patient_id'] . ' | ' . $row['patient_name'] . ' | ' . $row['email'] . "\n";
}

$conn->close();
echo "\n========================================\n";
echo "Database updated successfully!\n";
echo "Default password for all prebooked patients: 'password'\n";
echo "========================================\n";
?>
