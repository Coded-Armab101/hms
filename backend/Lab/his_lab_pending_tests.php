<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$lab_id = $_SESSION['lab_id'];

// Get pending lab tests (without results)
$query = "SELECT l.*, CONCAT(d.doc_fname, ' ', d.doc_lname) AS doctor_name 
          FROM his_laboratory l 
          LEFT JOIN his_docs d ON l.doc_id = d.doc_id 
          WHERE (l.lab_pat_results IS NULL OR l.lab_pat_results = '') 
          ORDER BY l.lab_date_rec DESC";
$result = $mysqli->query($query);
?>

<!-- Display table of pending tests with links to update them -->