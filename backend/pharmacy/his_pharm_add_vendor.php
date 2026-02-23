<?php
session_start();
include('assets/inc/config.php');

if (isset($_POST['add_vendor'])) {
    $v_name = trim($_POST['v_name']); // Required field
    $v_adr = isset($_POST['v_adr']) ? trim($_POST['v_adr']) : NULL;
    $v_number = isset($_POST['v_number']) ? trim($_POST['v_number']) : NULL;
    $v_email = isset($_POST['v_email']) ? trim($_POST['v_email']) : NULL;
    $v_phone = isset($_POST['v_phone']) ? trim($_POST['v_phone']) : NULL;
    $v_desc = isset($_POST['v_desc']) ? trim($_POST['v_desc']) : NULL;

    // Ensure vendor name is provided
    if (empty($v_name)) {
        echo "<script>alert('Vendor name is required!');</script>";
    } else {
        // Check if vendor already exists
        $check_query = "SELECT COUNT(*) FROM his_vendor WHERE v_name = ?";
        $stmt_check = $mysqli->prepare($check_query);
        $stmt_check->bind_param("s", $v_name);
        $stmt_check->execute();
        $stmt_check->bind_result($count);
        $stmt_check->fetch();
        $stmt_check->close();

        if ($count > 0) {
            echo "<script>alert('Vendor with this name already exists!');</script>";
        } else {
            // Insert new vendor
            $insert_query = "INSERT INTO his_vendor (v_name, v_adr, v_number, v_email, v_phone, v_desc) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert = $mysqli->prepare($insert_query);
            $stmt_insert->bind_param("ssssss", $v_name, $v_adr, $v_number, $v_email, $v_phone, $v_desc);

            if ($stmt_insert->execute()) {
                $success = "Vendor Details Added Successfully!";
                header("Location: " . $_SERVER['PHP_SELF'] . "?success=" . urlencode($success));
                exit;
            } else {
                echo "<script>alert('Error adding vendor. Please try again.');</script>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('assets/inc/head.php'); ?>
    <title>Add Vendor</title>
</head>
<body>

    <!-- Begin page -->
    <div id="wrapper">

        <!-- Topbar Start -->
        <?php include("assets/inc/nav.php"); ?>
        <!-- end Topbar -->

        <!-- Left Sidebar Start -->
        <?php include("assets/inc/sidebar.php"); ?>
        <!-- Left Sidebar End -->

        <!-- Start Page Content here -->
        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    
                    <!-- Start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="his_admin_dashboard.php">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="#">Vendor</a></li>
                                        <li class="breadcrumb-item active">Add Vendor</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Add Vendor Details</h4>
                            </div>
                        </div>
                    </div>     
                    <!-- End page title -->

                    <!-- Form row -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="header-title">Fill all fields</h4>

                                    <!-- Add Vendor Form -->
                                    <form method="post">
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Company Name (Required)</label>
                                                <input type="text" required name="v_name" class="form-control">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="col-form-label">Vendor Phone Number (Optional)</label>
                                                <input type="text" name="v_phone" class="form-control">
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label class="col-form-label">Vendor Address (Optional)</label>
                                            <input type="text" class="form-control" name="v_adr">
                                        </div>

                                        <div class="form-group">
                                            <label class="col-form-label">Vendor Email (Optional)</label>
                                            <input type="email" class="form-control" name="v_email">
                                        </div>

                                        <div class="form-group">
                                            <label class="col-form-label">Vendor Details (Optional)</label>
                                            <textarea class="form-control" name="v_desc"></textarea>
                                        </div>

                                        <button type="submit" name="add_vendor" class="btn btn-success">Add Vendor</button>
                                    </form>
                                    <!-- End Vendor Form -->

                                </div> <!-- end card-body -->
                            </div> <!-- end card-->
                        </div> <!-- end col -->
                    </div>
                    <!-- End row -->

                </div> <!-- Container -->

            </div> <!-- Content -->

            <!-- Footer Start -->
            <?php include('assets/inc/footer.php'); ?>
            <!-- End Footer -->

        </div>
        <!-- End Page Content -->

    </div>
    <!-- End Wrapper -->

    <!-- Right bar overlay -->
    <div class="rightbar-overlay"></div>

    <!-- Scripts -->
    <script src="//cdn.ckeditor.com/4.6.2/basic/ckeditor.js"></script>
    <script>
        CKEDITOR.replace('editor');
    </script>

    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
    <script src="assets/libs/ladda/spin.js"></script>
    <script src="assets/libs/ladda/ladda.js"></script>
    <script src="assets/js/pages/loading-btn.init.js"></script>
    
</body>
</html>
