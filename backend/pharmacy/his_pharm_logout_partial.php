<?php
    session_start();
    unset($_SESSION['ad_id']);
    session_destroy();

    header("Location: his_pharm_logout.php");
    exit;
?>