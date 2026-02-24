<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$aid = $_SESSION['ad_id'];

// Get patient number from URL or POST
$pat_number = '';

// Check GET parameter first
if (isset($_GET['pat_number']) && !empty($_GET['pat_number'])) {
    $pat_number = $_GET['pat_number'];
}
// If not in GET, check POST
elseif (isset($_POST['pat_number']) && !empty($_POST['pat_number'])) {
    $pat_number = $_POST['pat_number'];
}
// If still not found, redirect back with error
else {
    $_SESSION['error'] = "Patient number is missing. Please select a patient first.";
    header("Location: his_record_view_presc.php");
    exit();
}

// Sanitize the patient number
$pat_number = trim($pat_number);

// Handle update of dispensed drug via AJAX/modal
if (isset($_POST['update_drug_modal'])) {
    $id = intval($_POST['id']);
    $quantity = intval($_POST['quantity']);
    $discount = floatval($_POST['discount']);
    $phar_id = intval($_POST['phar_id']);
    
    // Get drug price
    $price_query = "SELECT phar_price_unit FROM his_pharmaceuticals WHERE phar_id = ?";
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
            echo json_encode(['success' => true, 'message' => 'Drug record updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating record: ' . $update_stmt->error]);
        }
        $update_stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Drug not found!']);
    }
    $price_stmt->close();
    exit();
}

// Handle delete of dispensed drug
if (isset($_POST['delete_drug'])) {
    $id = intval($_POST['id']);
    $delete_query = "DELETE FROM his_dispensed_drugs WHERE id = ?";
    $delete_stmt = $mysqli->prepare($delete_query);
    $delete_stmt->bind_param('i', $id);
    
    if ($delete_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Drug record deleted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting record: ' . $delete_stmt->error]);
    }
    $delete_stmt->close();
    exit();
}

// Fetch patient details
$query_patient = "SELECT * FROM his_patients WHERE pat_number=?";
$stmt_patient = $mysqli->prepare($query_patient);
$stmt_patient->bind_param('s', $pat_number);
$stmt_patient->execute();
$result_patient = $stmt_patient->get_result();
$patient = $result_patient->fetch_object();

if (!$patient) {
    $_SESSION['error'] = "Patient not found with number: " . htmlspecialchars($pat_number);
    header("Location: his_record_view_presc.php");
    exit();
}

// Fetch prescription details for this patient
$query_prescription = "SELECT * FROM his_prescriptions WHERE pat_number = ? ORDER BY pres_date DESC LIMIT 1";
$stmt_prescription = $mysqli->prepare($query_prescription);
$stmt_prescription->bind_param('s', $pat_number);
$stmt_prescription->execute();
$res_prescription = $stmt_prescription->get_result();
$prescription = $res_prescription->fetch_object();

// Fetch all dispensed drugs for this patient
$query_dispensed = "SELECT d.*, p.phar_name, p.phar_price_unit, CAST(p.phar_qty AS UNSIGNED) as stock_qty
                    FROM his_dispensed_drugs d 
                    JOIN his_pharmaceuticals p ON d.phar_id = p.phar_id
                    WHERE d.pat_number = ? 
                    ORDER BY d.dispense_date DESC";
$stmt_dispensed = $mysqli->prepare($query_dispensed);
$stmt_dispensed->bind_param('s', $pat_number);
$stmt_dispensed->execute();
$res_dispensed = $stmt_dispensed->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<?php include('assets/inc/head.php'); ?>

<style>
    .edit-btn, .delete-btn {
        cursor: pointer;
        margin: 0 2px;
    }
    .modal-content {
        border-radius: 10px;
    }
    .modal-header {
        background-color: #007bff;
        color: white;
        border-radius: 10px 10px 0 0;
    }
    .close {
        color: white;
    }
    .close:hover {
        color: #f8f9fa;
    }
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
    }
</style>

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
                                    Patient: <?php echo htmlspecialchars($patient->pat_fname . ' ' . $patient->pat_lname); ?> (<?php echo htmlspecialchars($patient->pat_number); ?>)
                                </h4>
                                <div class="page-title-right">
                                    <a href="his_record_view_presc.php" class="btn btn-secondary btn-sm">
                                        <i class="mdi mdi-arrow-left"></i> Back to List
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Patient Details Card -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
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
                                        <div class="col-md-2">
                                            <strong>Gender:</strong><br>
                                            <?php echo htmlspecialchars($patient->pat_sex ?? 'N/A'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Prescription Details -->
                    <?php if ($prescription) { ?>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Prescription Details</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <p><strong>Diagnosis / Ailment:</strong> <?php echo htmlspecialchars($prescription->pres_pat_ailment ?: 'N/A'); ?></p>
                                            <p><strong>Instructions:</strong> <?php echo nl2br(htmlspecialchars($prescription->pres_ins ?: 'No instructions')); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <p><strong>Date Prescribed:</strong> <?php echo date('d/m/Y H:i', strtotime($prescription->pres_date)); ?></p>
                                            <p><strong>Status:</strong> 
                                                <span class="badge bg-<?php echo $prescription->pres_status == 'completed' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($prescription->pres_status ?: 'pending'); ?>
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>

                    <!-- Dispensed Drugs Table with Edit/Delete Icons -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Dispensed Medications</h5>
                                    <span class="badge badge-light">Total: <?php echo $res_dispensed->num_rows; ?></span>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover" id="drugs-table">
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
                                                <?php if ($res_dispensed && $res_dispensed->num_rows > 0) { 
                                                    $cnt = 1;
                                                    while ($drug = $res_dispensed->fetch_object()) { 
                                                ?>
                                                <tr id="drug-row-<?php echo $drug->id; ?>">
                                                    <td><?php echo $cnt++; ?></td>
                                                    <td><strong><?php echo htmlspecialchars($drug->phar_name); ?></strong></td>
                                                    <td><?php echo $drug->quantity_dispensed; ?></td>
                                                    <td>$<?php echo number_format($drug->phar_price_unit ?? 0, 2); ?></td>
                                                    <td>$<?php echo number_format($drug->amount, 2); ?></td>
                                                    <td><?php echo $drug->discount; ?>%</td>
                                                    <td><strong>$<?php echo number_format($drug->final_amount, 2); ?></strong></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($drug->dispense_date)); ?></td>
                                                    <td>
                                                        <button class="btn btn-warning btn-sm edit-btn" 
                                                                onclick="openEditModal(<?php echo $drug->id; ?>, <?php echo $drug->phar_id; ?>, '<?php echo htmlspecialchars($drug->phar_name); ?>', <?php echo $drug->quantity_dispensed; ?>, <?php echo $drug->discount; ?>, <?php echo $drug->phar_price_unit ?? 0; ?>, <?php echo $drug->stock_qty; ?>)">
                                                            <i class="mdi mdi-pencil"></i>
                                                        </button>
                                                        <button class="btn btn-danger btn-sm delete-btn" onclick="deleteDrug(<?php echo $drug->id; ?>)">
                                                            <i class="mdi mdi-delete"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php } 
                                                } else { ?>
                                                <tr>
                                                    <td colspan="9" class="text-center text-muted py-4">
                                                        <i class="mdi mdi-alert-circle"></i> No dispensed drugs found for this patient
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

                    <!-- Add New Drug Section -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <a href="his_record_add_single_pres.php?pat_number=<?php echo urlencode($pat_number); ?>" class="btn btn-primary">
                                        <i class="mdi mdi-plus"></i> Add New Dispensed Drug
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- container -->
            </div> <!-- content -->

            <?php include('assets/inc/footer.php'); ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Dispensed Drug</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editForm">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="phar_id" id="edit_phar_id">
                        <input type="hidden" name="price_unit" id="edit_price_unit">
                        
                        <div class="form-group">
                            <label>Drug Name</label>
                            <input type="text" class="form-control" id="edit_drug_name" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label>Available Stock</label>
                            <input type="text" class="form-control" id="edit_stock_qty" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label>Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="quantity" id="edit_quantity" min="1" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Discount (%)</label>
                            <input type="number" class="form-control" name="discount" id="edit_discount" min="0" max="100" step="0.01" value="0">
                        </div>
                        
                        <div class="form-group">
                            <label>Amount</label>
                            <input type="text" class="form-control" id="edit_amount" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label>Final Amount</label>
                            <input type="text" class="form-control" id="edit_final_amount" readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveChanges">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast container for notifications -->
    <div class="toast-container"></div>

    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Check if there's a success/error message in session and display it
        <?php if (isset($_SESSION['success'])): ?>
        showToast('success', '<?php echo $_SESSION['success']; ?>');
        <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
        showToast('error', '<?php echo $_SESSION['error']; ?>');
        <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    });

    // Calculate amount and final amount
    function calculateAmount() {
        var quantity = parseFloat($('#edit_quantity').val()) || 0;
        var price = parseFloat($('#edit_price_unit').val()) || 0;
        var discount = parseFloat($('#edit_discount').val()) || 0;
        
        var amount = quantity * price;
        var discountAmount = (discount / 100) * amount;
        var finalAmount = amount - discountAmount;
        
        $('#edit_amount').val('$' + amount.toFixed(2));
        $('#edit_final_amount').val('$' + finalAmount.toFixed(2));
    }
    
    // Open edit modal
    function openEditModal(id, phar_id, drug_name, quantity, discount, price, stock) {
        $('#edit_id').val(id);
        $('#edit_phar_id').val(phar_id);
        $('#edit_drug_name').val(drug_name);
        $('#edit_quantity').val(quantity);
        $('#edit_discount').val(discount);
        $('#edit_price_unit').val(price);
        $('#edit_stock_qty').val(stock + ' units available');
        
        calculateAmount();
        $('#editModal').modal('show');
    }
    
    // Handle form submission
    $('#editForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            id: $('#edit_id').val(),
            phar_id: $('#edit_phar_id').val(),
            quantity: $('#edit_quantity').val(),
            discount: $('#edit_discount').val(),
            update_drug_modal: true
        };
        
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast('success', response.message);
                    $('#editModal').modal('hide');
                    // Refresh the page after 1 second
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showToast('error', response.message);
                }
            },
            error: function(xhr, status, error) {
                showToast('error', 'An error occurred while updating: ' + error);
            }
        });
    });
    
    // Delete drug
    function deleteDrug(id) {
        if (confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: {
                    id: id,
                    delete_drug: true
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('success', response.message);
                        $('#drug-row-' + id).fadeOut(500, function() {
                            $(this).remove();
                            // Check if table is empty
                            if ($('#drugs-table tbody tr').length === 0) {
                                location.reload();
                            }
                        });
                    } else {
                        showToast('error', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    showToast('error', 'An error occurred while deleting: ' + error);
                }
            });
        }
    }
    
    // Show toast notification
    function showToast(type, message) {
        var bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
        var toastHtml = '<div class="toast show" role="alert" aria-live="assertive" aria-atomic="true" style="min-width: 300px;">' +
            '<div class="toast-header ' + bgClass + ' text-white">' +
            '<strong class="mr-auto">' + (type === 'success' ? 'Success' : 'Error') + '</strong>' +
            '<button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast" aria-label="Close">' +
            '<span aria-hidden="true">&times;</span>' +
            '</button>' +
            '</div>' +
            '<div class="toast-body">' + message + '</div>' +
            '</div>';
        
        $('.toast-container').html(toastHtml);
        $('.toast').toast({ delay: 3000 });
        $('.toast').toast('show');
        
        setTimeout(function() {
            $('.toast-container').empty();
        }, 3000);
    }
    
    // Recalculate on quantity or discount change
    $('#edit_quantity, #edit_discount').on('input', function() {
        calculateAmount();
    });
    
    // Validate quantity doesn't exceed stock
    $('#edit_quantity').on('change', function() {
        var quantity = parseInt($(this).val());
        var stockText = $('#edit_stock_qty').val();
        var stock = parseInt(stockText.split(' ')[0]);
        
        if (quantity > stock) {
            showToast('error', 'Quantity cannot exceed available stock (' + stock + ' units)');
            $(this).val(stock);
            calculateAmount();
        }
    });
    </script>
</body>
</html>