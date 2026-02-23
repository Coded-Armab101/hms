<?php
// Only start session once and turn on errors
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'assets/inc/config.php';
require_once 'assets/inc/checklogin.php';
check_login();

// 1) Ensure student is logged in
$student_id = $_SESSION['student_id'] ?? null;
if (!$student_id) {
    die('Not authenticated');
}

// 2) Fetch this student's patient record
$stmt = $mysqli->prepare("
  SELECT
    pat_id,
    pat_lname, pat_fname, pat_age, pat_dob, pat_sex,
    pat_title, pat_nationality, pat_state, pat_religion,
    pat_faculty, pat_department, pat_number AS matric_no,
    pat_jamb_regno, pat_phone, marital_status, pat_addr, pat_hostel_address,
    pat_nok, pat_relation_nok, pat_nok_address, pat_nok_phone
  FROM his_patients
  WHERE student_id = ? LIMIT 1
");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_object();
$stmt->close();
if (!$patient) {
    die("Patient not found");
  
}

$stmt = $mysqli->prepare("SELECT height, weight, bmi, visual_r, visual_l, blood_pressure, pulse_rate, urine_albumin, urine_sugar, genotype, blood_group FROM his_screening_forms WHERE student_id = ?");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$formData = $stmt->get_result()->fetch_object();  // Rename to $formData
$stmt->close();



// 3) Handle form submit (saving screening)
if (isset($_POST['save_screening'])) {
    // flags A/B/C + lifestyle...
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
    // free-text + Part II + Part III
    $details_if_yes      = $mysqli->real_escape_string($_POST['details_if_yes'] ?? '');
    $other_relevant_info = $mysqli->real_escape_string($_POST['other_relevant_info'] ?? '');
    $height         = $_POST['height']         ?? null;
    $weight         = $_POST['weight']         ?? null;
    $bmi            = $_POST['bmi']            ?? null;
    $visual_r       = $_POST['visual_r']       ?? null;
    $visual_l       = $_POST['visual_l']       ?? null;
    $blood_pressure = $_POST['blood_pressure'] ?? null;
    $pulse_rate     = $_POST['pulse_rate']     ?? null;
    $urine_albumin  = $_POST['urine_albumin']  ?? null;
    $urine_sugar    = $_POST['urine_sugar']    ?? null;
    $genotype       = $_POST['genotype']       ?? null;
    $blood_group    = $_POST['blood_group']    ?? null;

    // Build columns, placeholders, types
    $cols = array_merge(
      ['pat_id'],
      $flags,
      ['details_if_yes','other_relevant_info'],
      ['height','weight','bmi','visual_r','visual_l','blood_pressure','pulse_rate'],
      ['urine_albumin','urine_sugar','genotype','blood_group']
    );
    $placeholders = implode(',', array_fill(0, count($cols), '?'));
    $types = 'i' 
           . str_repeat('s', count($flags)) 
           . 'ss'  // two text
           . 'dd'  // height, weight
           . 'd'   // bmi
           . 'ss'  // visual R/L
           . 's'   // blood_pressure
           . 'i'   // pulse_rate
           . 'ssss'; // four lab
    $bindValues = array_merge(
      [$patient->pat_id],
      array_values($values),
      [$details_if_yes, $other_relevant_info],
      [$height, $weight, $bmi, $visual_r, $visual_l, $blood_pressure, $pulse_rate],
      [$urine_albumin, $urine_sugar, $genotype, $blood_group]
    );

    $sql = "INSERT INTO his_screening_forms (" . implode(',', $cols) . ")
            VALUES ($placeholders)";
    $ins = $mysqli->prepare($sql);
    $ins->bind_param($types, ...$bindValues);
    if ($ins->execute()) {
        $success = "Screening form saved successfully.";
    } else {
        $err = "Error saving: " . $ins->error;
    }
    $ins->close();
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
  <!-- Topbar Start -->
            <?php include('assets/inc/nav.php');?>
            <!-- end Topbar -->
  <?php include("assets/inc/sidebar.php"); ?>

  <div class="content-page">
    <div class="content">
      <div class="container-fluid">

        <!-- Feedback -->
        <?php if (!empty($success)): ?>
          <div class="alert alert-success"><?= $success ?></div>
        <?php elseif (!empty($err)): ?>
          <div class="alert alert-danger"><?= $err ?></div>
        <?php endif; ?>

        <!-- EVERYTHING INSIDE HERE GETS RENDERED IN THE PDF -->
        <div id="screeningForm" style="background: #fff; padding: 20px; font-size: 12px; color: #000;">
          <form method="post">

          
            <!-- PART I -->
            
                <!-- Page Title -->
                <div class="row mb-4">
                  <div class="col-12">
                    <h4 class="page-title">Medical Entrance Screening Form</h4>
                    <h5>
                      Screening for:
                      <?= htmlspecialchars($patient->pat_fname . ' ' . $patient->pat_lname) ?>
                      (<?= htmlspecialchars($patient->matric_no) ?>)
                    </h5>
                  </div>
                </div>
                <h5>PART I: Student Information</h5>
                <div class="row">
                  <div class="col-md-4"><strong>Surname:</strong> <?= $patient->pat_lname ?></div>
                  <div class="col-md-4"><strong>Other Names:</strong> <?= $patient->pat_fname ?></div>
                  <div class="col-md-4"><strong>Age:</strong> <?= $patient->pat_age ?></div>
                </div>
                <div class="row mt-2">
                  
                  <div class="col-md-4"><strong>D.O.B:</strong> <?= date('d/m/Y',strtotime($patient->pat_dob)) ?></div>
                  <div class="col-md-4"><strong>Sex:</strong> <?= $patient->pat_sex ?></div>
                  <div class="col-md-4"><strong>Title:</strong> <?= $patient->pat_title ?></div>
                </div>
                <!-- … more demographic rows … -->
                <div class="row mt-2">
                  <div class="col-md-4"><strong>Nationality:</strong> <?= $patient->pat_nationality ?></div>
                  <div class="col-md-4"><strong>State:</strong> <?= $patient->pat_state ?></div>
                  <div class="col-md-4"><strong>Religion:</strong> <?= $patient->pat_religion ?></div>
                </div>
                <div class="row mt-2">
                  <div class="col-md-4"><strong>Faculty:</strong> <?= $patient->pat_faculty ?></div>
                  <div class="col-md-4"><strong>Department:</strong> <?= $patient->pat_department ?></div>
                  <div class="col-md-4"><strong>Matric No:</strong> <?= $patient->matric_no ?></div>
                </div>
                <div class="row mt-2">
                  <div class="col-md-4"><strong>Jamb Reg No:</strong> <?= $patient->pat_jamb_regno ?></div>
                  <div class="col-md-4"><strong>Telephone:</strong> <?= $patient->pat_phone ?></div>
                  <div class="col-md-4"><strong>Marital Status:</strong> <?= $patient->marital_status ?></div>
                </div>
                <div class ="row mt-2">
                  <div class="col-md-4"><strong>Home Address:</strong> <?= $patient->pat_addr?></div>
                  <div class="col-md-4"><strong>Hostel Address:</strong> <?= $patient->pat_hostel_address?></div>
                </div>
                <div class="card mb-3">
                  <div class="card-body">
                    <h5>For Emergencies:</h5>
                    <div class="row">
                      <div class="col-md-6"><strong>Next of Kin:</strong> <?= $patient->pat_nok ?></div>
                      <div class="col-md-6"><strong>Relationship:</strong> <?= $patient->pat_relation_nok ?></div>
                    </div>
                    <div class="row mt-2">
                      <div class="col-md-6"><strong>Address (NOK):</strong> <?= $patient->pat_nok_address ?></div>
                      <div class="col-md-6"><strong>Telephone (NOK):</strong> <?= $patient->pat_nok_phone ?></div>
                    </div>
                            <br><h5>Section A: Personal History</h5>
                        <p>Do you suffer from or have you suffered from any of the following:</p>
                        <div class="row">
                          <?php foreach ([
                            'Tuberculosis'=>'a_tuberculosis','Asthma'=>'a_asthma',
                            'Peptic Ulcer Disease'=>'a_peptic_ulcer','Sickle cell disease'=>'a_sickle_cell',
                            'Allergies'=>'a_allergies','Diabetes'=>'a_diabetes',
                            'Hypertension'=>'a_hypertension','Seizures/Convulsions'=>'a_seizures',
                            'Mental illness or Insanity'=>'a_mental_ill'
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
                        <br><h5>Section B: Family History</h5>
                        <p>Has any member of your family suffered from:</p>
                        <div class="row">
                          <?php foreach ([
                            'Tuberculosis'=>'b_tuberculosis','Mental illness or Insanity'=>'b_mental_illness',
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
             

            
            

            <!-- Section A / B / C / Lifestyle -->
            <!--<div class="card mb-3">
              <div class="card-body">
                <h5>Section A: Personal History</h5>
                <p>Do you suffer from or have you suffered from any of the following:</p>
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
                </div>-->
                
                <!-- similarly Section B & C… -->
                <!-- Section B: Family History -->
            <!--<div class="card mb-3">
              <div class="card-body">
                <h5>Section B: Family History</h5>
                <p>Has any member of your family suffered from:</p>
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
            </div>-->

                <!-- Section C: Immunizations -->
                <div class="card mb-3">
                  <div class="card-body">
                    <h5>Section C: Immunizations</h5>
                    <p>Have you been immunized against any of the following diseases:</p>
                    <div class="row">
                      <?php foreach ([
                        'Small pox'=>'c_smallpox','Poliomyelitis'=>'c_poliomyelitis',
                        'Tuberculosis'=>'c_tuberculosis_vax','Meningitis'=>'c_meningitis',
                        'HPV (for female Only)'=>'c_hpv','Hepatitis B'=>'c_hepatitis_b'
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
                    <h5>Lifestyle</h5>
                    <p>Do you currently use or you have someone at home/school/hostel who use these when you are present:</p>
                    <div class="row">
                      <?php foreach ([
                        'Do you currently use tobacco products?'=>'uses_tobacco',
                        'Are you a passive smoker?'=>'exposed_to_smoke',
                        'Do you currently consume alcohol?'=>'consumes_alcohol'
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
                      <textarea name="details_if_yes" class="form-control" rows="2"><?= htmlspecialchars($_POST['details_if_yes']??'') ?></textarea>
                    </div>
                    <div class="form-group">
                      <label>Any other relevant medical information, provide details</label>
                      <textarea name="other_relevant_info" class="form-control" rows="2"><?= htmlspecialchars($_POST['other_relevant_info']??'') ?></textarea>
                    </div>

                    <h5>PART II: Clinical Examination</h5>
                      <div class="form-row">
                        <div class="form-group col-md-4">
                          <label>Height (m)</label>
                          <div class="form-control-plaintext"><?= htmlspecialchars(!empty($formData->height) ? $formData->height : 'N/A') ?></div>
                        </div>
                        <div class="form-group col-md-4">
                          <label>Weight (kg)</label>
                          <div class="form-control-plaintext"><?= htmlspecialchars($formData->weight ?? '') ?></div>
                        </div>
                        <div class="form-group col-md-4">
                          <label>BMI</label>
                          <div class="form-control-plaintext"><?= htmlspecialchars($formData->bmi ?? 'N/A') ?></div>
                        </div>
                      </div>
                      <div class="form-row">
                        <div class="form-group col-md-4">
                          <label>Visual Acuity (R)</label>
                          <div class="form-control-plaintext"><?= htmlspecialchars($formData->visual_r ?? 'N/A') ?></div>
                        </div>
                        <div class="form-group col-md-4">
                          <label>Visual Acuity (L)</label>
                          <div class="form-control-plaintext"><?= htmlspecialchars($formData->visual_l ?? 'N/A') ?></div>
                        </div>
                        <div class="form-group col-md-4">
                          <label>Blood Pressure</label>
                          <div class="form-control-plaintext"><?= htmlspecialchars($formData->blood_pressure ?? 'N/A') ?></div>
                        </div>
                      </div>
                      <div class="form-row">
                        <div class="form-group col-md-4">
                          <label>Pulse Rate</label>
                          <div class="form-control-plaintext"><?= htmlspecialchars($formData->pulse_rate ?? 'N/A') ?></div>
                        </div>
                      </div>
                       <h5>PART III: Laboratory Investigations</h5>
                          <div class="form-row">
                            <div class="form-group col-md-3">
                              <label>Urine Albumin</label>
                              <div class="form-control-plaintext"><?= htmlspecialchars($formData->urine_albumin ?? 'N/A') ?></div>
                            </div>
                            <div class="form-group col-md-3">
                              <label>Urine Sugar</label>
                              <div class="form-control-plaintext"><?= htmlspecialchars($formData->urine_sugar ?? 'N/A') ?></div>
                            </div>
                            <div class="form-group col-md-3">
                              <label>Genotype</label>
                              <div class="form-control-plaintext"><?= htmlspecialchars($formData->genotype ?? 'N/A') ?></div>
                            </div>
                            <div class="form-group col-md-3">
                              <label>Blood Group</label>
                              <div class="form-control-plaintext"><?= htmlspecialchars($formData->blood_group ?? 'N/A') ?></div>
                            </div>
                          </div>


                <!-- Lifestyle -->
                <!--<div class="card mb-3">
                  <div class="card-body">
                  
                    <h5>Lifestyle</h5>
                    <p>Do you currently use or you have someone at home/school/hostel who use these when you are present:</p>
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
                </div>-->

              
            

                  <!-- PART II: Clinical Examination -->
                  <!--<div class="card mb-3">
                    <div class="card-body">
                      <h5>PART II: Clinical Examination:(To be completed by clinic staff)</h5>
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
                  </div>-->

                  
            <!--<div class="card mb-3">
              <div class="card-body">
                <h5>PART III: Laboratory Investigations:(To be completed by clinic staff)</h5>
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
            </div>-->

            <div class="text-center mb-4">
              <button type="submit" name="save_screening" class="btn btn-success">
                <i class="mdi mdi-content-save"></i> Save Screening Form
              </button>
            </div>
          </form>
        </div>
        <!-- /#screeningForm -->

        <!-- Download PDF button (outside the capture div!) -->
        <div class="text-center mb-5">
          <button type="button" id="btnDownloadPdf" class="btn btn-secondary">
            <i class="mdi mdi-file-pdf-box"></i> Download as PDF
          </button>
        </div>
            
      </div><!-- /.container-fluid -->
    </div><!-- /.content -->
  </div><!-- /.content-page -->
</div><!-- /#wrapper -->


<!-- 1) html2pdf bundle -->
<!--<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>-->
<!-- 2) Download script -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
      const btn       = document.getElementById('btnDownloadPdf');
      const container = document.getElementById('screeningForm');
      btn.addEventListener('click', () => {
        html2pdf()
          .set({
            margin:       0,
            filename:     `screening-<?= addslashes($patient->matric_no) ?>.pdf`,
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 1.5, scrollY: 0 },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
            pagebreak:    { mode: ['avoid-all', 'css', 'legacy'] }
          })
          .from(container)
          .save()
          .catch(err => console.error('PDF error:', err));
      });
    });
  </script>

<!-- 3) Your other JS -->
<script src="assets/js/vendor.min.js"></script>
<script src="assets/js/app.min.js"></script>
</body>
</html>
