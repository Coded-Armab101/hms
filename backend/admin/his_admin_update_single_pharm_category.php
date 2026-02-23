<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$aid = $_SESSION['ad_id'];

// Update handler
if (isset($_POST['update_pharmaceutical_category'])) {
    // Get pharm_cat_id from GET
    $pharm_cat_id = intval($_GET['pharm_cat_id']);
    
    // Get updated values from POST
    $new_pharm_cat_name = $_POST['pharm_cat_name'];
    $pharm_cat_desc = $_POST['pharm_cat_desc'];
    //$pharm_cat_vendor = $_POST['pharm_cat_vendor']; // even if hidden

    // Update query using pharm_cat_id
    $query = "UPDATE his_pharmaceuticals_categories 
              SET pharm_cat_name = ?, pharm_cat_desc = ? 
              WHERE pharm_cat_id = ?";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
         die("Prepare failed: " . $mysqli->error);
    }
    $stmt->bind_param('ssi', $new_pharm_cat_name, $pharm_cat_desc, $pharm_cat_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
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
    <!-- Begin page -->
    <div id="wrapper">
        <!-- Topbar Start -->
        <?php include("assets/inc/nav.php"); ?>
        <!-- End Topbar -->

        <!-- Left Sidebar Start -->
        <?php include("assets/inc/sidebar.php"); ?>
        <!-- End Left Sidebar -->

        <!-- Start Page Content -->
        <?php
        // Check if pharm_cat_id is provided
        if (isset($_GET['pharm_cat_id'])) {
            $pharm_cat_id = intval($_GET['pharm_cat_id']);
            $ret = "SELECT * FROM his_pharmaceuticals_categories WHERE pharm_cat_id = ?";
            $stmt = $mysqli->prepare($ret);
            $stmt->bind_param('i', $pharm_cat_id);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($row = $res->fetch_object()) {
        ?>
        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="his_admin_dashboard.php">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Pharmaceuticals</a></li>
                                        <li class="breadcrumb-item active">Manage Pharmaceutical Category</li>
                                    </ol>
                                </div>
                                <h4 class="page-title"><?php echo $row->pharm_cat_name; ?></h4>
                            </div>
                        </div>
                    </div>
                    <!-- end page title --> 

                    <!-- Form row -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="header-title">Fill all fields</h4>
                                    <!-- Update Category Form -->
                                    <form method="post">
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label for="inputEmail4" class="col-form-label">Pharmaceutical Category Name</label>
                                                <input type="text" value="<?php echo $row->pharm_cat_name; ?>" required="required" name="pharm_cat_name" class="form-control" id="inputEmail4">
                                            </div>
                                            
                                        </div>
                                        <div class="form-group">
                                            <label for="inputAddress" class="col-form-label">Pharmaceutical Category Description</label>
                                            <textarea  class="form-control" name="pharm_cat_desc" id="editor"><?php echo $row->pharm_cat_desc; ?></textarea>
                                        </div>
                                       <button type="submit" name="update_pharmaceutical_category" class="ladda-button btn btn-danger" data-style="expand-right">Update Category</button>
                                    </form>
                                </div> <!-- end card-body -->
                            </div> <!-- end card-->
                        </div> <!-- end col -->
                    </div>
                    <!-- end row -->
                </div> <!-- container -->
            </div> <!-- content -->
            <!-- Footer Start -->
            <?php include('assets/inc/footer.php'); ?>
            <!-- end Footer -->
        </div>
        <?php 
            } else {
                echo "<h3 style='color:red; text-align:center;'>No category found!</h3>";
            }
        } else {
            echo "<h3 style='color:red; text-align:center;'>Invalid request!</h3>";
        }
        ?>
        <!-- End Page Content -->
    </div>
    <!-- END wrapper -->

    <!-- Load CKEDITOR Javascript -->
    <script src="//cdn.ckeditor.com/4.6.2/basic/ckeditor.js"></script>
    <script type="text/javascript">
        CKEDITOR.replace('editor');
    </script>
       
    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>
    <!-- App js-->
    <script src="assets/js/app.min.js"></script>
    <!-- Loading buttons js -->
    <script src="assets/libs/ladda/spin.js"></script>
    <script src="assets/libs/ladda/ladda.js"></script>
    <!-- Buttons init js-->
    <script src="assets/js/pages/loading-btn.init.js"></script>
</body>
</html>
