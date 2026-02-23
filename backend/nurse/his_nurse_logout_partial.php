<?php
    session_start();
    unset($_SESSION['ns_id']);
    unset($_SESSION['ns_email']);
    session_destroy();

    header("Location: his_nurse_logout.php");
    exit;
?>