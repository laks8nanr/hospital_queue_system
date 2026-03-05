<?php
session_start();

if(isset($_SESSION['patient_id'])){
    header("Location: lab_booking.html");
} else {
    header("Location: patient_login.html?redirect=lab_booking.html");
}
exit();
?>
