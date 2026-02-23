<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Africa/Lagos'); // Force Nigeria timezone

include('assets/inc/config.php');

if (isset($_POST['book_appointment'])) {
    $pat_number = $_GET['pat_number'];
    $pat_id = $_GET['pat_id'];
    $app_date = $_POST['app_date'];
    $app_time = date("H:i:s A"); // This should now reflect Africa/Lagos time in 12-hour format
    $app_status = $_POST['app_status'];
    
    // OPTIONAL: Debug output - remove after testing
    // echo "Current app_time: $app_time"; exit;
    
    // Insert values into appointments table, saving only pat_id (not pat_number) into the DB
    $query = "INSERT INTO appointments (pat_id, app_date, app_time, app_status) VALUES (?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('isss', $pat_id, $app_date, $app_time, $app_status);
    $stmt->execute();
    
    if ($stmt) {
        $success = "Appointment Booked Successfully";
    } else {
        $err = "Please Try Again Or Try Later";
    }
    
    $stmt->close();
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
                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="his_admin_dashboard.php">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="#">Patients</a></li>
                                        <li class="breadcrumb-item active">Book Appointment</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Book Appointment</h4>
                            </div>
                        </div>
                    </div>
                    <!-- End Page Title -->
                    
                    <!-- Form row -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="header-title">Fill all fields</h4>
                                    <!-- Appointment Form -->
                                    <form method="post">
                                        <div class="form-row">
                                            <!-- Display only: Patient File Number (not saved in DB) -->
                                            <div class="form-group col-md-4">
                                                <label for="patNumber" class="col-form-label">Patient File Number</label>
                                                <input type="text" required="required" name="display_pat_number" class="form-control" id="patNumber" value="<?php echo isset($_GET['pat_number']) ? $_GET['pat_number'] : ''; ?>" readonly>
                                            </div>
                                            <!-- Hidden: Patient ID (saved to DB) -->
                                            <input type="hidden" name="pat_id" value="<?php echo isset($_GET['pat_id']) ? $_GET['pat_id'] : ''; ?>">
                                            
                                            <!-- Appointment Date -->
                                            <div class="form-group col-md-4">
                                                <label for="appDate" class="col-form-label">Date</label>
                                                <input type="date" required name="app_date" class="form-control" id="appDate">
                                            </div>
                                            
                                            <!-- Status (Default: Pending) -->
                                            <div class="form-group col-md-4">
                                                <?php $status = 'Pending'; ?>
                                                <label for="appStatus" class="col-form-label">Status</label>
                                                <input type="text" name="app_status" value="<?php echo $status;?>" class="form-control" id="appStatus" readonly>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" name="book_appointment" class="ladda-button btn btn-primary" data-style="expand-right">Appoint</button>
                                    </form>
                                    <!-- End Appointment Form -->
                                </div> <!-- end card-body -->
                            </div> <!-- end card-->
                        </div> <!-- end col -->
                    </div>
                    <!-- end row -->

                </div> <!-- container -->
            </div> <!-- content -->

            <!-- Footer -->
            <?php include('assets/inc/footer.php'); ?>
        </div> <!-- End Content-Page -->
    </div> <!-- END wrapper -->

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
