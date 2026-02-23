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

// Get the admin ID from session
$ad_id = $_SESSION['ad_id'];
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'admin';

// Initialize doc_id as 0 since admin doesn't have a doctor ID
$doc_id = 0;
$doc_name = 'Administrator'; // Default name for admin

// If the user is a doctor, get their details
if (isset($_SESSION['doc_id']) && $user_role == 'doctor') {
    $doc_id = $_SESSION['doc_id'];
    $doc_query = "SELECT doc_fname, doc_lname FROM his_docs WHERE doc_id = ?";
    $doc_stmt = $mysqli->prepare($doc_query);
    $doc_stmt->bind_param('i', $doc_id);
    $doc_stmt->execute();
    $doc_res = $doc_stmt->get_result();
    $doctor = $doc_res->fetch_object();
    $doc_name = $doctor ? $doctor->doc_fname . ' ' . $doctor->doc_lname : 'Unknown Doctor';
    $doc_stmt->close();
} else {
    // Admin user - get admin name if available
    if (isset($_SESSION['ad_fname']) && isset($_SESSION['ad_lname'])) {
        $doc_name = $_SESSION['ad_fname'] . ' ' . $_SESSION['ad_lname'];
    }
}

$err = ''; // Initialize error variable

/* ---------------------------
   Handle Doctor's Note Submission
--------------------------- */
if (isset($_POST['add_note'])) {
    $pat_id = intval($_POST['pat_id']);
    $pat_notes = trim($_POST['pat_notes']);
    $notes_date = date('Y-m-d H:i:s');
    $pat_number = trim($_POST['pat_number']);
    
    // First check if table exists
    $table_exists = $mysqli->query("SHOW TABLES LIKE 'his_notes'");
    if ($table_exists->num_rows > 0) {
        // Check if his_notes table has doc_id column
        $check_column = $mysqli->query("SHOW COLUMNS FROM his_notes LIKE 'doc_id'");
        
        if ($check_column->num_rows > 0) {
            // Table has doc_id column - check if NULL is allowed
            $column_info = $check_column->fetch_assoc();
            $allows_null = ($column_info['Null'] == 'YES');
            
            if ($doc_id > 0) {
                // Doctor is adding note
                $query = "INSERT INTO his_notes (pat_id, pat_notes, notes_date, doc_id) VALUES (?, ?, ?, ?)";
                $stmt = $mysqli->prepare($query);
                if ($stmt) {
                    $stmt->bind_param('issi', $pat_id, $pat_notes, $notes_date, $doc_id);
                }
            } else {
                // Admin is adding note
                if ($allows_null) {
                    // Column allows NULL
                    $query = "INSERT INTO his_notes (pat_id, pat_notes, notes_date, doc_id) VALUES (?, ?, ?, NULL)";
                    $stmt = $mysqli->prepare($query);
                    if ($stmt) {
                        $stmt->bind_param('iss', $pat_id, $pat_notes, $notes_date);
                    }
                } else {
                    // Column does NOT allow NULL, use 0
                    $zero_value = 0;
                    $query = "INSERT INTO his_notes (pat_id, pat_notes, notes_date, doc_id) VALUES (?, ?, ?, ?)";
                    $stmt = $mysqli->prepare($query);
                    if ($stmt) {
                        $stmt->bind_param('issi', $pat_id, $pat_notes, $notes_date, $zero_value);
                    }
                }
            }
        } else {
            // Table doesn't have doc_id column
            $query = "INSERT INTO his_notes (pat_id, pat_notes, notes_date) VALUES (?, ?, ?)";
            $stmt = $mysqli->prepare($query);
            if ($stmt) {
                $stmt->bind_param('iss', $pat_id, $pat_notes, $notes_date);
            }
        }
        
        if ($stmt && $stmt->execute()) {
            // Use JavaScript redirect instead of header() to avoid issues
            echo '<script>window.location.href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?pat_id=' . $pat_id . '&pat_number=' . urlencode($pat_number) . '";</script>';
            exit;
        } else {
            $err = "Error saving note: " . ($stmt ? $stmt->error : $mysqli->error);
        }
        if ($stmt) $stmt->close();
    } else {
        $err = "Notes table does not exist.";
    }
}

/* ---------------------------
   Handle Laboratory Test Submission
--------------------------- */
if (isset($_POST['add_lab_test'])) {
    $pat_id = intval($_GET['pat_id']);
    $lab_pat_name = trim($_POST['lab_pat_name']);
    $lab_pat_ailment = trim($_POST['lab_pat_ailment'] ?? '');
    $lab_date_rec = date('Y-m-d H:i:s');
    $lab_pat_number = trim($_GET['pat_number']);
    $lab_pat_tests = trim($_POST['lab_pat_tests']);
    $lab_pat_results = trim($_POST['lab_pat_results'] ?? ''); // Initially empty
    
    // First check if table exists
    $table_exists = $mysqli->query("SHOW TABLES LIKE 'his_laboratory'");
    if ($table_exists->num_rows > 0) {
        // Check table structure to see what columns exist
        $table_structure = $mysqli->query("DESCRIBE his_laboratory");
        $columns = [];
        while ($col = $table_structure->fetch_assoc()) {
            $columns[$col['Field']] = $col;
        }
        
        // Build query based on actual table structure
        $query = "INSERT INTO his_laboratory (";
        $placeholders = "(";
        $bind_types = "";
        $bind_values = [];
        
        // Always include these columns
        $query .= "pat_id, lab_pat_name, lab_pat_ailment, lab_date_rec, lab_pat_number, lab_pat_tests, lab_pat_results";
        $placeholders .= "?, ?, ?, ?, ?, ?, ?";
        $bind_types .= "issssss";
        $bind_values[] = &$pat_id;
        $bind_values[] = &$lab_pat_name;
        $bind_values[] = &$lab_pat_ailment;
        $bind_values[] = &$lab_date_rec;
        $bind_values[] = &$lab_pat_number;
        $bind_values[] = &$lab_pat_tests;
        $bind_values[] = &$lab_pat_results;
        
        // Check if doc_id column exists and handle it
        if (isset($columns['doc_id'])) {
            $allows_null = ($columns['doc_id']['Null'] == 'YES');
            
            if ($doc_id > 0) {
                $query .= ", doc_id";
                $placeholders .= ", ?";
                $bind_types .= "i";
                $bind_values[] = &$doc_id;
            } else {
                if ($allows_null) {
                    $query .= ", doc_id";
                    $placeholders .= ", NULL";
                } else {
                    $query .= ", doc_id";
                    $placeholders .= ", ?";
                    $bind_types .= "i";
                    $zero_value = 0;
                    $bind_values[] = &$zero_value;
                }
            }
        }
        
        // Check if lab_status column exists
        if (isset($columns['lab_status'])) {
            $query .= ", lab_status";
            $placeholders .= ", 'pending'";
        }
        
        $query .= ") VALUES " . $placeholders . ")";
        
        // Prepare and execute query
        $stmt = $mysqli->prepare($query);
        if ($stmt) {
            // Dynamically bind parameters
            if (!empty($bind_types)) {
                $params = array_merge([$bind_types], $bind_values);
                call_user_func_array([$stmt, 'bind_param'], $params);
            }
            
            if ($stmt->execute()) {
                echo '<script>window.location.href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?pat_id=' . $pat_id . '&pat_number=' . urlencode($lab_pat_number) . '";</script>';
                exit;
            } else {
                $err = "Error saving lab test: " . $stmt->error;
                // Debug: show the actual query
                error_log("Failed query: " . $query);
                error_log("Bind types: " . $bind_types);
                error_log("Error: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $err = "Error preparing lab test query: " . $mysqli->error;
            error_log("Prepare error: " . $mysqli->error);
            error_log("Query: " . $query);
        }
    } else {
        $err = "Laboratory table does not exist.";
    }
}

/* ---------------------------
   Handle Prescription Submission - ENHANCED VERSION
--------------------------- */
if (isset($_POST['add_patient_presc'])) {
    
    $pres_ins = trim($_POST['pres_ins']);
    $pat_number = trim($_POST['pat_number']);
    $pres_pat_name = trim($_POST['pres_pat_name']);
    $pres_pat_age = intval($_POST['pres_pat_age']);
    $pres_pat_addr = trim($_POST['pres_pat_addr']);
    $pres_pat_type = trim($_POST['pres_pat_type']);
    $pres_pat_ailment = trim($_POST['pres_pat_ailment'] ?? '');
    $pres_date = date('Y-m-d H:i:s');
    
    // Get patient ID if available
    $pat_id = isset($_GET['pat_id']) ? intval($_GET['pat_id']) : 0;
    
    // First check if table exists
    $table_exists = $mysqli->query("SHOW TABLES LIKE 'his_prescriptions'");
    if ($table_exists->num_rows > 0) {
        // Check table structure
        $table_structure = $mysqli->query("DESCRIBE his_prescriptions");
        $columns = [];
        while ($col = $table_structure->fetch_assoc()) {
            $columns[$col['Field']] = $col;
        }
        
        // Build query dynamically based on actual table structure
        $query = "INSERT INTO his_prescriptions (";
        $placeholders = "(";
        $bind_types = "";
        $bind_values = [];
        
        // Always include these core columns
        $query .= "pat_number, pres_pat_name, pres_pat_age, pres_pat_addr, pres_pat_type, pres_date, pres_pat_ailment, pres_ins";
        $placeholders .= "?, ?, ?, ?, ?, ?, ?, ?";
        $bind_types .= "ssisssss";
        $bind_values[] = &$pat_number;
        $bind_values[] = &$pres_pat_name;
        $bind_values[] = &$pres_pat_age;
        $bind_values[] = &$pres_pat_addr;
        $bind_values[] = &$pres_pat_type;
        $bind_values[] = &$pres_date;
        $bind_values[] = &$pres_pat_ailment;
        $bind_values[] = &$pres_ins;
        
        // Add pat_id if column exists and we have the value
        if (isset($columns['pat_id']) && $pat_id > 0) {
            $query .= ", pat_id";
            $placeholders .= ", ?";
            $bind_types .= "i";
            $bind_values[] = &$pat_id;
        }
        
        // Add doc_id if column exists
        if (isset($columns['doc_id'])) {
            if ($doc_id > 0) {
                $query .= ", doc_id";
                $placeholders .= ", ?";
                $bind_types .= "i";
                $bind_values[] = &$doc_id;
            }
        }
        
        // Add doc_name if column exists
        if (isset($columns['doc_name'])) {
            $query .= ", doc_name";
            $placeholders .= ", ?";
            $bind_types .= "s";
            $bind_values[] = &$doc_name;
        }
        
        // IMPORTANT: Always set pres_status to 'pending' by default
        if (isset($columns['pres_status'])) {
            $query .= ", pres_status";
            $placeholders .= ", 'pending'";
        }
        
        $query .= ") VALUES " . $placeholders . ")";
        
        // Prepare and execute query
        $stmt = $mysqli->prepare($query);
        if ($stmt) {
            // Dynamically bind parameters
            if (!empty($bind_types)) {
                $params = array_merge([$bind_types], $bind_values);
                call_user_func_array([$stmt, 'bind_param'], $params);
            }
            
            if ($stmt->execute()) {
                $new_pres_id = $stmt->insert_id;
                
                // Store prescription ID in session for reference if needed
                $_SESSION['last_pres_id'] = $new_pres_id;
                
                echo '<script>window.location.href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?pat_id=' . $pat_id . '&pat_number=' . urlencode($pat_number) . '&pres_added=1";</script>';
                exit;
            } else {
                $err = "Error saving prescription: " . $stmt->error;
                error_log("Prescription Error: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $err = "Error preparing prescription query: " . $mysqli->error;
        }
    } else {
        $err = "Prescriptions table does not exist.";
    }
}

/* ---------------------------
   Handle Vitals Submission
--------------------------- */
if (isset($_POST['add_vitals'])) {
    $vit_bodytemp = floatval($_POST['vit_bodytemp']);
    $vit_heartpulse = intval($_POST['vit_heartpulse']);
    $vit_resprate = intval($_POST['vit_resprate']);
    $vit_bloodpress = trim($_POST['vit_bloodpress']);
    $vit_weight = floatval($_POST['vit_weight']);
    $vit_height = floatval($_POST['vit_height']);
    $vit_daterec = date('Y-m-d H:i:s');
    $pat_id = intval($_GET['pat_id']);
    $vit_number = trim($_GET['pat_number']);
    
    // First check if table exists
    $table_exists = $mysqli->query("SHOW TABLES LIKE 'his_vitals'");
    if ($table_exists->num_rows > 0) {
        // Calculate BMI: weight (kg) / (height (m))^2
        if ($vit_height > 0) {
            $vit_bmi = $vit_weight / ($vit_height * $vit_height);
        } else {
            $vit_bmi = 0;
        }
        
        $query = "INSERT INTO his_vitals (vit_number, pat_id, vit_bodytemp, vit_heartpulse, vit_resprate, vit_bloodpress, vit_weight, vit_height, vit_bmi, vit_daterec)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param("sissssddds", $vit_number, $pat_id, $vit_bodytemp, $vit_heartpulse, $vit_resprate, $vit_bloodpress, $vit_weight, $vit_height, $vit_bmi, $vit_daterec);
            
            if ($stmt->execute()) {
                echo '<script>window.location.href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?pat_id=' . $pat_id . '&pat_number=' . urlencode($vit_number) . '";</script>';
                exit;
            } else {
                $err = "Error saving vitals: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $err = "Error preparing vitals query: " . $mysqli->error;
        }
    } else {
        $err = "Vitals table does not exist.";
    }
}

/* ---------------------------
   Fetch Existing Patient Data
--------------------------- */
if (!isset($_GET['pat_id']) || !isset($_GET['pat_number'])) {
    die("Patient ID and Number are required");
}

$pat_number = $_GET['pat_number'];
$pat_id = intval($_GET['pat_id']);

$ret = "SELECT * FROM his_patients WHERE pat_id=?";
$stmt = $mysqli->prepare($ret);
$stmt->bind_param('i', $pat_id);
$stmt->execute();
$res = $stmt->get_result();
$patient_details = $res->fetch_object();

if (!$patient_details) {
    die("Patient not found");
}
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
                    <!-- Display error messages -->
                    <?php if(!empty($err)): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($err ?? ''); ?>
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
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Patients</a></li>
                                        <li class="breadcrumb-item active">View Patients</li>
                                    </ol>
                                </div>
                                <h4 class="page-title"><?php echo htmlspecialchars($patient_details->pat_fname . ' ' . $patient_details->pat_lname) ?? 'Patient Profile'; ?>'s Profile</h4>
                                <p class="text-muted">
                                    <strong>User:</strong> <?php echo htmlspecialchars($doc_name); ?> 
                                    <strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($user_role)); ?>
                                </p>
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
                                        <span class="ml-2 font-20"><strong><?php echo htmlspecialchars($patient_details->pat_number ?? ''); ?></strong></span>
                                    </p>
                                    <hr>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>Full Name :</strong>
                                        <span class="ml-2"><?php echo htmlspecialchars(($patient_details->pat_fname ?? '') . ' ' . ($patient_details->pat_lname ?? '')); ?></span>
                                    </p>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>Mobile :</strong>
                                        <span class="ml-2"><?php echo htmlspecialchars($patient_details->pat_phone ?? ''); ?></span>
                                    </p>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>Address :</strong>
                                        <span class="ml-2"><?php echo htmlspecialchars($patient_details->pat_addr ?? ''); ?></span>
                                    </p>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>Date Of Birth :</strong>
                                        <span class="ml-2"><?php echo htmlspecialchars($patient_details->pat_dob ?? ''); ?></span>
                                    </p>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>Age :</strong>
                                        <span class="ml-2"><?php echo htmlspecialchars($patient_details->pat_age ?? ''); ?> Years</span>
                                    </p>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>State :</strong>
                                        <span class="ml-2"><?php echo htmlspecialchars($patient_details->pat_state ?? ''); ?></span>
                                    </p>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>Nationality :</strong>
                                        <span class="ml-2"><?php echo htmlspecialchars($patient_details->pat_nationality ?? ''); ?></span>
                                    </p>
                                    <p class="text-muted mb-2 font-13">
                                        <strong>Date Of Registration :</strong>
                                        <span class="ml-2"><?php echo htmlspecialchars($patient_details->pat_date_joined ?? ''); ?></span>
                                    </p>
                                    <hr>
                                </div>
                            </div>
                        </div> 

                        <!-- Main Content: Tabs -->
                        <div class="col-lg-8 col-xl-8">
                            <div class="card-box">
                                <!-- REARRANGED TAB ORDER: vitals/note/prescription/lab -->
                                <ul class="nav nav-pills navtab-bg nav-justified">
                                    <li class="nav-item">
                                        <a href="#vitals" class="nav-link active" data-toggle="tab" aria-expanded="true">Vitals</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#doctor_note" class="nav-link" data-toggle="tab" aria-expanded="false">Doctor's Note</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#prescription" class="nav-link" data-toggle="tab" aria-expanded="false">Prescription</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#lab_records" class="nav-link" data-toggle="tab" aria-expanded="false">Lab Records</a>
                                    </li>
                                </ul>
                                
                                <!-- Tab Panes - FIXED STRUCTURE -->
                                <div class="tab-content">
                                    <!-- Vitals Tab Pane -->
                                    <div class="tab-pane fade show active" id="vitals">
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h4 class="header-title">Record Vitals</h4>
                                            </div>
                                            <div class="card-body">
                                                <form method="post">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label>Body Temperature (°C)</label>
                                                                <input type="number" step="0.1" class="form-control" name="vit_bodytemp" required>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label>Heart Rate/Pulse (BPM)</label>
                                                                <input type="number" class="form-control" name="vit_heartpulse" required>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label>Respiratory Rate (breaths/min)</label>
                                                                <input type="number" class="form-control" name="vit_resprate" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label>Blood Pressure (mmHg)</label>
                                                                <input type="text" class="form-control" name="vit_bloodpress" placeholder="120/80" required>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label>Weight (kg)</label>
                                                                <input type="number" step="0.1" class="form-control" name="vit_weight" required>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label>Height (m)</label>
                                                                <input type="number" step="0.01" class="form-control" name="vit_height" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <button type="submit" name="add_vitals" class="btn btn-primary">Save Vitals</button>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <hr>
                                        <h4>Recorded Vitals</h4>
                                        <ul class="list-unstyled timeline-sm">
                                            <?php
                                            if(isset($_GET['pat_id'])){
                                                $pat_id = intval($_GET['pat_id']);
                                                $query = "SELECT * FROM his_vitals WHERE pat_id = ? ORDER BY vit_id DESC";
                                                $stmt= $mysqli->prepare($query);
                                                if ($stmt) {
                                                    $stmt->bind_param("i", $pat_id);
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();
                                                    if ($result->num_rows > 0) {
                                                        while($row = $result->fetch_object()){
                                                            $bmi = $row->vit_bmi ?? 0;
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
                                                    <p><strong>Body Temp:</strong> <?php echo htmlspecialchars($row->vit_bodytemp ?? ''); ?> °C</p>
                                                    <p><strong>Pulse Rate:</strong> <?php echo htmlspecialchars($row->vit_heartpulse ?? ''); ?> bpm</p>
                                                    <p><strong>Respiratory Rate:</strong> <?php echo htmlspecialchars($row->vit_resprate ?? ''); ?> breaths/min</p>
                                                    <p><strong>Blood Pressure:</strong> <?php echo htmlspecialchars($row->vit_bloodpress ?? ''); ?> mmHg</p>
                                                    <p><strong>Weight:</strong> <?php echo htmlspecialchars($row->vit_weight ?? ''); ?> kg</p>
                                                    <p><strong>Height:</strong> <?php echo htmlspecialchars($row->vit_height ?? ''); ?> m</p>
                                                    <p><strong>BMI:</strong> <?php echo number_format($row->vit_bmi ?? 0, 2); ?> (<?php echo htmlspecialchars($category); ?>)</p>
                                                </div>
                                            </li>
                                            <?php 
                                                        } 
                                                    } else {
                                                        echo '<li class="timeline-sm-item">No vitals recorded yet.</li>';
                                                    }
                                                    $stmt->close();
                                                }
                                            } 
                                            ?>
                                        </ul>
                                    </div> <!-- End #vitals -->
                                    
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
                                                    <input type="hidden" name="pat_id" value="<?php echo htmlspecialchars($_GET['pat_id'] ?? ''); ?>">
                                                    <input type="hidden" name="pat_number" value="<?php echo htmlspecialchars($_GET['pat_number'] ?? ''); ?>">
                                                    <button type="submit" name="add_note" class="btn btn-primary">Save Note</button>
                                                </form>
                                            </div>
                                        </div>
                                        <h4>Medical Notes</h4>
                                        <ul class="list-unstyled timeline-sm">
                                            <?php
                                            $pat_id = intval($_GET['pat_id']);
                                            // Simple query without JOIN to avoid errors
                                            $ret = "SELECT * FROM his_notes WHERE pat_id = ? ORDER BY notes_date DESC";
                                            $stmt = $mysqli->prepare($ret);
                                            if ($stmt) {
                                                $stmt->bind_param('i', $pat_id);
                                                $stmt->execute();
                                                $res = $stmt->get_result();
                                                if ($res->num_rows > 0) {
                                                    while ($row = $res->fetch_object()) {
                                                        $mysqlDateTime = $row->notes_date;
                                            ?>
                                            <li class="timeline-sm-item">
                                                <span class="timeline-sm-date"><?php echo date("d-m-Y", strtotime($mysqlDateTime)); ?></span>
                                                <div class="border p-2 mb-2 rounded">
                                                    <strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($row->pat_notes ?? '')); ?>
                                                </div>
                                            </li>
                                            <?php 
                                                    } 
                                                } else {
                                                    echo '<li class="timeline-sm-item">No notes found.</li>';
                                                }
                                                $stmt->close();
                                            }
                                            ?>
                                        </ul>
                                    </div> <!-- End #doctor_note -->
                                    
                                    <!-- Prescription Tab Pane -->
                                    <div class="tab-pane fade" id="prescription">
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
                                                    <input type="hidden" name="pat_number" value="<?php echo htmlspecialchars($_GET['pat_number'] ?? ''); ?>">
                                                    <input type="hidden" name="pres_pat_name" value="<?php echo htmlspecialchars(($patient_details->pat_fname ?? '') . ' ' . ($patient_details->pat_lname ?? '')); ?>">
                                                    <input type="hidden" name="pres_pat_age" value="<?php echo htmlspecialchars($patient_details->pat_age ?? ''); ?>">
                                                    <input type="hidden" name="pres_pat_addr" value="<?php echo htmlspecialchars($patient_details->pat_addr ?? ''); ?>">
                                                    <input type="hidden" name="pres_pat_type" value="Outpatient">
                                                    <input type="hidden" name="pres_pat_ailment" value="">
                                                    <button type="submit" name="add_patient_presc" class="btn btn-primary">Save Prescription</button>
                                                </form>
                                            </div>
                                        </div>
                                        <h4>Previous Prescriptions</h4>
                                        <ul class="list-unstyled timeline-sm">
                                            <?php
                                            if(isset($_GET['pat_number'])){
                                                $pat_number = $_GET['pat_number'];
                                                // Simple query without JOIN to avoid errors
                                                $query = "SELECT * FROM his_prescriptions WHERE pat_number = ? ORDER BY pres_date DESC";
                                                $stmt = $mysqli->prepare($query);
                                                if ($stmt) {
                                                    $stmt->bind_param("s", $pat_number);
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();
                                                    if ($result->num_rows > 0) {
                                                        while($row = $result->fetch_object()){
                                                            $mysqlDateTime = $row->pres_date;
                                            ?>
                                            <li class="timeline-sm-item">
                                                <span class="timeline-sm-date"><?php echo date("d-m-Y", strtotime($mysqlDateTime));?></span>
                                                <div class="border p-2 mb-2 rounded">
                                                    <?php if(isset($row->doc_name) && !empty($row->doc_name)): ?>
                                                    <strong>Doctor:</strong> <?php echo htmlspecialchars($row->doc_name ?? ''); ?><br>
                                                    <?php endif; ?>
                                                    <strong>Prescription:</strong> <?php echo nl2br(htmlspecialchars($row->pres_ins ?? '')); ?>
                                                </div>
                                            </li>
                                            <?php 
                                                        } 
                                                    } else {
                                                        echo '<li class="timeline-sm-item">No prescriptions found.</li>';
                                                    }
                                                    $stmt->close();
                                                }
                                            } 
                                            ?>
                                        </ul>
                                    </div> <!-- End #prescription -->
                                    
                                    <!-- Lab Records Tab Pane -->
                                    <div class="tab-pane fade" id="lab_records">
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h4 class="header-title">Add Laboratory Test</h4>
                                            </div>
                                            <div class="card-body">
                                                <form method="post">
                                                    <div class="form-row">
                                                        <div class="form-group col-md-12">
                                                            <label>Test Name *</label>
                                                            <input type="text" class="form-control" name="lab_pat_tests" required>
                                                        </div>
                                                    </div>
                                                    <!-- Hidden fields -->
                                                    <input type="hidden" name="pat_id" value="<?php echo htmlspecialchars($_GET['pat_id'] ?? ''); ?>">
                                                    <input type="hidden" name="lab_pat_name" value="<?php echo htmlspecialchars(($patient_details->pat_fname ?? '') . ' ' . ($patient_details->pat_lname ?? '')); ?>">
                                                    <input type="hidden" name="lab_pat_ailment" value="">
                                                    <input type="hidden" name="pat_number" value="<?php echo htmlspecialchars($_GET['pat_number'] ?? ''); ?>">
                                                    <!-- NO lab_number field - it will be generated in PHP -->
                                                    <button type="submit" name="add_lab_test" class="btn btn-primary">Save Lab Test</button>
                                                </form>
                                            </div>
                                        </div>
                                        <h4>Laboratory Tests</h4>
                                        <ul class="list-unstyled timeline-sm">
                                            <?php
                                            if(isset($_GET['pat_id'])){
                                                $pat_id = intval($_GET['pat_id']);
                                                // Simple query without JOIN to avoid errors
                                                $query = "SELECT * FROM his_laboratory WHERE pat_id = ? ORDER BY lab_date_rec DESC";
                                                $stmt = $mysqli->prepare($query);
                                                if ($stmt) {
                                                    $stmt->bind_param("i", $pat_id);
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();
                                                    if ($result->num_rows > 0) {
                                                        while($row = $result->fetch_object()){
                                                            $mysqlDateTime = $row->lab_date_rec;
                                            ?>
                                            <li class="timeline-sm-item">
                                                <span class="timeline-sm-date"><?php echo date("d-m-Y", strtotime($mysqlDateTime)); ?></span>
                                                <div class="border p-2 mb-2 rounded">
                                                    <p><strong>Test:</strong> <?php echo nl2br(htmlspecialchars($row->lab_pat_tests ?? '')); ?></p>
                                                    <?php if (empty($row->lab_pat_results)): ?>
                                                        <p><strong>No Result yet</strong></p>
                                                    <?php else: ?>
                                                        <p><strong>Result:</strong> <?php echo nl2br(htmlspecialchars($row->lab_pat_results ?? '')); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </li>
                                            <?php 
                                                        } 
                                                    } else {
                                                        echo '<li class="timeline-sm-item">No laboratory tests found.</li>';
                                                    }
                                                    $stmt->close();
                                                }
                                            } 
                                            ?>
                                        </ul>
                                    </div> <!-- End #lab_records -->
                                </div> <!-- End tab-content -->
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
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Debug info
        console.log('Page loaded, checking for errors...');
        
        // Only show alert if there's a genuine error (not empty or whitespace)
        const errorMsg = '<?php echo addslashes(trim($err)); ?>';
        
        if (errorMsg && errorMsg.trim() !== '') {
            console.log('Found error:', errorMsg);
            // Use setTimeout to ensure DOM is fully loaded
            setTimeout(function() {
                alert('Error: ' + errorMsg);
            }, 100);
        } else {
            console.log('No errors found.');
        }
        
        // Form validation
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!this.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                this.classList.add('was-validated');
            });
        });
    });
    </script>
</body>
</html>