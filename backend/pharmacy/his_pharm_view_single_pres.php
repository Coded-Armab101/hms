<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
//$aid = $_SESSION['ad_id'];

$pat_number = $_GET['pat_number'];

// Fetch patient details
$query_patient = "SELECT * FROM his_patients WHERE pat_number=?";
$stmt_patient = $mysqli->prepare($query_patient);
$stmt_patient->bind_param('s', $pat_number);
$stmt_patient->execute();
$result_patient = $stmt_patient->get_result();
$patient = $result_patient->fetch_object();

// Fetch last day's dispensed drugs for this patient
$query_dispensed = "SELECT d.*, p.phar_name, p.phar_price_unit 
                    FROM his_dispensed_drugs d 
                    JOIN his_pharmaceuticals p ON d.phar_id = p.phar_id
                    WHERE d.pat_number = ? 
                      AND DATE(d.dispense_date) = (
                          SELECT DATE(MAX(dispense_date)) FROM his_dispensed_drugs WHERE pat_number = ?
                      )";
$stmt_dispensed = $mysqli->prepare($query_dispensed);
$stmt_dispensed->bind_param('ss', $pat_number, $pat_number);
$stmt_dispensed->execute();
$res_dispensed = $stmt_dispensed->get_result();
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
                                <h4 class="page-title">Drugs Dispensed on Last Recorded Date</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Patient Details -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h4>Patient Details</h4>
                                    <p><strong>Name:</strong> <?php echo $patient->pat_fname . " " . $patient->pat_lname; ?></p>
                                    <p><strong>Age:</strong> <?php echo $patient->pat_age; ?> years</p>
                                    <p><strong>Number:</strong> <?php echo $patient->pat_number; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dispensed Drugs Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4>Drugs Dispensed on the Last Recorded Date</h4>
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Drug Name</th>
                                                <th>Quantity</th>
                                                <th>Price/Unit</th>
                                                <th>Discount</th>
                                                <th>Total Amount</th>
                                                <th>Final Amount</th>
                                                <th>Date Dispensed</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($drug = $res_dispensed->fetch_object()) { ?>
                                                <tr>
                                                    <td><?php echo $drug->phar_name; ?></td>
                                                    <td><?php echo $drug->quantity_dispensed; ?></td>
                                                    <td><?php echo number_format($drug->phar_price_unit, 2); ?></td>
                                                    <td><?php echo number_format($drug->discount, 2); ?></td>
                                                    <td><?php echo number_format($drug->amount, 2); ?></td>
                                                    <td><?php echo number_format($drug->final_amount, 2); ?></td>
                                                    <td><?php echo $drug->dispense_date; ?></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
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
