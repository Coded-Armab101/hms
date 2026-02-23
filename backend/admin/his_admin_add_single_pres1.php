<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$aid = $_SESSION['ad_id'];

// Get prescription details from URL
$pat_number = $_GET['pat_number'];
$pres_id = $_GET['pres_id'];

// Fetch prescription details
$pres_query = "SELECT * FROM his_prescriptions WHERE pres_id = ? AND pat_number = ?";
$pres_stmt = $mysqli->prepare($pres_query);
$pres_stmt->bind_param('is', $pres_id, $pat_number);
$pres_stmt->execute();
$pres_res = $pres_stmt->get_result();
$pres_details = $pres_res->fetch_object();

// Fetch available drugs from inventory
$drugs_query = "SELECT * FROM his_pharmaceuticals WHERE drug_qty > 0 ORDER BY drug_name ASC";
$drugs_stmt = $mysqli->prepare($drugs_query);
$drugs_stmt->execute();
$drugs_res = $drugs_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<?php include('assets/inc/head.php');?>

<body>
    <div id="wrapper">
        <?php include('assets/inc/nav.php');?>
        <?php include("assets/inc/sidebar.php");?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Pharmacy</a></li>
                                        <li class="breadcrumb-item"><a href="his_pharm_view_prescriptions.php">Prescriptions</a></li>
                                        <li class="breadcrumb-item active">Dispense Multiple Drugs</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Dispense Multiple Drugs</h4>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <!-- Patient Info Card -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card-box">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="text-uppercase mt-0 bg-light p-2">Patient Information</h5>
                                        <table class="table table-borderless table-sm">
                                            <tr>
                                                <th width="150">Patient Name:</th>
                                                <td><strong><?php echo htmlspecialchars($pres_details->pres_pat_name); ?></strong></td>
                                            </tr>
                                            <tr>
                                                <th>Patient Number:</th>
                                                <td><span class="badge badge-info"><?php echo htmlspecialchars($pres_details->pat_number); ?></span></td>
                                            </tr>
                                            <tr>
                                                <th>Ailment:</th>
                                                <td><?php echo htmlspecialchars($pres_details->pres_pat_ailment); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Prescription Date:</th>
                                                <td><?php echo date('d M Y', strtotime($pres_details->created_at)); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <h5 class="text-uppercase mt-0 bg-light p-2">Doctor's Prescription</h5>
                                        <div class="p-3 border rounded">
                                            <?php echo nl2br(htmlspecialchars($pres_details->prescription_text)); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Multiple Drugs Dispensing Form -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card-box">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="header-title mb-0">Dispense Multiple Drugs</h4>
                                    <button type="button" class="btn btn-success btn-sm" id="addMoreDrugs">
                                        <i class="fas fa-plus-circle"></i> Add Another Drug
                                    </button>
                                </div>

                                <form id="dispenseForm" method="POST" action="his_pharm_process_dispense.php">
                                    <input type="hidden" name="pres_id" value="<?php echo $pres_id; ?>">
                                    <input type="hidden" name="pat_number" value="<?php echo $pat_number; ?>">
                                    <input type="hidden" name="pat_name" value="<?php echo htmlspecialchars($pres_details->pres_pat_name); ?>">
                                    
                                    <div id="drugEntries">
                                        <!-- Drug Entry Template -->
                                        <div class="drug-entry card mb-3" id="entry1">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between">
                                                    <h6 class="text-primary">Drug #1</h6>
                                                    <button type="button" class="btn btn-danger btn-sm remove-entry" style="display: none;">
                                                        <i class="fas fa-trash"></i> Remove
                                                    </button>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-5">
                                                        <div class="form-group">
                                                            <label>Select Drug <span class="text-danger">*</span></label>
                                                            <select name="drug_id[]" class="form-control drug-select" required onchange="updateStockInfo(this)">
                                                                <option value="">-- Select Drug --</option>
                                                                <?php while($drug = $drugs_res->fetch_object()) { ?>
                                                                <option value="<?php echo $drug->drug_id; ?>" 
                                                                        data-stock="<?php echo $drug->drug_qty; ?>"
                                                                        data-price="<?php echo $drug->drug_price; ?>"
                                                                        data-name="<?php echo htmlspecialchars($drug->drug_name); ?>">
                                                                    <?php echo htmlspecialchars($drug->drug_name); ?> 
                                                                    (Stock: <?php echo $drug->drug_qty; ?>, 
                                                                    Price: $<?php echo number_format($drug->drug_price, 2); ?>)
                                                                </option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Dosage <span class="text-danger">*</span></label>
                                                            <input type="text" name="dosage[]" class="form-control" 
                                                                   placeholder="e.g., 500mg" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <div class="form-group">
                                                            <label>Quantity <span class="text-danger">*</span></label>
                                                            <input type="number" name="quantity[]" class="form-control quantity" 
                                                                   min="1" required onchange="checkStock(this)">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <div class="form-group">
                                                            <label>Duration</label>
                                                            <input type="text" name="duration[]" class="form-control" 
                                                                   placeholder="e.g., 7 days">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <small class="text-muted stock-info">Available Stock: -</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <small class="text-muted price-info">Unit Price: -</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Additional Information -->
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Dispensing Notes</label>
                                                <textarea name="dispensing_notes" class="form-control" rows="3" 
                                                          placeholder="Add any special instructions or notes..."></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Pharmacist/Admin Notes</label>
                                                <textarea name="pharmacist_notes" class="form-control" rows="3" 
                                                          placeholder="Internal notes..."></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="row mt-4">
                                        <div class="col-md-12">
                                            <button type="submit" name="dispense_multiple" class="btn btn-primary btn-lg">
                                                <i class="fas fa-check-circle"></i> Dispense All Drugs
                                            </button>
                                            <a href="his_pharm_view_prescriptions.php" class="btn btn-secondary btn-lg">
                                                <i class="fas fa-times"></i> Cancel
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <?php include('assets/inc/footer.php');?>
        </div>
    </div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/libs/footable/footable.all.min.js"></script>
    <script src="assets/js/app.min.js"></script>

    <script>
    let drugCount = 1;

    $(document).ready(function() {
        // Add more drug entries
        $("#addMoreDrugs").click(function() {
            drugCount++;
            let newEntry = $("#entry1").clone();
            newEntry.attr('id', 'entry' + drugCount);
            newEntry.find('h6').text('Drug #' + drugCount);
            newEntry.find('.remove-entry').show();
            newEntry.find('select').val('');
            newEntry.find('input').val('');
            newEntry.find('.stock-info').text('Available Stock: -');
            newEntry.find('.price-info').text('Unit Price: -');
            $("#drugEntries").append(newEntry);
        });

        // Remove drug entry
        $(document).on('click', '.remove-entry', function() {
            $(this).closest('.drug-entry').remove();
            updateDrugNumbers();
        });

        // Update stock info when drug is selected
        window.updateStockInfo = function(selectElement) {
            let entry = $(selectElement).closest('.drug-entry');
            let selected = $(selectElement).find(':selected');
            let stock = selected.data('stock');
            let price = selected.data('price');
            let drugName = selected.data('name');
            
            entry.find('.stock-info').text('Available Stock: ' + stock + ' units');
            entry.find('.price-info').text('Unit Price: $' + (price ? parseFloat(price).toFixed(2) : '0.00'));
        };

        // Check stock availability
        window.checkStock = function(inputElement) {
            let entry = $(inputElement).closest('.drug-entry');
            let quantity = parseInt($(inputElement).val()) || 0;
            let select = entry.find('.drug-select');
            let selected = select.find(':selected');
            let stock = parseInt(selected.data('stock')) || 0;
            
            if(quantity > stock) {
                alert('Warning: Requested quantity exceeds available stock!');
                $(inputElement).val(stock);
            }
        };

        // Update drug numbers after removal
        function updateDrugNumbers() {
            let count = 1;
            $('.drug-entry').each(function() {
                $(this).find('h6').text('Drug #' + count);
                count++;
            });
            drugCount = count - 1;
        }

        // Form validation
        $("#dispenseForm").submit(function(e) {
            let hasDrug = false;
            $('.drug-select').each(function() {
                if($(this).val() !== '') hasDrug = true;
            });
            
            if(!hasDrug) {
                alert('Please select at least one drug to dispense.');
                e.preventDefault();
                return false;
            }
            
            return confirm('Are you sure you want to dispense these drugs?');
        });
    });
    </script>

    <style>
    .drug-entry {
        border-left: 4px solid #007bff;
        transition: all 0.3s ease;
    }
    .drug-entry:hover {
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .remove-entry {
        transition: all 0.3s ease;
    }
    .remove-entry:hover {
        background-color: #dc3545;
        border-color: #dc3545;
    }
    </style>
</body>
</html>