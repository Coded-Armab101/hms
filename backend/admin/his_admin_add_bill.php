<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$aid=$_SESSION['ad_id'];

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if(isset($_POST['add_bill']))
{
    $pat_id = intval($_POST['pat_id']);
    $bill_type = trim($_POST['bill_type']);
    $bill_details = trim($_POST['bill_details']);
    $bill_amount = $_POST['bill_amount'] !== '' ? floatval($_POST['bill_amount']) : 0.0;

    // Basic validation
    if ($pat_id <= 0) {
        $err = "Invalid patient selected.";
    } elseif ($bill_type === '') {
        $err = "Please select a bill type.";
    } elseif (!is_numeric($bill_amount) || $bill_amount <= 0) {
        $err = "Please enter a valid amount.";
    } else {
        
        // Get patient number
        $pat_query = "SELECT pat_number FROM his_patients WHERE pat_id = ?";
        $pat_stmt = $mysqli->prepare($pat_query);
        $pat_stmt->bind_param('i', $pat_id);
        $pat_stmt->execute();
        $pat_res = $pat_stmt->get_result();
        $pat_row = $pat_res->fetch_object();
        $pat_number = $pat_row->pat_number ?? '';
        $pat_stmt->close();
        
        // Insert into his_patient_bills
        $query = "INSERT INTO his_patient_bills (pat_id, bill_type, bill_details, bill_amount, date_generated, status) 
                  VALUES (?, ?, ?, ?, NOW(), 'Unpaid')";
        $stmt = $mysqli->prepare($query);
        
        if (!$stmt) {
            $err = "Prepare failed: " . $mysqli->error;
        } else {
            $stmt->bind_param('issd', $pat_id, $bill_type, $bill_details, $bill_amount);
            
            if ($stmt->execute()) {
                $bill_id = $stmt->insert_id;
                
                // Manually insert into his_transactions - FIXED bind_param
                $trans_query = "INSERT INTO his_transactions 
                               (pat_id, pat_number, service_type, service_id, description, amount, final_amount, payment_status, transaction_date)
                               VALUES (?, ?, ?, ?, ?, ?, ?, 'Unpaid', NOW())";
                $trans_stmt = $mysqli->prepare($trans_query);
                
                if (!$trans_stmt) {
                    $err = "Transaction prepare failed: " . $mysqli->error;
                } else {
                    $description = $bill_type . " - " . $bill_details;
                    // 7 parameters: pat_id(i), pat_number(s), service_type(s), service_id(i), description(s), amount(d), final_amount(d)
                    $trans_stmt->bind_param('issisdd', $pat_id, $pat_number, $bill_type, $bill_id, $description, $bill_amount, $bill_amount);
                    
                    if ($trans_stmt->execute()) {
                        $success = "Patient Bill Added Successfully";
                    } else {
                        $err = "Failed to create transaction: " . $trans_stmt->error;
                    }
                    $trans_stmt->close();
                }
                
            } else {
                $err = "Execute failed: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <?php include('assets/inc/head.php');?>
    <body>
        <div id="wrapper">
            <?php include("assets/inc/nav.php");?>
            <?php include("assets/inc/sidebar.php");?>
            <div class="content-page">
                <div class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <div class="page-title-box">
                                    <div class="page-title-right">
                                        <ol class="breadcrumb m-0">
                                            <li class="breadcrumb-item"><a href="his_admin_dashboard.php">Dashboard</a></li>
                                            <li class="breadcrumb-item"><a href="javascript: void(0);">Billing</a></li>
                                            <li class="breadcrumb-item active">Add Bill</li>
                                        </ol>
                                    </div>
                                    <h4 class="page-title">Add Patient Bill</h4>
                                </div>
                            </div>
                        </div>     
                        
                        <?php if(isset($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php endif; ?>

                        <?php if(isset($err)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-alert-circle"></i> <?php echo htmlspecialchars($err); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="header-title">Fill all fields</h4>
                                        <form method="post">
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label for="inputEmail4" class="col-form-label">Patient</label>
                                                    <select name="pat_id" class="form-control" required="required">
                                                        <option value="">Select Patient</option>
                                                        <?php
                                                            $ret="SELECT pat_id, pat_fname, pat_lname, pat_number FROM his_patients ORDER BY pat_fname";
                                                            $stmt= $mysqli->prepare($ret) ;
                                                            $stmt->execute() ;
                                                            $res=$stmt->get_result();
                                                            while($row=$res->fetch_object())
                                                            {
                                                        ?>
                                                        <option value="<?php echo $row->pat_id;?>"><?php echo $row->pat_fname;?> <?php echo $row->pat_lname;?> (<?php echo $row->pat_number;?>)</option>
                                                        <?php }?>
                                                        <?php $stmt->close(); ?>
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label for="inputPassword4" class="col-form-label">Bill Type</label>
                                                    <select required="required" name="bill_type" class="form-control">
                                                        <option value="">Select Bill Type</option>
                                                        <?php
                                                            $ret="SELECT type_name FROM his_bill_types ORDER BY type_name"; 
                                                            $stmt= $mysqli->prepare($ret) ;
                                                            $stmt->execute() ;
                                                            $res=$stmt->get_result();
                                                            while($row=$res->fetch_object())
                                                            {
                                                        ?>
                                                        <option value="<?php echo $row->type_name;?>"><?php echo $row->type_name;?></option>
                                                        <?php }?>
                                                        <?php $stmt->close(); ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                 <div class="form-group col-md-6">
                                                    <label for="inputEmail4" class="col-form-label">Bill Details</label>
                                                    <input type="text" required="required" name="bill_details" class="form-control" placeholder="e.g. Malaria Treatment">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label for="inputPassword4" class="col-form-label">Amount (₦)</label>
                                                    <input type="number" step="0.01" required="required" name="bill_amount" class="form-control" min="0.01">
                                                </div>
                                            </div>

                                           <button type="submit" name="add_bill" class="ladda-button btn btn-success" data-style="expand-right">Add Bill</button>
                                           <a href="his_admin_manage_bills.php" class="btn btn-secondary">Cancel</a>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include('assets/inc/footer.php');?>
            </div>
        </div>
        <div class="rightbar-overlay"></div>
        <script src="assets/js/vendor.min.js"></script>
        <script src="assets/js/app.min.js"></script>
        <script src="assets/libs/ladda/spin.js"></script>
        <script src="assets/libs/ladda/ladda.js"></script>
        <script src="assets/js/pages/loading-btn.init.js"></script>
    </body>
</html>