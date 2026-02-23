<?php
    session_start();
    unset($_SESSION['ad_id']);
    session_destroy();

    header("Location: his_record_logout.php");
    exit;
?>