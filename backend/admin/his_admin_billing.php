<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$aid = $_SESSION['ad_id'];
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
                                            <li class="breadcrumb-item active">Manage Bills</li>
                                        </ol>
                                    </div>
                                    <h4 class="page-title">Manage All Transactions</h4>
                                </div>
                            </div>
                        </div>     
                        
                        <?php if(isset($_SESSION['bill_success'])): ?>
                        <div class="alert alert-success">
                            <i class="mdi mdi-check-circle"></i> <?= htmlspecialchars($_SESSION['bill_success']) ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                        <?php unset($_SESSION['bill_success']); endif; ?>

                        <?php if(isset($_SESSION['bill_error'])): ?>
                        <div class="alert alert-danger">
                            <i class="mdi mdi-alert-circle"></i> <?= htmlspecialchars($_SESSION['bill_error']) ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                        <?php unset($_SESSION['bill_error']); endif; ?>

                        <?php
                        // ========== DROP AND RECREATE TABLE TO FIX COLUMNS ==========
                        $mysqli->query("DROP TABLE IF EXISTS his_transactions");
                        
                        // Create fresh table with correct columns
                        $mysqli->query("CREATE TABLE his_transactions (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            pat_id INT NOT NULL,
                            pat_number VARCHAR(50),
                            service_type VARCHAR(50),
                            service_id INT,
                            description TEXT,
                            amount DECIMAL(10,2) DEFAULT 0,
                            discount DECIMAL(10,2) DEFAULT 0,
                            final_amount DECIMAL(10,2) DEFAULT 0,
                            payment_status VARCHAR(20) DEFAULT 'pending',
                            payment_method VARCHAR(50),
                            transaction_ref VARCHAR(100),
                            transaction_date DATETIME,
                            INDEX(pat_id),
                            INDEX(payment_status)
                        )");

                        // ========== MIGRATE ALL DATA ==========
                        
                        // 1. Migrate Pharmacy - Dispensed Drugs
                        $mysqli->query("INSERT INTO his_transactions 
                            (pat_id, pat_number, service_type, service_id, description, amount, discount, final_amount, payment_status, transaction_date)
                            SELECT pat_id, pat_number, 'Pharmacy', id, 
                            CONCAT('Drug Dispensed'),
                            amount, IFNULL(discount,0), IFNULL(final_amount, amount),
                            CASE WHEN IFNULL(final_amount, amount) <= 0 THEN 'paid' ELSE 'pending' END,
                            dispense_date
                            FROM his_dispensed_drugs");
                        
                        // 2. Migrate Patient Bills
                        $mysqli->query("INSERT INTO his_transactions 
                            (pat_id, pat_number, service_type, service_id, description, amount, final_amount, payment_status, transaction_date)
                            SELECT b.pat_id, p.pat_number, 
                            CASE WHEN b.bill_type = 'Pharmaceuticals' THEN 'Pharmacy' ELSE b.bill_type END,
                            b.bill_id, b.bill_details, b.bill_amount, b.bill_amount, b.status, b.date_generated
                            FROM his_patient_bills b 
                            LEFT JOIN his_patients p ON b.pat_id = p.pat_id");
                        
                        // 3. Migrate Registration
                        $mysqli->query("INSERT INTO his_transactions 
                            (pat_id, pat_number, service_type, service_id, description, amount, discount, final_amount, payment_status, payment_method, transaction_ref, transaction_date)
                            SELECT pat_id, pat_number, 'Registration', bill_id, 
                            'Registration Fee',
                            amount, IFNULL(discount,0), final_amount, 
                            IFNULL(payment_status, 'pending'), payment_method,
                            IFNULL(transaction_id, transaction_ref), bill_date
                            FROM his_patient_billing");
                        
                        // 4. Migrate Laboratory
                        $mysqli->query("INSERT INTO his_transactions 
                            (pat_id, pat_number, service_type, service_id, description, payment_status, transaction_date)
                            SELECT pat_id, lab_pat_number, 'Laboratory', lab_id, 
                            IFNULL(lab_pat_tests, 'Lab Test'),
                            IFNULL(lab_status, 'pending'), lab_date_rec
                            FROM his_laboratory 
                            WHERE pat_id IS NOT NULL");

                        // ========== HANDLE STATUS UPDATE ==========
                        if(isset($_POST['update_status'])) {
                            $id = $_POST['id'];
                            $status = $_POST['payment_status'];
                            $method = $_POST['payment_method'] ?? null;
                            $ref = $_POST['transaction_ref'] ?? null;
                            
                            $stmt = $mysqli->prepare("UPDATE his_transactions SET 
                                payment_status = ?, 
                                payment_method = ?, 
                                transaction_ref = ? 
                                WHERE id = ?");
                            $stmt->bind_param('sssi', $status, $method, $ref, $id);
                            $stmt->execute();
                            
                            $_SESSION['bill_success'] = "Status updated to $status";
                            header("Location: ".$_SERVER['PHP_SELF']);
                            exit;
                        }

                        // ========== FILTERS ==========
                        $where = "WHERE 1=1";
                        $search = $_GET['search'] ?? '';
                        $service = $_GET['service'] ?? '';
                        $status_filter = $_GET['status_filter'] ?? '';
                        
                        if($search) {
                            $search = $mysqli->real_escape_string($search);
                            $where .= " AND (pat_number LIKE '%$search%' OR description LIKE '%$search%')";
                        }
                        if($service) {
                            $service = $mysqli->real_escape_string($service);
                            $where .= " AND service_type = '$service'";
                        }
                        if($status_filter) {
                            $status_filter = $mysqli->real_escape_string($status_filter);
                            $where .= " AND payment_status = '$status_filter'";
                        }
                        
                        // ========== GET ALL TRANSACTIONS ==========
                        $result = $mysqli->query("
                            SELECT t.*, p.pat_fname, p.pat_lname 
                            FROM his_transactions t
                            LEFT JOIN his_patients p ON t.pat_id = p.pat_id
                            $where
                            ORDER BY t.transaction_date DESC
                            LIMIT 500
                        ");
                        ?>

                        <!-- Simple Search Box -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card-box">
                                    <form method="GET" class="form-inline">
                                        <div class="form-group mr-2 mb-2">
                                            <input type="text" name="search" class="form-control form-control-sm" 
                                                   placeholder="Search..." 
                                                   value="<?= htmlspecialchars($search) ?>" style="width: 200px;">
                                        </div>
                                        <div class="form-group mr-2 mb-2">
                                            <select name="service" class="form-control form-control-sm">
                                                <option value="">All Services</option>
                                                <option value="Pharmacy" <?= $service=='Pharmacy'?'selected':'' ?>>Pharmacy</option>
                                                <option value="Registration" <?= $service=='Registration'?'selected':'' ?>>Registration</option>
                                                <option value="Laboratory" <?= $service=='Laboratory'?'selected':'' ?>>Lab</option>
                                                <option value="Consultation" <?= $service=='Consultation'?'selected':'' ?>>Consultation</option>
                                            </select>
                                        </div>
                                        <div class="form-group mr-2 mb-2">
                                            <select name="status_filter" class="form-control form-control-sm">
                                                <option value="">All Status</option>
                                                <option value="paid" <?= $status_filter=='paid'?'selected':'' ?>>Paid</option>
                                                <option value="pending" <?= $status_filter=='pending'?'selected':'' ?>>Pending</option>
                                                <option value="unpaid" <?= $status_filter=='unpaid'?'selected':'' ?>>Unpaid</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm mb-2">Go</button>
                                        <?php if($search || $service || $status_filter): ?>
                                            <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary btn-sm mb-2 ml-2">Clear</a>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Transactions Table -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card-box">
                                    <h4 class="header-title">All Transactions</h4>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Service</th>
                                                    <th>Patient</th>
                                                    <th>Patient No</th>
                                                    <th>Description</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php if($result && $result->num_rows > 0): ?>
                                                <?php while($row = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?= date('d-m-Y', strtotime($row['transaction_date'])) ?></td>
                                                    <td>
                                                        <?php 
                                                        $badge = 'badge-secondary';
                                                        if($row['service_type'] == 'Pharmacy') $badge = 'badge-success';
                                                        if($row['service_type'] == 'Laboratory') $badge = 'badge-info';
                                                        if($row['service_type'] == 'Registration') $badge = 'badge-warning';
                                                        if($row['service_type'] == 'Consultation') $badge = 'badge-primary';
                                                        ?>
                                                        <span class="badge <?= $badge ?>"><?= $row['service_type'] ?></span>
                                                    </td>
                                                    <td><?= htmlspecialchars($row['pat_fname'] . ' ' . $row['pat_lname']) ?></td>
                                                    <td><span class="badge badge-info"><?= htmlspecialchars($row['pat_number'] ?? 'N/A') ?></span></td>
                                                    <td><?= htmlspecialchars(substr($row['description'] ?? '', 0, 30)) ?></td>
                                                    <td>₦ <?= number_format($row['final_amount'] ?? $row['amount'] ?? 0, 2) ?></td>
                                                    <td>
                                                        <?php 
                                                        $status = $row['payment_status'] ?? 'pending';
                                                        $status_badge = 'badge-warning';
                                                        if($status == 'paid') $status_badge = 'badge-success';
                                                        if($status == 'unpaid') $status_badge = 'badge-danger';
                                                        ?>
                                                        <span class="badge <?= $status_badge ?>"><?= ucfirst($status) ?></span>
                                                    </td>
                                                    <td>
                                                        <!-- View Button - Links to view bill page -->
                                                        <a href="his_admin_view_bill.php?bill_id=<?= $row['service_id'] ?>&type=<?= $row['service_type'] ?>" 
                                                           class="badge badge-info" 
                                                           style="margin-right: 5px;">
                                                            <i class="mdi mdi-eye"></i> View
                                                        </a>
                                                        
                                                        <!-- Update Button - Opens Modal -->
                                                        <a href="#updateModal<?= $row['id'] ?>" 
                                                           class="badge badge-primary"
                                                           data-toggle="modal">
                                                            <i class="mdi mdi-update"></i> Update
                                                        </a>
                                                    </td>
                                                </tr>

                                                <!-- Update Modal -->
                                                <div class="modal fade" id="updateModal<?= $row['id'] ?>">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Update Payment Status</h5>
                                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                                    
                                                                    <p><strong>Patient:</strong> <?= htmlspecialchars($row['pat_fname'] . ' ' . $row['pat_lname']) ?></p>
                                                                    <p><strong>Service:</strong> <?= $row['service_type'] ?></p>
                                                                    <p><strong>Amount:</strong> ₦ <?= number_format($row['final_amount'] ?? $row['amount'] ?? 0, 2) ?></p>
                                                                    
                                                                    <div class="form-group">
                                                                        <label>Status</label>
                                                                        <select name="payment_status" class="form-control" onchange="toggleFields(this)">
                                                                            <option value="pending" <?= $status=='pending'?'selected':'' ?>>Pending</option>
                                                                            <option value="paid" <?= $status=='paid'?'selected':'' ?>>Paid</option>
                                                                            <option value="unpaid" <?= $status=='unpaid'?'selected':'' ?>>Unpaid</option>
                                                                        </select>
                                                                    </div>
                                                                    
                                                                    <div class="form-group payment-fields" style="display: <?= ($status=='paid')?'block':'none' ?>;">
                                                                        <label>Payment Method</label>
                                                                        <select name="payment_method" class="form-control">
                                                                            <option value="">Select</option>
                                                                            <option value="Cash">Cash</option>
                                                                            <option value="Card">Card</option>
                                                                            <option value="Transfer">Transfer</option>
                                                                        </select>
                                                                    </div>
                                                                    
                                                                    <div class="form-group payment-fields" style="display: <?= ($status=='paid')?'block':'none' ?>;">
                                                                        <label>Reference</label>
                                                                        <input type="text" name="transaction_ref" class="form-control" 
                                                                               value="<?= htmlspecialchars($row['transaction_ref'] ?? '') ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="update_status" class="btn btn-primary">Update</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">No transactions found</td>
                                                </tr>
                                            <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include('assets/inc/footer.php');?>
            </div>
        </div>
        
        <script>
        function toggleFields(select) {
            var fields = document.querySelectorAll('.payment-fields');
            var show = select.value === 'paid';
            fields.forEach(function(field) {
                field.style.display = show ? 'block' : 'none';
            });
        }
        </script>
        
        <script src="assets/js/vendor.min.js"></script>
        <script src="assets/js/app.min.js"></script>
    </body>
</html>