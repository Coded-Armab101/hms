<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$aid = $_SESSION['ad_id'];

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get patient number from URL
if (!isset($_GET['pat_number']) || empty($_GET['pat_number'])) {
    die("<h3 style='color:red; text-align:center;'>Error: Missing patient number.</h3>");
}
$pat_number = mysqli_real_escape_string($mysqli, $_GET['pat_number']);

// Fetch patient details
$query_patient = "SELECT * FROM his_patients WHERE pat_number=?";
$stmt_patient = $mysqli->prepare($query_patient);
$stmt_patient->bind_param('s', $pat_number);
$stmt_patient->execute();
$result_patient = $stmt_patient->get_result();
$patient = $result_patient->fetch_object();

if (!$patient) {
    die("<h3 style='color:red; text-align:center;'>Patient not found!</h3>");
}

// Fetch all dispensed drugs for this patient - FIXED: Cast phar_qty to integer
$query_dispensed = "SELECT d.*, p.phar_name, p.phar_price_unit, CAST(p.phar_qty AS UNSIGNED) as stock_qty
                    FROM his_dispensed_drugs d 
                    JOIN his_pharmaceuticals p ON d.phar_id = p.phar_id
                    WHERE d.pat_number = ? 
                    ORDER BY d.dispense_date DESC";
$stmt_dispensed = $mysqli->prepare($query_dispensed);
$stmt_dispensed->bind_param('s', $pat_number);
$stmt_dispensed->execute();
$res_dispensed = $stmt_dispensed->get_result();

// Handle update of dispensed drug
if (isset($_POST['update_drug'])) {
    $id = intval($_POST['id']);
    $quantity = intval($_POST['quantity']);
    $discount = floatval($_POST['discount']);
    $phar_id = intval($_POST['phar_id']);
    
    // Validate inputs
    if ($quantity <= 0) {
        $err = "Quantity must be greater than 0";
    } else {
        // Get drug price
        $price_query = "SELECT phar_price_unit, phar_name, CAST(phar_qty AS UNSIGNED) as stock_qty FROM his_pharmaceuticals WHERE phar_id = ?";
        $price_stmt = $mysqli->prepare($price_query);
        $price_stmt->bind_param('i', $phar_id);
        $price_stmt->execute();
        $price_result = $price_stmt->get_result();
        $drug = $price_result->fetch_object();
        
        if ($drug) {
            $amount = $drug->phar_price_unit * $quantity;
            $discount_amount = ($discount / 100) * $amount;
            $final_amount = $amount - $discount_amount;
            
            $update_query = "UPDATE his_dispensed_drugs SET 
                             quantity_dispensed = ?, 
                             discount = ?, 
                             amount = ?, 
                             final_amount = ? 
                             WHERE id = ?";
            $update_stmt = $mysqli->prepare($update_query);
            $update_stmt->bind_param('idddi', $quantity, $discount, $amount, $final_amount, $id);
            
            if ($update_stmt->execute()) {
                $success = "Drug record updated successfully!";
            } else {
                $err = "Error updating record: " . $update_stmt->error;
            }
            $update_stmt->close();
        } else {
            $err = "Drug not found!";
        }
        $price_stmt->close();
    }
}

// Handle delete of dispensed drug
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $delete_query = "DELETE FROM his_dispensed_drugs WHERE id = ?";
    $delete_stmt = $mysqli->prepare($delete_query);
    $delete_stmt->bind_param('i', $id);
    
    if ($delete_stmt->execute()) {
        $success = "Drug record deleted successfully!";
        header("Location: his_admin_update_single_pres.php?pat_number=" . urlencode($pat_number));
        exit;
    } else {
        $err = "Error deleting record: " . $delete_stmt->error;
    }
    $delete_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include('assets/inc/head.php'); ?>

<body>
    <div id="wrapper">
        <?php include('assets/inc/nav.php'); ?>
        <?php include('assets/inc/sidebar.php'); ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">

                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title">
                                    Edit Dispensed Drugs - <?php echo htmlspecialchars($patient->pat_fname . ' ' . $patient->pat_lname); ?> 
                                    <small>(<?php echo htmlspecialchars($patient->pat_number); ?>)</small>
                                </h4>
                                <div class="page-title-right">
                                    <a href="his_admin_view_single_pres.php?pat_number=<?php echo urlencode($pat_number); ?>" class="btn btn-primary btn-sm">
                                        <i class="mdi mdi-eye"></i> View Details
                                    </a>
                                    <a href="his_admin_view_pres.php" class="btn btn-secondary btn-sm">
                                        <i class="mdi mdi-arrow-left"></i> Back
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Success/Error Messages -->
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

                    <!-- Patient Info Card -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Patient Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>Name:</strong><br>
                                            <?php echo htmlspecialchars($patient->pat_fname . " " . $patient->pat_lname); ?>
                                        </div>
                                        <div class="col-md-2">
                                            <strong>Patient #:</strong><br>
                                            <span class="badge badge-info"><?php echo htmlspecialchars($patient->pat_number); ?></span>
                                        </div>
                                        <div class="col-md-2">
                                            <strong>Age:</strong><br>
                                            <?php echo htmlspecialchars($patient->pat_age); ?> years
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Contact:</strong><br>
                                            <?php echo htmlspecialchars($patient->pat_phone ?? 'N/A'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dispensed Drugs Edit Table -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-warning text-white">
                                    <h5 class="mb-0">Edit Dispensed Medications</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($res_dispensed && $res_dispensed->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Drug Name</th>
                                                    <th>Quantity</th>
                                                    <th>Price/Unit</th>
                                                    <th>Amount</th>
                                                    <th>Discount (%)</th>
                                                    <th>Final Amount</th>
                                                    <th>Date Dispensed</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $cnt = 1;
                                                while ($drug = $res_dispensed->fetch_object()): 
                                                ?>
                                                <tr>
                                                    <td><?php echo $cnt++; ?></td>
                                                    <td><strong><?php echo htmlspecialchars($drug->phar_name); ?></strong></td>
                                                    <td>
                                                        <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to update this record?');">
                                                            <input type="hidden" name="id" value="<?php echo $drug->id; ?>">
                                                            <input type="hidden" name="phar_id" value="<?php echo $drug->phar_id; ?>">
                                                            <input type="number" name="quantity" value="<?php echo $drug->quantity_dispensed; ?>" 
                                                                   min="1" class="form-control form-control-sm" style="width: 70px; display: inline;" required>
                                                    </td>
                                                    <td>$<?php echo number_format($drug->phar_price_unit ?? 0, 2); ?></td>
                                                    <td>$<?php echo number_format($drug->amount, 2); ?></td>
                                                    <td>
                                                            <input type="number" name="discount" value="<?php echo $drug->discount; ?>" 
                                                                   min="0" max="100" step="0.01" class="form-control form-control-sm" style="width: 70px; display: inline;">
                                                    </td>
                                                    <td><strong>$<?php echo number_format($drug->final_amount, 2); ?></strong></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($drug->dispense_date)); ?></td>
                                                    <td>
                                                            <button type="submit" name="update_drug" class="btn btn-success btn-sm" title="Update">
                                                                <i class="mdi mdi-content-save"></i>
                                                            </button>
                                                        </form>
                                                        <a href="?pat_number=<?php echo urlencode($pat_number); ?>&delete_id=<?php echo $drug->id; ?>" 
                                                           class="btn btn-danger btn-sm" title="Delete"
                                                           onclick="return confirm('Are you sure you want to delete this record?');">
                                                            <i class="mdi mdi-delete"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="mdi mdi-information"></i> No dispensed drugs found for this patient.
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Back Button -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <a href="his_admin_view_single_pres.php?pat_number=<?php echo urlencode($pat_number); ?>" class="btn btn-secondary">
                                <i class="mdi mdi-arrow-left"></i> Back to View Details
                            </a>
                        </div>
                    </div>

                </div> <!-- container -->
            </div> <!-- content -->

            <?php include('assets/inc/footer.php'); ?>
        </div>
    </div>

    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
</body>
</html>