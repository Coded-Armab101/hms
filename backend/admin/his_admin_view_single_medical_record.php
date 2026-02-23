<?php
  session_start();
  include('assets/inc/config.php');
  include('assets/inc/checklogin.php');
  check_login();
  $aid=$_SESSION['ad_id'];
?>
<!DOCTYPE html>
<html lang="en">
    
<?php include ('assets/inc/head.php');?>

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
                $mdr_number=$_GET['mdr_number'];
                $mdr_id=$_GET['mdr_id'];
                $ret="SELECT  * FROM his_medical_records WHERE mdr_id = ?";
                $stmt= $mysqli->prepare($ret) ;
                $stmt->bind_param('i',$mdr_id);
                $stmt->execute() ;//ok
                $res=$stmt->get_result();
                //$cnt=1;
                while($row=$res->fetch_object())
                {
                    $mysqlDateTime = $row->mdr_date_rec;

                    // Initialize variables for fetched data
                    $prescription = null;
                    $doctor_name = "Not specified";
                    $doctor_id = null;
                    $ailment_from_note = null;
                    $doctor_note = null;
                    
                    // Get patient ID if available in medical records or from patients table
                    $pat_id = null;
                    if (!empty($row->mdr_pat_number)) {
                        // Try to get patient ID from patients table using patient number
                        $pat_query = "SELECT pat_id FROM his_patients WHERE pat_number = ?";
                        $pat_stmt = $mysqli->prepare($pat_query);
                        $pat_stmt->bind_param('s', $row->mdr_pat_number);
                        $pat_stmt->execute();
                        $pat_res = $pat_stmt->get_result();
                        if ($pat_row = $pat_res->fetch_object()) {
                            $pat_id = $pat_row->pat_id;
                        }
                        $pat_stmt->close();
                    }

                    // 1. Fetch latest prescription for this patient
                    if (!empty($row->mdr_pat_number)) {
                        $param = $row->mdr_pat_number;

                        // Check table structure for prescriptions
                        $pres_columns = [];
                        $pres_cols_query = "SHOW COLUMNS FROM his_prescriptions";
                        $pres_cols_result = $mysqli->query($pres_cols_query);
                        while ($col = $pres_cols_result->fetch_assoc()) {
                            $pres_columns[] = $col['Field'];
                        }

                        // Build the best possible query based on available columns
                        $has_doc_id = in_array('doc_id', $pres_columns);
                        $has_doc_name = in_array('doc_name', $pres_columns);
                        $has_pat_number = in_array('pat_number', $pres_columns);
                        $has_pres_pat_number = in_array('pres_pat_number', $pres_columns);
                        $has_pres_number = in_array('pres_number', $pres_columns);
                        
                        // Determine the patient number column name
                        $pat_number_col = 'pat_number';
                        if ($has_pat_number) {
                            $pat_number_col = 'pat_number';
                        } elseif ($has_pres_pat_number) {
                            $pat_number_col = 'pres_pat_number';
                        } elseif ($has_pres_number) {
                            $pat_number_col = 'pres_number';
                        }

                        // Build query based on available columns
                        if ($has_doc_id) {
                            // Try to get doctor name by joining with docs table
                            $query = "SELECT p.*, d.doc_id, CONCAT(d.doc_fname, ' ', d.doc_lname) AS doctor_name 
                                      FROM his_prescriptions p 
                                      LEFT JOIN his_docs d ON p.doc_id = d.doc_id 
                                      WHERE p.$pat_number_col = ? 
                                      ORDER BY p.pres_date DESC LIMIT 1";
                        } elseif ($has_doc_name) {
                            // Use doc_name column directly
                            $query = "SELECT p.*, p.doc_name AS doctor_name 
                                      FROM his_prescriptions p 
                                      WHERE p.$pat_number_col = ? 
                                      ORDER BY p.pres_date DESC LIMIT 1";
                        } else {
                            // Basic query without doctor info
                            $query = "SELECT p.* FROM his_prescriptions p 
                                      WHERE p.$pat_number_col = ? 
                                      ORDER BY p.pres_date DESC LIMIT 1";
                        }

                        try {
                            $stmtP = $mysqli->prepare($query);
                            if ($stmtP) {
                                $stmtP->bind_param('s', $param);
                                $stmtP->execute();
                                $resP = $stmtP->get_result();
                                if ($presRow = $resP->fetch_object()) {
                                    $prescription = $presRow;
                                    
                                    // Extract doctor info
                                    if (isset($presRow->doctor_name) && !empty($presRow->doctor_name)) {
                                        $doctor_name = $presRow->doctor_name;
                                    } elseif (isset($presRow->doc_name) && !empty($presRow->doc_name)) {
                                        $doctor_name = $presRow->doc_name;
                                    }
                                    
                                    if (isset($presRow->doc_id)) {
                                        $doctor_id = $presRow->doc_id;
                                    }
                                }
                                $stmtP->close();
                            }
                        } catch (Exception $e) {
                            error_log("Error fetching prescription: " . $e->getMessage());
                        }
                    }

                    // 2. Fetch latest doctor's note to extract ailment
                    if ($pat_id) {
                        $note_query = "SELECT pat_notes, notes_date, doc_id 
                                       FROM his_notes 
                                       WHERE pat_id = ? 
                                       ORDER BY notes_date DESC LIMIT 1";
                        $note_stmt = $mysqli->prepare($note_query);
                        if ($note_stmt) {
                            $note_stmt->bind_param('i', $pat_id);
                            $note_stmt->execute();
                            $note_res = $note_stmt->get_result();
                            if ($note_row = $note_res->fetch_object()) {
                                $doctor_note = $note_row->pat_notes;
                                
                                // Extract ailment from doctor's note using pattern matching
                                $patterns = [
                                    '/diagnosis[:\s]+([^\.\n]+)/i',
                                    '/diagnosed with[:\s]+([^\.\n]+)/i',
                                    '/diagnosed as[:\s]+([^\.\n]+)/i',
                                    '/ailment[:\s]+([^\.\n]+)/i',
                                    '/suffering from[:\s]+([^\.\n]+)/i',
                                    '/presenting with[:\s]+([^\.\n]+)/i',
                                    '/condition[:\s]+([^\.\n]+)/i',
                                    '/findings[:\s]+([^\.\n]+)/i',
                                    '/assessment[:\s]+([^\.\n]+)/i'
                                ];
                                
                                foreach ($patterns as $pattern) {
                                    if (preg_match($pattern, $doctor_note, $matches)) {
                                        $ailment_from_note = trim($matches[1]);
                                        break;
                                    }
                                }
                                
                                // If no pattern matched, take first significant line
                                if (empty($ailment_from_note)) {
                                    $lines = preg_split('/[\n\r]+/', $doctor_note);
                                    foreach ($lines as $line) {
                                        $line = trim($line);
                                        if (!empty($line) && strlen($line) > 10) {
                                            $ailment_from_note = $line;
                                            break;
                                        }
                                    }
                                }
                                
                                // Get doctor name from note if doc_id is available
                                if ($note_row->doc_id) {
                                    $doc_query = "SELECT CONCAT(doc_fname, ' ', doc_lname) AS doctor_name 
                                                  FROM his_docs WHERE doc_id = ?";
                                    $doc_stmt = $mysqli->prepare($doc_query);
                                    $doc_stmt->bind_param('i', $note_row->doc_id);
                                    $doc_stmt->execute();
                                    $doc_res = $doc_stmt->get_result();
                                    if ($doc_row = $doc_res->fetch_object()) {
                                        // Update doctor name if not already set from prescription
                                        if (empty($doctor_name) || $doctor_name == "Not specified") {
                                            $doctor_name = $doc_row->doctor_name;
                                            $doctor_id = $note_row->doc_id;
                                        }
                                    }
                                    $doc_stmt->close();
                                }
                            }
                            $note_stmt->close();
                        }
                    }

                    // 3. If we still don't have doctor info, try to get from medical record itself
                    if (empty($doctor_name) || $doctor_name == "Not specified") {
                        if (!empty($row->mdr_doc_id)) {
                            $doc_query = "SELECT CONCAT(doc_fname, ' ', doc_lname) AS doctor_name 
                                          FROM his_docs WHERE doc_id = ?";
                            $doc_stmt = $mysqli->prepare($doc_query);
                            $doc_stmt->bind_param('i', $row->mdr_doc_id);
                            $doc_stmt->execute();
                            $doc_res = $doc_stmt->get_result();
                            if ($doc_row = $doc_res->fetch_object()) {
                                $doctor_name = $doc_row->doctor_name;
                                $doctor_id = $row->mdr_doc_id;
                            }
                            $doc_stmt->close();
                        }
                    }
            ?>

                <div class="content-page">
                    <div class="content">

                        <!-- Start Content-->
                        <div class="container-fluid">
                            
                            <!-- start page title -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="page-title-box">
                                        <div class="page-title-right">
                                            <ol class="breadcrumb m-0">
                                                <li class="breadcrumb-item"><a href="his_admin_dashboard.php">Dashboard</a></li>
                                                <li class="breadcrumb-item"><a href="javascript: void(0);">Medical Records</a></li>
                                                <li class="breadcrumb-item active">View Medical Records</li>
                                            </ol>
                                        </div>
                                        <h4 class="page-title">Medical Record #<?php echo $row->mdr_number;?></h4>
                                        <p class="text-muted">Doctor: <strong><?php echo htmlspecialchars($doctor_name); ?></strong></p>
                                    </div>
                                </div>
                            </div>     
                            <!-- end page title --> 

                            <div class="row">
                                <div class="col-12">
                                    <div class="card-box">
                                        <div class="row">
                                            <div class="col-xl-5">

                                                <div class="tab-content pt-0">

                                                    <div class="tab-pane active show" id="product-1-item">
                                                        <img src="assets/images/medical_record.png" alt="" class="img-fluid mx-auto d-block rounded">
                                                    </div>
                            
                                                </div>
                                                
                                                <!-- Doctor Information Card -->
                                                <div class="card mt-3 border-primary">
                                                    <div class="card-header bg-primary text-white">
                                                        <h5 class="mb-0">
                                                            <i class="fas fa-user-md mr-2"></i>
                                                            Doctor Information
                                                        </h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="text-center mb-3">
                                                            <img src="assets/images/users/doctor.png" class="rounded-circle avatar-lg img-thumbnail" alt="doctor-image">
                                                        </div>
                                                        <table class="table table-sm table-borderless">
                                                            <tr>
                                                                <th class="text-muted" width="40%">Doctor Name:</th>
                                                                <td><strong><?php echo htmlspecialchars($doctor_name); ?></strong></td>
                                                            </tr>
                                                            <?php if($doctor_id): ?>
                                                            <tr>
                                                                <th class="text-muted">Doctor ID:</th>
                                                                <td><span class="badge badge-info">#<?php echo htmlspecialchars($doctor_id); ?></span></td>
                                                            </tr>
                                                            <?php endif; ?>
                                                            <?php if($prescription && isset($prescription->pres_date)): ?>
                                                            <tr>
                                                                <th class="text-muted">Prescription Date:</th>
                                                                <td><?php echo date("d/m/Y", strtotime($prescription->pres_date)); ?></td>
                                                            </tr>
                                                            <?php endif; ?>
                                                        </table>
                                                    </div>
                                                </div>
                                                
                                            </div> <!-- end col -->
                                            <div class="col-xl-7">
                                                <div class="pl-xl-3 mt-3 mt-xl-0">
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <h2>Patient: <?php echo htmlspecialchars($row->mdr_pat_name);?></h2>
                                                        <?php if($doctor_id): ?>
                                                        <span class="badge badge-primary">
                                                            <i class="fas fa-user-md mr-1"></i> Doctor ID: <?php echo htmlspecialchars($doctor_id); ?>
                                                        </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <hr>
                                                    
                                                    <!-- Ailment Information -->
                                                    <div class="card border-info mb-3">
                                                        <div class="card-header bg-info text-white">
                                                            <h5 class="mb-0">
                                                                <i class="fas fa-stethoscope mr-2"></i>
                                                                Ailment/Diagnosis Information
                                                            </h5>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <h6><strong>From Medical Record:</strong></h6>
                                                                    <p class="text-muted">
                                                                        <?php echo !empty($row->mdr_pat_ailment) ? htmlspecialchars($row->mdr_pat_ailment) : '<em>Not specified in medical record</em>'; ?>
                                                                    </p>
                                                                </div>
                                                                <?php if($ailment_from_note): ?>
                                                                <div class="col-md-6 border-left">
                                                                    <h6><strong>Extracted from Doctor's Note:</strong></h6>
                                                                    <p class="text-success">
                                                                        <i class="fas fa-check-circle text-success mr-1"></i>
                                                                        <?php echo htmlspecialchars($ailment_from_note); ?>
                                                                    </p>
                                                                    <small class="text-muted">Automatically extracted from latest doctor's note</small>
                                                                </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="card border-light mb-3">
                                                                <div class="card-body">
                                                                    <h6 class="text-muted">Age</h6>
                                                                    <h4 class="text-danger"><?php echo $row->mdr_pat_age;?> Years</h4>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="card border-light mb-3">
                                                                <div class="card-body">
                                                                    <h6 class="text-muted">Patient Number</h6>
                                                                    <h4 class="text-danger"><?php echo $row->mdr_pat_number;?></h4>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="card border-light mb-3">
                                                        <div class="card-body">
                                                            <h6 class="text-muted">Date Recorded</h6>
                                                            <h5 class="text-dark"><?php echo date("d/m/Y - h:i:s", strtotime($mysqlDateTime));?></h5>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Prescription Information -->
                                                    <div class="card border-warning">
                                                        <div class="card-header bg-warning text-dark">
                                                            <h4 class="mb-0">
                                                                <i class="fas fa-prescription mr-2"></i>
                                                                Prescription Information
                                                            </h4>
                                                        </div>
                                                        <div class="card-body">
                                                            <?php if(!empty($row->mdr_pat_prescr)): ?>
                                                            <div class="mb-4">
                                                                <h6><strong>Stored in Medical Record:</strong></h6>
                                                                <div class="p-3 bg-light border rounded">
                                                                    <?php echo nl2br(htmlspecialchars($row->mdr_pat_prescr)); ?>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if($prescription && !empty($prescription->pres_ins)): ?>
                                                            <div class="mt-3">
                                                                <h6><strong>Latest Prescription Record:</strong></h6>
                                                                <?php if(isset($prescription->pres_number)): ?>
                                                                <p class="text-muted">
                                                                    Prescription #: <span class="badge badge-info"><?php echo htmlspecialchars($prescription->pres_number); ?></span>
                                                                </p>
                                                                <?php endif; ?>
                                                                
                                                                <div class="p-3 bg-white border rounded prescription-box">
                                                                    <?php echo nl2br(htmlspecialchars($prescription->pres_ins)); ?>
                                                                </div>
                                                                
                                                                <div class="mt-3 pt-3 border-top">
                                                                    <?php if(isset($prescription->pres_date)): ?>
                                                                    <small class="text-muted">
                                                                        <i class="fas fa-calendar-alt mr-1"></i>
                                                                        Prescribed on: <?php echo date("d/m/Y H:i", strtotime($prescription->pres_date)); ?>
                                                                    </small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>
                                                            
                                                            <!-- Doctor Prescriber Info -->
                                                            <?php if($doctor_name && $doctor_name != "Not specified"): ?>
                                                            <div class="alert alert-light mt-3 border">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="flex-shrink-0">
                                                                        <i class="fas fa-user-md fa-2x text-primary"></i>
                                                                    </div>
                                                                    <div class="flex-grow-1 ms-3">
                                                                        <h6 class="mb-1">Prescribed by:</h6>
                                                                        <h5 class="mb-0"><?php echo htmlspecialchars($doctor_name); ?></h5>
                                                                        <?php if($doctor_id): ?>
                                                                        <small class="text-muted">Doctor ID: <?php echo htmlspecialchars($doctor_id); ?></small>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if(empty($row->mdr_pat_prescr) && empty($prescription)): ?>
                                                            <div class="alert alert-warning">
                                                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                                                No prescription information available for this medical record.
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Doctor's Note (if available) -->
                                                    <?php if($doctor_note): ?>
                                                    <div class="card border-success mt-3">
                                                        <div class="card-header bg-success text-white">
                                                            <h5 class="mb-0">
                                                                <i class="fas fa-file-medical-alt mr-2"></i>
                                                                Latest Doctor's Note
                                                            </h5>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="p-3 bg-light border rounded" style="max-height: 200px; overflow-y: auto;">
                                                                <?php echo nl2br(htmlspecialchars($doctor_note)); ?>
                                                            </div>
                                                            <small class="text-muted mt-2 d-block">
                                                                <i class="fas fa-info-circle mr-1"></i>
                                                                This note was used to extract ailment information above.
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                    
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
            <?php }?>

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
        
        <style>
        .prescription-box {
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            line-height: 1.6;
            background-color: #f8f9fa;
        }
        
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1rem;
        }
        
        .card-header {
            font-weight: 600;
        }
        
        .border-left {
            border-left: 1px solid #dee2e6 !important;
        }
        
        @media (max-width: 768px) {
            .border-left {
                border-left: none !important;
                border-top: 1px solid #dee2e6 !important;
                padding-top: 1rem;
                margin-top: 1rem;
            }
        }
        
        .badge {
            font-size: 0.85em;
            padding: 0.35em 0.65em;
        }
        
        h2, h3, h4, h5, h6 {
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        hr {
            margin: 1rem 0;
        }
        </style>
        
    </body>

</html>