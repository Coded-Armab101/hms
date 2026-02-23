<?php
session_start();
include('assets/inc/config.php');

$pat_number = $_GET['pat_number'];

// Fetch patient details
$query_patient = "SELECT * FROM his_patients WHERE pat_number=?";
$stmt_patient = $mysqli->prepare($query_patient);
$stmt_patient->bind_param('s', $pat_number);
$stmt_patient->execute();
$result_patient = $stmt_patient->get_result();
$patient = $result_patient->fetch_object();

// Fetch last prescription for this patient
$query_last_pres = "SELECT pres_ins, pres_date FROM his_prescriptions 
                    WHERE pat_number = ? 
                      AND DATE(pres_date) = (
                          SELECT DATE(MAX(pres_date)) FROM his_prescriptions WHERE pat_number = ?
                      )";
$stmt_last_pres = $mysqli->prepare($query_last_pres);
$stmt_last_pres->bind_param('ss', $pat_number, $pat_number);
$stmt_last_pres->execute();
$res_last_pres = $stmt_last_pres->get_result();
$last_prescriptions = array();
while ($row_pres = $res_last_pres->fetch_assoc()) {
    $last_prescriptions[] = $row_pres;
}

// Function to deduct stock quantity
function deductStock($mysqli, $phar_id, $quantity_dispensed) {
    $query_stock = "SELECT phar_qty FROM his_pharmaceuticals WHERE phar_id = ?";
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

// Handle form submission
if (isset($_POST['dispense_drug'])) {
    $phar_id = $_POST['phar_name']; // Drug ID
    $quantity_dispensed = $_POST['size'];
    $discount = $_POST['discount'] ?: 0; // Default to 0 if empty
    $amount = $_POST['amount'];
    $final_amount = $_POST['final_amount'];

    // Fetch pat_id based on pat_number
    $query_pat = "SELECT pat_id FROM his_patients WHERE pat_number=?";
    $stmt_pat = $mysqli->prepare($query_pat);
    $stmt_pat->bind_param('s', $pat_number);
    $stmt_pat->execute();
    $res_pat = $stmt_pat->get_result();
    $row_pat = $res_pat->fetch_assoc();
    $pat_id = $row_pat['pat_id'];

    if ($pat_id) {
        // Insert into his_dispensed_drugs table
        $query_insert = "INSERT INTO his_dispensed_drugs (pat_id, pat_number, phar_id, quantity_dispensed, discount, amount, final_amount) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $mysqli->prepare($query_insert);
        $stmt_insert->bind_param('isiiidd', $pat_id, $pat_number, $phar_id, $quantity_dispensed, $discount, $amount, $final_amount);

        if ($stmt_insert->execute()) {
            // Deduct stock after successful insertion
            if (deductStock($mysqli, $phar_id, $quantity_dispensed)) {
                echo "<script>alert('Drug dispensed successfully, stock updated!'); window.location.href='his_admin_add_presc.php';</script>";
            } else {
                echo "<script>alert('Drug dispensed, but stock update failed.');</script>";
            }
        } else {
            echo "<script>alert('Error dispensing drug. Try again.');</script>";
        }
    } else {
        echo "<script>alert('Patient ID not found.');</script>";
    }
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
                    <div class="row">
                        <!-- Left Column: Dispense Drug Form -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="header-title">Dispense Drug</h4>
                                    <form method="post">
                                        <div class="form-group">
                                            <label>Drug Name</label>
                                            <select name="phar_name" id="phar_name" class="form-control" required>
                                                <option value="">Select Drug</option>
                                                <?php
                                                $query_drugs = "SELECT * FROM his_pharmaceuticals";
                                                $stmt_drugs = $mysqli->prepare($query_drugs);
                                                $stmt_drugs->execute();
                                                $res_drugs = $stmt_drugs->get_result();
                                                while ($drug = $res_drugs->fetch_object()) {
                                                    echo "<option value='{$drug->phar_id}' 
                                                                data-price='{$drug->phar_price_unit}' 
                                                                data-vendor='{$drug->phar_vendor}' 
                                                                data-category='{$drug->phar_cat}'
                                                                data-attribute='{$drug->phar_attribute}'
                                                                data-quantity='{$drug->phar_qty}'>
                                                            {$drug->phar_name}
                                                          </option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Category</label>
                                            <input type="text" id="phar_cat" name="phar_cat" class="form-control" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>Vendor</label>
                                            <input type="text" id="phar_vendor" name="phar_vendor" class="form-control" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>Attribute</label>
                                            <input type="text" id="phar_attribute" name="phar_attribute" class="form-control" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>Price/Unit</label>
                                            <input type="number" id="phar_price_unit" name="phar_price_unit" class="form-control" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>Quantity in Stock</label>
                                            <input type="number" id="phar_qty" name="phar_qty" class="form-control" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>Quantity to Dispense</label>
                                            <input type="number" id="size" name="size" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Amount (Naira)</label>
                                            <input type="text" id="amount" name="amount" class="form-control" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>Discount (%)</label>
                                            <input type="number" id="discount" name="discount" class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label>Final Amount (Naira)</label>
                                            <input type="text" id="final_amount" name="final_amount" class="form-control" readonly>
                                        </div>
                                        <button type="submit" name="dispense_drug" class="btn btn-primary">Dispense Drug</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Patient Details & Last Prescription -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="header-title">Patient Details</h4>
                                    <p><strong>Name:</strong> <?php echo $patient->pat_fname . " " . $patient->pat_lname; ?></p>
                                    <p><strong>Age:</strong> <?php echo $patient->pat_age; ?></p>
                                    <p><strong>Number:</strong> <?php echo $patient->pat_number; ?></p>
                                    <p><strong>Address:</strong> <?php echo $patient->pat_addr; ?></p>
                                    <p><strong>Type:</strong> <?php echo $patient->pat_type; ?></p>
                                    <p><strong>Ailment:</strong> <?php echo $patient->pat_ailment; ?></p>
                                    <hr>
                                    <h4 class="header-title">Last Day Prescription</h4>
                                    <?php if (!empty($last_prescriptions)) : ?>
                                        <ul>
                                            <?php foreach ($last_prescriptions as $pres) : ?>
                                                <li><?php echo htmlspecialchars($pres['pres_ins']); ?><br>
                                                    <small><?php echo date('d-m-Y', strtotime($pres['pres_date'])); ?></small>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else : ?>
                                        <p>No prescription found for the last day.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <!-- End Right Column -->
                    </div>
                </div>
            </div>
            <?php include('assets/inc/footer.php'); ?>
        </div>
    </div>

    <script>
        document.getElementById("phar_name").addEventListener("change", function () {
            let selected = this.options[this.selectedIndex];
            document.getElementById("phar_cat").value = selected.getAttribute("data-category");
            document.getElementById("phar_vendor").value = selected.getAttribute("data-vendor");
            document.getElementById("phar_attribute").value = selected.getAttribute("data-attribute");
            document.getElementById("phar_price_unit").value = selected.getAttribute("data-price");
            document.getElementById("phar_qty").value = selected.getAttribute("data-quantity");
        });

        document.getElementById("size").addEventListener("input", function () {
            let price = parseFloat(document.getElementById("phar_price_unit").value) || 0;
            let quantity = parseInt(this.value) || 0;
            let amount = price * quantity;
            document.getElementById("amount").value = amount;
            calculateFinalAmount();
        });

        document.getElementById("discount").addEventListener("input", function () {
            calculateFinalAmount();
        });

        function calculateFinalAmount() {
            let amount = parseFloat(document.getElementById("amount").value) || 0;
            let discount = parseFloat(document.getElementById("discount").value) || 0;
            let discountAmount = (discount / 100) * amount;
            document.getElementById("final_amount").value = amount - discountAmount;
        }
    </script>
</body>
</html>
