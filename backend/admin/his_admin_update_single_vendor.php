<?php
session_start();
include('assets/inc/config.php');
if (isset($_POST['update_vendor'])) {
    $v_name = $_POST['v_name'];
    $v_adr = !empty($_POST['v_adr']) ? $_POST['v_adr'] : NULL;
    $v_email = !empty($_POST['v_email']) ? $_POST['v_email'] : NULL;
    $v_phone = !empty($_POST['v_phone']) ? $_POST['v_phone'] : NULL;
    $v_desc = !empty($_POST['v_desc']) ? $_POST['v_desc'] : NULL;
    $v_number = $_GET['v_number'];

    // Update query allowing optional fields
    $query = "UPDATE his_vendor SET v_name=?, v_adr=?, v_email=?, v_phone=?, v_desc=? WHERE v_number=?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ssssss', $v_name, $v_adr, $v_email, $v_phone, $v_desc, $v_number);
    $stmt->execute();

    if ($stmt) {
        $success = "Vendor Details Updated";
        header("Location:his_admin_manage_vendor.php? success=".urlencode($success));
    } else {
        $err = "Please Try Again Or Try Later";
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
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title">Update Vendor Details</h4>
                            </div>
                        </div>
                    </div>
                    <?php
                    $v_number = $_GET['v_number'];
                    $ret = "SELECT * FROM his_vendor WHERE v_number = ?";
                    $stmt = $mysqli->prepare($ret);
                    $stmt->bind_param('s', $v_number);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_object()) {
                    ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="header-title">Update Vendor</h4>
                                        <form method="post">
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label>Vendor Name (Required)</label>
                                                    <input type="text" required name="v_name" value="<?php echo $row->v_name; ?>" class="form-control">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label>Vendor Address (Optional)</label>
                                                    <input type="text" name="v_adr" value="<?php echo $row->v_adr; ?>" class="form-control">
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-5">
                                                    <label>Vendor Email (Optional)</label>
                                                    <input type="email" name="v_email" value="<?php echo $row->v_email; ?>" class="form-control">
                                                </div>
                                            
                                                <div class="form-group col-md-5">
                                                    <label>Vendor Phone (Optional)</label>
                                                    <input type="text" name="v_phone" value="<?php echo $row->v_phone; ?>" class="form-control">
                                                </div>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Vendor Details (Optional)</label>
                                                <textarea name="v_desc" class="form-control"><?php echo $row->v_desc; ?></textarea>
                                            </div>
                                            
                                            <button type="submit" name="update_vendor" class="btn btn-success">Update Vendor</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <?php include('assets/inc/footer.php'); ?>
        </div>
    </div>
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
</body>
</html>