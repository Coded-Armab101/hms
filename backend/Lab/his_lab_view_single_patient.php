<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();

$lab_id = $_SESSION['lab_id'];

// Get lab scientist full name
$query = "SELECT CONCAT(lab_fname, ' ', lab_lname) AS lab_name FROM his_lab_scist WHERE lab_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $lab_id);
$stmt->execute();
$stmt->bind_result($lab_name);
$stmt->fetch();
$stmt->close();

/* ---------------------------
   Handle Laboratory Test Submission
--------------------------- */
if (isset($_POST['add_lab_test'])) {
    $pat_id = $_GET['pat_id'];
    $lab_pat_name = $_POST['lab_pat_name'];
    $lab_pat_ailment = $_POST['lab_pat_ailment'] ?? '';
    $lab_date_rec = date('Y-m-d H:i:s');
    $lab_pat_number = $_GET['pat_number'];
    $lab_pat_tests = trim($_POST['lab_pat_tests']);
    $lab_pat_results = trim($_POST['lab_pat_results'] ?? '');
    $lab_number = $_POST['lab_number'];
    
    // Insert with lab scientist ID
    $query = "INSERT INTO his_laboratory (pat_id, lab_pat_name, lab_pat_ailment, lab_date_rec, lab_pat_number, lab_pat_tests, lab_pat_results, lab_number, lab_scientist_id, lab_scientist_name) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('isssssssis', $pat_id, $lab_pat_name, $lab_pat_ailment, $lab_date_rec, $lab_pat_number, $lab_pat_tests, $lab_pat_results, $lab_number, $lab_id, $lab_name);
    
    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?pat_id=" . $_GET['pat_id'] . "&pat_number=" . $_GET['pat_number']);
        exit;
    } else {
        $err = "Error saving lab test: " . $stmt->error;
    }
}

if (isset($_POST['update_lab_test'])) {
    $lab_id = $_POST['lab_id'];
    $lab_pat_results = trim($_POST['lab_pat_results']);
    
    // Update with lab scientist info
    $query = "UPDATE his_laboratory SET lab_pat_results = ?, lab_scientist_id = ?, lab_scientist_name = ?, lab_result_date = NOW() WHERE lab_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('sisi', $lab_pat_results, $lab_id, $lab_name, $lab_id);
    
    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?pat_id=" . $_GET['pat_id'] . "&pat_number=" . $_GET['pat_number']);
        exit;
    } else {
        $err = "Error updating lab test: " . $stmt->error;
    }
}

/* ---------------------------
   Fetch Existing Patient Data
--------------------------- */
if (isset($_GET['pat_id']) && isset($_GET['pat_number'])) {
    $pat_number = $_GET['pat_number'];
    $pat_id = $_GET['pat_id'];
    
    $ret = "SELECT * FROM his_patients WHERE pat_id = ?";
    $stmt = $mysqli->prepare($ret);
    $stmt->bind_param('i', $pat_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $patient_details = $res->fetch_object();
    
    if (!$patient_details) {
        die("Patient not found");
    }
} else {
    die("Patient ID and Number are required");
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
        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <!-- Error Messages -->
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
                    
                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Patients</a></li>
                                        <li class="breadcrumb-item active">View Patients</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">
                                    <?php echo htmlspecialchars($patient_details->pat_fname . ' ' . $patient_details->pat_lname); ?>'s Profile
                                </h4>
                            </div>
                        </div>
                    </div>
                    <!-- End Page Title -->

                    <div class="row">
                        <!-- Patient Details Sidebar -->
                        <div class="col-lg-4 col-xl-4">
                            <div class="card-box text-center">
                                <img src="assets/images/users/patient.png" class="rounded-circle avatar-lg img-thumbnail" alt="profile-image">
                                <div class="text-left mt-3">
                                    <p class="text-muted mb-2 font-20">
                                        <strong>File Number :</strong>
                                        <span class="ml-2 font-20"><strong><?php echo htmlspecialchars($patient_details->pat_number); ?></strong></span>
                                    </p>
                                    <hr>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>Full Name :</strong>
                                        <span class="ml-2"><?php echo htmlspecialchars($patient_details->pat_fname); ?> <?php echo htmlspecialchars($patient_details->pat_lname); ?></span>
                                    </p>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>Mobile :</strong>
                                        <span class="ml-2"><?php echo htmlspecialchars($patient_details->pat_phone); ?></span>
                                    </p>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>Address :</strong>
                                        <span class="ml-2"><?php echo htmlspecialchars($patient_details->pat_addr); ?></span>
                                    </p>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>Date Of Birth :</strong>
                                        <span class="ml-2"><?php echo htmlspecialchars($patient_details->pat_dob); ?></span>
                                    </p>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>Age :</strong>
                                        <span class="ml-2"><?php echo htmlspecialchars($patient_details->pat_age); ?> Years</span>
                                    </p>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>State :</strong>
                                        <span class="ml-2"><?php echo htmlspecialchars($patient_details->pat_state); ?></span>
                                    </p>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>Nationality :</strong>
                                        <span class="ml-2"><?php echo htmlspecialchars($patient_details->pat_nationality); ?></span>
                                    </p>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>Date Of Registration :</strong>
                                        <span class="ml-2"><?php echo htmlspecialchars($patient_details->pat_date_joined); ?></span>
                                    </p>
                                    <hr>
                                </div>
                            </div>
                        </div> 

                        <!-- Main Content: Tabs -->
                        <div class="col-lg-8 col-xl-8">
                            <div class="card-box">
                                <!-- Nav Tabs -->
                                <ul class="nav nav-pills navtab-bg nav-justified">
                                    <li class="nav-item">
                                        <a href="#lab_records" class="nav-link active" data-toggle="tab" aria-expanded="true">Lab Records</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#pending_tests" class="nav-link" data-toggle="tab" aria-expanded="false">Pending Tests</a>
                                    </li>
                                </ul>

                                <!-- Tab Panes -->
                                <div class="tab-content">
                                    <!-- Lab Records Tab Pane -->
                                    <div class="tab-pane fade show active" id="lab_records">
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h4 class="header-title">Add New Laboratory Test</h4>
                                            </div>
                                            <div class="card-body">
                                                <form method="post">
                                                    <div class="form-group">
                                                        <label for="lab_pat_tests">Test Name/Description</label>
                                                        <textarea class="form-control" name="lab_pat_tests" rows="4" placeholder="Enter test name and description here..." required></textarea>
                                                    </div>
                                                    <div class="form-row">
                                                        <div class="form-group col-md-6">
                                                            <label>Test Result (Optional - can be added later)</label>
                                                            <input type="text" class="form-control" name="lab_pat_results" placeholder="Enter test results if available">
                                                        </div>
                                                        <div class="form-group col-md-6">
                                                            <label>Lab Test ID</label>
                                                            <input type="text" class="form-control" value="<?php echo 'LAB-' . date('YmdHis'); ?>" readonly>
                                                            <input type="hidden" name="lab_number" value="<?php echo 'LAB-' . date('YmdHis'); ?>">
                                                        </div>
                                                    </div>
                                                    <!-- Hidden fields -->
                                                    <input type="hidden" name="pat_id" value="<?php echo htmlspecialchars($_GET['pat_id']); ?>">
                                                    <input type="hidden" name="lab_pat_name" value="<?php echo htmlspecialchars($patient_details->pat_fname . ' ' . $patient_details->pat_lname); ?>">
                                                    <input type="hidden" name="lab_pat_ailment" value="">
                                                    <input type="hidden" name="pat_number" value="<?php echo htmlspecialchars($_GET['pat_number']); ?>">
                                                    
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-user-md mr-1"></i>
                                                        <strong>Lab Scientist:</strong> <?php echo htmlspecialchars($lab_name); ?> (ID: <?php echo htmlspecialchars($lab_id); ?>)
                                                    </div>
                                                    
                                                    <button type="submit" name="add_lab_test" class="btn btn-primary">
                                                        <i class="fas fa-save"></i> Save Lab Test
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <h4>Existing Lab Records</h4>
                                        <ul class="list-unstyled timeline-sm">
                                            <?php
                                            // Get existing lab tests for this patient
                                            $query = "SELECT l.*, 
                                                     CONCAT(d.doc_fname, ' ', d.doc_lname) AS doctor_name,
                                                     d.doc_number AS doctor_number
                                                     FROM his_laboratory l 
                                                     LEFT JOIN his_docs d ON l.doc_id = d.doc_id 
                                                     WHERE l.pat_id = ? 
                                                     ORDER BY l.lab_date_rec DESC";
                                            $stmt = $mysqli->prepare($query);
                                            $stmt->bind_param("i", $pat_id);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            
                                            if ($result->num_rows > 0) {
                                                while($lab_row = $result->fetch_object()) {
                                                    $mysqlDateTime = $lab_row->lab_date_rec;
                                            ?>
                                            <li class="timeline-sm-item">
                                                <span class="timeline-sm-date"><?php echo date("d-m-Y", strtotime($mysqlDateTime)); ?></span>
                                                <div class="border p-2 mb-2 rounded">
                                                    <div class="row">
                                                        <div class="col-md-8">
                                                            <p><strong>Test:</strong> <?php 
                                                                // Clean HTML tags before displaying
                                                                $clean_test = strip_tags($lab_row->lab_pat_tests);
                                                                echo nl2br(htmlspecialchars($clean_test)); 
                                                            ?></p>
                                                        </div>
                                                        <div class="col-md-4 text-right">
                                                            <small class="text-muted">ID: <?php echo htmlspecialchars($lab_row->lab_number); ?></small>
                                                        </div>
                                                    </div>
                                                    
                                                    <?php if (!empty($lab_row->doctor_name)): ?>
                                                    <p><strong>Requested by Doctor:</strong> <?php echo htmlspecialchars($lab_row->doctor_name); ?> 
                                                        (<?php echo htmlspecialchars($lab_row->doctor_number); ?>)
                                                    </p>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (empty($lab_row->lab_pat_results)): ?>
                                                    <!-- Inline Update Form if result is empty -->
                                                    <form method="post" class="mt-2">
                                                        <div class="form-row">
                                                            <div class="form-group col-md-8">
                                                                <label>Enter Test Result:</label>
                                                                <input type="text" name="lab_pat_results" class="form-control" placeholder="Enter test results..." required>
                                                            </div>
                                                            <div class="form-group col-md-4">
                                                                <label>&nbsp;</label>
                                                                <input type="hidden" name="lab_id" value="<?php echo $lab_row->lab_id; ?>">
                                                                <button type="submit" name="update_lab_test" class="btn btn-warning btn-block">
                                                                    <i class="fas fa-check"></i> Update Result
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                    <?php else: ?>
                                                    <div class="alert alert-success mt-2">
                                                        <strong>Result:</strong> <?php 
                                                            $clean_result = strip_tags($lab_row->lab_pat_results);
                                                            echo nl2br(htmlspecialchars($clean_result)); 
                                                        ?>
                                                        <?php if (!empty($lab_row->lab_scientist_name)): ?>
                                                        <br><small class="text-muted">
                                                            <i class="fas fa-user-circle"></i> 
                                                            Processed by: <?php echo htmlspecialchars($lab_row->lab_scientist_name); ?>
                                                            <?php if (!empty($lab_row->lab_result_date)): ?>
                                                            on <?php echo date("d/m/Y H:i", strtotime($lab_row->lab_result_date)); ?>
                                                            <?php endif; ?>
                                                        </small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </li>
                                            <?php 
                                                }
                                            } else {
                                                echo '<li class="text-center text-muted py-3">No lab records found for this patient.</li>';
                                            }
                                            $stmt->close();
                                            ?>
                                        </ul>
                                    </div>
                                    
                                    <!-- Pending Tests Tab Pane -->
                                    <div class="tab-pane fade" id="pending_tests">
                                        <div class="card mb-3">
                                            <div class="card-header bg-warning text-white">
                                                <h4 class="header-title mb-0">Pending Tests (From Doctors)</h4>
                                            </div>
                                            <div class="card-body">
                                                <ul class="list-unstyled timeline-sm">
                                                    <?php
                                                    // Get tests requested by doctors that don't have results yet
                                                    $pending_query = "SELECT l.*, 
                                                                     CONCAT(d.doc_fname, ' ', d.doc_lname) AS doctor_name,
                                                                     d.doc_number AS doctor_number
                                                                     FROM his_laboratory l 
                                                                     JOIN his_docs d ON l.doc_id = d.doc_id 
                                                                     WHERE l.pat_id = ? 
                                                                     AND (l.lab_pat_results IS NULL OR l.lab_pat_results = '')
                                                                     ORDER BY l.lab_date_rec DESC";
                                                    $pending_stmt = $mysqli->prepare($pending_query);
                                                    $pending_stmt->bind_param("i", $pat_id);
                                                    $pending_stmt->execute();
                                                    $pending_result = $pending_stmt->get_result();
                                                    
                                                    if ($pending_result->num_rows > 0) {
                                                        while($pending_row = $pending_result->fetch_object()) {
                                                            $mysqlDateTime = $pending_row->lab_date_rec;
                                                    ?>
                                                    <li class="timeline-sm-item">
                                                        <span class="timeline-sm-date"><?php echo date("d-m-Y", strtotime($mysqlDateTime)); ?></span>
                                                        <div class="border p-3 mb-3 rounded bg-light">
                                                            <div class="row">
                                                                <div class="col-md-9">
                                                                    <h6><strong>Test Request:</strong></h6>
                                                                    <div class="bg-white p-2 rounded mb-2">
                                                                        <?php 
                                                                        $clean_test = strip_tags($pending_row->lab_pat_tests);
                                                                        echo nl2br(htmlspecialchars($clean_test)); 
                                                                        ?>
                                                                    </div>
                                                                    <p class="mb-1">
                                                                        <strong>Requested by:</strong> Dr. <?php echo htmlspecialchars($pending_row->doctor_name); ?>
                                                                        (<?php echo htmlspecialchars($pending_row->doctor_number); ?>)
                                                                    </p>
                                                                </div>
                                                                <div class="col-md-3 text-right">
                                                                    <form method="post" class="mt-2">
                                                                        <div class="form-group">
                                                                            <label class="small">Enter Result:</label>
                                                                            <textarea name="lab_pat_results" class="form-control form-control-sm" rows="3" placeholder="Enter results..." required></textarea>
                                                                        </div>
                                                                        <input type="hidden" name="lab_id" value="<?php echo $pending_row->lab_id; ?>">
                                                                        <button type="submit" name="update_lab_test" class="btn btn-success btn-sm btn-block">
                                                                            <i class="fas fa-check-circle"></i> Submit Results
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </li>
                                                    <?php 
                                                        }
                                                    } else {
                                                        echo '<li class="text-center text-muted py-3">No pending tests from doctors.</li>';
                                                    }
                                                    $pending_stmt->close();
                                                    ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Tab Panes -->
                            </div>
                        </div>
                        <!-- End Main Content -->
                    </div>
                    <!-- End Row -->
                </div>
                <!-- End Container -->
            </div>
            <!-- End Content -->

            <!-- Footer Start -->
            <?php include('assets/inc/footer.php'); ?>
            <!-- End Footer -->
        </div>
        <!-- End Content-Page -->
    </div>
    <!-- END Wrapper -->

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>
    
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>
    
    <!-- Additional Scripts -->
    <script>
    $(document).ready(function() {
        // Tab functionality
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            localStorage.setItem('lastTab', $(e.target).attr('href'));
        });
        
        // Restore last tab
        var lastTab = localStorage.getItem('lastTab');
        if (lastTab) {
            $('a[href="' + lastTab + '"]').tab('show');
        }
        
        // Form validation for lab results
        $('form').on('submit', function(e) {
            var textarea = $(this).find('textarea[name="lab_pat_results"]');
            if (textarea.length && textarea.val().trim().length < 5) {
                e.preventDefault();
                alert('Please enter at least 5 characters for the test results.');
                textarea.focus();
                return false;
            }
            return true;
        });
    });
    </script>
</body>
</html>