<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$aid = $_SESSION['ad_id'];

if(isset($_POST['update_transaction_status'])) {
    
    $transaction_id = $_POST['transaction_id'];
    $bill_id = isset($_POST['bill_id']) ? $_POST['bill_id'] : null;
    $payment_status = $_POST['payment_status'];
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : null;
    $transaction_ref = isset($_POST['transaction_ref']) ? $_POST['transaction_ref'] : null;
    $pat_id = isset($_POST['pat_id']) ? $_POST['pat_id'] : null;
    $pat_number = isset($_POST['pat_number']) ? $_POST['pat_number'] : null;
    $service_type = isset($_POST['service_type']) ? $_POST['service_type'] : null;
    $final_amount = isset($_POST['final_amount']) ? $_POST['final_amount'] : 0;
    
    $mysqli->begin_transaction();
    
    try {
        
        // 1. Update his_transactions table
        $update_trans = "UPDATE his_transactions SET 
                        payment_status = ?,
                        payment_method = ?,
                        transaction_ref = ?,
                        paid_date = CASE WHEN ? IN ('paid', 'partial') THEN NOW() ELSE NULL END
                        WHERE transaction_id = ?";
        $stmt = $mysqli->prepare($update_trans);
        $stmt->bind_param('ssssi', $payment_status, $payment_method, $transaction_ref, $payment_status, $transaction_id);
        $stmt->execute();
        
        // 2. Update his_patient_billing if this is a registration
        if($service_type == 'Registration' && $bill_id) {
            $check_billing = $mysqli->query("SHOW TABLES LIKE 'his_patient_billing'");
            if($check_billing && $check_billing->num_rows > 0) {
                $update_billing = "UPDATE his_patient_billing SET 
                                  payment_status = ?,
                                  payment_method = ?,
                                  transaction_ref = ?,
                                  paid_date = CASE WHEN ? IN ('paid', 'partial') THEN NOW() ELSE NULL END
                                  WHERE bill_id = ?";
                $stmt2 = $mysqli->prepare($update_billing);
                $stmt2->bind_param('ssssi', $payment_status, $payment_method, $transaction_ref, $payment_status, $bill_id);
                $stmt2->execute();
            }
        }
        
        // 3. Update his_patient_bills for pharmacy/other bills
        if($bill_id && ($service_type == 'Pharmacy' || $service_type == 'Consultation')) {
            $check_bills = $mysqli->query("SHOW TABLES LIKE 'his_patient_bills'");
            if($check_bills && $check_bills->num_rows > 0) {
                $update_bills = "UPDATE his_patient_bills SET status = ? WHERE bill_id = ?";
                $stmt3 = $mysqli->prepare($update_bills);
                $stmt3->bind_param('si', $payment_status, $bill_id);
                $stmt3->execute();
            }
        }
        
        // 4. Update his_dispensed_drugs for pharmacy
        if($service_type == 'Pharmacy' && $bill_id) {
            $check_dispensed = $mysqli->query("SHOW TABLES LIKE 'his_dispensed_drugs'");
            if($check_dispensed && $check_dispensed->num_rows > 0) {
                // Check if the column exists
                $check_col = $mysqli->query("SHOW COLUMNS FROM his_dispensed_drugs LIKE 'payment_status'");
                if($check_col && $check_col->num_rows > 0) {
                    $update_dispensed = "UPDATE his_dispensed_drugs SET payment_status = ? WHERE id = ?";
                    $stmt4 = $mysqli->prepare($update_dispensed);
                    $stmt4->bind_param('si', $payment_status, $bill_id);
                    $stmt4->execute();
                }
            }
        }
        
        // 5. If payment is paid/partial and amount > 0, create receipt record
        if(($payment_status == 'paid' || $payment_status == 'partial') && $final_amount > 0) {
            $check_receipts = $mysqli->query("SHOW TABLES LIKE 'his_payment_receipts'");
            if($check_receipts && $check_receipts->num_rows > 0) {
                
                // Generate receipt number
                $receipt_number = 'RCT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                $insert_receipt = "INSERT INTO his_payment_receipts 
                                  (receipt_number, pat_id, pat_number, payment_method, transaction_ref, total_paid, received_by, payment_date)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt5 = $mysqli->prepare($insert_receipt);
                $stmt5->bind_param('sisssdi', $receipt_number, $pat_id, $pat_number, $payment_method, $transaction_ref, $final_amount, $aid);
                $stmt5->execute();
            }
        }
        
        $mysqli->commit();
        $_SESSION['bill_success'] = "Transaction #$transaction_id updated to " . ucfirst($payment_status);
        
    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['bill_error'] = "Error: " . $e->getMessage();
    }
    
    // Redirect back
    if(isset($_SERVER['HTTP_REFERER'])) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        header("Location: his_admin_manage_bills.php");
    }
    exit();
    
} else {
    header("Location: his_admin_manage_bills.php");
    exit();
}
?>