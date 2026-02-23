<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('assets/inc/config.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$pat_number = $_GET['pat_number'] ?? '';

if (isset($_POST['update_patient_presc'])) {
    $mysqli->begin_transaction();
    try {
        if (!empty($_POST['dispensed_drugs'])) {
            foreach ($_POST['dispensed_drugs'] as $dispensed_id => $values) {
                $pat_number = $values['pat_number'];
                $quantity_dispensed = $values['quantity_dispensed'];
                $discount = $values['discount'];
                $amount = $values['amount'];
                $final_amount = $values['final_amount'];

                // Check stock availability
                $query_stock = "SELECT phar_qty FROM his_pharmaceuticals WHERE phar_id = ?";
                $stmt_stock = $mysqli->prepare($query_stock);
                $stmt_stock->bind_param('i', $phar_id);
                $stmt_stock->execute();
                $result_stock = $stmt_stock->get_result();
                $row_stock = $result_stock->fetch_assoc();

                if ($row_stock['phar_qty'] < $quantity_dispensed) {
                    throw new Exception("Error: Not enough stock for drug ID $phar_id!");
                }

                // Update dispensed drugs
                $query_disp = "UPDATE his_dispensed_drugs 
                               SET quantity_dispensed = ?, discount = ?, amount = ?, final_amount = ?
                               WHERE id = ? AND pat_number = ?";
                $stmt2 = $mysqli->prepare($query_disp);
                $stmt2->bind_param('iddisi', $quantity_dispensed, $discount, $amount, $final_amount, $dispensed_id, $pat_number);
                $stmt2->execute();

                // Deduct stock
                $query_deduct = "UPDATE his_pharmaceuticals 
                                 SET phar_qty = phar_qty - ? 
                                 WHERE phar_id = ?";
                $stmt_deduct = $mysqli->prepare($query_deduct);
                $stmt_deduct->bind_param('ii', $quantity_dispensed, $phar_id);
                $stmt_deduct->execute();
            }
        }

        // Commit Transaction
        $mysqli->commit();
        $success = "Patient Prescription and Dispensed Drugs Updated Successfully";
    } catch (Exception $e) {
        $mysqli->rollback();
        $err = "Error Updating Records: " . $e->getMessage();
    }
}

// Fetch last 10 dispensed drugs
$ret = "SELECT d.*, p.phar_name, p.phar_qty 
        FROM his_dispensed_drugs d 
        JOIN his_pharmaceuticals p ON d.phar_id = p.phar_id 
        WHERE d.pat_number = ? 
        ORDER BY d.dispense_date DESC 
        LIMIT 10";
$stmt = $mysqli->prepare($ret);
$stmt->bind_param('s', $pat_number);
$stmt->execute();
$res = $stmt->get_result();
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
                                <h4 class="page-title">Update Patient Prescription</h4>
                            </div>
                        </div>
                    </div>     

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="header-title">Last 10 Dispensed Drugs</h4>

                                    <?php if (isset($success)) { ?>
                                        <div class="alert alert-success"><?php echo $success; ?></div>
                                    <?php } elseif (isset($err)) { ?>
                                        <div class="alert alert-danger"><?php echo $err; ?></div>
                                    <?php } ?>

                                    <form method="post">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Drug Name</th>
                                                        <th>Quantity Dispensed</th>
                                                        <th>Available Stock</th>
                                                        <th>Discount</th>
                                                        <th>Amount</th>
                                                        <th>Final Amount</th>
                                                        <th>Dispense Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($row = $res->fetch_object()) { ?>
                                                    <tr>
                                                        <td><?php echo $row->phar_name; ?></td>

                                                        <input type="hidden" name="dispensed_drugs[<?php echo $row->id; ?>][phar_id]" value="<?php echo $row->phar_id; ?>">

                                                        <td>
                                                            <input type="number" name="dispensed_drugs[<?php echo $row->id; ?>][quantity_dispensed]" 
                                                                   class="form-control" 
                                                                   value="<?php echo $row->quantity_dispensed; ?>" 
                                                                   min="1" max="<?php echo $row->phar_qty; ?>">
                                                        </td>
                                                        <td><?php echo $row->phar_qty; ?></td>
                                                        <td>
                                                            <input type="text" name="dispensed_drugs[<?php echo $row->id; ?>][discount]" 
                                                                   class="form-control" 
                                                                   value="<?php echo $row->discount; ?>">
                                                        </td>
                                                        <td>
                                                            <input type="text" name="dispensed_drugs[<?php echo $row->id; ?>][amount]" 
                                                                   class="form-control" 
                                                                   value="<?php echo $row->amount; ?>">
                                                        </td>
                                                        <td>
                                                            <input type="text" name="dispensed_drugs[<?php echo $row->id; ?>][final_amount]" 
                                                                   class="form-control" 
                                                                   value="<?php echo $row->final_amount; ?>">
                                                        </td>
                                                        <td><?php echo $row->dispense_date; ?></td>
                                                    </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <button type="submit" name="update_patient_presc" class="btn btn-primary">Update Prescription</button>
                                    </form>

                                </div> 
                            </div>
                        </div>
                    </div>
                    
                </div> 

            </div>

            <?php include('assets/inc/footer.php');?>

        </div>
        
    </div>

</body>
</html>
