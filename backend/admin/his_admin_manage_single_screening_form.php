<?php
// Start session and setup
if (session_status() === PHP_SESSION_NONE) session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();

// ====================================================================
// CREATE SCREENING FORMS TABLE IF IT DOESN'T EXIST (SIMPLIFIED)
// ====================================================================
$create_table_sql = "
CREATE TABLE IF NOT EXISTS `his_screening_forms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pat_id` INT NOT NULL,
    
    -- Section A: Personal History
    `a_tuberculosis` ENUM('Yes','No') DEFAULT 'No',
    `a_asthma` ENUM('Yes','No') DEFAULT 'No',
    `a_peptic_ulcer` ENUM('Yes','No') DEFAULT 'No',
    `a_sickle_cell` ENUM('Yes','No') DEFAULT 'No',
    `a_allergies` ENUM('Yes','No') DEFAULT 'No',
    `a_diabetes` ENUM('Yes','No') DEFAULT 'No',
    `a_hypertension` ENUM('Yes','No') DEFAULT 'No',
    `a_seizures` ENUM('Yes','No') DEFAULT 'No',
    `a_mental_ill` ENUM('Yes','No') DEFAULT 'No',
    
    -- Section B: Family History
    `b_tuberculosis` ENUM('Yes','No') DEFAULT 'No',
    `b_mental_illness` ENUM('Yes','No') DEFAULT 'No',
    `b_diabetes_mellitus` ENUM('Yes','No') DEFAULT 'No',
    `b_heart_disease` ENUM('Yes','No') DEFAULT 'No',
    
    -- Section C: Immunizations
    `c_smallpox` ENUM('Yes','No') DEFAULT 'No',
    `c_poliomyelitis` ENUM('Yes','No') DEFAULT 'No',
    `c_tuberculosis_vax` ENUM('Yes','No') DEFAULT 'No',
    `c_meningitis` ENUM('Yes','No') DEFAULT 'No',
    `c_hpv` ENUM('Yes','No') DEFAULT 'No',
    `c_hepatitis_b` ENUM('Yes','No') DEFAULT 'No',
    
    -- Lifestyle
    `uses_tobacco` ENUM('Yes','No') DEFAULT 'No',
    `exposed_to_smoke` ENUM('Yes','No') DEFAULT 'No',
    `consumes_alcohol` ENUM('Yes','No') DEFAULT 'No',
    
    -- Details
    `details_if_yes` TEXT DEFAULT NULL,
    `other_relevant_info` TEXT DEFAULT NULL,
    
    -- PART II: Clinical Examination
    `height` DECIMAL(5,2) DEFAULT NULL COMMENT 'in meters',
    `weight` DECIMAL(5,2) DEFAULT NULL COMMENT 'in kg',
    `bmi` DECIMAL(5,2) DEFAULT NULL,
    `visual_r` VARCHAR(20) DEFAULT NULL COMMENT 'Right eye visual acuity',
    `visual_l` VARCHAR(20) DEFAULT NULL COMMENT 'Left eye visual acuity',
    `blood_pressure` VARCHAR(20) DEFAULT NULL COMMENT 'e.g., 120/80',
    `pulse_rate` INT DEFAULT NULL COMMENT 'beats per minute',
    
    -- PART III: Laboratory Investigations
    `urine_albumin` VARCHAR(20) DEFAULT NULL,
    `urine_sugar` VARCHAR(20) DEFAULT NULL,
    `genotype` VARCHAR(10) DEFAULT NULL,
    `blood_group` VARCHAR(10) DEFAULT NULL,
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_pat_id` (`pat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Execute table creation
if (!$mysqli->query($create_table_sql)) {
    // Try an even simpler version without ENUM
    $create_table_simple = "
    CREATE TABLE IF NOT EXISTS `his_screening_forms` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `pat_id` INT NOT NULL,
        
        -- Section A: Personal History
        `a_tuberculosis` VARCHAR(3) DEFAULT 'No',
        `a_asthma` VARCHAR(3) DEFAULT 'No',
        `a_peptic_ulcer` VARCHAR(3) DEFAULT 'No',
        `a_sickle_cell` VARCHAR(3) DEFAULT 'No',
        `a_allergies` VARCHAR(3) DEFAULT 'No',
        `a_diabetes` VARCHAR(3) DEFAULT 'No',
        `a_hypertension` VARCHAR(3) DEFAULT 'No',
        `a_seizures` VARCHAR(3) DEFAULT 'No',
        `a_mental_ill` VARCHAR(3) DEFAULT 'No',
        
        -- Section B: Family History
        `b_tuberculosis` VARCHAR(3) DEFAULT 'No',
        `b_mental_illness` VARCHAR(3) DEFAULT 'No',
        `b_diabetes_mellitus` VARCHAR(3) DEFAULT 'No',
        `b_heart_disease` VARCHAR(3) DEFAULT 'No',
        
        -- Section C: Immunizations
        `c_smallpox` VARCHAR(3) DEFAULT 'No',
        `c_poliomyelitis` VARCHAR(3) DEFAULT 'No',
        `c_tuberculosis_vax` VARCHAR(3) DEFAULT 'No',
        `c_meningitis` VARCHAR(3) DEFAULT 'No',
        `c_hpv` VARCHAR(3) DEFAULT 'No',
        `c_hepatitis_b` VARCHAR(3) DEFAULT 'No',
        
        -- Lifestyle
        `uses_tobacco` VARCHAR(3) DEFAULT 'No',
        `exposed_to_smoke` VARCHAR(3) DEFAULT 'No',
        `consumes_alcohol` VARCHAR(3) DEFAULT 'No',
        
        -- Details
        `details_if_yes` TEXT DEFAULT NULL,
        `other_relevant_info` TEXT DEFAULT NULL,
        
        -- PART II: Clinical Examination
        `height` VARCHAR(10) DEFAULT NULL COMMENT 'in meters',
        `weight` VARCHAR(10) DEFAULT NULL COMMENT 'in kg',
        `bmi` VARCHAR(10) DEFAULT NULL,
        `visual_r` VARCHAR(20) DEFAULT NULL COMMENT 'Right eye visual acuity',
        `visual_l` VARCHAR(20) DEFAULT NULL COMMENT 'Left eye visual acuity',
        `blood_pressure` VARCHAR(20) DEFAULT NULL COMMENT 'e.g., 120/80',
        `pulse_rate` VARCHAR(10) DEFAULT NULL COMMENT 'beats per minute',
        
        -- PART III: Laboratory Investigations
        `urine_albumin` VARCHAR(20) DEFAULT NULL,
        `urine_sugar` VARCHAR(20) DEFAULT NULL,
        `genotype` VARCHAR(10) DEFAULT NULL,
        `blood_group` VARCHAR(10) DEFAULT NULL,
        
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX `idx_pat_id` (`pat_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    if (!$mysqli->query($create_table_simple)) {
        die("Error creating screening forms table: " . $mysqli->error);
    }
}
// ====================================================================

$pat_number = isset($_GET['pat_number']) ? trim((string)$_GET['pat_number']) : '';
$pat_id     = isset($_GET['pat_id']) ? intval($_GET['pat_id']) : 0;
if ($pat_number === '' && !$pat_id) die("Missing patient identifier");

// First, check what columns exist in the table
$check_columns = $mysqli->query("SHOW COLUMNS FROM his_patients");
$available_columns = [];
while ($col = $check_columns->fetch_assoc()) {
    $available_columns[] = $col['Field'];
}

// Define all possible columns we might need
$possible_columns = [
    'pat_id', 'pat_lname', 'pat_fname', 'pat_age', 'pat_dob', 
    'pat_sex', 'pat_gender', 'pat_title', 'pat_nationality', 
    'pat_state', 'pat_religion', 'pat_faculty', 'pat_department', 
    'pat_number', 'pat_jamb_regno', 'pat_phone', 'pat_nok', 
    'pat_relation_nok', 'pat_nok_address', 'pat_nok_phone',
    'pat_file_number'
];

// Build query with only existing columns
$select_cols = [];
foreach ($possible_columns as $col) {
    if (in_array($col, $available_columns)) {
        // Alias pat_number to matric_no for consistency
        if ($col === 'pat_number') {
            $select_cols[] = 'pat_number AS matric_no';
        } else {
            $select_cols[] = $col;
        }
    }
}

// Add pat_sex if pat_gender exists but pat_sex doesn't
if (!in_array('pat_sex', $available_columns) && in_array('pat_gender', $available_columns)) {
    $select_cols[] = 'pat_gender AS pat_sex';
}

if (empty($select_cols)) {
    die("No valid columns found in patients table");
}

$select_sql = implode(', ', $select_cols);

// Fetch demographic data by pat_number OR pat_id
if ($pat_number !== '') {
    $stmt = $mysqli->prepare("SELECT $select_sql FROM his_patients WHERE pat_number = ?");
    $stmt->bind_param('s', $pat_number);
} else {
    $stmt = $mysqli->prepare("SELECT $select_sql FROM his_patients WHERE pat_id = ?");
    $stmt->bind_param('i', $pat_id);
}

$stmt->execute();
$patient = $stmt->get_result()->fetch_object();
$stmt->close();

if (!$patient) die("Patient not found");

// Create a safe property accessor
function getPatientProperty($patient, $property, $default = 'N/A') {
    return property_exists($patient, $property) && !empty($patient->$property) 
           ? $patient->$property 
           : $default;
}

$display_number = getPatientProperty($patient, 'matric_no', 
                   getPatientProperty($patient, 'pat_file_number', 
                     'ID:' . getPatientProperty($patient, 'pat_id', 'N/A')));

// Screening form flags
$flags = [
    // Section A
    'a_tuberculosis','a_asthma','a_peptic_ulcer','a_sickle_cell','a_allergies',
    'a_diabetes','a_hypertension','a_seizures','a_mental_ill',
    // Section B
    'b_tuberculosis','b_mental_illness','b_diabetes_mellitus','b_heart_disease',
    // Section C
    'c_smallpox','c_poliomyelitis','c_tuberculosis_vax','c_meningitis','c_hpv','c_hepatitis_b',
    // Lifestyle
    'uses_tobacco','exposed_to_smoke','consumes_alcohol'
];

// Fetch previous screening data
$formData = [];
$screening = $mysqli->prepare("SELECT * FROM his_screening_forms WHERE pat_id = ?");
if ($screening) {
    $screening->bind_param('i', $patient->pat_id);
    $screening->execute();
    $res = $screening->get_result();
    if ($res->num_rows > 0) {
        $formData = $res->fetch_assoc();
    }
    $screening->close();
}

// Form submission
if (isset($_POST['save_screening'])) {
    foreach ($flags as $f) {
        $formData[$f] = (isset($_POST[$f]) && $_POST[$f] === 'Yes') ? 'Yes' : 'No';
    }

    $formData['details_if_yes'] = $_POST['details_if_yes'] ?? '';
    $formData['other_relevant_info'] = $_POST['other_relevant_info'] ?? '';
    $formData['height'] = $_POST['height'] ?? null;
    $formData['weight'] = $_POST['weight'] ?? null;
    $formData['bmi'] = $_POST['bmi'] ?? null;
    $formData['visual_r'] = $_POST['visual_r'] ?? '';
    $formData['visual_l'] = $_POST['visual_l'] ?? '';
    $formData['blood_pressure'] = $_POST['blood_pressure'] ?? '';
    $formData['pulse_rate'] = $_POST['pulse_rate'] ?? null;
    $formData['urine_albumin'] = $_POST['urine_albumin'] ?? '';
    $formData['urine_sugar'] = $_POST['urine_sugar'] ?? '';
    $formData['genotype'] = $_POST['genotype'] ?? '';
    $formData['blood_group'] = $_POST['blood_group'] ?? '';

    $cols = array_merge(
        $flags,
        ['details_if_yes','other_relevant_info','height','weight','bmi',
         'visual_r','visual_l','blood_pressure','pulse_rate',
         'urine_albumin','urine_sugar','genotype','blood_group']
    );

    // Check if record exists
    $chk = $mysqli->prepare("SELECT id FROM his_screening_forms WHERE pat_id = ?");
    $chk->bind_param('i', $patient->pat_id);
    $chk->execute();
    $chk->store_result();
    $record_exists = $chk->num_rows > 0;
    $chk->close();

    // Prepare values for binding
    $values = [];
    foreach ($cols as $col) {
        $values[] = $formData[$col];
    }

    if ($record_exists) {
        // UPDATE
        $set_clause = implode(' = ?, ', $cols) . ' = ?';
        $sql = "UPDATE his_screening_forms SET $set_clause WHERE pat_id = ?";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            // Add pat_id to the end of values for WHERE clause
            $values[] = $patient->pat_id;
            $types = str_repeat('s', count($values)) . 'i';
            $stmt->bind_param($types, ...$values);
        }
    } else {
        // INSERT
        $placeholders = str_repeat('?,', count($cols)) . '?';
        $sql = "INSERT INTO his_screening_forms (pat_id, " . implode(',', $cols) . ") VALUES (?, $placeholders)";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            // Add pat_id at the beginning of values
            array_unshift($values, $patient->pat_id);
            $types = 'i' . str_repeat('s', count($values) - 1);
            $stmt->bind_param($types, ...$values);
        }
    }

    if ($stmt && $stmt->execute()) {
        $success = $record_exists ? "Screening form updated successfully." : "Screening form saved successfully.";
    } else {
        $err = "Error: " . ($stmt ? $stmt->error : $mysqli->error);
    }
    
    if (isset($stmt)) $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('assets/inc/head.php'); ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>

<body>
<div id="wrapper">
    <?php include("assets/inc/nav.php"); ?>
    <?php include("assets/inc/sidebar.php"); ?>

    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php elseif (!empty($err)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
                <?php endif; ?>
                
                <div id="screeningForm" style="background: #fff; padding: 20px; font-size: 12px; color: #000;">
                    <form method="post">
                        <!-- PART I: Demographic Info -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="card mb-3">
                                    <div class="col-12">
                                        <h4 class="page-title">Medical Entrance Screening Form</h4>
                                        <h5>Screening for: <?= htmlspecialchars($patient->pat_fname . ' ' . $patient->pat_lname) ?> (<?= htmlspecialchars($display_number) ?>)</h5>
                                    </div>
                                </div>
                                
                                <h5>PART I: Student Information</h5>
                                <div class="row">
                                    <div class="col-md-4"><strong>Surname:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_lname')) ?></div>
                                    <div class="col-md-4"><strong>Other Names:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_fname')) ?></div>
                                    <div class="col-md-4"><strong>Age:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_age')) ?></div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-4"><strong>D.O.B:</strong> <?= !empty($patient->pat_dob) ? date('d/m/Y', strtotime($patient->pat_dob)) : 'N/A' ?></div>
                                    <div class="col-md-4"><strong>Sex:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_sex')) ?></div>
                                    <div class="col-md-4"><strong>Marital Status:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_title')) ?></div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-4"><strong>Nationality:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_nationality')) ?></div>
                                    <div class="col-md-4"><strong>State:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_state')) ?></div>
                                    <div class="col-md-4"><strong>Religion:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_religion')) ?></div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-4"><strong>Faculty:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_faculty')) ?></div>
                                    <div class="col-md-4"><strong>Department:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_department')) ?></div>
                                    <div class="col-md-4"><strong>Matric No:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'matric_no')) ?></div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-4"><strong>Jamb Reg No:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_jamb_regno')) ?></div>
                                    <div class="col-md-4"><strong>Phone:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_phone')) ?></div>
                                </div>
                                
                                <h5>Emergency Contact</h5>
                                <div class="row mt-2">
                                    <div class="col-md-6"><strong>Next of Kin:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_nok')) ?></div>
                                    <div class="col-md-6"><strong>Relation:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_relation_nok')) ?></div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6"><strong>Address:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_nok_address')) ?></div>
                                    <div class="col-md-6"><strong>Phone:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_nok_phone')) ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Flags Sections -->
                        <?php
                        function renderFlags($title, $prefix, $list, $formData) {
                            echo "<div class='card mb-3'><div class='card-body'><h5>$title</h5><div class='row'>";
                            foreach ($list as $flag) {
                                $val = $formData[$flag] ?? 'No';
                                echo "<div class='col-md-4'><label>" . ucwords(str_replace("_"," ",$flag)) . "</label>
                                    <select name='$flag' class='form-control'>
                                        <option value='Yes' " . ($val === 'Yes' ? 'selected' : '') . ">Yes</option>
                                        <option value='No' " . ($val === 'No' ? 'selected' : '') . ">No</option>
                                    </select></div>";
                            }
                            echo "</div></div></div>";
                        }

                        renderFlags("Section A: Personal History", 'a_', array_slice($flags, 0, 9), $formData);
                        renderFlags("Section B: Family History", 'b_', array_slice($flags, 9, 4), $formData);
                        renderFlags("Section C: Immunizations", 'c_', array_slice($flags, 13, 6), $formData);
                        renderFlags("Lifestyle", '', array_slice($flags, 19), $formData);
                        ?>

                        <!-- Details and Other Info -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5>Details</h5>
                                <label>If any "Yes", explain:</label>
                                <textarea name="details_if_yes" class="form-control" rows="3"><?= htmlspecialchars($formData['details_if_yes'] ?? '') ?></textarea>
                                <label class="mt-2">Other medical info:</label>
                                <textarea name="other_relevant_info" class="form-control" rows="3"><?= htmlspecialchars($formData['other_relevant_info'] ?? '') ?></textarea>
                                
                                <h5>PART II: Clinical Examination</h5>
                                <div class="form-row">
                                    <?php
                                    $clinical_fields = [
                                        'height'=>'Height (m)', 'weight'=>'Weight (kg)', 'bmi'=>'BMI',
                                        'visual_r'=>'Visual (Right)', 'visual_l'=>'Visual (Left)',
                                        'blood_pressure'=>'Blood Pressure', 'pulse_rate'=>'Pulse Rate'
                                    ];
                                    
                                    foreach ($clinical_fields as $field => $label) {
                                        $value = htmlspecialchars($formData[$field] ?? '');
                                        $input_type = in_array($field, ['height', 'weight', 'bmi', 'pulse_rate']) ? 'number' : 'text';
                                        $step_attr = in_array($field, ['height', 'weight', 'bmi']) ? "step='0.01'" : "";
                                        
                                        echo "<div class='form-group col-md-4'><label>$label</label>
                                            <input type='$input_type' 
                                            name='$field' class='form-control' 
                                            value='$value' $step_attr></div>";
                                    }
                                    ?>
                                </div>
                                
                                <h5>PART III: Laboratory Investigations</h5>
                                <div class="form-row">
                                    <?php
                                    $lab_fields = [
                                        'urine_albumin'=>'Urine Albumin', 'urine_sugar'=>'Urine Sugar',
                                        'genotype'=>'Genotype', 'blood_group'=>'Blood Group'
                                    ];
                                    
                                    foreach ($lab_fields as $field => $label) {
                                        echo "<div class='form-group col-md-3'><label>$label</label>
                                            <input type='text' name='$field' class='form-control' 
                                            value='".htmlspecialchars($formData[$field] ?? '')."'></div>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mb-5">
                            <button type="submit" name="save_screening" class="btn btn-success">
                                <i class="mdi mdi-content-save"></i> Save Screening Form
                            </button>
                            <button type="button" id="btnDownloadPdf" class="btn btn-secondary">
                                <i class="mdi mdi-file-pdf-box"></i> Download as PDF
                            </button>
                        </div>
                    </form>
                </div><!-- /#screeningForm -->

                <?php include('assets/inc/footer.php'); ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('btnDownloadPdf').addEventListener('click', () => {
        html2pdf().set({
            margin: 0,
            filename: 'screening-<?= addslashes($display_number) ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 1.5, scrollY: 0 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
            pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
        }).from(document.getElementById('screeningForm')).save();
    });
});
</script>

<script src="assets/js/vendor.min.js"></script>
<script src="assets/js/app.min.js"></script>
</body>
</html>