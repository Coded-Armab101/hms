<?php
    session_start();
    unset($_SESSION['pharm_id']);
    unset($_SESSION['pharm_email']);
    session_destroy();

    header("Location: his_doc_logout.php");
    exit;
?>