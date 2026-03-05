<?php
session_start();

if(isset($_SESSION['patient_id'])){
    header("Location: pharmacy_token.html");
} else {
    header("Location: patient_login.html?redirect=pharmacy_token.html");
}
exit();
?>
