<?php
session_start();

if(isset($_SESSION['patient_id'])){
    header("Location: patient_dashboard.php");
} else {
    header("Location: patient_login.html");
}
exit();
?>
