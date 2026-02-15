<?php
include "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate inputs
    if (empty($name) || empty($email) || empty($password)) {
        header("Location: register.html?error=All fields are required");
        exit();
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM patients WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        header("Location: register.html?error=Email already registered");
        exit();
    }

    // Generate unique patient ID
    $patient_id = 'PAT' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new patient
    $stmt = $conn->prepare("INSERT INTO patients (patient_id, name, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $patient_id, $name, $email, $hashedPassword);

    if ($stmt->execute()) {
        header("Location: patient_login.html?success=Registration successful! Your Patient ID is: " . $patient_id);
        exit();
    } else {
        header("Location: register.html?error=Registration failed. Please try again.");
        exit();
    }
}
?>
