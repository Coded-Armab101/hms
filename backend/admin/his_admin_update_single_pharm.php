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
        header("Location: his_admin_view_single_pres.php?pat_number=$pat_number&success=1");
        exit;
    } else {
        $err = "Update failed: " . $stmt->error;
    }
}

// Get patient details
$patient = $mysqli->query("SELECT * FROM his_patients WHERE pat_number = '$pat_number'")->fetch_object();

if (!$patient) {
    die("<h3 style='color:red; text-align:center;'>Patient not found!</h3>");
}

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

// Get all dispensed drugs for this patient
$dispensed_drugs = $mysqli->query("
    SELECT d.*, p.phar_name, p.phar_qty, p.phar_price_unit 
    FROM his_dispensed_drugs d 
    JOIN his_pharmaceuticals p ON d.phar_id = p.phar_id 
    WHERE d.pat_number = '$pat_number' 
    ORDER BY d.dispense_date DESC
");
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
                                    Edit Dispensed Drugs - <?php echo $patient->pat_fname . ' ' . $patient->pat_lname; ?> (<?php echo $patient->pat_number; ?>)
                                </h4>
                                <div class="page-title-right">
                                    <a href="his_admin_view_single_pres.php?pat_number=<?php echo $pat_number; ?>" class="btn btn-info btn-sm">
                                        <i class="mdi mdi-eye"></i> View Patient
                                    </a>
                                    <a href="his_admin_view_pres.php" class="btn btn-secondary btn-sm">
                                        <i class="mdi mdi-arrow-left"></i> Back
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Error Message -->
                    <?php if (isset($err)) { ?>
                        <div class="alert alert-danger"><?php echo $err; ?></div>
                    <?php } ?>

                    <!-- EDIT DISPENSED DRUG FORM - Shows when edit button is clicked -->
                    <?php if ($edit_drug) { ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-warning">
                                <div class="card-header bg-warning">
                                    <h5 class="mb-0">
                                        <i class="mdi mdi-pencil"></i> 
                                        Editing: <?php echo $edit_drug->phar_name; ?> (Dispensed #<?php echo $edit_drug->id; ?>)
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <input type="hidden" name="dispensed_id" value="<?php echo $edit_drug->id; ?>">
                                        <input type="hidden" name="phar_id" value="<?php echo $edit_drug->phar_id; ?>">
                                        
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Drug Name</label>
                                                    <input type="text" class="form-control" value="<?php echo $edit_drug->phar_name; ?>" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Current Stock</label>
                                                    <input type="text" class="form-control" value="<?php echo $edit_drug->phar_qty; ?> units" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Quantity <span class="text-danger">*</span></label>
                                                    <input type="number" name="quantity_dispensed" class="form-control" 
                                                           value="<?php echo $edit_drug->quantity_dispensed; ?>" 
                                                           min="1" max="<?php echo $edit_drug->phar_qty; ?>" required>
                                                    <small class="text-muted">Max: <?php echo $edit_drug->phar_qty; ?></small>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Amount ($) <span class="text-danger">*</span></label>
                                                    <input type="number" step="0.01" name="amount" class="form-control" 
                                                           value="<?php echo $edit_drug->amount; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Discount ($)</label>
                                                    <input type="number" step="0.01" name="discount" class="form-control" 
                                                           value="<?php echo $edit_drug->discount; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Final Amount <span class="text-danger">*</span></label>
                                                    <input type="number" step="0.01" name="final_amount" class="form-control" 
                                                           value="<?php echo $edit_drug->final_amount; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label>Updated By</label>
                                                    <input type="text" class="form-control" value="<?php echo $current_user; ?>" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <hr>
                                        
                                        <button type="submit" name="update_dispensed" class="btn btn-warning">
                                            <i class="mdi mdi-update"></i> Update Dispensed Drug
                                        </button>
                                        <a href="his_admin_update_single_pres.php?pat_number=<?php echo $pat_number; ?>" class="btn btn-secondary">
                                            <i class="mdi mdi-cancel"></i> Cancel
                                        </a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <?php } ?>

                    <!-- DISPENSED DRUGS LIST - All dispensed drugs with edit buttons -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">
                                        <i class="mdi mdi-pill"></i> 
                                        Dispensed Medications - Click Edit to Modify
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Drug Name</th>
                                                    <th>Quantity</th>
                                                    <th>Unit Price</th>
                                                    <th>Amount</th>
                                                    <th>Discount</th>
                                                    <th>Final Amount</th>
                                                    <th>Dispense Date</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($dispensed_drugs && $dispensed_drugs->num_rows > 0) { ?>
                                                    <?php while ($drug = $dispensed_drugs->fetch_object()) { ?>
                                                    <tr <?php echo ($edit_drug && $edit_drug->id == $drug->id) ? 'class="table-warning"' : ''; ?>>
                                                        <td>#<?php echo $drug->id; ?></td>
                                                        <td><strong><?php echo $drug->phar_name; ?></strong></td>
                                                        <td><?php echo $drug->quantity_dispensed; ?></td>
                                                        <td>$<?php echo number_format($drug->phar_price_unit ?? 0, 2); ?></td>
                                                        <td>$<?php echo number_format($drug->amount, 2); ?></td>
                                                        <td>$<?php echo number_format($drug->discount, 2); ?></td>
                                                        <td><strong>$<?php echo number_format($drug->final_amount, 2); ?></strong></td>
                                                        <td><?php echo date('d/m/Y H:i', strtotime($drug->dispense_date)); ?></td>
                                                        <td>
                                                            <a href="his_admin_update_single_pres.php?pat_number=<?php echo $pat_number; ?>&dispensed_id=<?php echo $drug->id; ?>" 
                                                               class="btn btn-sm <?php echo ($edit_drug && $edit_drug->id == $drug->id) ? 'btn-success' : 'btn-warning'; ?>">
                                                                <i class="mdi mdi-pencil"></i> Edit
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <tr>
                                                        <td colspan="9" class="text-center">
                                                            <div class="alert alert-info mb-0">
                                                                No dispensed drugs found for this patient.
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <a href="his_admin_view_single_pres.php?pat_number=<?php echo $pat_number; ?>" class="btn btn-info">
                                        <i class="mdi mdi-eye"></i> View Patient Details
                                    </a>
                                    <a href="his_admin_view_pres.php" class="btn btn-secondary">
                                        <i class="mdi mdi-arrow-left"></i> Back to Prescriptions List
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- container -->
            </div> <!-- content -->

            <?php include('assets/inc/footer.php'); ?>
        </div> <!-- content-page -->
    </div> <!-- wrapper -->

    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
    
    <script>
    // Auto-calculate final amount
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