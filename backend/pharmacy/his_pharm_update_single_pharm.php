<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$aid = $_SESSION['ad_id'];

// Update handler
if (isset($_POST['update_pharmaceutical'])) {
    // Get the unique identifier from GET
    $phar_bcode = $_GET['phar_bcode'];
    // Retrieve update values from POST
    $phar_name    = $_POST['phar_name'];
    $phar_desc    = $_POST['phar_desc'];
    $phar_qty     = $_POST['phar_qty'];
    $phar_cat     = $_POST['phar_cat'];
    $phar_vendor  = $_POST['phar_vendor'];
    $phar_attribute  = $_POST['phar_attribute'];
    $phar_unit  = $_POST['phar_unit'];
    $phar_price_unit  = $_POST['phar_price_unit'];
    
    // Update query on his_pharmaceuticals table
    $query = "UPDATE his_pharmaceuticals 
              SET phar_name = ?, phar_desc = ?, phar_qty = ?, phar_cat = ?, phar_vendor = ?, phar_attribute = ?, phar_unit = ?, phar_price_unit = ?
              WHERE phar_bcode = ?";
    $stmt_update = $mysqli->prepare($query);
    if (!$stmt_update) {
         die("Prepare failed: " . $mysqli->error);
    }
    $stmt_update->bind_param('ssssssssi', $phar_name, $phar_desc, $phar_qty, $phar_cat, $phar_vendor, $phar_attribute, $phar_unit, $phar_price_unit, $phar_bcode);

    if (!$stmt_update->execute()) {
        die("Update failed: " . $stmt_update->error);
    }
    
    if ($stmt_update->affected_rows > 0) {
         header("Location: his_admin_manage_pharm_cat.php");
         exit;
    } else {
         $err = "Please try again later";
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
        
        <?php
        if (isset($_GET['phar_bcode']) && !empty($_GET['phar_bcode'])) {
            $phar_bcode = $_GET['phar_bcode'];
        } else {
            die("<h3 style='color:red; text-align:center;'>Error: Missing pharmaceutical barcode.</h3>");
        }
        
        $query_fetch = "SELECT * FROM his_pharmaceuticals WHERE phar_bcode = ?";
        $stmt_cat = $mysqli->prepare($query_fetch);
        
        if (!$stmt_cat) {
            die("<h3 style='color:red; text-align:center;'>Error: Query preparation failed - " . $mysqli->error . "</h3>");
        }
        
        $stmt_cat->bind_param('s', $phar_bcode);
        $stmt_cat->execute();
        $res_cat = $stmt_cat->get_result();
        $row = $res_cat->fetch_object();
        
        if (!$row) {
            die("<h3 style='color:red; text-align:center;'>No category found!</h3>");
        }
        ?>
        
        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title">Update #<?php echo $row->phar_bcode; ?> - <?php echo $row->phar_name; ?></h4>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="header-title">Fill all fields</h4>
                                    <form method="post">
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Pharmaceutical Name</label>
                                                <input type="text" required value="<?php echo $row->phar_name; ?>" name="phar_name" class="form-control">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Pharmaceutical Quantity</label>
                                                <input type="text" required value="<?php echo $row->phar_qty; ?>" name="phar_qty" class="form-control">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Pharmaceutical Category</label>
                                                <select name="phar_cat" class="form-control">
                                                    <?php
                                                    $query_cat = "SELECT * FROM his_pharmaceuticals_categories ORDER BY RAND()";
                                                    $stmt_cat = $mysqli->prepare($query_cat);
                                                    $stmt_cat->execute();
                                                    $res_cat = $stmt_cat->get_result();
                                                    while ($cat_row = $res_cat->fetch_object()) {
                                                        $selected = ($cat_row->pharm_cat_name == $row->phar_cat) ? 'selected' : '';
                                                        echo "<option value='{$cat_row->pharm_cat_name}' $selected>{$cat_row->pharm_cat_name}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Pharmaceutical Vendor</label>
                                                <select name="phar_vendor" class="form-control">
                                                    <?php
                                                    $query_vendor = "SELECT * FROM his_vendor ORDER BY RAND()";
                                                    $stmt_vendor = $mysqli->prepare($query_vendor);
                                                    $stmt_vendor->execute();
                                                    $res_vendor = $stmt_vendor->get_result();
                                                    while ($vendor_row = $res_vendor->fetch_object()) {
                                                        $selected = ($vendor_row->v_name == $row->phar_vendor) ? 'selected' : '';
                                                        echo "<option value='{$vendor_row->v_name}' $selected>{$vendor_row->v_name}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label>Pharmaceutical Attribute</label>
                                                    <input type="text" required value="<?php echo $row->phar_attribute; ?>" name="phar_attribute" class="form-control">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label>Unit/package</label>
                                                    <input type="text" required value="<?php echo $row->phar_unit; ?>" name="phar_unit" class="form-control">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label>Price/unit</label>
                                                    <input type="number" required value="<?php echo $row->phar_price_unit; ?>" name="phar_price_unit" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        </div>
                                        <button type="submit" name="update_pharmaceutical" class="btn btn-warning">Update Pharmaceutical</button>
                                    </form>
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
</body>
</html>
