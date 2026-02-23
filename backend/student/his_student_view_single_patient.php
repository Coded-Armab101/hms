<?php
// Start session (only once) and set error reporting.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include configuration and login check
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();

// Get the doctor ID from session
$doc_id = $_SESSION['doc_id'];


/* ---------------------------
   Handle Doctor's Note Submission
--------------------------- */
if (isset($_POST['add_note'])) {
    $pat_id = $_POST['pat_id'];
    $pat_notes = $_POST['pat_notes'];
    $notes_date = date('Y-m-d H:i:s');
    $pat_number = $_POST['pat_number'];
    
    // Insert into his_notes along with the current doctor ID
    $query = "INSERT INTO his_notes (pat_id, pat_notes, notes_date, doc_id) VALUES (?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('issi', $pat_id, $pat_notes, $notes_date, $doc_id);
    
    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?pat_id=" . $pat_id . "&pat_number=" . $_GET['pat_number']);
        exit;
    } else {
        $err = "Error saving note: " . $stmt->error;
    }
    $stmt->close();
}

/* ---------------------------
   Handle Laboratory Test Submission
--------------------------- */
if (isset($_POST['add_lab_test'])) {
    $pat_id = $_GET['pat_id'];
    $lab_pat_name = $_POST['lab_pat_name'];
    $lab_pat_ailment = $_POST['lab_pat_ailment'];
    $lab_date_rec = date('Y-m-d H:i:s');
    $lab_pat_number = $_GET['pat_number'];
    $lab_pat_tests = $_POST['lab_pat_tests'];
    $lab_pat_results = $_POST['lab_pat_results'];
    $lab_number = $_POST['lab_number'];
    
    // Insert doc_id into the laboratory table
    $query = "INSERT INTO his_laboratory (pat_id, lab_pat_name, lab_pat_ailment, lab_date_rec, lab_pat_number, lab_pat_tests, lab_pat_results, lab_number, doc_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('isssssssi', $pat_id, $lab_pat_name, $lab_pat_ailment, $lab_date_rec, $lab_pat_number, $lab_pat_tests, $lab_pat_results, $lab_number, $doc_id);
    
    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?pat_id=" . $_GET['pat_id'] . "&pat_number=" . $_GET['pat_number']);
        exit;
    } else {
        $err = "Error saving lab test: " . $stmt->error;
    }
    $stmt->close();
}

if (isset($_POST['update_lab_test'])) {
    $lab_id = $_POST['lab_id'];
    $lab_pat_results = $_POST['lab_pat_results'];
    // Optionally, update doc_id if needed, here we assume it stays as is
    $query = "UPDATE his_laboratory SET lab_pat_results = ? WHERE lab_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('si', $lab_pat_results, $lab_id);
    if ($stmt->execute()){
        header("Location: " . $_SERVER['PHP_SELF'] . "?pat_id=" . $_GET['pat_id'] . "&pat_number=" . $_GET['pat_number']);
        exit;
    } else {
         $err = "Error updating lab test: " . $stmt->error;
    }
    $stmt->close();
}

/* ---------------------------
   Handle Prescription Submission
--------------------------- */
if (isset($_POST['add_patient_presc'])) {
    $pres_ins = trim($_POST['pres_ins']);
    $pat_number = trim($_POST['pat_number']);
    $pres_pat_name = trim($_POST['pres_pat_name']);
    $pres_pat_age = intval($_POST['pres_pat_age']);
    $pres_pat_addr = trim($_POST['pres_pat_addr']);
    $pres_pat_type = trim($_POST['pres_pat_type']);
    $pres_pat_ailment = trim($_POST['pres_pat_ailment']);
    $pres_date = date('Y-m-d H:i:s');
    
    // Insert into his_prescriptions with the doctor ID
    $query = "INSERT INTO his_prescriptions (pres_pat_name, pres_pat_age, pat_number, pres_pat_addr, pres_pat_type, pres_date, pres_pat_ailment, pres_ins, doc_id, doc_name)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
         die("Prepare failed: " . $mysqli->error);
    }
    $stmt->bind_param("sissssssis", $pres_pat_name, $pres_pat_age, $pat_number, $pres_pat_addr, $pres_pat_type, $pres_date, $pres_pat_ailment, $pres_ins, $doc_id, $doc_name);
    if ($stmt->execute()) {
         header("Location: " . $_SERVER['PHP_SELF'] . "?pat_id=" . $_GET['pat_id'] . "&pat_number=" . $pat_number);
         exit;
    } else {
         $err = "Error saving prescription: " . $stmt->error;
    }
    $stmt->close();
}

/* ---------------------------
   Handle Vitals Submission
--------------------------- */
if (isset($_POST['add_vitals'])) {
    $vit_bodytemp = $_POST['vit_bodytemp'];
    $vit_heartpulse = $_POST['vit_heartpulse'];
    $vit_resprate = $_POST['vit_resprate'];
    $vit_bloodpress = $_POST['vit_bloodpress'];
    $vit_weight = $_POST['vit_weight'];
    $vit_height = $_POST['vit_height'];
    $vit_daterec = date('Y-m-d H:i:s');
    $pat_id = $_GET['pat_id'];
    $vit_number = $_GET['pat_number']; // patient number
    
    // Calculate BMI: weight (kg) / (height (m))^2
    if ($vit_height > 0) {
        $vit_bmi = $vit_weight / ($vit_height * $vit_height);
    } else {
        $vit_bmi = 0;
    }
    
    $query = "INSERT INTO his_vitals (vit_number, pat_id, vit_bodytemp, vit_heartpulse, vit_resprate, vit_bloodpress, vit_weight, vit_height, vit_bmi, vit_daterec)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("sissssddds", $vit_number, $pat_id, $vit_bodytemp, $vit_heartpulse, $vit_resprate, $vit_bloodpress, $vit_weight, $vit_height, $vit_bmi, $vit_daterec);
    
    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?pat_id=" . $pat_id . "&pat_number=" . $vit_number);
        exit;
    } else {
        $err = "Error saving vitals: " . $stmt->error;
    }
    $stmt->close();
}

/* ---------------------------
   Fetch Existing Patient Data
--------------------------- */
$pat_number = $_GET['pat_number'];
$pat_id = $_GET['pat_id'];
$ret = "SELECT * FROM his_patients WHERE pat_id=?";
$stmt = $mysqli->prepare($ret);
$stmt->bind_param('i', $pat_id);
$stmt->execute();
$res = $stmt->get_result();
$patient_details = $res->fetch_object();
?>

<!DOCTYPE html>
<html lang="en">
<?php include('assets/inc/head.php'); ?>
<body>
    <!-- Begin page -->
    <div id="wrapper">
        <!-- Topbar -->
        <?php include("assets/inc/nav.php"); ?>
        <!-- Left Sidebar -->
        <?php include("assets/inc/sidebar.php"); ?>
        <!-- Start Page Content -->
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
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Patients</a></li>
                                        <li class="breadcrumb-item active">View Patients</li>
                                    </ol>
                                </div>
                                <h4 class="page-title"><?php echo $patient_details->pat_fname . ' ' . $patient_details->pat_lname; ?>'s Profile</h4>
                            </div>
                        </div>
                    </div>
                    <!-- End page title -->

                    <div class="row">
                        <!-- Patient Details Sidebar -->
                        <div class="col-lg-4 col-xl-4">
                            <div class="card-box text-center">
                                <img src="assets/images/users/patient.png" class="rounded-circle avatar-lg img-thumbnail" alt="profile-image">
                                <div class="text-left mt-3">
                                    <p class="text-muted mb-2 font-20">
                                        <strong>File Number :</strong> 
                                        <span class="ml-2 font-20"><strong><?php echo $patient_details->pat_number; ?></strong></span>
                                    </p>
                                    <hr>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>Full Name :</strong>
                                        <span class="ml-2"><?php echo $patient_details->pat_fname; ?> <?php echo $patient_details->pat_lname; ?></span>
                                    </p>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>Mobile :</strong>
                                        <span class="ml-2"><?php echo $patient_details->pat_phone; ?></span>
                                    </p>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>Address :</strong>
                                        <span class="ml-2"><?php echo $patient_details->pat_addr; ?></span>
                                    </p>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>Date Of Birth :</strong>
                                        <span class="ml-2"><?php echo $patient_details->pat_dob; ?></span>
                                    </p>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>Age :</strong>
                                        <span class="ml-2"><?php echo $patient_details->pat_age; ?> Years</span>
                                    </p>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>State :</strong>
                                        <span class="ml-2"><?php echo $patient_details->pat_state; ?></span>
                                    </p>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>Nationality :</strong>
                                        <span class="ml-2"><?php echo $patient_details->pat_nationality; ?></span>
                                    </p>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>Date Of Registration :</strong>
                                        <span class="ml-2"><?php echo $patient_details->pat_date_joined; ?></span>
                                    </p>
                                    <hr>
                                </div>
                            </div>
                        </div> 

                        <!-- Main Content: Tabs -->
                        <div class="col-lg-8 col-xl-8">
                            <div class="card-box">
                                <ul class="nav nav-pills navtab-bg nav-justified" role="tablist">
                                    <li class="nav-item">
                                        <a id="tab-prescription" href="#prescription" class="nav-link active" data-toggle="tab" role="tab" aria-controls="prescription" aria-selected="true">Prescription</a>
                                    </li>
                                    <li class="nav-item">
                                        <a id="tab-vitals" href="#vitals" class="nav-link" data-toggle="tab" role="tab" aria-controls="vitals" aria-selected="false">Vitals</a>
                                    </li>
                                    <li class="nav-item">
                                        <a id="tab-lab-records" href="#lab_records" class="nav-link" data-toggle="tab" role="tab" aria-controls="lab_records" aria-selected="false">Lab Records</a>
                                    </li>
                                    <li class="nav-item">
                                        <a id="tab-doctor-note" href="#doctor_note" class="nav-link" data-toggle="tab" role="tab" aria-controls="doctor_note" aria-selected="false">Doctor's Note</a>
                                    </li>
                                </ul>
                                
                                <!-- Tab Panes -->
                                <div class="tab-content">
                                    <!-- Prescription Tab Pane -->
                                    <div class="tab-pane fade show active" id="prescription" role="tabpanel" aria-labelledby="tab-prescription">
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h4 class="header-title">Add Prescription</h4>
                                            </div>
                                            <div class="card-body">
                                                <form method="post">
                                                    <div class="form-group">
                                                        <textarea class="form-control" name="pres_ins" rows="4" placeholder="Enter Prescription notes here..." required></textarea>
                                                    </div>
                                                    <!-- Hidden patient details -->
                                                    <input type="hidden" name="pat_number" value="<?php echo $_GET['pat_number']; ?>">
                                                    <input type="hidden" name="pres_pat_name" value="<?php echo $patient_details->pat_fname . ' ' . $patient_details->pat_lname; ?>">
                                                    <input type="hidden" name="pres_pat_age" value="<?php echo $patient_details->pat_age; ?>">
                                                    <input type="hidden" name="pres_pat_addr" value="<?php echo $patient_details->pat_addr; ?>">
                                                    <input type="hidden" name="pres_pat_type" value="Outpatient">
                                                    <input type="hidden" name="pres_pat_ailment" value="">
                                                    <button type="submit" name="add_patient_presc" class="btn btn-primary">Save Prescription</button>
                                                </form>
                                            </div>
                                        </div>
                                        <ul class="list-unstyled timeline-sm">
                                            <?php
                                            if(isset($_GET['pat_number'])){
                                                $pat_number = $_GET['pat_number'];
                                                $query = "SELECT p.*, CONCAT(d.doc_fname, ' ', d.doc_lname) AS doctor_name FROM his_prescriptions p JOIN his_docs d ON p.doc_id = d.doc_id WHERE p.pat_number = ? ORDER BY p.pres_date DESC";
                                                $stmt= $mysqli->prepare($query);
                                                $stmt->bind_param("s", $pat_number);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                while($row = $result->fetch_object()){
                                                    $mysqlDateTime = $row->pres_date;
                                            ?>
                                            <li class="timeline-sm-item">
                                                <span class="timeline-sm-date"><?php echo date("d-m-Y", strtotime($mysqlDateTime));?></span>
                                                <div class="border p-2 mb-2 rounded">
                                                <div class="border p-2 mb-2 rounded">
                                                    <strong>Doctor:</strong> <?php echo htmlspecialchars($row->doctor_name); ?><br>
                                                    <strong>Prescription:</strong> <?php echo nl2br(htmlspecialchars($row->pres_ins)); ?>
                                                    
                                                </div>

                                                </div>
                                            </li>
                                            <?php } } ?>
                                        </ul>
                                    </div>
                                    
                                    <!-- Vitals Tab Pane -->
                                    <div class="tab-pane fade" id="vitals">
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h4 class="header-title">Record Vitals</h4>
                                            </div>
                                            <!--<div class="card-body">
                                                <form method="post">
                                                    ... [Vitals form commented out] ...
                                                </form>
                                            </div>-->
                                        </div>
                                        <hr>
                                        <h4>Recorded Vitals</h4>
                                        <ul class="list-unstyled timeline-sm">
                                            <?php
                                            if(isset($_GET['pat_id'])){
                                                $pat_id = $_GET['pat_id'];
                                                $query = "SELECT * FROM his_vitals WHERE pat_id = ? ORDER BY vit_id DESC";
                                                $stmt= $mysqli->prepare($query);
                                                $stmt->bind_param("i", $pat_id);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                while($row = $result->fetch_object()){
                                                    $bmi = $row->vit_bmi;
                                                    $category = "";
                                                    if ($bmi < 18.5) {
                                                        $category = "Underweight";
                                                    } elseif ($bmi >= 18.5 && $bmi <= 24.9) {
                                                        $category = "Normal";
                                                    } elseif ($bmi >= 25 && $bmi <= 29.9) {
                                                        $category = "Overweight";
                                                    } else {
                                                        $category = "Obesity";
                                                    }
                                            ?>
                                            <li class="timeline-sm-item">
                                                <span class="timeline-sm-date"><?php echo date("d-m-Y", strtotime($row->vit_daterec));?></span>
                                                <div class="border p-2 mb-2 rounded">
                                                    <p><strong>Body Temp:</strong> <?php echo $row->vit_bodytemp; ?> °C</p>
                                                    <p><strong>Pulse Rate:</strong> <?php echo $row->vit_heartpulse; ?> bpm</p>
                                                    <p><strong>Respiratory Rate:</strong> <?php echo $row->vit_resprate; ?> breaths/min</p>
                                                    <p><strong>Blood Pressure:</strong> <?php echo $row->vit_bloodpress; ?> mmHg</p>
                                                    <p><strong>Weight:</strong> <?php echo $row->vit_weight; ?> kg</p>
                                                    <p><strong>Height:</strong> <?php echo $row->vit_height; ?> m</p>
                                                    <p><strong>BMI:</strong> <?php echo number_format($row->vit_bmi,2); ?> (<?php echo $category; ?>)</p>
                                                </div>
                                            </li>
                                            <?php } } ?>
                                        </ul>
                                    </div>
                                    
                                    <!-- Lab Records Tab Pane -->
                                    <div class="tab-pane fade" id="lab_records">
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h4 class="header-title">Add Laboratory Test</h4>
                                            </div>
                                            <div class="card-body">
                                                <form method="post">
                                                    <div class="form-row">
                                                        <div class="form-group col-md-3">
                                                            <label>Test Name</label>
                                                            <input type="text" class="form-control" name="lab_pat_tests" required>
                                                        </div>
                                                        <div class="form-group col-md-3">
                                                            <label>Test Result</label>
                                                            <input type="text" class="form-control" name="lab_pat_results" disabled>
                                                        </div>
                                                    </div>
                                                    <!-- Hidden fields -->
                                                    <input type="hidden" name="pat_id" value="<?php echo $_GET['pat_id']; ?>">
                                                    <input type="hidden" name="lab_pat_name" value="<?php echo $patient_details->pat_fname . ' ' . $patient_details->pat_lname; ?>">
                                                    <input type="hidden" name="lab_pat_ailment" value="">
                                                    <input type="hidden" name="pat_number" value="<?php echo $_GET['pat_number']; ?>">
                                                    <input type="hidden" name="lab_number" value="<?php echo uniqid('lab'); ?>">
                                                    <button type="submit" name="add_lab_test" class="btn btn-primary">Save Lab Test</button>
                                                </form>
                                            </div>
                                            <ul class="list-unstyled timeline-sm">
                                                <?php
                                                if(isset($_GET['pat_id'])){
                                                    $pat_number = $_GET['pat_id'];
                                                    $query = "SELECT l.*, CONCAT(d.doc_fname, ' ', d.doc_lname) AS doctor_name FROM his_laboratory l JOIN his_docs d ON l.doc_id = d.doc_id WHERE l.pat_id = ? ORDER BY l.lab_date_rec DESC";
                                                    $stmt= $mysqli->prepare($query);
                                                    $stmt->bind_param("s", $pat_id);
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();
                                                    while($row = $result->fetch_object()){
                                                        $mysqlDateTime = $row->lab_date_rec;
                                                ?>
                                                <li class="timeline-sm-item">
                                                    <span class="timeline-sm-date"><?php echo date("d-m-Y", strtotime($mysqlDateTime)); ?></span>
                                                    <div class="border p-2 mb-2 rounded">
                                                        <strong>Doctor:</strong> <?php echo htmlspecialchars($row->doctor_name); ?><br>
                                                        <p><strong>Test:</strong> <?php echo nl2br(htmlspecialchars($row->lab_pat_tests)); ?></p>
                                                        <?php if (empty($row->lab_pat_results)): ?>
                                                        <p><strong>No Result yet</strong> </p>
                                                        <?php else: ?>
                                                        <p><strong>Result:</strong> <?php echo nl2br(htmlspecialchars($row->lab_pat_results)); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </li>
                                                <?php } } ?>
                                            </ul>
                                        </div>   
                                    </div>
                                    
                                    <!-- Doctor's Note Tab Pane -->
                                    <div class="tab-pane fade" id="doctor_note">
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h4 class="header-title">Add New Note</h4>
                                            </div>
                                            <div class="card-body">
                                                <form method="post">
                                                    <div class="form-group">
                                                        <textarea class="form-control" name="pat_notes" rows="4" placeholder="Enter clinical notes here..." required></textarea>
                                                    </div>
                                                    <input type="hidden" name="pat_id" value="<?php echo $_GET['pat_id']; ?>">
                                                    <input type="hidden" name="pat_number" value="<?php echo $_GET['pat_number']; ?>">
                                                    <button type="submit" name="add_note" class="btn btn-primary">Save Note</button>
                                                </form>
                                            </div>
                                        </div>
                                        <ul class="list-unstyled timeline-sm">
                                            <?php
                                            $pat_id = $_GET['pat_id'];
                                            $ret = "SELECT p.*, CONCAT(d.doc_fname, ' ', d.doc_lname) AS doctor_name FROM his_notes p JOIN his_docs d ON p.doc_id = d.doc_id WHERE p.pat_id = ? ORDER BY p.notes_date DESC";
                                            $stmt= $mysqli->prepare($ret);
                                            $stmt->bind_param('i', $pat_id);
                                            $stmt->execute();
                                            $res=$stmt->get_result();
                                            while ($row = $res->fetch_object()) {
                                                $mysqlDateTime = $row->notes_date;
                                            ?>
                                            <li class="timeline-sm-item">
                                                <span class="timeline-sm-date"><?php echo date("d-m-Y", strtotime($mysqlDateTime)); ?></span>
                                                <div class="border p-2 mb-2 rounded">
                                                    <strong>Doctor:</strong> <?php echo htmlspecialchars($row->doctor_name); ?><br>
                                                    <strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($row->pat_notes)); ?>
                                                </div>
                                            </li>
                                            <?php } ?>
                                        </ul>
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

    <!-- App js-->
    <script src="assets/js/app.min.js"></script>
    
</body>
</html>
