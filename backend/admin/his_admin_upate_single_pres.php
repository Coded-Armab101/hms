<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$aid = $_SESSION['ad_id'];

// Get current user
$current_user = 'Admin';
if (isset($_SESSION['ad_id'])) {
    $admin = $mysqli->query("SELECT ad_fname, ad_lname FROM his_admin WHERE ad_id = '$aid'")->fetch_object();
    if ($admin) {
        $current_user = $admin->ad_fname . ' ' . $admin->ad_lname . ' (Admin)';
    }
}

// Get patient number from URL
if (!isset($_GET['pat_number']) || empty($_GET['pat_number'])) {
    die("<h3 style='color:red; text-align:center;'>Error: Missing patient number.</h3>");
}
$pat_number = $_GET['pat_number'];

// Handle dispensed drug update
if (isset($_POST['update_dispensed'])) {
    $dispensed_id = $_POST['dispensed_id'];
    $phar_id = $_POST['phar_id'];
    $quantity_dispensed = $_POST['quantity_dispensed'];
    $discount = $_POST['discount'];
    $amount = $_POST['amount'];
    $final_amount = $_POST['final_amount'];
    
    $query = "UPDATE his_dispensed_drugs 
              SET quantity_dispensed = ?, discount = ?, amount = ?, final_amount = ?
              WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('iddii', $quantity_dispensed, $discount, $amount, $final_amount, $dispensed_id);
    
    if ($stmt->execute()) {
        $success = "Dispensed drug updated successfully!";
    } else {
        $err = "Update failed: " . $stmt->error;
    }
}

// Get patient details
$patient = $mysqli->query("SELECT * FROM his_patients WHERE pat_number = '$pat_number'")->fetch_object();

// Get prescription with doctor name
$prescription = $mysqli->query("
    SELECT p.*, 
           CONCAT(d.doc_fname, ' ', d.doc_lname) as doctor_fullname
    FROM his_prescriptions p 
    LEFT JOIN his_docs d ON p.doc_id = d.doc_id 
    WHERE p.pat_number = '$pat_number' 
    ORDER BY p.pres_id DESC LIMIT 1
")->fetch_object();

// Determine who prescribed
$prescribed_by = 'Unknown';
if ($prescription) {
    if ($prescription->doctor_fullname) {
        $prescribed_by = $prescription->doctor_fullname . ' (Doctor)';
    } elseif ($prescription->doc_name) {
        $prescribed_by = $prescription->doc_name . ' (Admin)';
    }
}

// Get all dispensed drugs for this patient
$dispensed_drugs = $mysqli->query("
    SELECT d.*, p.phar_name, p.phar_qty, p.phar_price_unit 
    FROM his_dispensed_drugs d 
    JOIN his_pharmaceuticals p ON d.phar_id = p.phar_id 
    WHERE d.pat_number = '$pat_number' 
    ORDER BY d.dispense_date DESC
");

// Get specific dispensed drug to edit
$edit_drug = null;
if (isset($_GET['dispensed_id'])) {
    $did = $_GET['dispensed_id'];
    $edit_drug = $mysqli->query("
        SELECT d.*, p.phar_name, p.phar_qty, p.phar_price_unit 
        FROM his_dispensed_drugs d 
        JOIN his_pharmaceuticals p ON d.phar_id = p.phar_id 
        WHERE d.id = '$did' AND d.pat_number = '$pat_number'
    ")->fetch_object();
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include('assets/inc/head.php'); ?>
<body>
    <div id="wrapper">
        <?php include("assets/inc/nav.php"); ?>
        <?php include("assets/inc/sidebar.php"); ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    
                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title">
                                    Patient: <?php echo $patient->pat_fname . ' ' . $patient->pat_lname; ?> (<?php echo $patient->pat_number; ?>)
                                </h4>
                                <p class="text-muted">Logged in as: <?php echo $current_user; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Success/Error Messages -->
                    <?php if (isset($success)) { ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php } ?>
                    <?php if (isset($err)) { ?>
                        <div class="alert alert-danger"><?php echo $err; ?></div>
                    <?php } ?>

                    <!-- Doctor's Prescription -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5>Prescription Details</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($prescription) { ?>
                                        <div class="row">
                                            <div class="col-md-8">
                                                <p><strong>Diagnosis / Ailment:</strong> <?php echo $prescription->pres_pat_ailment ?: 'N/A'; ?></p>
                                                <p><strong>Instructions:</strong> <?php echo nl2br($prescription->pres_ins ?: 'No instructions'); ?></p>
                                            </div>
                                            <div class="col-md-4">
                                                <p><strong>Prescribed By:</strong> <?php echo $prescribed_by; ?></p>
                                                <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($prescription->pres_date)); ?></p>
                                                <p><strong>Status:</strong> 
                                                    <span class="badge bg-<?php echo $prescription->pres_status == 'completed' ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($prescription->pres_status ?: 'pending'); ?>
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    <?php } else { ?>
                                        <p class="text-muted">No prescription found for this patient.</p>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Dispensed Drug Form -->
                    <?php if ($edit_drug) { ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-warning">
                                <div class="card-header bg-warning">
                                    <h5>Edit Dispensed Drug: <?php echo $edit_drug->phar_name; ?></h5>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <input type="hidden" name="dispensed_id" value="<?php echo $edit_drug->id; ?>">
                                        <input type="hidden" name="phar_id" value="<?php echo $edit_drug->phar_id; ?>">
                                        
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Current Stock</label>
                                                    <input type="text" class="form-control" value="<?php echo $edit_drug->phar_qty; ?> units" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Quantity</label>
                                                    <input type="number" name="quantity_dispensed" class="form-control" 
                                                           value="<?php echo $edit_drug->quantity_dispensed; ?>" min="1" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Amount ($)</label>
                                                    <input type="number" step="0.01" name="amount" class="form-control" 
                                                           value="<?php echo $edit_drug->amount; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Discount ($)</label>
                                                    <input type="number" step="0.01" name="discount" class="form-control" 
                                                           value="<?php echo $edit_drug->discount; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Final Amount</label>
                                                    <input type="number" step="0.01" name="final_amount" class="form-control" 
                                                           value="<?php echo $edit_drug->final_amount; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Updated By</label>
                                                    <input type="text" class="form-control" value="<?php echo $current_user; ?>" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" name="update_dispensed" class="btn btn-warning">
                                            Update Drug
                                        </button>
                                        <a href="his_admin_update_single_pres.php?pat_number=<?php echo $pat_number; ?>" class="btn btn-secondary">
                                            Cancel
                                        </a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>

                    <!-- Dispensed Drugs List -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5>Dispensed Medications</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Drug</th>
                                                    <th>Qty</th>
                                                    <th>Amount</th>
                                                    <th>Discount</th>
                                                    <th>Final</th>
                                                    <th>Date</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($dispensed_drugs && $dispensed_drugs->num_rows > 0) { ?>
                                                    <?php while ($drug = $dispensed_drugs->fetch_object()) { ?>
                                                    <tr>
                                                        <td><?php echo $drug->phar_name; ?></td>
                                                        <td><?php echo $drug->quantity_dispensed; ?></td>
                                                        <td>$<?php echo number_format($drug->amount, 2); ?></td>
                                                        <td>$<?php echo number_format($drug->discount, 2); ?></td>
                                                        <td>$<?php echo number_format($drug->final_amount, 2); ?></td>
                                                        <td><?php echo date('d/m/Y', strtotime($drug->dispense_date)); ?></td>
                                                        <td>
                                                            <a href="his_admin_update_single_pres.php?pat_number=<?php echo $pat_number; ?>&dispensed_id=<?php echo $drug->id; ?>" 
                                                               class="btn btn-sm btn-warning">Edit</a>
                                                        </td>
                                                    </tr>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center">No dispensed drugs found</td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <?php include('assets/inc/footer.php'); ?>
        </div>
    </div>

    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
    
    <script>
    $(document).ready(function() {
        $('input[name="amount"], input[name="discount"]').on('keyup change', function() {
            var amount = parseFloat($('input[name="amount"]').val()) || 0;
            var discount = parseFloat($('input[name="discount"]').val()) || 0;
            var final = amount - discount;
            if (final < 0) final = 0;
            $('input[name="final_amount"]').val(final.toFixed(2));
        });
    });
    </script>
</body>
</html>