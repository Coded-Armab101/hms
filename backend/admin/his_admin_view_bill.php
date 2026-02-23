<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$aid=$_SESSION['ad_id'];
?>

<!DOCTYPE html>
<html lang="en">
    
<?php include('assets/inc/head.php');?>

<style>
    @media print {
        .no-print, .breadcrumb, .page-title-right, .btn, .footer {
            display: none !important;
        }
        .card-box {
            border: none !important;
            box-shadow: none !important;
        }
        body {
            background: white !important;
        }
    }
    .badge-paid {
        background-color: #28a745;
        color: white;
        padding: 5px 10px;
        border-radius: 20px;
    }
    .badge-unpaid {
        background-color: #dc3545;
        color: white;
        padding: 5px 10px;
        border-radius: 20px;
    }
    .badge-pending {
        background-color: #ffc107;
        color: #212529;
        padding: 5px 10px;
        border-radius: 20px;
    }
    .badge-partial {
        background-color: #17a2b8;
        color: white;
        padding: 5px 10px;
        border-radius: 20px;
    }
    .badge-cancelled {
        background-color: #6c757d;
        color: white;
        padding: 5px 10px;
        border-radius: 20px;
    }
</style>

<body>

    <!-- Begin page -->
    <div id="wrapper">

        <!-- Topbar Start -->
        <?php include('assets/inc/nav.php');?>
        <!-- end Topbar -->

        <!-- ========== Left Sidebar Start ========== -->
        <?php include("assets/inc/sidebar.php");?>
        <!-- Left Sidebar End -->

        <!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->

        <div class="content-page">
            <div class="content">

                <!-- Start Content-->
                <div class="container-fluid">
                    
                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="his_admin_dashboard.php">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="his_admin_manage_bills.php">Billing</a></li>
                                        <li class="breadcrumb-item active">View Bill</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">View Patient Bill</h4>
                            </div>
                        </div>
                    </div>     
                    <!-- end page title --> 

                    <div class="row">
                        <div class="col-12">
                            <div class="card-box">
                                <!-- Logo & title -->
                                <div class="clearfix">
                                    <div class="float-left">
                                        <h3><i class="mdi mdi-hospital-building"></i> Hospital Management System</h3>
                                    </div>
                                    <div class="float-right">
                                        <h4 class="m-0 d-print-none">INVOICE</h4>
                                    </div>
                                </div>

                                <?php
                                    // Enable error reporting for debugging
                                    ini_set('display_errors', 1);
                                    ini_set('display_startup_errors', 1);
                                    error_reporting(E_ALL);

                                    // Check if we have bill_id or dispensing_id
                                    $bill_id = isset($_GET['bill_id']) ? intval($_GET['bill_id']) : 0;
                                    $dispensing_id = isset($_GET['dispensing_id']) ? intval($_GET['dispensing_id']) : 0;
                                    $transaction_id = isset($_GET['transaction_id']) ? intval($_GET['transaction_id']) : 0;
                                    
                                    $row = null;
                                    $drugs = [];
                                    $total_amount = 0;
                                    
                                    if ($dispensing_id > 0) {
                                        // View dispensing record
                                        $query = "SELECT d.*, p.pat_fname, p.pat_lname, p.pat_addr, p.pat_phone, p.pat_number,
                                                 ph.phar_name, ph.phar_price_unit, ph.phar_attribute
                                                 FROM his_dispensed_drugs d
                                                 LEFT JOIN his_patients p ON d.pat_id = p.pat_id
                                                 LEFT JOIN his_pharmaceuticals ph ON d.phar_id = ph.phar_id
                                                 WHERE d.id = ?";
                                        $stmt = $mysqli->prepare($query);
                                        $stmt->bind_param('i', $dispensing_id);
                                        $stmt->execute();
                                        $res = $stmt->get_result();
                                        $row = $res->fetch_object();
                                        
                                        if ($row) {
                                            // Get all drugs from the same dispensing session (within 1 hour)
                                            $session_query = "SELECT d.*, ph.phar_name, ph.phar_price_unit, ph.phar_attribute
                                                              FROM his_dispensed_drugs d
                                                              LEFT JOIN his_pharmaceuticals ph ON d.phar_id = ph.phar_id
                                                              WHERE d.pat_id = ? 
                                                              AND ABS(TIMESTAMPDIFF(MINUTE, d.dispense_date, ?)) < 60
                                                              ORDER BY d.id";
                                            $session_stmt = $mysqli->prepare($session_query);
                                            $session_stmt->bind_param('is', $row->pat_id, $row->dispense_date);
                                            $session_stmt->execute();
                                            $session_res = $session_stmt->get_result();
                                            
                                            while ($drug = $session_res->fetch_object()) {
                                                $drugs[] = $drug;
                                                $total_amount += $drug->final_amount;
                                            }
                                        }
                                        
                                    } elseif ($transaction_id > 0) {
                                        // View from his_transactions
                                        $query = "SELECT t.*, p.pat_fname, p.pat_lname, p.pat_addr, p.pat_phone, p.pat_number
                                                 FROM his_transactions t
                                                 LEFT JOIN his_patients p ON t.pat_id = p.pat_id
                                                 WHERE t.id = ?";
                                        $stmt = $mysqli->prepare($query);
                                        $stmt->bind_param('i', $transaction_id);
                                        $stmt->execute();
                                        $res = $stmt->get_result();
                                        $row = $res->fetch_object();
                                        
                                        if ($row) {
                                            $total_amount = $row->final_amount ?? $row->amount ?? 0;
                                            
                                            // For pharmacy, try to get drugs
                                            if ($row->service_type == 'Pharmacy' && $row->service_id) {
                                                $drug_query = "SELECT d.*, ph.phar_name, ph.phar_price_unit, ph.phar_attribute
                                                               FROM his_dispensed_drugs d
                                                               LEFT JOIN his_pharmaceuticals ph ON d.phar_id = ph.phar_id
                                                               WHERE d.id = ? 
                                                               UNION
                                                               SELECT d.*, ph.phar_name, ph.phar_price_unit, ph.phar_attribute
                                                               FROM his_dispensed_drugs d
                                                               LEFT JOIN his_pharmaceuticals ph ON d.phar_id = ph.phar_id
                                                               WHERE d.pat_id = ? 
                                                               AND ABS(TIMESTAMPDIFF(MINUTE, d.dispense_date, (
                                                                   SELECT dispense_date FROM his_dispensed_drugs WHERE id = ?
                                                               ))) < 60";
                                                $drug_stmt = $mysqli->prepare($drug_query);
                                                $drug_stmt->bind_param('iii', $row->service_id, $row->pat_id, $row->service_id);
                                                $drug_stmt->execute();
                                                $drug_res = $drug_stmt->get_result();
                                                
                                                $drugs = [];
                                                $total_amount = 0;
                                                while ($drug = $drug_res->fetch_object()) {
                                                    $drugs[] = $drug;
                                                    $total_amount += $drug->final_amount;
                                                }
                                            }
                                        }
                                        
                                    } elseif ($bill_id > 0) {
                                        // View from his_patient_bills
                                        $query = "SELECT b.*, p.pat_fname, p.pat_lname, p.pat_addr, p.pat_phone, p.pat_number
                                                 FROM his_patient_bills b
                                                 LEFT JOIN his_patients p ON b.pat_id = p.pat_id
                                                 WHERE b.bill_id = ?";
                                        $stmt = $mysqli->prepare($query);
                                        $stmt->bind_param('i', $bill_id);
                                        $stmt->execute();
                                        $res = $stmt->get_result();
                                        $row = $res->fetch_object();
                                        
                                        if ($row) {
                                            $total_amount = $row->bill_amount ?? 0;
                                        }
                                    }

                                    if (!isset($row) || !$row) {
                                        echo '<div class="alert alert-danger mt-3">
                                                <i class="mdi mdi-alert-circle"></i> 
                                                Bill not found. Please check the ID and try again.
                                              </div>';
                                        echo '<div class="text-center mt-3">
                                                <a href="his_admin_manage_bills.php" class="btn btn-primary">
                                                    <i class="mdi mdi-arrow-left"></i> Back to Bills
                                                </a>
                                              </div>';
                                    } else {
                                        
                                        // Determine status and badge class
                                        $status = 'Unknown';
                                        $badge_class = 'badge-secondary';
                                        
                                        if (isset($row->status)) {
                                            $status = $row->status;
                                        } elseif (isset($row->payment_status)) {
                                            $status = $row->payment_status;
                                        } elseif (isset($row->pres_status)) {
                                            $status = $row->pres_status;
                                        }
                                        
                                        $status_lower = strtolower($status);
                                        if ($status_lower == 'paid' || $status_lower == 'completed') {
                                            $badge_class = 'badge-paid';
                                            $status = 'Paid';
                                        } elseif ($status_lower == 'unpaid') {
                                            $badge_class = 'badge-unpaid';
                                            $status = 'Unpaid';
                                        } elseif ($status_lower == 'pending') {
                                            $badge_class = 'badge-pending';
                                            $status = 'Pending';
                                        } elseif ($status_lower == 'partial') {
                                            $badge_class = 'badge-partial';
                                            $status = 'Partial';
                                        } elseif ($status_lower == 'cancelled') {
                                            $badge_class = 'badge-cancelled';
                                            $status = 'Cancelled';
                                        }
                                        
                                        // Get date field
                                        $date_field = $row->date_generated ?? $row->dispense_date ?? $row->transaction_date ?? $row->created_at ?? $row->bill_date ?? date('Y-m-d H:i:s');
                                        $order_date = date("d-m-Y", strtotime($date_field));
                                        $order_time = date("H:i", strtotime($date_field));
                                        
                                        // Get order ID
                                        $order_id = $dispensing_id ?: $transaction_id ?: $bill_id ?: ($row->id ?? $row->bill_id ?? 'N/A');
                                        
                                        // Get patient name
                                        $patient_name = '';
                                        if (isset($row->pat_fname) && isset($row->pat_lname)) {
                                            $patient_name = $row->pat_fname . ' ' . $row->pat_lname;
                                        } elseif (isset($row->pres_pat_name)) {
                                            $patient_name = $row->pres_pat_name;
                                        } else {
                                            $patient_name = 'N/A';
                                        }
                                        
                                        // Get patient address
                                        $patient_addr = $row->pat_addr ?? $row->pres_pat_addr ?? 'N/A';
                                        
                                        // Get patient phone
                                        $patient_phone = $row->pat_phone ?? 'N/A';
                                        
                                        // Get patient number
                                        $patient_number = $row->pat_number ?? $row->pres_pat_number ?? 'N/A';
                                ?>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mt-3">
                                            <p><b>Hello, <?php echo htmlspecialchars($patient_name);?></b></p>
                                            <p class="text-muted">Thank you for choosing our hospital. Below is your invoice details.</p>
                                        </div>
                                    </div><!-- end col -->
                                    <div class="col-md-4 offset-md-2">
                                        <div class="mt-3 float-right">
                                            <p class="m-b-10"><strong>Order Date : </strong> 
                                                <span class="float-right">
                                                    &nbsp;&nbsp;&nbsp;&nbsp; 
                                                    <?php echo $order_date; ?> at <?php echo $order_time; ?>
                                                </span>
                                            </p>
                                            <p class="m-b-10"><strong>Order Status : </strong> 
                                                <span class="float-right">
                                                    <span class="badge <?php echo $badge_class; ?>">
                                                        <?php echo ucfirst($status);?>
                                                    </span>
                                                </span>
                                            </p>
                                            <p class="m-b-10"><strong>Invoice No : </strong> 
                                                <span class="float-right">
                                                    #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?>
                                                </span>
                                            </p>
                                            <p class="m-b-10"><strong>Patient No : </strong> 
                                                <span class="float-right">
                                                    <span class="badge badge-info">
                                                        <?php echo htmlspecialchars($patient_number);?>
                                                    </span>
                                                </span>
                                            </p>
                                        </div>
                                    </div><!-- end col -->
                                </div>
                                <!-- end row -->

                                <div class="row mt-3">
                                    <div class="col-sm-6">
                                        <h6>Billing Address</h6>
                                        <address>
                                            <strong><?php echo htmlspecialchars($patient_name);?></strong><br>
                                            <?php echo htmlspecialchars($patient_addr);?><br>
                                            <abbr title="Phone">P:</abbr> <?php echo htmlspecialchars($patient_phone);?><br>
                                        </address>
                                    </div> <!-- end col -->
                                    <div class="col-sm-6">
                                        <h6>Payment Information</h6>
                                        <address>
                                            <?php 
                                            if(isset($row->payment_method) && !empty($row->payment_method)) {
                                                echo '<strong>Method:</strong> ' . htmlspecialchars($row->payment_method) . '<br>';
                                            }
                                            if(isset($row->transaction_ref) && !empty($row->transaction_ref)) {
                                                echo '<strong>Reference:</strong> ' . htmlspecialchars($row->transaction_ref) . '<br>';
                                            }
                                            if(isset($row->paid_date) && !empty($row->paid_date) && $row->paid_date != '0000-00-00 00:00:00') {
                                                echo '<strong>Paid Date:</strong> ' . date('d-m-Y H:i', strtotime($row->paid_date)) . '<br>';
                                            }
                                            if(empty($row->payment_method) && empty($row->transaction_ref)) {
                                                echo 'Payment details not available<br>';
                                            }
                                            ?>
                                        </address>
                                    </div> <!-- end col -->
                                </div> 
                                <!-- end row -->

                                <div class="row">
                                    <div class="col-12">
                                        <div class="table-responsive">
                                            <table class="table mt-4 table-centered table-bordered">
                                                <thead class="thead-light">
                                                <tr>
                                                    <th style="width: 5%">#</th>
                                                    <th style="width: 40%">Item / Drug</th>
                                                    <th style="width: 10%">Qty</th>
                                                    <th style="width: 15%">Unit Price</th>
                                                    <th style="width: 10%">Discount</th>
                                                    <th style="width: 20%" class="text-right">Total</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php if (!empty($drugs) && count($drugs) > 0): ?>
                                                    <?php $count = 1; foreach ($drugs as $drug): ?>
                                                    <tr>
                                                        <td><?php echo $count++; ?></td>
                                                        <td>
                                                            <b><?php echo htmlspecialchars($drug->phar_name ?? $drug->service_name ?? $row->bill_type ?? 'Pharmacy Item');?></b> <br/>
                                                            <small class="text-muted">
                                                                <?php 
                                                                echo htmlspecialchars($drug->phar_attribute ?? '');
                                                                if(isset($drug->description) && !empty($drug->description)) {
                                                                    echo ' - ' . htmlspecialchars(substr($drug->description, 0, 50));
                                                                }
                                                                ?>
                                                            </small>
                                                        </td>
                                                        <td><?php echo $drug->quantity_dispensed ?? $drug->quantity ?? 1; ?></td>
                                                        <td>₦ <?php echo number_format($drug->phar_price_unit ?? ($drug->amount / ($drug->quantity_dispensed ?? 1)), 2); ?></td>
                                                        <td>
                                                            <?php 
                                                            if(isset($drug->discount) && $drug->discount > 0) {
                                                                echo $drug->discount . '%';
                                                            } else {
                                                                echo '-';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td class="text-right">₦ <?php echo number_format($drug->final_amount ?? $drug->amount ?? 0, 2); ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                    
                                                <?php elseif (isset($row->bill_amount) && !empty($drugs) == 0): ?>
                                                    <tr>
                                                        <td>1</td>
                                                        <td>
                                                            <b><?php echo htmlspecialchars($row->bill_type ?? $row->service_type ?? 'Service');?></b> <br/>
                                                            <small class="text-muted"><?php echo htmlspecialchars(substr($row->bill_details ?? $row->description ?? '', 0, 100));?></small>
                                                        </td>
                                                        <td><?php echo $row->quantity ?? 1; ?></td>
                                                        <td>₦ <?php echo number_format(($row->final_amount ?? $row->amount ?? $row->bill_amount ?? 0) / ($row->quantity ?? 1), 2); ?></td>
                                                        <td><?php echo ($row->discount ?? 0) > 0 ? $row->discount . '%' : '-'; ?></td>
                                                        <td class="text-right">₦ <?php echo number_format($row->final_amount ?? $row->amount ?? $row->bill_amount ?? 0, 2); ?></td>
                                                    </tr>
                                                    
                                                <?php else: ?>
                                                    <tr>
                                                        <td>1</td>
                                                        <td>
                                                            <b><?php echo htmlspecialchars($row->service_type ?? $row->bill_type ?? 'Service');?></b> <br/>
                                                            <small class="text-muted"><?php echo htmlspecialchars(substr($row->description ?? $row->bill_details ?? '', 0, 100));?></small>
                                                        </td>
                                                        <td><?php echo $row->quantity ?? 1; ?></td>
                                                        <td>₦ <?php echo number_format(($row->final_amount ?? $row->amount ?? $row->bill_amount ?? 0) / ($row->quantity ?? 1), 2); ?></td>
                                                        <td><?php echo ($row->discount ?? 0) > 0 ? $row->discount . '%' : '-'; ?></td>
                                                        <td class="text-right">₦ <?php echo number_format($row->final_amount ?? $row->amount ?? $row->bill_amount ?? 0, 2); ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <th colspan="5" class="text-right">Subtotal:</th>
                                                        <th class="text-right">₦ <?php echo number_format($total_amount, 2); ?></th>
                                                    </tr>
                                                    <?php if (($row->discount ?? 0) > 0): ?>
                                                    <tr>
                                                        <th colspan="5" class="text-right">Discount:</th>
                                                        <th class="text-right text-danger">- ₦ <?php echo number_format(($row->amount ?? 0) - ($row->final_amount ?? $row->amount ?? 0), 2); ?></th>
                                                    </tr>
                                                    <?php endif; ?>
                                                    <tr class="bg-light">
                                                        <th colspan="5" class="text-right h5">Total:</th>
                                                        <th class="text-right h5 text-primary">₦ <?php echo number_format($total_amount, 2); ?></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div> <!-- end table-responsive -->
                                    </div> <!-- end col -->
                                </div>
                                <!-- end row -->

                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="clearfix pt-5">
                                            <h6 class="text-muted">Notes:</h6>
                                            <small class="text-muted">
                                                <?php 
                                                if (!empty($row->notes)) {
                                                    echo htmlspecialchars($row->notes);
                                                } elseif ($status == 'Unpaid' || $status == 'Pending') {
                                                    echo 'Please settle the payment within 7 days from receipt of invoice.';
                                                } elseif ($status == 'Paid') {
                                                    echo 'Payment completed. Thank you for your business.';
                                                } else {
                                                    echo 'Thank you for choosing our hospital.';
                                                }
                                                ?>
                                            </small>
                                        </div>
                                    </div> <!-- end col -->
                                    <div class="col-sm-6">
                                        <div class="float-right">
                                            <h2>₦ <?php echo number_format($total_amount, 2); ?></h2>
                                            <p class="text-muted"><small>Total Amount</small></p>
                                        </div>
                                        <div class="clearfix"></div>
                                    </div> <!-- end col -->
                                </div>
                                <!-- end row -->

                                <div class="mt-4 mb-1 no-print">
                                    <div class="text-right">
                                        <a href="javascript:window.print()" class="btn btn-primary waves-effect waves-light">
                                            <i class="mdi mdi-printer mr-1"></i> Print Invoice
                                        </a>
                                        <a href="his_admin_billing.php" class="btn btn-secondary waves-effect waves-light ml-2">
                                            <i class="mdi mdi-arrow-left mr-1"></i> Back to Bills
                                        </a>
                                        <?php if ($status == 'Unpaid' || $status == 'Pending'): ?>
                                        <a href="his_admin_billing.php?pay_bill=<?php echo $order_id; ?>" class="btn btn-success waves-effect waves-light ml-2">
                                            <i class="mdi mdi-cash-multiple mr-1"></i> Process Payment
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php } ?>
                            </div> <!-- end card-box -->
                        </div> <!-- end col -->
                    </div>
                    <!-- end row -->

                </div> <!-- container -->

            </div> <!-- content -->

            <!-- Footer Start -->
            <?php include('assets/inc/footer.php');?>
            <!-- end Footer -->

        </div>

        <!-- ============================================================== -->
        <!-- End Page content -->
        <!-- ============================================================== -->

    </div>
    <!-- END wrapper -->

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>

    <!-- App js -->
    <script src="assets/js/app.min.js"></script>
    
</body>

</html>