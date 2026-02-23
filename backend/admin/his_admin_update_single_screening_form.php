<?php
// Only start session once and turn on errors
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();

// Accept either pat_number or pat_id in the URL (pat_number preferred)
$pat_number = isset($_GET['pat_number']) ? trim((string)$_GET['pat_number']) : '';
$pat_id     = isset($_GET['pat_id']) ? intval($_GET['pat_id']) : 0;
if ($pat_number === '' && !$pat_id) {
    die("Missing patient identifier");
}

// Define all possible patient columns
$possible_columns = [
    'pat_id', 'pat_lname', 'pat_fname', 'pat_age', 'pat_dob', 'pat_sex',
    'pat_title', 'pat_nationality', 'pat_state', 'pat_religion',
    'pat_faculty', 'pat_department', 'pat_number', 'pat_jamb_regno', 'pat_phone',
    'pat_nok', 'pat_relation_nok', 'pat_nok_address', 'pat_nok_phone', 'pat_file_number'
];

// Fetch patient data - simpler approach
$patient = null;
$query = "SELECT * FROM his_patients WHERE ";
$params = [];
$types = "";

if ($pat_number !== '') {
    $query .= "pat_number = ? LIMIT 1";
    $params[] = $pat_number;
    $types = "s";
} else {
    $query .= "pat_id = ? LIMIT 1";
    $params[] = $pat_id;
    $types = "i";
}

try {
    $stmt = $mysqli->prepare($query);
    if ($stmt) {
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $patient = $result->fetch_object();
        $stmt->close();
    }
} catch (Exception $e) {
    // Log error if needed
    error_log("Patient fetch error: " . $e->getMessage());
}

if (!$patient) {
    die("Patient not found");
}

// Create a safe accessor for patient properties
function getPatientProperty($patient, $property, $default = 'N/A') {
    return property_exists($patient, $property) && !empty($patient->$property) 
           ? $patient->$property 
           : $default;
}

// Determine display number
$display_number = getPatientProperty($patient, 'pat_number', 
                   getPatientProperty($patient, 'pat_file_number', 
                     'ID:' . getPatientProperty($patient, 'pat_id', 'N/A')));

// Handle "Save Screening Form" submission
// [Keep your existing form submission code]
?>
<?php
// Handle “Save Screening Form” submission
if (isset($_POST['save_screening'])) {
    // Sections A/B/C flags
    $flags = [
        'a_tuberculosis','a_asthma','a_peptic_ulcer','a_sickle_cell','a_allergies',
        'a_diabetes','a_hypertension','a_seizures','a_mental_ill',
        'b_tuberculosis','b_mental_illness','b_diabetes_mellitus','b_heart_disease',
        'c_smallpox','c_poliomyelitis','c_tuberculosis_vax','c_meningitis','c_hpv','c_hepatitis_b',
        'uses_tobacco','exposed_to_smoke','consumes_alcohol'
    ];
    $values = [];
    foreach ($flags as $f) {
        $values[$f] = (isset($_POST[$f]) && $_POST[$f] === 'Yes') ? 'Yes' : 'No';
    }
    // Free-text
    $details_if_yes      = $mysqli->real_escape_string($_POST['details_if_yes'] ?? '');
    $other_relevant_info = $mysqli->real_escape_string($_POST['other_relevant_info'] ?? '');
    // Part II: Clinical
    $height         = $_POST['height']         ?: null;
    $weight         = $_POST['weight']         ?: null;
    $bmi            = $_POST['bmi']            ?: null;
    $visual_r       = $_POST['visual_r']       ?: '';
    $visual_l       = $_POST['visual_l']       ?: '';
    $blood_pressure = $_POST['blood_pressure'] ?: '';
    $pulse_rate     = $_POST['pulse_rate']     ?: null;
    // Part III: Lab
    $urine_albumin  = $_POST['urine_albumin']  ?: '';
    $urine_sugar    = $_POST['urine_sugar']    ?: '';
    $genotype       = $_POST['genotype']       ?: '';
    $blood_group    = $_POST['blood_group']    ?: '';

    // Build column list & placeholders
    $cols = array_merge(
        ['pat_id'],
        $flags,
        ['details_if_yes','other_relevant_info'],
        ['height','weight','bmi','visual_r','visual_l','blood_pressure','pulse_rate'],
        ['urine_albumin','urine_sugar','genotype','blood_group']
    );
    $placeholders = implode(',', array_fill(0, count($cols), '?'));
    $types = 'i'                     // pat_id
           . str_repeat('s', count($flags))   // flags
           . 'ss'                   // two free-text
           . 'dd'                   // height, weight
           . 'd'                    // bmi
           . 'ss'                   // visual R/L
           . 's'                    // blood_pressure
           . 'i'                    // pulse_rate
           . 'ssss';                // four lab fields
    // Gather bind values in the same order
    $bindValues = array_merge(
        [$patient->pat_id],
        array_values($values),
        [$details_if_yes, $other_relevant_info],
        [$height, $weight, $bmi, $visual_r, $visual_l, $blood_pressure, $pulse_rate],
        [$urine_albumin, $urine_sugar, $genotype, $blood_group]
    );

    $sql = "INSERT INTO his_screening_forms (" . implode(',', $cols) . ")
            VALUES ($placeholders)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$bindValues);
    if ($stmt->execute()) {
        $success = "Screening form saved successfully.";
    } else {
        $err = "Error saving: " . $stmt->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <!-- your head.php includes… -->
  <?php include('assets/inc/head.php'); ?>
  <script
    src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"
    integrity="sha512-…"
    crossorigin="anonymous"
    referrerpolicy="no-referrer"
  ></script>
</head>
<body>
<div id="wrapper">
  <?php include("assets/inc/nav.php"); ?>
  <?php include("assets/inc/sidebar.php"); ?>

  <div class="content-page">
    <div class="content">
      <div class="container-fluid">

        <!-- Page Title -->
        <div class="row mb-4">
          <div class="col-12">
            <h4 class="page-title">Medical Entrance Screening Form</h4>
            <h5>
              Screening for:
              <?= htmlspecialchars($patient->pat_fname . ' ' . $patient->pat_lname) ?>
              (<?= htmlspecialchars($display_number) ?>)
            </h5>
          </div>
        </div>

        <!-- Success / Error Alerts -->
        <?php if (!empty($success)): ?>
          <div class="alert alert-success"><?= $success ?></div>
        <?php elseif (!empty($err)): ?>
          <div class="alert alert-danger"><?= $err ?></div>
        <?php endif; ?>

 <!-- PART I: Demographics -->
<div class="card mb-3">
  <div class="card-body">
    <h5>PART I: Student Information</h5>
    <div class="row">
      <div class="col-md-6"><strong>Surname:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_lname')) ?></div>
      <div class="col-md-6"><strong>Other Names:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_fname')) ?></div>
    </div>
    <div class="row mt-2">
      <div class="col-md-3"><strong>Age:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_age')) ?></div>
      <div class="col-md-3"><strong>D.O.B:</strong> <?= !empty($patient->pat_dob) ? date('d/m/Y', strtotime($patient->pat_dob)) : 'N/A' ?></div>
      <div class="col-md-3"><strong>Sex:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_sex')) ?></div>
      <div class="col-md-3"><strong>Marital Status:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_title')) ?></div>
    </div>
    <div class="row mt-2">
      <div class="col-md-4"><strong>Nationality:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_nationality')) ?></div>
      <div class="col-md-4"><strong>State:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_state')) ?></div>
      <div class="col-md-4"><strong>Religion:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_religion')) ?></div>
    </div>
    <div class="row mt-2">
      <div class="col-md-4"><strong>Faculty:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_faculty')) ?></div>
      <div class="col-md-4"><strong>Department:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_department')) ?></div>
      <div class="col-md-4"><strong>Matric No:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_number')) ?></div>
    </div>
    <div class="row mt-2">
      <div class="col-md-4"><strong>Jamb Reg No:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_jamb_regno')) ?></div>
      <div class="col-md-4"><strong>Telephone:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_phone')) ?></div>
    </div>
  </div>
</div>

<!-- Emergencies -->
<div class="card mb-3">
  <div class="card-body">
    <h5>For Emergencies:</h5>
    <div class="row">
      <div class="col-md-6"><strong>Next of Kin:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_nok')) ?></div>
      <div class="col-md-6"><strong>Relationship:</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_relation_nok')) ?></div>
    </div>
    <div class="row mt-2">
      <div class="col-md-8"><strong>Address (NOK):</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_nok_address')) ?></div>
      <div class="col-md-4"><strong>Telephone (NOK):</strong> <?= htmlspecialchars(getPatientProperty($patient, 'pat_nok_phone')) ?></div>
    </div>
  </div>
</div>
            <!-- Section A: Personal History -->
            <div class="card mb-3">
              <div class="card-body">
                <h5>Section A: Personal History</h5>
                <div class="row">
                  <?php foreach ([
                    'Tuberculosis'=>'a_tuberculosis','Asthma'=>'a_asthma',
                    'Peptic Ulcer Disease'=>'a_peptic_ulcer','Sickle cell disease'=>'a_sickle_cell',
                    'Allergies'=>'a_allergies','Diabetes'=>'a_diabetes',
                    'Hypertension'=>'a_hypertension','Seizures/Convulsions'=>'a_seizures',
                    'Mental illness'=>'a_mental_ill'
                  ] as $label=>$name): ?>
                    <div class="col-md-4 form-group">
                      <label><?= $label ?></label>
                      <select name="<?= $name ?>" class="form-control">
                        <option value="Yes"<?= (($_POST[$name]??'No')==='Yes')?' selected':'';?>>Yes</option>
                        <option value="No" <?= (($_POST[$name]??'No')==='No')?' selected':'';?>>No</option>
                      </select>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <!-- Section B: Family History -->
            <div class="card mb-3">
              <div class="card-body">
                <h5>Section B: Family History</h5>
                <div class="row">
                  <?php foreach ([
                    'Tuberculosis'=>'b_tuberculosis','Mental illness'=>'b_mental_illness',
                    'Diabetes Mellitus'=>'b_diabetes_mellitus','Heart Disease'=>'b_heart_disease'
                  ] as $label=>$name): ?>
                    <div class="col-md-3 form-group">
                      <label><?= $label ?></label>
                      <select name="<?= $name ?>" class="form-control">
                        <option value="Yes"<?= (($_POST[$name]??'No')==='Yes')?' selected':'';?>>Yes</option>
                        <option value="No" <?= (($_POST[$name]??'No')==='No')?' selected':'';?>>No</option>
                      </select>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <!-- Section C: Immunizations -->
            <div class="card mb-3">
              <div class="card-body">
                <h5>Section C: Immunizations</h5>
                <div class="row">
                  <?php foreach ([
                    'Small pox'=>'c_smallpox','Poliomyelitis'=>'c_poliomyelitis',
                    'Tuberculosis'=>'c_tuberculosis_vax','Meningitis'=>'c_meningitis',
                    'HPV'=>'c_hpv','Hepatitis B'=>'c_hepatitis_b'
                  ] as $label=>$name): ?>
                    <div class="col-md-4 form-group">
                      <label><?= $label ?></label>
                      <select name="<?= $name ?>" class="form-control">
                        <option value="Yes"<?= (($_POST[$name]??'No')==='Yes')?' selected':'';?>>Yes</option>
                        <option value="No" <?= (($_POST[$name]??'No')==='No')?' selected':'';?>>No</option>
                      </select>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <!-- Lifestyle -->
            <div class="card mb-3">
              <div class="card-body">
                <h5>Lifestyle</h5>
                <div class="row">
                  <?php foreach ([
                    'Use tobacco products?'=>'uses_tobacco',
                    'Second-hand smoke?'=>'exposed_to_smoke',
                    'Consume alcohol?'=>'consumes_alcohol'
                  ] as $label=>$name): ?>
                    <div class="col-md-4 form-group">
                      <label><?= $label ?></label>
                      <select name="<?= $name ?>" class="form-control">
                        <option value="Yes"<?= (($_POST[$name]??'No')==='Yes')?' selected':'';?>>Yes</option>
                        <option value="No" <?= (($_POST[$name]??'No')==='No')?' selected':'';?>>No</option>
                      </select>
                    </div>
                  <?php endforeach; ?>
                </div>
                <div class="form-group mt-3">
                  <label>If any “Yes”, provide details</label>
                  <textarea name="details_if_yes" class="form-control" rows="3"><?= htmlspecialchars($_POST['details_if_yes']??'') ?></textarea>
                </div>
                <div class="form-group">
                  <label>Any other relevant medical information</label>
                  <textarea name="other_relevant_info" class="form-control" rows="3"><?= htmlspecialchars($_POST['other_relevant_info']??'') ?></textarea>
                </div>
              </div>
            </div>

            <!-- PART II: Clinical Examination -->
            <div class="card mb-3">
              <div class="card-body">
                <h5>PART II: Clinical Examination</h5>
                <div class="form-row">
                  <div class="form-group col-md-4">
                    <label>Height (m)</label>
                    <input type="number" step="0.01" name="height" class="form-control" value="<?= htmlspecialchars($_POST['height']??'') ?>">
                  </div>
                  <div class="form-group col-md-4">
                    <label>Weight (kg)</label>
                    <input type="number" step="0.1" name="weight" class="form-control" value="<?= htmlspecialchars($_POST['weight']??'') ?>">
                  </div>
                  <div class="form-group col-md-4">
                    <label>BMI</label>
                    <input type="number" step="0.01" name="bmi" class="form-control" value="<?= htmlspecialchars($_POST['bmi']??'') ?>">
                  </div>
                </div>
                <div class="form-row mt-2">
                  <div class="form-group col-md-4">
                    <label>Visual Acuity (R)</label>
                    <input type="text" name="visual_r" class="form-control" value="<?= htmlspecialchars($_POST['visual_r']??'') ?>">
                  </div>
                  <div class="form-group col-md-4">
                    <label>Visual Acuity (L)</label>
                    <input type="text" name="visual_l" class="form-control" value="<?= htmlspecialchars($_POST['visual_l']??'') ?>">
                  </div>
                  <div class="form-group col-md-4">
                    <label>Blood Pressure (BP)</label>
                    <input type="text" name="blood_pressure" class="form-control" value="<?= htmlspecialchars($_POST['blood_pressure']??'') ?>">
                  </div>
                </div>
                <div class="form-row mt-2">
                  <div class="form-group col-md-4">
                    <label>Pulse Rate (PR)</label>
                    <input type="number" name="pulse_rate" class="form-control" value="<?= htmlspecialchars($_POST['pulse_rate']??'') ?>">
                  </div>
                </div>
              </div>
            </div>

            <!-- PART III: Lab Investigations -->
            <div class="card mb-3">
              <div class="card-body">
                <h5>PART III: Laboratory Investigations</h5>
                <div class="form-row">
                  <div class="form-group col-md-3">
                    <label>Urine Albumin</label>
                    <input type="text" name="urine_albumin" class="form-control" value="<?= htmlspecialchars($_POST['urine_albumin']??'') ?>">
                  </div>
                  <div class="form-group col-md-3">
                    <label>Urine Sugar</label>
                    <input type="text" name="urine_sugar" class="form-control" value="<?= htmlspecialchars($_POST['urine_sugar']??'') ?>">
                  </div>
                  <div class="form-group col-md-3">
                    <label>Genotype</label>
                    <input type="text" name="genotype" class="form-control" value="<?= htmlspecialchars($_POST['genotype']??'') ?>">
                  </div>
                  <div class="form-group col-md-3">
                    <label>Blood Group</label>
                    <input type="text" name="blood_group" class="form-control" value="<?= htmlspecialchars($_POST['blood_group']??'') ?>">
                  </div>
                </div>
              </div>
            </div>

            <!-- Buttons -->
            <div class="mb-5 text-center">
              <button type="submit" name="save_screening" class="btn btn-success">
                <i class="mdi mdi-content-save"></i> Save Screening Form
              </button>
              <button type="button" id="btnDownloadPdf" class="btn btn-secondary">
                <i class="mdi mdi-file-pdf-box"></i> Download as PDF
              </button>
            </div>
          </form>
        </div><!-- /#screeningForm -->

      </div><!-- /.container-fluid -->
    </div><!-- /.content -->

    <?php include('assets/inc/footer.php'); ?>
  </div><!-- /.content-page -->
</div><!-- /#wrapper -->

 <!-- 1) load the html2pdf bundle *first* from the CDN -->
 <script>
    document.addEventListener('DOMContentLoaded', () => {
      const btn       = document.getElementById('btnDownloadPdf');
      const container = document.getElementById('screeningForm');
      btn.addEventListener('click', () => {
        html2pdf()
          .set({
            margin:       [0.1,0.1,0.1,0.1],
            filename:     `screening-<?= addslashes($display_number) ?>.pdf`,
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, scrollY: 0 },
            jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' },
            pagebreak:    { mode: ['avoid-all', 'css', 'legacy'] }
          })
          .from(container)
          .save()
          .catch(err => console.error('PDF error:', err));
      });
    });
  </script>   


    <!-- 3) your other vendor/app scripts -->
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
  </body>
</html>