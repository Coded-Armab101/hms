<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$aid = $_SESSION['ad_id'];
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'pharm';

// Check if user has pharmacy role
if ($user_role != 'pharm' && $user_role != 'admin') {
    $_SESSION['error'] = "Access denied. Pharmacy role required.";
    header('Location: his_admin_dashboard.php');
    exit;
}

// Initialize error variable
$err = '';

/* ---------------------------
   Handle Drug Dispensation (ENHANCED)
--------------------------- */
if (isset($_POST['add_to_dispense'])) {  // Changed from 'dispense_drugs'
    $pat_number = trim($_POST['pat_number']);
    $dispensed_by = $_SESSION['ad_fname'] . ' ' . $_SESSION['ad_lname'];
    $dispense_date = date('Y-m-d H:i:s');
    $dispense_notes = trim($_POST['dispense_notes'] ?? '');
    
    // Get drug names from prescription text
    $drug_names_input = trim($_POST['drug_names'] ?? '');
    
    // Validate drug names
    if (empty($drug_names_input)) {
        $err = "Please provide drug names from the prescription.";
    } else {
        // Parse drug names (could be comma separated or line separated)
        $drug_names = array_map('trim', preg_split('/[\n,]+/', $drug_names_input));
        $drug_names = array_filter($drug_names); // Remove empty entries
        
        if (empty($drug_names)) {
            $err = "Please provide valid drug names.";
        }
    }
    
    if (empty($err)) {
        // Begin transaction
        $mysqli->begin_transaction();
        
        try {
            // 1. Get LATEST prescription details for this patient
            $get_pres_query = "SELECT * FROM his_prescriptions WHERE pat_number = ? ORDER BY pres_date DESC LIMIT 1";
            $get_pres_stmt = $mysqli->prepare($get_pres_query);
            $get_pres_stmt->bind_param('s', $pat_number);
            $get_pres_stmt->execute();
            $pres_result = $get_pres_stmt->get_result();
            
            if ($pres_result->num_rows === 0) {
                throw new Exception("Prescription not found for patient number: " . htmlspecialchars($pat_number));
            }
            
            $prescription = $pres_result->fetch_object();
            $get_pres_stmt->close();
            
            // 2. Get patient ID from prescription or patient table
            $pat_id = $prescription->pat_id ?? 0;
            if ($pat_id == 0 && !empty($pat_number)) {
                // Try to get pat_id from patients table
                $pat_query = "SELECT pat_id FROM his_patients WHERE pat_number = ? LIMIT 1";
                $pat_stmt = $mysqli->prepare($pat_query);
                $pat_stmt->bind_param('s', $pat_number);
                $pat_stmt->execute();
                $pat_res = $pat_stmt->get_result();
                if ($pat_row = $pat_res->fetch_object()) {
                    $pat_id = $pat_row->pat_id;
                }
                $pat_stmt->close();
            }
            
            // 3. Get entire LATEST doctor's note for this patient
            $ailment = '';
            $latest_doctor_note = '';
            $note_date = '';
            
            if ($pat_id > 0) {
                // Get the LATEST doctor's note for this patient
                $get_note_query = "SELECT pat_notes, notes_date FROM his_notes WHERE pat_id = ? ORDER BY notes_date DESC LIMIT 1";
                $get_note_stmt = $mysqli->prepare($get_note_query);
                if ($get_note_stmt) {
                    $get_note_stmt->bind_param('i', $pat_id);
                    $get_note_stmt->execute();
                    $note_result = $get_note_stmt->get_result();
                    if ($note_result->num_rows > 0) {
                        $note = $note_result->fetch_object();
                        $latest_doctor_note = $note->pat_notes ?? '';
                        $note_date = $note->notes_date ?? '';
                        
                        // Use the ENTIRE doctor's note as the ailment
                        $ailment = $latest_doctor_note;
                    }
                    $get_note_stmt->close();
                }
            }
            
            // 4. Check if already dispensed
            $check_status_col = $mysqli->query("SHOW COLUMNS FROM his_prescriptions LIKE 'pres_status'");
            if ($check_status_col->num_rows > 0) {
                if (isset($prescription->pres_status) && $prescription->pres_status == 'dispensed') {
                    throw new Exception("This prescription has already been dispensed.");
                }
            }
            
            // 5. Check dispensed drugs table
            $check_dispensed = $mysqli->prepare("SELECT * FROM his_dispensed_drugs WHERE pat_number = ?");
            $check_dispensed->bind_param('s', $pat_number);
            $check_dispensed->execute();
            $dispensed_result = $check_dispensed->get_result();
            
            if ($dispensed_result->num_rows > 0) {
                throw new Exception("This prescription has already been dispensed (record found in dispensed drugs table).");
            }
            $check_dispensed->close();
            
            // 6. Update prescription status
            if ($check_status_col->num_rows > 0) {
                // Check what ID column exists
                $id_column = 'pat_number'; // default to patient number
                $pres_id_value = $pat_number;
                
                // Check which ID column exists in the prescriptions table
                $check_id_columns = ['pres_id', 'id', 'prescription_id'];
                foreach ($check_id_columns as $col) {
                    $check_col = $mysqli->query("SHOW COLUMNS FROM his_prescriptions LIKE '$col'");
                    if ($check_col->num_rows > 0 && isset($prescription->$col)) {
                        $id_column = $col;
                        $pres_id_value = $prescription->$col;
                        break;
                    }
                }
                
                if ($id_column == 'pat_number') {
                    $update_query = "UPDATE his_prescriptions SET pres_status = 'dispensed' WHERE pat_number = ?";
                    $update_stmt = $mysqli->prepare($update_query);
                    $update_stmt->bind_param('s', $pat_number);
                } else {
                    $update_query = "UPDATE his_prescriptions SET pres_status = 'dispensed' WHERE pat_number = ? AND $id_column = ?";
                    $update_stmt = $mysqli->prepare($update_query);
                    if (in_array($id_column, ['pres_id', 'id', 'prescription_id'])) {
                        $update_stmt->bind_param('si', $pat_number, $pres_id_value);
                    } else {
                        $update_stmt->bind_param('ss', $pat_number, $pres_id_value);
                    }
                }
                
                if (!$update_stmt->execute()) {
                    throw new Exception("Error updating prescription status: " . $update_stmt->error);
                }
                $update_stmt->close();
            }
            
            // 7. Check/Create enhanced dispensed drugs table
            $table_exists = $mysqli->query("SHOW TABLES LIKE 'his_dispensed_drugs'");
            if ($table_exists->num_rows == 0) {
                // Create enhanced table
                $create_table = "CREATE TABLE his_dispensed_drugs (
                    dispense_id INT AUTO_INCREMENT PRIMARY KEY,
                    pat_number VARCHAR(50) NOT NULL,
                    patient_name VARCHAR(255) NOT NULL,
                    dispensed_by VARCHAR(255) NOT NULL,
                    dispense_date DATETIME NOT NULL,
                    dispense_notes TEXT,
                    prescription_text TEXT,
                    ailment TEXT,
                    drug_names JSON,
                    pres_id INT,
                    note_id INT,
                    INDEX idx_pat_number (pat_number),
                    INDEX idx_dispense_date (dispense_date),
                    INDEX idx_pres_id (pres_id)
                )";
                
                if (!$mysqli->query($create_table)) {
                    throw new Exception("Error creating dispensed drugs table: " . $mysqli->error);
                }
            } else {
                // Check if table has new columns, add if missing
                $add_columns = [];
                $check_columns = ['ailment', 'drug_names', 'pres_id', 'note_id'];
                
                foreach ($check_columns as $column) {
                    $check = $mysqli->query("SHOW COLUMNS FROM his_dispensed_drugs LIKE '$column'");
                    if ($check->num_rows == 0) {
                        if ($column == 'drug_names') {
                            $add_columns[] = "ADD COLUMN drug_names JSON";
                        } elseif ($column == 'pres_id') {
                            $add_columns[] = "ADD COLUMN pres_id INT";
                        } elseif ($column == 'note_id') {
                            $add_columns[] = "ADD COLUMN note_id INT";
                        } else {
                            $add_columns[] = "ADD COLUMN $column TEXT";
                        }
                    }
                }
                
                if (!empty($add_columns)) {
                    $alter_query = "ALTER TABLE his_dispensed_drugs " . implode(", ", $add_columns);
                    if (!$mysqli->query($alter_query)) {
                        error_log("Failed to add columns: " . $mysqli->error);
                    }
                }
            }
            
            // 8. Get note_id for reference
            $note_id = 0;
            if ($pat_id > 0) {
                $note_id_query = "SELECT notes_id FROM his_notes WHERE pat_id = ? ORDER BY notes_date DESC LIMIT 1";
                $note_id_stmt = $mysqli->prepare($note_id_query);
                $note_id_stmt->bind_param('i', $pat_id);
                $note_id_stmt->execute();
                $note_id_res = $note_id_stmt->get_result();
                if ($note_id_row = $note_id_res->fetch_object()) {
                    $note_id = $note_id_row->notes_id;
                }
                $note_id_stmt->close();
            }
            
            // 9. Get prescription ID for storage
            $pres_id_for_storage = 0;
            $check_pres_cols = ['pres_id', 'id', 'prescription_id'];
            foreach ($check_pres_cols as $col) {
                if (isset($prescription->$col)) {
                    $pres_id_for_storage = $prescription->$col;
                    break;
                }
            }
            
            // 10. Prepare drug names as JSON
            $drug_names_json = json_encode($drug_names);
            
            // 11. Insert into dispensed drugs table
            $insert_query = "INSERT INTO his_dispensed_drugs 
                            (pat_number, patient_name, dispensed_by, dispense_date, 
                             dispense_notes, prescription_text, ailment, drug_names, pres_id, note_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $mysqli->prepare($insert_query);
            
            $prescription_text = "Patient: " . ($prescription->pres_pat_name ?? 'N/A') . "\n" .
                               "Age: " . ($prescription->pres_pat_age ?? 'N/A') . "\n" .
                               "Type: " . ($prescription->pres_pat_type ?? 'N/A') . "\n" .
                               "Doctor's Note: " . ($ailment ?: 'N/A') . "\n" .
                               "Prescription: " . ($prescription->pres_ins ?? 'N/A') . "\n" .
                               "Prescription ID: " . ($pres_id_for_storage ?: 'N/A') . "\n" .
                               "Date: " . ($prescription->pres_date ?? 'N/A');
            
            $insert_stmt->bind_param('ssssssssii', 
                $pat_number,
                $prescription->pres_pat_name,
                $dispensed_by,
                $dispense_date,
                $dispense_notes,
                $prescription_text,
                $ailment,
                $drug_names_json,
                $pres_id_for_storage,
                $note_id
            );
            
            if (!$insert_stmt->execute()) {
                throw new Exception("Error recording dispensation: " . $insert_stmt->error);
            }
            
            $dispense_id = $insert_stmt->insert_id;
            $insert_stmt->close();
            
            // 12. Commit transaction
            $mysqli->commit();
            
            $_SESSION['success'] = "✅ Drugs successfully added to dispense for " . htmlspecialchars($prescription->pres_pat_name) . "!";
            $_SESSION['last_dispense_id'] = $dispense_id;
            
            // Redirect with dispense ID
            header("Location: his_admin_add_presc.php?dispensed=1&dispense_id=" . $dispense_id);
            exit;
            
        } catch (Exception $e) {
            $mysqli->rollback();
            $err = $e->getMessage();
        }
    }
}

/* ---------------------------
   Check if we're viewing single prescription or list
--------------------------- */
$view_single = false;
if (isset($_GET['pat_number']) && !empty($_GET['pat_number'])) {
    $view_single = true;
    $pat_number = $_GET['pat_number'];
    
    // 1. Fetch LATEST prescription for this patient
    $ret = "SELECT * FROM his_prescriptions WHERE pat_number = ? ORDER BY pres_date DESC LIMIT 1";
    $stmt = $mysqli->prepare($ret);
    $stmt->bind_param('s', $pat_number);
    $stmt->execute();
    $res = $stmt->get_result();
    $prescription = $res->fetch_object();

    if (!$prescription) {
        $_SESSION['error'] = "No prescription found for patient number: " . htmlspecialchars($pat_number);
        header("Location: his_admin_add_presc.php");
        exit;
    }
    
    // 2. Get patient ID from prescription or patient table
    $pat_id = $prescription->pat_id ?? 0;
    if ($pat_id == 0 && !empty($pat_number)) {
        $pat_query = "SELECT pat_id FROM his_patients WHERE pat_number = ? LIMIT 1";
        $pat_stmt = $mysqli->prepare($pat_query);
        $pat_stmt->bind_param('s', $pat_number);
        $pat_stmt->execute();
        $pat_res = $pat_stmt->get_result();
        if ($pat_row = $pat_res->fetch_object()) {
            $pat_id = $pat_row->pat_id;
        }
        $pat_stmt->close();
    }
    
    // 3. Fetch LATEST doctor's note - get entire note
    $latest_doctor_note = '';
    $ailment = '';
    $note_date = '';
    
    if ($pat_id > 0) {
        $note_query = "SELECT pat_notes, notes_date FROM his_notes WHERE pat_id = ? ORDER BY notes_date DESC LIMIT 1";
        $note_stmt = $mysqli->prepare($note_query);
        if ($note_stmt) {
            $note_stmt->bind_param('i', $pat_id);
            $note_stmt->execute();
            $note_res = $note_stmt->get_result();
            if ($note_row = $note_res->fetch_object()) {
                $latest_doctor_note = $note_row->pat_notes ?? '';
                $note_date = $note_row->notes_date ?? '';
                
                // Use the ENTIRE doctor's note as the ailment
                $ailment = $latest_doctor_note;
            }
            $note_stmt->close();
        }
    }
    
    // 4. Try to extract drug names from LATEST prescription text
    $extracted_drugs = [];
    if (!empty($prescription->pres_ins)) {
        // Enhanced drug patterns
        $drug_patterns = [
            // Common drug names with dosages
            '/(\b(?:amoxicillin|paracetamol|ibuprofen|aspirin|metformin|insulin|atorvastatin|simvastatin|losartan|enalapril|lisinopril|omeprazole|pantoprazole|ranitidine|ciprofloxacin|azithromycin|doxycycline|metronidazole|fluconazole|prednisone|hydrocortisone|salbutamol|ventolin|warfarin|heparin|furosemide|spironolactone|digoxin|nitroglycerin|morphine|tramadol|codeine|diazepam|lorazepam|sertraline|fluoxetine|amitriptyline)\s*(?:\d+\s*(?:mg|g|ml)?)?\b)/i',
            
            // Drug instructions with numbers
            '/(\b(?:take|use|apply|inhale|swallow|chew|administer)\s+\d+\s*\w*\s+[^,\n]+)/i',
            
            // Lines with dosage patterns
            '/([^,\n]+\d+\s*(?:mg|g|ml|tablet|tab|capsule|cap|dose|drop|puff|inhaler|patch|times|hour|day|week)[^,\n]*)/i'
        ];
        
        $prescription_text = $prescription->pres_ins;
        
        // Try to extract using patterns
        foreach ($drug_patterns as $pattern) {
            if (preg_match_all($pattern, $prescription_text, $matches)) {
                foreach ($matches[0] as $match) {
                    $clean_match = trim(preg_replace('/^\d+\.\s*/', '', $match)); // Remove numbered lists
                    if (!empty($clean_match) && !in_array($clean_match, $extracted_drugs)) {
                        $extracted_drugs[] = $clean_match;
                    }
                }
            }
        }
        
        // If no patterns matched, split by common delimiters
        if (empty($extracted_drugs)) {
            $lines = preg_split('/[\n\r,;]+/', $prescription_text);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line) && (preg_match('/\d+/', $line) || 
                    preg_match('/^(?:take|use|apply|inhale)/i', $line) ||
                    preg_match('/(?:mg|g|ml|tablet|tab|capsule)/i', $line))) {
                    $extracted_drugs[] = $line;
                }
            }
        }
        
        // Remove duplicates and limit to 8 drugs
        $extracted_drugs = array_unique($extracted_drugs);
        $extracted_drugs = array_slice($extracted_drugs, 0, 8);
    }

    // 5. Check if already dispensed (check LATEST prescription status)
    $is_dispensed = false;
    $dispensed_details = null;

    // Check pres_status column for LATEST prescription
    $check_status_col = $mysqli->query("SHOW COLUMNS FROM his_prescriptions LIKE 'pres_status'");
    if ($check_status_col->num_rows > 0) {
        if (isset($prescription->pres_status) && $prescription->pres_status == 'dispensed') {
            $is_dispensed = true;
        }
    }

    // Check if dispensed drugs table exists and if LATEST prescription is dispensed
    $table_exists = $mysqli->query("SHOW TABLES LIKE 'his_dispensed_drugs'");
    if ($table_exists->num_rows > 0 && !$is_dispensed) {
        // Check if dispensed_drugs table has pres_id column
        $check_disp_col = $mysqli->query("SHOW COLUMNS FROM his_dispensed_drugs LIKE 'pres_id'");
        
        // Get prescription ID if available
        $pres_id_value = 0;
        $check_id_columns = ['pres_id', 'id', 'prescription_id'];
        foreach ($check_id_columns as $col) {
            if (isset($prescription->$col)) {
                $pres_id_value = $prescription->$col;
                break;
            }
        }
        
        if ($check_disp_col->num_rows > 0 && $pres_id_value > 0) {
            // Check by both patient number and prescription ID
            $check_dispensed = $mysqli->prepare("SELECT * FROM his_dispensed_drugs WHERE pat_number = ? AND pres_id = ? ORDER BY dispense_date DESC LIMIT 1");
            if ($check_dispensed) {
                $check_dispensed->bind_param('si', $pat_number, $pres_id_value);
            }
        } else {
            // Fallback: check by patient number only
            $check_dispensed = $mysqli->prepare("SELECT * FROM his_dispensed_drugs WHERE pat_number = ? ORDER BY dispense_date DESC LIMIT 1");
            if ($check_dispensed) {
                $check_dispensed->bind_param('s', $pat_number);
            }
        }
        
        if (isset($check_dispensed) && $check_dispensed) {
            $check_dispensed->execute();
            $dispensed_result = $check_dispensed->get_result();

            if ($dispensed_result->num_rows > 0) {
                $is_dispensed = true;
                $dispensed_details = $dispensed_result->fetch_object();
            }
            $check_dispensed->close();
        }
    }
    
    // 6. Get prescription ID for display
    $display_pres_id = 'N/A';
    $check_id_columns = ['pres_id', 'id', 'prescription_id'];
    foreach ($check_id_columns as $col) {
        if (isset($prescription->$col)) {
            $display_pres_id = $prescription->$col;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include('assets/inc/head.php'); ?>

<body>
    <div id="wrapper">
        <!-- Topbar Start -->
        <?php include('assets/inc/nav.php'); ?>
        <!-- end Topbar -->

        <!-- ========== Left Sidebar Start ========== -->
        <?php include("assets/inc/sidebar.php"); ?>
        <!-- Left Sidebar End -->

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <!-- Display error messages -->
                    <?php if(!empty($err)): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                <?php echo htmlspecialchars($err); ?>
                                <button type="button" class="close" data-dismiss="alert">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Display success messages from session -->
                    <?php if(isset($_SESSION['success'])): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle mr-2"></i>
                                <?php echo htmlspecialchars($_SESSION['success']); ?>
                                <?php unset($_SESSION['success']); ?>
                                <button type="button" class="close" data-dismiss="alert">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(isset($_SESSION['error'])): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                <?php echo htmlspecialchars($_SESSION['error']); ?>
                                <?php unset($_SESSION['error']); ?>
                                <button type="button" class="close" data-dismiss="alert">
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
                                        <li class="breadcrumb-item"><a href="his_admin_dashboard.php">Dashboard</a></li>
                                        <li class="breadcrumb-item active">Dispense Drugs</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">
                                    <i class="fas fa-capsules text-primary mr-2"></i> 
                                    <?php echo $view_single ? 'Add to Dispense' : 'Prescriptions to Dispense'; ?>
                                </h4>
                                <?php if($view_single): ?>
                                <p class="text-muted mb-0">
                                    Patient: <strong><?php echo htmlspecialchars($prescription->pres_pat_name); ?></strong> | 
                                    Patient #: <strong class="badge badge-info"><?php echo htmlspecialchars($pat_number); ?></strong> 
                                    <?php if($display_pres_id != 'N/A'): ?>
                                    | Prescription ID: <strong class="badge badge-primary">#<?php echo htmlspecialchars($display_pres_id); ?></strong>
                                    <?php endif; ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <?php if($view_single): ?>
                    <!-- SINGLE PRESCRIPTION VIEW -->
                    <div class="row">
                        <!-- Main Content Area -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                    <h4 class="header-title mb-0">
                                        <i class="fas fa-prescription mr-2"></i>
                                        Latest Prescription Details
                                    </h4>
                                    <div>
                                        <span class="badge badge-light mr-2">
                                            <i class="fas fa-calendar-alt mr-1"></i>
                                            <?php echo date('d/m/Y', strtotime($prescription->pres_date)); ?>
                                        </span>
                                        <span class="badge badge-<?php echo $is_dispensed ? 'success' : 'warning'; ?>">
                                            <i class="fas fa-<?php echo $is_dispensed ? 'check-circle' : 'clock'; ?> mr-1"></i>
                                            <?php echo $is_dispensed ? 'Dispensed' : 'Pending'; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Dispensed Warning -->
                                    <?php if($is_dispensed): ?>
                                    <div class="alert alert-warning">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-check-circle fa-2x mt-1"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h5 class="alert-heading">Already Dispensed</h5>
                                                <?php if($dispensed_details && isset($dispensed_details->dispensed_by)): ?>
                                                <p class="mb-2">
                                                    <strong>Dispensed on:</strong> <?php echo date('d/m/Y H:i', strtotime($dispensed_details->dispense_date)); ?><br>
                                                    <strong>Dispensed by:</strong> <?php echo htmlspecialchars($dispensed_details->dispensed_by); ?>
                                                </p>
                                                <?php endif; ?>
                                                <?php if($dispensed_details && !empty($dispensed_details->dispense_notes)): ?>
                                                <p class="mb-0"><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($dispensed_details->dispense_notes)); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Latest Information Banner -->
                                    <div class="alert alert-info">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-info-circle fa-2x"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="alert-heading">Showing Latest Information</h6>
                                                <p class="mb-0">
                                                    <strong>Latest Prescription:</strong> #<?php echo htmlspecialchars($display_pres_id); ?> from <?php echo date('d/m/Y H:i', strtotime($prescription->pres_date)); ?><br>
                                                    <?php if(!empty($note_date)): ?>
                                                    <strong>Latest Doctor's Note:</strong> <?php echo date('d/m/Y H:i', strtotime($note_date)); ?>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Patient Information -->
                                    <div class="card border mb-4">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0"><i class="fas fa-user-injured mr-2"></i> Patient Information</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <table class="table table-sm table-borderless">
                                                        <tr>
                                                            <th class="text-muted" width="40%">Full Name:</th>
                                                            <td><strong><?php echo htmlspecialchars($prescription->pres_pat_name); ?></strong></td>
                                                        </tr>
                                                        <tr>
                                                            <th class="text-muted">Patient Number:</th>
                                                            <td>
                                                                <span class="badge badge-info"><?php echo htmlspecialchars($pat_number); ?></span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th class="text-muted">Age:</th>
                                                            <td><?php echo htmlspecialchars($prescription->pres_pat_age); ?> years</td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div class="col-md-6">
                                                    <table class="table table-sm table-borderless">
                                                        <tr>
                                                            <th class="text-muted" width="40%">Patient Type:</th>
                                                            <td>
                                                                <span class="badge badge-<?php echo ($prescription->pres_pat_type == 'Outpatient') ? 'success' : 'primary'; ?>">
                                                                    <?php echo htmlspecialchars($prescription->pres_pat_type); ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th class="text-muted">Address:</th>
                                                            <td><?php echo htmlspecialchars($prescription->pres_pat_addr); ?></td>
                                                        </tr>
                                                        <?php if($pat_id > 0): ?>
                                                        <tr>
                                                            <th class="text-muted">Patient ID:</th>
                                                            <td><span class="badge badge-secondary">#<?php echo htmlspecialchars($pat_id); ?></span></td>
                                                        </tr>
                                                        <?php endif; ?>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Latest Prescription Instructions -->
                                    <div class="card border-info mb-4">
                                        <div class="card-header bg-info text-white">
                                            <h5 class="mb-0">
                                                <i class="fas fa-file-prescription mr-2"></i>
                                                Latest Prescription Instructions
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <?php if(!empty(trim($prescription->pres_ins))): ?>
                                            <div class="prescription-box p-3 bg-light border rounded" style="font-family: 'Courier New', monospace; white-space: pre-wrap; line-height: 1.8;">
                                                <?php echo nl2br(htmlspecialchars($prescription->pres_ins)); ?>
                                            </div>
                                            <?php else: ?>
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                                No prescription instructions provided.
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Entire Doctor's Note -->
                                    <div class="card border-warning mb-4">
                                        <div class="card-header bg-warning text-dark">
                                            <h5 class="mb-0">
                                                <i class="fas fa-stethoscope mr-2"></i>
                                                Complete Doctor's Note
                                                <?php if(!empty($note_date)): ?>
                                                <small class="float-right">Note Date: <?php echo date('d/m/Y H:i', strtotime($note_date)); ?></small>
                                                <?php endif; ?>
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <?php if(!empty($latest_doctor_note)): ?>
                                            <div class="p-3 bg-light border rounded" style="max-height: 300px; overflow-y: auto;">
                                                <?php echo nl2br(htmlspecialchars($latest_doctor_note)); ?>
                                            </div>
                                            <small class="text-muted mt-2 d-block">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                This entire doctor's note will be saved as the ailment in the dispensation record.
                                            </small>
                                            <?php else: ?>
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                                No doctor's note found for this patient.
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Add to Dispense Form (Only if not already dispensed) -->
                                    <?php if(!$is_dispensed): ?>
                                    <div class="mt-4">
                                        <div class="card border-success">
                                            <div class="card-header bg-success text-white">
                                                <h4 class="mb-0">
                                                    <i class="fas fa-capsules mr-2"></i>
                                                    Add to Dispense
                                                </h4>
                                            </div>
                                            <div class="card-body">
                                                <form method="post" id="dispenseForm">
                                                    <input type="hidden" name="pat_number" value="<?php echo htmlspecialchars($pat_number); ?>">
                                                    
                                                    <!-- Drug Names from LATEST Prescription -->
                                                    <div class="card border-primary mb-4">
                                                        <div class="card-header bg-primary text-white">
                                                            <h5 class="mb-0">
                                                                <i class="fas fa-pills mr-2"></i>
                                                                Drug Names (Extracted from Latest Prescription)
                                                            </h5>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="form-group">
                                                                <label for="drug_names" class="font-weight-bold">
                                                                    Drug Names *
                                                                    <small class="text-muted">(One per line or comma separated)</small>
                                                                </label>
                                                                <textarea class="form-control" id="drug_names" name="drug_names" 
                                                                          rows="4" placeholder="Enter drug names from the prescription...
e.g., Amoxicillin 500mg
Paracetamol 500mg
Ibuprofen 400mg" required><?php 
if (!empty($extracted_drugs)) {
    echo htmlspecialchars(implode("\n", $extracted_drugs));
} elseif (!empty($prescription->pres_ins)) {
    echo htmlspecialchars($prescription->pres_ins);
}
?></textarea>
                                                                <small class="form-text text-muted">
                                                                    System extracted <?php echo count($extracted_drugs); ?> potential drug(s) from latest prescription. Review and edit as needed.
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Dispensation Notes -->
                                                    <div class="form-group">
                                                        <label for="dispense_notes" class="font-weight-bold">
                                                            Pharmacy Notes <small class="text-muted">(Optional)</small>
                                                        </label>
                                                        <textarea class="form-control" id="dispense_notes" name="dispense_notes" 
                                                                  rows="3" placeholder="Enter any pharmacy notes about this dispensation..."></textarea>
                                                        <small class="form-text text-muted">
                                                            These notes will be saved in the permanent dispensation record.
                                                        </small>
                                                    </div>
                                                    
                                                    <!-- Verification Checklist -->
                                                    <div class="card border-primary mb-4">
                                                        <div class="card-header bg-primary text-white">
                                                            <h5 class="mb-0">
                                                                <i class="fas fa-clipboard-check mr-2"></i>
                                                                Pharmacy Verification Checklist
                                                            </h5>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="custom-control custom-checkbox mb-3">
                                                                <input type="checkbox" class="custom-control-input" id="verify_patient" required>
                                                                <label class="custom-control-label font-weight-bold" for="verify_patient">
                                                                    <span class="text-primary">✓</span> Patient identity verified
                                                                </label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox mb-3">
                                                                <input type="checkbox" class="custom-control-input" id="verify_allergies" required>
                                                                <label class="custom-control-label font-weight-bold" for="verify_allergies">
                                                                    <span class="text-primary">✓</span> Drug allergies checked
                                                                </label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox mb-3">
                                                                <input type="checkbox" class="custom-control-input" id="verify_dosage" required>
                                                                <label class="custom-control-label font-weight-bold" for="verify_dosage">
                                                                    <span class="text-primary">✓</span> Dosage instructions confirmed
                                                                </label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input" id="verify_instructions" required>
                                                                <label class="custom-control-label font-weight-bold" for="verify_instructions">
                                                                    <span class="text-primary">✓</span> Usage instructions provided to patient
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Final Confirmation -->
                                                    <div class="alert alert-warning border-warning">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input" id="final_confirmation" required>
                                                            <label class="custom-control-label" for="final_confirmation">
                                                                <strong class="text-danger">FINAL CONFIRMATION:</strong> 
                                                                I confirm that all medications from the LATEST prescription have been dispensed as prescribed.
                                                                The complete doctor's note above will be recorded as the ailment.
                                                            </label>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Action Buttons -->
                                                    <div class="d-flex justify-content-between mt-4">
                                                        <a href="his_admin_add_presc.php" class="btn btn-secondary">
                                                            <i class="fas fa-arrow-left mr-2"></i> Back to List
                                                        </a>
                                                        <button type="submit" name="add_to_dispense" class="btn btn-success btn-lg">
                                                            <i class="fas fa-check-circle mr-2"></i> Add to Dispense
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <!-- Already Dispensed - View Only -->
                                    <div class="mt-4">
                                        <div class="card border-secondary">
                                            <div class="card-header bg-secondary text-white">
                                                <h5 class="mb-0">
                                                    <i class="fas fa-ban mr-2"></i>
                                                    Already Added to Dispense
                                                </h5>
                                            </div>
                                            <div class="card-body text-center py-5">
                                                <div class="mb-4">
                                                    <i class="fas fa-check-circle fa-5x text-success"></i>
                                                </div>
                                                <h4 class="text-success mb-3">Latest Prescription Already Dispensed</h4>
                                                <p class="text-muted mb-4">
                                                    Prescription #<?php echo htmlspecialchars($display_pres_id); ?> has been processed and cannot be dispensed again.
                                                </p>
                                                <div class="d-flex justify-content-center">
                                                    <a href="his_admin_add_presc.php" class="btn btn-primary mr-3">
                                                        <i class="fas fa-arrow-left mr-2"></i> Back to Prescriptions
                                                    </a>
                                                    <button onclick="printPrescription()" class="btn btn-info">
                                                        <i class="fas fa-print mr-2"></i> Print Copy
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sidebar -->
                        <div class="col-lg-4">
                            <!-- Quick Actions -->
                            <div class="card mb-4">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-bolt mr-2"></i>
                                        Quick Actions
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush">
                                        <a href="his_admin_add_presc.php" class="list-group-item list-group-item-action">
                                            <i class="fas fa-list text-primary mr-2"></i>
                                            View All Prescriptions
                                        </a>
                                        <?php if($pat_id > 0): ?>
                                        <a href="his_admin_view_single_patient.php?pat_id=<?php echo $pat_id; ?>&pat_number=<?php echo urlencode($pat_number); ?>" 
                                           class="list-group-item list-group-item-action">
                                            <i class="fas fa-user-injured text-primary mr-2"></i>
                                            View Patient Profile
                                        </a>
                                        <?php endif; ?>
                                        <a href="his_admin_dashboard.php" class="list-group-item list-group-item-action">
                                            <i class="fas fa-tachometer-alt text-primary mr-2"></i>
                                            Dashboard
                                        </a>
                                        <button onclick="printPrescription()" class="list-group-item list-group-item-action">
                                            <i class="fas fa-print text-primary mr-2"></i>
                                            Print Prescription Copy
                                        </button>
                                        <?php if($is_dispensed && $dispensed_details && isset($dispensed_details->dispensed_by)): ?>
                                        <button onclick="printDispensationReceipt()" class="list-group-item list-group-item-action">
                                            <i class="fas fa-receipt text-primary mr-2"></i>
                                            Print Dispensation Receipt
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Latest Information Status -->
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-history mr-2"></i>
                                        Latest Information Status
                                    </h5>
                                </div>
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between mb-2">
                                        <small>Latest Prescription:</small>
                                        <small>
                                            <span class="badge badge-<?php echo $is_dispensed ? 'success' : 'warning'; ?>">
                                                #<?php echo htmlspecialchars($display_pres_id); ?>
                                            </span>
                                        </small>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <small>Prescription Date:</small>
                                        <small>
                                            <?php echo date('d/m/Y', strtotime($prescription->pres_date)); ?>
                                        </small>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <small>Doctor's Note:</small>
                                        <small>
                                            <?php if(!empty($latest_doctor_note)): ?>
                                            <span class="badge badge-success">
                                                <?php echo strlen($latest_doctor_note) > 50 ? 'Available' : 'Available'; ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="badge badge-warning">Not Found</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <small>Ailment/Note:</small>
                                        <small>
                                            <?php if(!empty($latest_doctor_note)): ?>
                                            <span class="badge badge-success">Complete Note</span>
                                            <?php else: ?>
                                            <span class="badge badge-danger">No Note</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <small>Drugs Extracted:</small>
                                        <small>
                                            <?php if(!empty($extracted_drugs)): ?>
                                            <span class="badge badge-success"><?php echo count($extracted_drugs); ?> found</span>
                                            <?php else: ?>
                                            <span class="badge badge-warning">Manual Entry</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pharmacy Guidelines -->
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0">
                                        <i class="fas fa-book-medical mr-2"></i>
                                        Pharmacy Guidelines
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <ol class="pl-3 mb-0" style="font-size: 13px;">
                                        <li class="mb-2">Always verify patient identity</li>
                                        <li class="mb-2">Check drug names against LATEST prescription</li>
                                        <li class="mb-2">Confirm dosage and usage instructions</li>
                                        <li class="mb-2">Check medication expiry dates</li>
                                        <li class="mb-2">The entire doctor's note is recorded as ailment</li>
                                        <li>Always update dispensation status</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- PRESCRIPTIONS LIST VIEW -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                    <h4 class="header-title mb-0">
                                        <i class="fas fa-prescription mr-2"></i>
                                        Prescriptions Pending Dispensation
                                    </h4>
                                    <span class="badge badge-light">
                                        <i class="fas fa-capsules mr-1"></i>
                                        Pharmacy
                                    </span>
                                </div>
                                <div class="card-body">
                                    <!-- Search Bar -->
                                    <div class="row mb-4">
                                        <div class="col-md-8">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text bg-light border-right-0">
                                                        <i class="fas fa-search text-muted"></i>
                                                    </span>
                                                </div>
                                                <input type="text" class="form-control border-left-0" id="searchInput" 
                                                       placeholder="Search by patient name, patient number, or address...">
                                                <div class="input-group-append">
                                                    <button class="btn btn-secondary" type="button" onclick="clearSearch()">
                                                        <i class="fas fa-times"></i> Clear
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-right">
                                            <button class="btn btn-info" onclick="refreshPage()">
                                                <i class="fas fa-sync-alt mr-2"></i> Refresh
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Prescriptions Table -->
                                    <div class="table-responsive">
                                        <table class="table table-hover table-centered mb-0" id="prescriptionsTable">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Patient Name</th>
                                                    <th>Patient Number</th>
                                                    <?php 
                                                    // Check if pres_id column exists for display
                                                    $check_pres_id_display = $mysqli->query("SHOW COLUMNS FROM his_prescriptions LIKE 'pres_id'");
                                                    $check_id_display = $mysqli->query("SHOW COLUMNS FROM his_prescriptions LIKE 'id'");
                                                    $check_presc_id_display = $mysqli->query("SHOW COLUMNS FROM his_prescriptions LIKE 'prescription_id'");
                                                    if ($check_pres_id_display->num_rows > 0 || $check_id_display->num_rows > 0 || $check_presc_id_display->num_rows > 0): ?>
                                                    <th>Prescription ID</th>
                                                    <?php endif; ?>
                                                    <th>Age</th>
                                                    <th>Category</th>
                                                    <th>Prescription Date</th>
                                                    <th>Status</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // First, check what columns exist in the prescriptions table
                                                $pres_columns = [];
                                                $col_query = "SHOW COLUMNS FROM his_prescriptions";
                                                $col_result = $mysqli->query($col_query);
                                                while ($col = $col_result->fetch_assoc()) {
                                                    $pres_columns[] = $col['Field'];
                                                }
                                                
                                                // Determine if we have an ID column
                                                $has_id_column = (in_array('pres_id', $pres_columns) || in_array('id', $pres_columns) || in_array('prescription_id', $pres_columns));
                                                
                                                // Get all LATEST prescriptions for each patient with pending status
                                                // First, get unique patients with their latest prescription
                                                $ret = "SELECT p1.* FROM his_prescriptions p1 
                                                        INNER JOIN (
                                                            SELECT pat_number, MAX(pres_date) as latest_date 
                                                            FROM his_prescriptions 
                                                            GROUP BY pat_number
                                                        ) p2 ON p1.pat_number = p2.pat_number AND p1.pres_date = p2.latest_date
                                                        WHERE (p1.pres_status IS NULL OR p1.pres_status = 'pending') 
                                                        ORDER BY p1.pres_date DESC";
                                                
                                                $stmt = $mysqli->prepare($ret);
                                                if ($stmt) {
                                                    $stmt->execute();
                                                    $res = $stmt->get_result();
                                                    $cnt = 1;
                                                    
                                                    while($row = $res->fetch_object()) {
                                                        // Check if LATEST prescription is dispensed
                                                        $is_dispensed = false;
                                                        
                                                        // Check pres_status column if exists
                                                        $check_status_col = $mysqli->query("SHOW COLUMNS FROM his_prescriptions LIKE 'pres_status'");
                                                        if ($check_status_col->num_rows > 0) {
                                                            if (isset($row->pres_status) && $row->pres_status == 'dispensed') {
                                                                $is_dispensed = true;
                                                            }
                                                        }
                                                        
                                                        // Check dispensed drugs table if exists
                                                        $table_exists = $mysqli->query("SHOW TABLES LIKE 'his_dispensed_drugs'");
                                                        if ($table_exists->num_rows > 0 && !$is_dispensed) {
                                                            // Get prescription ID value if available
                                                            $pres_id_value = 0;
                                                            if (isset($row->pres_id)) {
                                                                $pres_id_value = $row->pres_id;
                                                            } elseif (isset($row->id)) {
                                                                $pres_id_value = $row->id;
                                                            } elseif (isset($row->prescription_id)) {
                                                                $pres_id_value = $row->prescription_id;
                                                            }
                                                            
                                                            // Check if dispensed_drugs table has pres_id column
                                                            $check_disp_col = $mysqli->query("SHOW COLUMNS FROM his_dispensed_drugs LIKE 'pres_id'");
                                                            
                                                            if ($check_disp_col->num_rows > 0 && $pres_id_value > 0) {
                                                                $check_dispensed = $mysqli->prepare("SELECT * FROM his_dispensed_drugs WHERE pat_number = ? AND pres_id = ?");
                                                                if ($check_dispensed) {
                                                                    $check_dispensed->bind_param('si', $row->pat_number, $pres_id_value);
                                                                }
                                                            } else {
                                                                $check_dispensed = $mysqli->prepare("SELECT * FROM his_dispensed_drugs WHERE pat_number = ?");
                                                                if ($check_dispensed) {
                                                                    $check_dispensed->bind_param('s', $row->pat_number);
                                                                }
                                                            }
                                                            
                                                            if (isset($check_dispensed) && $check_dispensed) {
                                                                $check_dispensed->execute();
                                                                $dispensed_result = $check_dispensed->get_result();
                                                                if ($dispensed_result->num_rows > 0) {
                                                                    $is_dispensed = true;
                                                                }
                                                                $check_dispensed->close();
                                                            }
                                                        }
                                                        
                                                        $status_class = $is_dispensed ? 'success' : 'warning';
                                                        $status_text = $is_dispensed ? 'Dispensed' : 'Pending';
                                                        $status_icon = $is_dispensed ? 'check-circle' : 'clock';
                                                        
                                                        // Get prescription ID for display
                                                        $display_pres_id_row = 'N/A';
                                                        if (isset($row->pres_id)) {
                                                            $display_pres_id_row = $row->pres_id;
                                                        } elseif (isset($row->id)) {
                                                            $display_pres_id_row = $row->id;
                                                        } elseif (isset($row->prescription_id)) {
                                                            $display_pres_id_row = $row->prescription_id;
                                                        }
                                                ?>
                                                <tr>
                                                    <td><?php echo $cnt; ?></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($row->pres_pat_name); ?></strong>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info"><?php echo htmlspecialchars($row->pat_number); ?></span>
                                                    </td>
                                                    <?php if ($has_id_column): ?>
                                                    <td>
                                                        <span class="badge badge-secondary">#<?php echo htmlspecialchars($display_pres_id_row); ?></span>
                                                    </td>
                                                    <?php endif; ?>
                                                    <td><?php echo htmlspecialchars($row->pres_pat_age); ?> Years</td>
                                                    <td>
                                                        <span class="badge badge-<?php echo ($row->pres_pat_type == 'Outpatient') ? 'success' : 'primary'; ?>">
                                                            <?php echo htmlspecialchars($row->pres_pat_type); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php echo date('d/m/Y', strtotime($row->pres_date)); ?><br>
                                                        <small class="text-muted"><?php echo date('H:i', strtotime($row->pres_date)); ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $status_class; ?>">
                                                            <i class="fas fa-<?php echo $status_icon; ?> mr-1"></i>
                                                            <?php echo $status_text; ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if(!$is_dispensed): ?>
                                                        <a href="his_admin_add_presc.php?pat_number=<?php echo urlencode($row->pat_number); ?>" 
                                                           class="btn btn-sm btn-success" title="Add to Dispense">
                                                            <i class="fas fa-plus-circle"></i> Add to Dispense
                                                        </a>
                                                        <?php else: ?>
                                                        <span class="text-muted">Already dispensed</span>
                                                        <?php endif; ?>
                                                        <button class="btn btn-sm btn-info ml-1" 
                                                                onclick="viewPrescription('<?php echo urlencode($row->pat_number); ?>')"
                                                                title="View Latest Prescription">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php 
                                                        $cnt++;
                                                    }
                                                    
                                                    if ($cnt == 1) {
                                                        $colspan = $has_id_column ? 9 : 8;
                                                        echo '<tr><td colspan="' . $colspan . '" class="text-center py-4">No pending prescriptions found.</td></tr>';
                                                    }
                                                    
                                                    $stmt->close();
                                                } else {
                                                    $colspan = $has_id_column ? 9 : 8;
                                                    echo '<tr><td colspan="' . $colspan . '" class="text-center py-4 text-danger">Error loading prescriptions: ' . htmlspecialchars($mysqli->error) . '</td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- No Results Message -->
                                    <div id="noResults" class="alert alert-warning mt-3" style="display: none;">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        No prescriptions found matching your search criteria.
                                    </div>
                                    
                                    <!-- Info Box -->
                                    <div class="alert alert-info mt-4">
                                        <h6><i class="fas fa-info-circle mr-2"></i> How to Add to Dispense</h6>
                                        <ol class="mb-0 pl-3">
                                            <li>This list shows the LATEST prescription for each patient</li>
                                            <li>Click the <span class="badge badge-success"><i class="fas fa-plus-circle"></i> Add to Dispense</span> button</li>
                                            <li>View the complete doctor's note (saved as ailment)</li>
                                            <li>Review extracted drug names from LATEST prescription</li>
                                            <li>Complete the verification checklist</li>
                                            <li>Click "Add to Dispense" to complete</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Footer Start -->
            <?php include('assets/inc/footer.php'); ?>
            <!-- end Footer -->
        </div>
    </div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>
    <!-- App js -->
    <script src="assets/js/app.min.js"></script>
    
    <?php if($view_single): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-expand textarea
        const textarea = document.getElementById('dispense_notes');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }
        
        // Also expand drug names textarea
        const drugTextarea = document.getElementById('drug_names');
        if (drugTextarea) {
            drugTextarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
            // Initial adjustment
            setTimeout(() => {
                drugTextarea.style.height = 'auto';
                drugTextarea.style.height = (drugTextarea.scrollHeight) + 'px';
            }, 100);
        }
        
        // Form validation
        const form = document.getElementById('dispenseForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Check all required checkboxes
                const checkboxes = form.querySelectorAll('input[type="checkbox"][required]');
                let allChecked = true;
                
                checkboxes.forEach(checkbox => {
                    if (!checkbox.checked) {
                        allChecked = false;
                        checkbox.parentElement.classList.add('text-danger');
                    } else {
                        checkbox.parentElement.classList.remove('text-danger');
                    }
                });
                
                if (!allChecked) {
                    e.preventDefault();
                    alert('Please complete all verification steps before adding to dispense.');
                    return false;
                }
                
                // Check drug names
                const drugNames = document.getElementById('drug_names').value.trim();
                if (!drugNames) {
                    e.preventDefault();
                    alert('Please enter drug names from the prescription.');
                    document.getElementById('drug_names').focus();
                    return false;
                }
                
                // Final confirmation
                if (!confirm('Are you sure you want to add this LATEST prescription to DISPENSE?\n\nThe complete doctor\'s note will be saved as the ailment.\n\nThis will mark it as dispensed and cannot be undone.')) {
                    e.preventDefault();
                    return false;
                }
                
                // Show loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Adding to Dispense...';
                    submitBtn.disabled = true;
                }
            });
        }
    });
    
    // Print prescription
    function printPrescription() {
        const printContent = `
            <html>
                <head>
                    <title>Prescription - <?php echo htmlspecialchars($pat_number); ?></title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
                        .patient-info, .prescription-info { margin-bottom: 20px; }
                        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f5f5f5; }
                        .prescription-box { border: 2px solid #000; padding: 15px; margin: 20px 0; background: #f9f9f9; font-family: 'Courier New', monospace; }
                        .doctor-note { border: 1px solid #ddd; padding: 15px; margin: 20px 0; background: #f0f8ff; font-family: Arial, sans-serif; white-space: pre-wrap; }
                        .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h2>LATEST PRESCRIPTION COPY</h2>
                        <p>Patient Number: <?php echo htmlspecialchars($pat_number); ?> | Prescription ID: <?php echo htmlspecialchars($display_pres_id); ?> | Date: <?php echo date('d/m/Y H:i'); ?></p>
                    </div>
                    
                    <div class="patient-info">
                        <h3>Patient Information</h3>
                        <table>
                            <tr><th>Name:</th><td><?php echo htmlspecialchars($prescription->pres_pat_name); ?></td></tr>
                            <tr><th>Patient Number:</th><td><?php echo htmlspecialchars($pat_number); ?></td></tr>
                            <tr><th>Age:</th><td><?php echo htmlspecialchars($prescription->pres_pat_age); ?> years</td></tr>
                            <tr><th>Type:</th><td><?php echo htmlspecialchars($prescription->pres_pat_type); ?></td></tr>
                            <tr><th>Prescription Date:</th><td><?php echo date('d/m/Y H:i', strtotime($prescription->pres_date)); ?></td></tr>
                        </table>
                    </div>
                    
                    <?php if(!empty($latest_doctor_note)): ?>
                    <div class="doctor-note">
                        <h4>Doctor's Note:</h4>
                        <?php echo nl2br(htmlspecialchars($latest_doctor_note)); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="prescription-info">
                        <h3>Latest Prescription Instructions</h3>
                        <div class="prescription-box">
                            <?php echo nl2br(htmlspecialchars($prescription->pres_ins)); ?>
                        </div>
                    </div>
                    
                    <div class="footer">
                        <p>Printed on: <?php echo date('d/m/Y H:i'); ?> | Printed by: <?php echo htmlspecialchars($_SESSION['ad_fname'] . ' ' . $_SESSION['ad_lname']); ?></p>
                        <p>This is a system-generated copy of the LATEST prescription for pharmacy use only.</p>
                    </div>
                </body>
            </html>
        `;
        
        const printWindow = window.open('', '_blank');
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.print();
    }
    
    function printDispensationReceipt() {
        alert('Dispensation receipt printing would show detailed dispensation record.');
    }
    </script>
    <?php else: ?>
    <script>
    // Search functionality for prescriptions list
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('prescriptionsTable');
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        const noResults = document.getElementById('noResults');
        
        function performSearch() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            let foundCount = 0;
            
            for (let row of rows) {
                const cells = row.getElementsByTagName('td');
                if (cells.length < 2) continue;
                
                let showRow = false;
                
                // Search in patient name (cell 1)
                if (cells[1].textContent.toLowerCase().includes(searchTerm)) {
                    showRow = true;
                }
                // Search in patient number (cell 2)
                else if (cells[2].textContent.toLowerCase().includes(searchTerm)) {
                    showRow = true;
                }
                
                if (showRow || searchTerm === '') {
                    row.style.display = '';
                    foundCount++;
                } else {
                    row.style.display = 'none';
                }
            }
            
            // Show/hide no results message
            if (searchTerm !== '' && foundCount === 0) {
                noResults.style.display = 'block';
            } else {
                noResults.style.display = 'none';
            }
        }
        
        // Add event listener for search input
        searchInput.addEventListener('keyup', performSearch);
        
        // Focus on search input
        searchInput.focus();
        
        // Also search on paste
        searchInput.addEventListener('paste', function() {
            setTimeout(performSearch, 10);
        });
    });
    
    function clearSearch() {
        document.getElementById('searchInput').value = '';
        document.getElementById('searchInput').focus();
        
        // Trigger search to show all rows
        const event = new Event('keyup');
        document.getElementById('searchInput').dispatchEvent(event);
    }
    
    function refreshPage() {
        window.location.reload();
    }
    
    function viewPrescription(patNumber) {
        window.location.href = 'his_admin_add_presc.php?pat_number=' + patNumber;
    }
    </script>
    <?php endif; ?>
    
    <style>
    .prescription-box {
        max-height: 300px;
        overflow-y: auto;
        font-family: 'Courier New', monospace;
        white-space: pre-wrap;
        line-height: 1.6;
    }
    
    .table th {
        background-color: #f8f9fa !important;
        font-weight: 600;
        font-size: 14px;
    }
    
    .badge {
        font-size: 12px;
        padding: 5px 10px;
        font-weight: 500;
    }
    
    .list-group-item {
        border: none;
        border-bottom: 1px solid #eee;
    }
    
    .list-group-item:last-child {
        border-bottom: none;
    }
    
    .list-group-item:hover {
        background-color: #f8f9fa;
    }
    
    .btn-sm {
        padding: 5px 10px;
        font-size: 12px;
    }
    
    @media (max-width: 768px) {
        .btn-lg {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .table-responsive {
            font-size: 14px;
        }
        
        .card-body {
            padding: 15px;
        }
    }
    
    /* Search input styling */
    #searchInput:focus {
        border-color: #6658dd;
        box-shadow: 0 0 0 0.2rem rgba(102, 88, 221, 0.25);
    }
    
    .input-group-text {
        border-right: none;
    }
    
    .input-group .form-control {
        border-left: none;
    }
    
    .input-group .form-control:focus {
        border-color: #ced4da;
        box-shadow: none;
    }
    
    /* Table styling */
    .table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    /* Auto-extraction status */
    .badge-success {
        background-color: #28a745 !important;
    }
    
    .badge-warning {
        background-color: #ffc107 !important;
        color: #212529 !important;
    }
    
    .badge-danger {
        background-color: #dc3545 !important;
    }
    
    .badge-secondary {
        background-color: #6c757d !important;
    }
    
    .badge-info {
        background-color: #17a2b8 !important;
    }
    </style>
</body>
</html>