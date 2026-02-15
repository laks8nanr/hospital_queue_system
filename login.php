<?php
session_start();
include "db.php";

if(isset($_POST['login'])){

    $login_input = $_POST['login_input'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM patients WHERE email=? OR patient_id=?");
    $stmt->bind_param("ss", $login_input, $login_input);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();

        if(password_verify($password, $row['password'])){
            $_SESSION['patient_id'] = $row['id'];
            $_SESSION['patient_name'] = $row['name'];

            header("Location: patient_dashboard.php");
            exit();
        } else {
            header("Location: patient_login.html?error=Incorrect password");
            exit();
        }
    } else {
        header("Location: patient_login.html?error=ID or Email not found");
        exit();
    }
}
?>
