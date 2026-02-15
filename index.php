<?php
session_start();

if(!isset($_SESSION['patient_id'])){
    header("Location: login.php");
    exit();
}

include("index.html");
?>
