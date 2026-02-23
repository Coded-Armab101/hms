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

// Fetch last prescription for this patient
$query_last_pres = "SELECT pres_ins, pres_date FROM his_prescriptions 
                    WHERE pat_number = ? 
                    ORDER BY pres_date DESC LIMIT 3";
$stmt_last_pres = $mysqli->prepare($query_last_pres);
$stmt_last_pres->bind_param('s', $pat_number);
$stmt_last_pres->execute();
$res_last_pres = $stmt_last_pres->get_result();
$last_prescriptions = array();
while ($row_pres = $res_last_pres->fetch_assoc()) {
    $last_prescriptions[] = $row_pres;
}

// Function to deduct stock quantity
function deductStock($mysqli, $phar_id, $quantity_dispensed) {
    // Cast phar_qty to integer since it might be stored as string
    $query_stock = "SELECT CAST(phar_qty AS UNSIGNED) as phar_qty FROM his_pharmaceuticals WHERE phar_id = ?";
    $stmt_stock = $mysqli->prepare($query_stock);
    $stmt_stock->bind_param('i', $phar_id);
    $stmt_stock->execute();
    $res_stock = $stmt_stock->get_result();
    $row_stock = $res_stock->fetch_assoc();
    
    if ($row_stock && $row_stock['phar_qty'] >= $quantity_dispensed) {
        $new_qty = $row_stock['phar_qty'] - $quantity_dispensed;
        $query_update = "UPDATE his_pharmaceuticals SET phar_qty = ? WHERE phar_id = ?";
        $stmt_update = $mysqli->prepare($query_update);
        $stmt_update->bind_param('ii', $new_qty, $phar_id);
        return $stmt_update->execute();
    }
    return false;
}

// Handle dispensing all selected drugs
if (isset($_POST['dispense_all'])) {
    $selected_drugs = json_decode($_POST['selected_drugs'], true);
    $success_count = 0;
    $error_count = 0;
    $errors = [];
    
    // Fetch pat_id
    $query_pat = "SELECT pat_id FROM his_patients WHERE pat_number=?";
    $stmt_pat = $mysqli->prepare($query_pat);
    $stmt_pat->bind_param('s', $pat_number);
    $stmt_pat->execute();
    $res_pat = $stmt_pat->get_result();
    $row_pat = $res_pat->fetch_assoc();
    $pat_id = $row_pat['pat_id'];
    
    if ($pat_id && !empty($selected_drugs)) {
        foreach ($selected_drugs as $drug) {
            // Start transaction
            $mysqli->begin_transaction();
            
            try {
                // 1. Insert into his_dispensed_drugs table
                $query_insert = "INSERT INTO his_dispensed_drugs 
                                (pat_id, pat_number, phar_id, quantity_dispensed, discount, amount, final_amount, dispensed_by) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_insert = $mysqli->prepare($query_insert);
                $stmt_insert->bind_param('isiiidds', 
                    $pat_id, 
                    $pat_number, 
                    $drug['phar_id'], 
                    $drug['quantity'], 
                    $drug['discount'], 
                    $drug['amount'], 
                    $drug['final_amount'],
                    $aid
                );
                
                if (!$stmt_insert->execute()) {
                    throw new Exception("Failed to insert dispensed drug: " . $stmt_insert->error);
                }
                $dispensed_id = $stmt_insert->insert_id;
                $stmt_insert->close();
                
                // 2. Deduct stock
                if (!deductStock($mysqli, $drug['phar_id'], $drug['quantity'])) {
                    throw new Exception("Failed to deduct stock for " . $drug['name'] . ". Insufficient stock.");
                }
                
                // 3. Insert into his_patient_bills - Fixed version without service_name
                $query_bill = "INSERT INTO his_patient_bills 
                               (pat_id, pat_number, bill_type, bill_details, bill_amount, discount, final_amount, status, date_generated) 
                               VALUES (?, ?, 'Pharmaceuticals', ?, ?, ?, ?, 'Unpaid', NOW())";
                $stmt_bill = $mysqli->prepare($query_bill);
                $bill_details = $drug['name'] . " - Qty: " . $drug['quantity'];
                $stmt_bill->bind_param('issddd', 
                    $pat_id, 
                    $pat_number, 
                    $bill_details, 
                    $drug['amount'], 
                    $drug['discount'], 
                    $drug['final_amount']
                );
                
                if (!$stmt_bill->execute()) {
                    throw new Exception("Failed to create bill: " . $stmt_bill->error);
                }
                $bill_id = $stmt_bill->insert_id;
                $stmt_bill->close();
                
                $mysqli->commit();
                $success_count++;
                
            } catch (Exception $e) {
                $mysqli->rollback();
                $error_count++;
                $errors[] = $e->getMessage();
            }
        }
    }
    
    // Show result message
    $message = "$success_count drug(s) dispensed successfully!";
    if ($error_count > 0) {
        $message .= " $error_count drug(s) failed: " . implode(", ", $errors);
    }
    
    echo "<script>
        alert('$message');
        window.location.href = 'his_admin_view_single_pres.php?pat_number=$pat_number';
    </script>";
    exit;
}

// Get available drugs with stock > 0
$query_drugs = "SELECT *, CAST(phar_qty AS UNSIGNED) as stock_qty 
                FROM his_pharmaceuticals 
                WHERE CAST(phar_qty AS UNSIGNED) > 0 
                ORDER BY phar_name";
$stmt_drugs = $mysqli->prepare($query_drugs);
$stmt_drugs->execute();
$res_drugs = $stmt_drugs->get_result();
$drugs = [];
while ($drug = $res_drugs->fetch_assoc()) {
    $drugs[] = $drug;
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include('assets/inc/head.php'); ?>

<style>
    .card {
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .card-header {
        font-weight: bold;
    }
    .badge-info {
        background-color: #17a2b8;
        color: white;
        padding: 5px 10px;
    }
    .table th {
        background-color: #f8f9fa;
    }
    .btn-action {
        margin: 0 2px;
    }
    .total-row {
        font-weight: bold;
        background-color: #e9ecef;
    }
    .stock-warning {
        color: #dc3545;
        font-size: 0.85em;
        margin-top: 5px;
    }
</style>

<body>
    <div id="wrapper">
        <?php include('assets/inc/nav.php'); ?>
        <?php include("assets/inc/sidebar.php"); ?>
        
        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    
                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title">
                                    <i class="mdi mdi-pill"></i> Dispense Drugs - 
                                    <?php echo htmlspecialchars($patient->pat_fname . ' ' . $patient->pat_lname); ?>
                                </h4>
                                <div class="page-title-right">
                                    <a href="his_admin_view_single_pres.php?pat_number=<?php echo urlencode($pat_number); ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="mdi mdi-eye"></i> View Details
                                    </a>
                                    <a href="his_admin_view_pres.php" class="btn btn-secondary btn-sm">
                                        <i class="mdi mdi-arrow-left"></i> Back
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Left Column: Dispense Drug Form -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="mdi mdi-cart-plus"></i> Dispense New Medication</h5>
                                </div>
                                <div class="card-body">
                                    
                                    <!-- Drug Selection Form -->
                                    <div class="form-group">
                                        <label class="font-weight-bold">Select Drug</label>
                                        <select id="phar_name" class="form-control select2">
                                            <option value="">-- Choose Drug --</option>
                                            <?php foreach ($drugs as $drug): ?>
                                                <option value="<?= $drug['phar_id'] ?>"
                                                        data-name="<?= htmlspecialchars($drug['phar_name']) ?>"
                                                        data-price="<?= $drug['phar_price_unit'] ?? 0 ?>"
                                                        data-category="<?= htmlspecialchars($drug['phar_cat'] ?? '') ?>"
                                                        data-vendor="<?= htmlspecialchars($drug['phar_vendor'] ?? '') ?>"
                                                        data-attribute="<?= htmlspecialchars($drug['phar_attribute'] ?? '') ?>"
                                                        data-quantity="<?= $drug['stock_qty'] ?>">
                                                    <?= htmlspecialchars($drug['phar_name']) ?> - 
                                                    ₦<?= number_format($drug['phar_price_unit'] ?? 0, 2) ?> 
                                                    (Stock: <?= $drug['stock_qty'] ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Category</label>
                                                <input type="text" id="phar_cat" class="form-control" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Vendor</label>
                                                <input type="text" id="phar_vendor" class="form-control" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Attribute</label>
                                                <input type="text" id="phar_attribute" class="form-control" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Price/Unit (₦)</label>
                                                <input type="number" id="phar_price_unit" class="form-control" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Available Stock</label>
                                                <input type="number" id="phar_qty" class="form-control" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Quantity to Dispense</label>
                                                <input type="number" id="size" class="form-control" min="1" value="1">
                                                <small id="stock-warning" class="stock-warning" style="display:none;"></small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Amount (₦)</label>
                                                <input type="text" id="amount" class="form-control" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Discount (%)</label>
                                                <input type="number" id="discount" class="form-control" min="0" max="100" value="0">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="font-weight-bold">Final Amount (₦)</label>
                                                <input type="text" id="final_amount" class="form-control form-control-lg text-primary font-weight-bold" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="button" id="addDrugBtn" class="btn btn-success btn-lg btn-block">
                                        <i class="mdi mdi-plus-circle"></i> Add to Dispense List
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Patient Details & Last Prescription -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="mdi mdi-account"></i> Patient Details</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <th width="40%">Name:</th>
                                            <td><strong><?php echo htmlspecialchars($patient->pat_fname . " " . $patient->pat_lname); ?></strong></td>
                                        </tr>
                                        <tr>
                                            <th>Patient No:</th>
                                            <td><span class="badge badge-info"><?php echo htmlspecialchars($patient->pat_number); ?></span></td>
                                        </tr>
                                        <tr>
                                            <th>Age/Sex:</th>
                                            <td><?php echo htmlspecialchars($patient->pat_age); ?> / <?php echo htmlspecialchars($patient->pat_sex ?? 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Patient Type:</th>
                                            <td><?php echo htmlspecialchars($patient->pat_type); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Phone:</th>
                                            <td><?php echo htmlspecialchars($patient->pat_phone ?? 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Address:</th>
                                            <td><?php echo htmlspecialchars($patient->pat_addr); ?></td>
                                        </tr>
                                    </table>
                                    
                                    <hr>
                                    
                                    <h5 class="header-title">
                                        <i class="mdi mdi-file-document"></i> Recent Prescriptions
                                    </h5>
                                    <?php if (!empty($last_prescriptions)) : ?>
                                        <div class="list-group">
                                            <?php foreach ($last_prescriptions as $pres) : ?>
                                                <div class="list-group-item p-2">
                                                    <div><?php echo htmlspecialchars(substr($pres['pres_ins'], 0, 50)) . '...'; ?></div>
                                                    <small class="text-muted">
                                                        <i class="mdi mdi-calendar"></i> 
                                                        <?php echo date('d-m-Y', strtotime($pres['pres_date'])); ?>
                                                    </small>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else : ?>
                                        <p class="text-muted">No recent prescriptions found.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Selected Drugs Table -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-warning text-white">
                                    <h5 class="mb-0"><i class="mdi mdi-format-list-bulleted"></i> Drugs to Dispense</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover" id="selected-drugs-table">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Drug Name</th>
                                                    <th>Qty</th>
                                                    <th>Price</th>
                                                    <th>Amount</th>
                                                    <th>Disc%</th>
                                                    <th>Final</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="selected-drugs-body">
                                                <tr>
                                                    <td colspan="8" class="text-center text-muted py-4">
                                                        <i class="mdi mdi-information"></i> No drugs selected
                                                    </td>
                                                </tr>
                                            </tbody>
                                            <tfoot class="total-row">
                                                <tr>
                                                    <th colspan="6" class="text-right">Total Amount:</th>
                                                    <th id="total-amount">₦ 0.00</th>
                                                    <th></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    
                                    <form method="post" id="dispenseForm" class="mt-3">
                                        <input type="hidden" name="selected_drugs" id="selected_drugs_input">
                                        <button type="submit" name="dispense_all" id="dispenseAllBtn" 
                                                class="btn btn-primary btn-lg btn-block" disabled>
                                            <i class="mdi mdi-cart-check"></i> Dispense All Selected Drugs
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div> <!-- container -->
            </div> <!-- content -->
            
            <?php include('assets/inc/footer.php'); ?>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
    
    <script>
        // Array to store selected drugs
        let selectedDrugs = [];
        
        // Initialize select2 if available
        $(document).ready(function() {
            if ($.fn.select2) {
                $('#phar_name').select2({
                    placeholder: '-- Search and Select Drug --',
                    width: '100%'
                });
            }
        });
        
        // Update drug details when selected
        document.getElementById("phar_name").addEventListener("change", function () {
            let selected = this.options[this.selectedIndex];
            
            if (this.value) {
                document.getElementById("phar_cat").value = selected.getAttribute("data-category") || '';
                document.getElementById("phar_vendor").value = selected.getAttribute("data-vendor") || '';
                document.getElementById("phar_attribute").value = selected.getAttribute("data-attribute") || '';
                document.getElementById("phar_price_unit").value = selected.getAttribute("data-price") || 0;
                document.getElementById("phar_qty").value = selected.getAttribute("data-quantity") || 0;
                calculateAmount();
                
                // Set max quantity
                document.getElementById("size").max = selected.getAttribute("data-quantity") || 0;
            } else {
                // Clear fields
                document.getElementById("phar_cat").value = '';
                document.getElementById("phar_vendor").value = '';
                document.getElementById("phar_attribute").value = '';
                document.getElementById("phar_price_unit").value = '';
                document.getElementById("phar_qty").value = '';
                document.getElementById("size").value = '1';
                document.getElementById("discount").value = '0';
                document.getElementById("amount").value = '';
                document.getElementById("final_amount").value = '';
            }
        });

        // Calculate amount when quantity or discount changes
        document.getElementById("size").addEventListener("input", function() {
            validateQuantity();
            calculateAmount();
        });
        
        document.getElementById("discount").addEventListener("input", calculateAmount);

        function validateQuantity() {
            let quantity = parseInt(document.getElementById("size").value) || 0;
            let stock = parseInt(document.getElementById("phar_qty").value) || 0;
            let warning = document.getElementById("stock-warning");
            
            if (quantity > stock && stock > 0) {
                warning.style.display = "block";
                warning.innerHTML = "⚠️ Only " + stock + " units available!";
            } else {
                warning.style.display = "none";
            }
        }

        function calculateAmount() {
            let price = parseFloat(document.getElementById("phar_price_unit").value) || 0;
            let quantity = parseInt(document.getElementById("size").value) || 0;
            let amount = price * quantity;
            document.getElementById("amount").value = "₦ " + amount.toFixed(2);
            
            let discount = parseFloat(document.getElementById("discount").value) || 0;
            let discountAmount = (discount / 100) * amount;
            let finalAmount = amount - discountAmount;
            document.getElementById("final_amount").value = "₦ " + finalAmount.toFixed(2);
            
            return { amount, finalAmount };
        }

        // Add drug to list
        document.getElementById("addDrugBtn").addEventListener("click", function() {
            let select = document.getElementById("phar_name");
            let selected = select.options[select.selectedIndex];
            
            if (!select.value) {
                alert("⚠️ Please select a drug");
                return;
            }
            
            let drugId = select.value;
            let drugName = selected.getAttribute("data-name");
            let price = parseFloat(selected.getAttribute("data-price")) || 0;
            let quantity = parseInt(document.getElementById("size").value) || 1;
            let discount = parseFloat(document.getElementById("discount").value) || 0;
            let amount = price * quantity;
            let finalAmount = amount - ((discount / 100) * amount);
            let stock = parseInt(selected.getAttribute("data-quantity")) || 0;
            
            // Validate
            if (quantity <= 0) {
                alert("⚠️ Quantity must be greater than 0");
                return;
            }
            
            if (quantity > stock) {
                alert(`⚠️ Only ${stock} units available in stock!`);
                return;
            }
            
            // Check if drug already in list
            let existing = selectedDrugs.find(item => item.phar_id === drugId);
            if (existing) {
                if (confirm(`"${drugName}" already in list. Add ${quantity} more?`)) {
                    existing.quantity += quantity;
                    existing.amount = existing.price * existing.quantity;
                    let existingDiscountAmount = (existing.discount / 100) * existing.amount;
                    existing.final_amount = existing.amount - existingDiscountAmount;
                } else {
                    return;
                }
            } else {
                selectedDrugs.push({
                    phar_id: drugId,
                    name: drugName,
                    quantity: quantity,
                    price: price,
                    amount: amount,
                    discount: discount,
                    final_amount: finalAmount
                });
            }
            
            updateSelectedDrugsTable();
            resetForm();
        });

        // Remove drug from list
        function removeDrug(index) {
            if (confirm('Are you sure you want to remove this drug?')) {
                selectedDrugs.splice(index, 1);
                updateSelectedDrugsTable();
            }
        }

        // Reset form fields
        function resetForm() {
            let select = document.getElementById("phar_name");
            select.value = "";
            if ($.fn.select2) {
                $(select).trigger('change');
            }
            document.getElementById("size").value = "1";
            document.getElementById("discount").value = "0";
            document.getElementById("phar_cat").value = "";
            document.getElementById("phar_vendor").value = "";
            document.getElementById("phar_attribute").value = "";
            document.getElementById("phar_price_unit").value = "";
            document.getElementById("phar_qty").value = "";
            document.getElementById("amount").value = "";
            document.getElementById("final_amount").value = "";
            document.getElementById("stock-warning").style.display = "none";
        }

        // Update the selected drugs table
        function updateSelectedDrugsTable() {
            let tbody = document.getElementById("selected-drugs-body");
            let total = 0;
            
            if (selectedDrugs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4"><i class="mdi mdi-information"></i> No drugs selected</td></tr>';
                document.getElementById("dispenseAllBtn").disabled = true;
                document.getElementById("selected_drugs_input").value = "";
                document.getElementById("total-amount").innerHTML = "₦ 0.00";
                return;
            }
            
            let html = "";
            selectedDrugs.forEach((drug, index) => {
                total += drug.final_amount;
                html += `<tr>
                    <td>${index + 1}</td>
                    <td><strong>${drug.name}</strong></td>
                    <td>${drug.quantity}</td>
                    <td>₦ ${drug.price.toFixed(2)}</td>
                    <td>₦ ${drug.amount.toFixed(2)}</td>
                    <td>${drug.discount}%</td>
                    <td class="font-weight-bold text-primary">₦ ${drug.final_amount.toFixed(2)}</td>
                    <td>
                        <button onclick="removeDrug(${index})" class="btn btn-danger btn-sm" title="Remove">
                            <i class="mdi mdi-delete"></i>
                        </button>
                    </td>
                </tr>`;
            });
            
            tbody.innerHTML = html;
            document.getElementById("total-amount").innerHTML = "₦ " + total.toFixed(2);
            document.getElementById("dispenseAllBtn").disabled = false;
            document.getElementById("selected_drugs_input").value = JSON.stringify(selectedDrugs);
        }
        
        // Confirmation before dispense
        document.getElementById("dispenseForm").addEventListener("submit", function(e) {
            if (selectedDrugs.length === 0) {
                e.preventDefault();
                alert("No drugs selected to dispense!");
                return;
            }
            
            let total = selectedDrugs.reduce((sum, drug) => sum + drug.final_amount, 0);
            if (!confirm(`Are you sure you want to dispense ${selectedDrugs.length} drug(s) totaling ₦ ${total.toFixed(2)}?`)) {
                e.preventDefault();
            }
        });
    </script>
    
</body>
</html>