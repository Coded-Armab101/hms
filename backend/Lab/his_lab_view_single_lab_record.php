<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();

// Get lab scientist ID from session
$lab_id = $_SESSION['lab_id'];

// Get lab scientist name
$lab_name_query = "SELECT CONCAT(lab_fname, ' ', lab_lname) AS lab_name FROM his_lab_scist WHERE lab_id = ?";
$stmt = $mysqli->prepare($lab_name_query);
$stmt->bind_param('i', $lab_id);
$stmt->execute();
$stmt->bind_result($lab_name);
$stmt->fetch();
$stmt->close();

// Handle lab result submission
if (isset($_POST['update_lab_result'])) {
    $lab_id_param = $_GET['lab_id'];
    $lab_pat_results = trim($_POST['lab_pat_results']);
    
    // FIXED: Check if columns exist before trying to update them
    $check_scientist_name = $mysqli->query("SHOW COLUMNS FROM his_laboratory LIKE 'lab_scientist_name'");
    $check_scientist_id = $mysqli->query("SHOW COLUMNS FROM his_laboratory LIKE 'lab_scientist_id'");
    $check_result_date = $mysqli->query("SHOW COLUMNS FROM his_laboratory LIKE 'lab_result_date'");
    
    // Build the UPDATE query based on what columns exist
    if ($check_scientist_name->num_rows > 0 && $check_scientist_id->num_rows > 0 && $check_result_date->num_rows > 0) {
        // All tracking columns exist - use full update
        $query = "UPDATE his_laboratory SET lab_pat_results = ?, lab_scientist_id = ?, lab_scientist_name = ?, lab_result_date = NOW() WHERE lab_id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('sisi', $lab_pat_results, $lab_id, $lab_name, $lab_id_param);
    } else {
        // Some columns don't exist - do basic update
        $query = "UPDATE his_laboratory SET lab_pat_results = ? WHERE lab_id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('si', $lab_pat_results, $lab_id_param);
    }
    
    if ($stmt->execute()) {
        $success = "Lab result updated successfully!";
        // Refresh page to show updated data
        header("Location: " . $_SERVER['PHP_SELF'] . "?lab_id=" . $lab_id_param . "&lab_number=" . $_GET['lab_number']);
        exit;
    } else {
        $err = "Error updating lab result: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
    
<?php include('assets/inc/head.php');?>

<body>

    <!-- Begin page -->
    <div id="wrapper">

        <!-- Topbar Start -->
        <?php include('assets/inc/nav.php');?>
        <!-- end Topbar -->

        <!-- ========== Left Sidebar Start ========== -->
        <?php include("assets/inc/sidebar.php");?>
        <!-- Left Sidebar End -->

        <!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->
        
        <?php
        if (isset($_GET['lab_id']) && isset($_GET['lab_number'])) {
            $lab_number = $_GET['lab_number'];
            $lab_id_param = $_GET['lab_id'];
            
            // Check if tracking columns exist
            $check_scientist_name = $mysqli->query("SHOW COLUMNS FROM his_laboratory LIKE 'lab_scientist_name'");
            $check_scientist_id = $mysqli->query("SHOW COLUMNS FROM his_laboratory LIKE 'lab_scientist_id'");
            $check_result_date = $mysqli->query("SHOW COLUMNS FROM his_laboratory LIKE 'lab_result_date'");
            
            // Build SELECT query based on available columns
            $select_columns = "l.*";
            if ($check_scientist_name->num_rows > 0) {
                $select_columns .= ", l.lab_scientist_name";
            }
            if ($check_scientist_id->num_rows > 0) {
                $select_columns .= ", l.lab_scientist_id";
            }
            if ($check_result_date->num_rows > 0) {
                $select_columns .= ", l.lab_result_date";
            }
            
            // Check if doc_id column exists
            $check_doc_col = $mysqli->query("SHOW COLUMNS FROM his_laboratory LIKE 'doc_id'");
            $has_doc_col = $check_doc_col && $check_doc_col->num_rows > 0;
            
            if ($has_doc_col) {
                // Get lab record with doctor info using JOIN
                $ret = "SELECT $select_columns, d.doc_fname, d.doc_lname, d.doc_number 
                        FROM his_laboratory l 
                        LEFT JOIN his_docs d ON l.doc_id = d.doc_id 
                        WHERE l.lab_id = ?";
            } else {
                // Fallback if doc_id column doesn't exist
                $ret = "SELECT $select_columns FROM his_laboratory WHERE lab_id = ?";
            }
            
            $stmt = $mysqli->prepare($ret);
            if (!$stmt) {
                die("Prepare failed: " . $mysqli->error);
            }
            
            $stmt->bind_param('i', $lab_id_param);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($res->num_rows > 0) {
                $row = $res->fetch_object();
                $mysqlDateTime = $row->lab_date_rec;
                
                // Get doctor information
                $doctor_name = "Not Specified";
                $doctor_number = "";
                
                // Method 1: If JOIN returned doctor info
                if (isset($row->doc_fname) && isset($row->doc_lname)) {
                    $doctor_name = $row->doc_fname . ' ' . $row->doc_lname;
                    $doctor_number = $row->doc_number ?? '';
                } 
                // Method 2: If we have doc_id but no JOIN results
                else if (isset($row->doc_id) && !empty($row->doc_id)) {
                    $doc_query = "SELECT doc_fname, doc_lname, doc_number FROM his_docs WHERE doc_id = ?";
                    $doc_stmt = $mysqli->prepare($doc_query);
                    $doc_stmt->bind_param('i', $row->doc_id);
                    $doc_stmt->execute();
                    $doc_stmt->bind_result($doc_fname, $doc_lname, $doc_number);
                    if ($doc_stmt->fetch()) {
                        $doctor_name = $doc_fname . ' ' . $doc_lname;
                        $doctor_number = $doc_number;
                    }
                    $doc_stmt->close();
                }
        ?>

        <div class="content-page">
            <div class="content">

                <!-- Start Content-->
                <div class="container-fluid">
                    
                    <!-- Display success/error messages -->
                    <?php if(isset($success)): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($success); ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(isset($err)): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($err); ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="his_lab_dashboard.php">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Laboratory Records</a></li>
                                        <li class="breadcrumb-item active">View & Update Record</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Lab Test #<?php echo htmlspecialchars($row->lab_number); ?></h4>
                            </div>
                        </div>
                    </div>     
                    <!-- end page title --> 

                    <div class="row">
                        <div class="col-12">
                            <div class="card-box">
                                <div class="row">
                                    <div class="col-xl-4">
                                        <div class="tab-content pt-0">
                                            <div class="tab-pane active show" id="product-1-item">
                                                <img src="assets/images/lab_test.png" alt="Laboratory Test" class="img-fluid mx-auto d-block rounded">
                                            </div>
                                        </div>
                                    </div> <!-- end col -->
                                    
                                    <div class="col-xl-8">
                                        <div class="pl-xl-3 mt-3 mt-xl-0">
                                            <!-- Patient Information -->
                                            <div class="card mb-3">
                                                <div class="card-header bg-light">
                                                    <h4 class="card-title mb-0">Test Information</h4>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <p><strong>Patient's Name:</strong> <?php echo htmlspecialchars($row->lab_pat_name); ?></p>
                                                            <p><strong>Patient Number:</strong> <?php echo htmlspecialchars($row->lab_pat_number); ?></p>
                                                            <p><strong>Requesting Doctor:</strong> <?php echo htmlspecialchars($doctor_name); ?></p>
                                                            <?php if (!empty($doctor_number)): ?>
                                                            <p><strong>Doctor ID:</strong> <?php echo htmlspecialchars($doctor_number); ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p><strong>Test ID:</strong> <?php echo htmlspecialchars($row->lab_number); ?></p>
                                                            <p><strong>Date Recorded:</strong> <?php echo date("d/m/Y - h:i A", strtotime($mysqlDateTime)); ?></p>
                                                            <?php if (isset($row->doc_id) && !empty($row->doc_id)): ?>
                                                            <p><strong>Doctor Database ID:</strong> <?php echo htmlspecialchars($row->doc_id); ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <?php if (!empty($row->lab_pat_ailment)): ?>
                                                    <p><strong>Patient Ailment:</strong> <?php echo htmlspecialchars($row->lab_pat_ailment); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <!-- Laboratory Test Section -->
                                            <div class="card mb-3">
                                                <div class="card-header bg-info text-white">
                                                    <h4 class="card-title mb-0">Laboratory Test Request</h4>
                                                </div>
                                                <div class="card-body">
                                                    <div class="bg-light p-3 rounded">
                                                        <?php 
                                                        if (!empty($row->lab_pat_tests)) {
                                                            // Clean HTML tags before displaying
                                                            $clean_test = strip_tags($row->lab_pat_tests);
                                                            echo nl2br(htmlspecialchars($clean_test));
                                                        } else {
                                                            echo '<p class="text-muted">No test details provided.</p>';
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Laboratory Result Section -->
                                            <div class="card mb-3">
                                                <div class="card-header bg-success text-white">
                                                    <h4 class="card-title mb-0">Laboratory Results</h4>
                                                </div>
                                                <div class="card-body">
                                                    <?php if (!empty($row->lab_pat_results)): ?>
                                                    <div class="bg-light p-3 rounded mb-3">
                                                        <?php 
                                                        // Clean HTML tags from results too
                                                        $clean_results = strip_tags($row->lab_pat_results);
                                                        echo nl2br(htmlspecialchars($clean_results)); 
                                                        ?>
                                                    </div>
                                                    <div class="alert alert-info">
                                                        <strong>Result Status:</strong> Completed
                                                        <?php if (!empty($row->lab_scientist_name)): ?>
                                                        <br><strong>Processed By:</strong> <?php echo htmlspecialchars($row->lab_scientist_name); ?>
                                                        <?php endif; ?>
                                                        <?php if (!empty($row->lab_result_date)): ?>
                                                        <br><strong>Result Date:</strong> <?php echo date("d/m/Y - h:i A", strtotime($row->lab_result_date)); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php else: ?>
                                                    <div class="alert alert-warning">
                                                        <strong>No results yet.</strong> Please add the laboratory results below.
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Lab Result Form -->
                                                    <form method="post" id="labResultForm">
                                                        <div class="form-group">
                                                            <label for="lab_pat_results"><strong>Enter Laboratory Results:</strong></label>
                                                            <textarea class="form-control" id="lab_pat_results" name="lab_pat_results" rows="8" 
                                                                      placeholder="Enter detailed laboratory results here..." required><?php 
                                                                      echo isset($_POST['lab_pat_results']) ? htmlspecialchars($_POST['lab_pat_results']) : 
                                                                           (!empty($row->lab_pat_results) ? htmlspecialchars(strip_tags($row->lab_pat_results)) : ''); 
                                                                  ?></textarea>
                                                            <small class="form-text text-muted">Be detailed and precise with measurements and observations.</small>
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input" id="confirmAccurate" required>
                                                                <label class="custom-control-label" for="confirmAccurate">
                                                                    I confirm that these results are accurate and have been verified.
                                                                </label>
                                                            </div>
                                                        </div>
                                                        
                                                        <input type="hidden" name="lab_id" value="<?php echo htmlspecialchars($lab_id_param); ?>">
                                                        <input type="hidden" name="lab_number" value="<?php echo htmlspecialchars($lab_number); ?>">
                                                        
                                                        <div class="form-group">
                                                            <button type="submit" name="update_lab_result" class="btn btn-success btn-lg">
                                                                <i class="fas fa-flask"></i> Submit Laboratory Results
                                                            </button>
                                                            <button type="reset" class="btn btn-secondary btn-lg ml-2">
                                                                <i class="fas fa-redo"></i> Clear Form
                                                            </button>
                                                        </div>
                                                        
                                                        <div class="alert alert-light border mt-3">
                                                            <small>
                                                                <i class="fas fa-info-circle text-primary mr-1"></i>
                                                                <strong>Processing Scientist:</strong> <?php echo htmlspecialchars($lab_name); ?> 
                                                                (ID: <?php echo htmlspecialchars($lab_id); ?>)<br>
                                                                <i class="fas fa-info-circle text-primary mr-1"></i>
                                                                <strong>Date:</strong> <?php echo date("d/m/Y - h:i A"); ?>
                                                            </small>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                            
                                            <!-- Print/Export Options -->
                                            <div class="card">
                                                <div class="card-header bg-light">
                                                    <h5 class="card-title mb-0">Export Options</h5>
                                                </div>
                                                <div class="card-body">
                                                    <div class="btn-group" role="group">
                                                        <a href="javascript:window.print()" class="btn btn-outline-primary">
                                                            <i class="fas fa-print"></i> Print Report
                                                        </a>
                                                        <a href="his_lab_generate_pdf.php?lab_id=<?php echo $lab_id_param; ?>" class="btn btn-outline-success ml-2" target="_blank">
                                                            <i class="fas fa-file-pdf"></i> Generate PDF
                                                        </a>
                                                        <a href="his_lab_export.php?lab_id=<?php echo $lab_id_param; ?>&format=excel" class="btn btn-outline-info ml-2">
                                                            <i class="fas fa-file-excel"></i> Export to Excel
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div> <!-- end col -->
                                </div>
                                <!-- end row -->
                            </div> <!-- end card-->
                        </div> <!-- end col-->
                    </div>
                    <!-- end row-->
                    
                </div> <!-- container -->

            </div> <!-- content -->

            <!-- Footer Start -->
            <?php include('assets/inc/footer.php');?>
            <!-- end Footer -->

        </div>
        
        <?php 
            } else {
                echo '<div class="content-page"><div class="content"><div class="container-fluid"><div class="alert alert-danger">Lab record not found!</div></div></div></div>';
            }
            if (isset($stmt)) {
                $stmt->close();
            }
        } else {
            echo '<div class="content-page"><div class="content"><div class="container-fluid"><div class="alert alert-danger">Lab ID and Number are required!</div></div></div></div>';
        }
        ?>

        <!-- ============================================================== -->
        <!-- End Page content -->
        <!-- ============================================================== -->

    </div>
    <!-- END wrapper -->

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>

    <!-- App js -->
    <script src="assets/js/app.min.js"></script>
    
    <!-- Additional Scripts for Lab Page -->
    <script>
    $(document).ready(function() {
        // Form validation
        $('#labResultForm').on('submit', function(e) {
            if (!$('#confirmAccurate').is(':checked')) {
                e.preventDefault();
                alert('Please confirm that the results are accurate before submitting.');
                $('#confirmAccurate').focus();
                return false;
            }
            
            var results = $('#lab_pat_results').val().trim();
            if (results.length < 10) {
                e.preventDefault();
                alert('Please enter detailed results (at least 10 characters).');
                $('#lab_pat_results').focus();
                return false;
            }
            
            return confirm('Are you sure you want to submit these laboratory results? This action cannot be undone.');
        });
    });
    </script>
    
</body>
</html>