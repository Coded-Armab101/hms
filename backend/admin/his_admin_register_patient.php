<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$aid = $_SESSION['ad_id'];

// Get registration fee from settings or set default
$registration_fee = 500; // Default registration fee

// Check and fix billing table structure
$check_billing_table = $mysqli->query("SHOW TABLES LIKE 'his_patient_billing'");
if ($check_billing_table->num_rows == 0) {
    // Create billing table with proper structure - NO bill_ref column
    $create_billing_table = "CREATE TABLE his_patient_billing (
        bill_id INT AUTO_INCREMENT PRIMARY KEY,
        pat_id INT NOT NULL,
        pat_number VARCHAR(50) NOT NULL,
        bill_type VARCHAR(50) DEFAULT 'Registration',
        description VARCHAR(255) DEFAULT 'Registration Fee',
        amount DECIMAL(10,2) NOT NULL,
        discount DECIMAL(10,2) DEFAULT 0,
        final_amount DECIMAL(10,2) NOT NULL,
        payment_status ENUM('pending', 'partial', 'paid') DEFAULT 'pending',
        payment_method VARCHAR(50),
        transaction_id VARCHAR(100),
        bill_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        paid_date DATETIME,
        created_by INT,
        INDEX idx_pat_number (pat_number),
        INDEX idx_payment_status (payment_status)
    )";
    $mysqli->query($create_billing_table);
} else {
    // Check if bill_ref column exists and remove it if it does
    $check_bill_ref = $mysqli->query("SHOW COLUMNS FROM his_patient_billing LIKE 'bill_ref'");
    if ($check_bill_ref->num_rows > 0) {
        // Drop the bill_ref column or modify it to accept NULL
        $mysqli->query("ALTER TABLE his_patient_billing DROP COLUMN bill_ref");
    }
    
    // Also check for any other UNIQUE constraints that might cause issues
    // Generate a unique bill reference for each transaction
    $check_bill_ref_unique = $mysqli->query("SHOW INDEX FROM his_patient_billing WHERE Column_name = 'bill_ref'");
    if ($check_bill_ref_unique->num_rows > 0) {
        $mysqli->query("DROP INDEX bill_ref ON his_patient_billing");
    }
}

// Check if pat_sex column exists in his_patients table, add it if not
$check_pat_sex = $mysqli->query("SHOW COLUMNS FROM his_patients LIKE 'pat_sex'");
if ($check_pat_sex->num_rows == 0) {
    // Add missing columns
    $add_columns = "ALTER TABLE his_patients 
                    ADD COLUMN pat_sex VARCHAR(10) NULL,
                    ADD COLUMN pat_jamb_regno VARCHAR(50) NULL,
                    ADD COLUMN pat_hostel_address VARCHAR(255) NULL,
                    ADD COLUMN pat_faculty VARCHAR(255) NULL,
                    ADD COLUMN pat_relation_nok VARCHAR(100) NULL";
    $mysqli->query($add_columns);
}

// Function to generate unique bill reference
function generateBillReference($pat_number) {
    return 'BILL-' . $pat_number . '-' . date('Ymd') . '-' . uniqid();
}

if(isset($_POST['add_patient']))
{
    // Validate required fields
    $required_fields = ['pat_fname', 'pat_lname', 'pat_file_number', 'pat_dob', 'pat_sex', 'pat_phone'];
    $missing_fields = [];
    foreach($required_fields as $field) {
        if(empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if(!empty($missing_fields)) {
        $err = "Please fill all required fields: " . implode(', ', $missing_fields);
    } else {
        // Sanitize and get form data
        $pat_fname = trim($_POST['pat_fname']);
        $pat_lname = trim($_POST['pat_lname']);
        $pat_number = trim($_POST['pat_file_number']);
        $pat_phone = trim($_POST['pat_phone']);
        $pat_type = $_POST['pat_type'] ?? 'Student (100%)';
        $pat_addr = trim($_POST['pat_addr'] ?? '');
        $pat_dob = $_POST['pat_dob'];
        $pat_date_joined = $_POST['pat_date_joined'] ?? date('Y-m-d');
        $pat_title = $_POST['pat_title'] ?? '';
        $pat_department = trim($_POST['pat_department'] ?? '');
        $pat_state = trim($_POST['pat_state'] ?? '');
        $pat_tribe = trim($_POST['pat_tribe'] ?? '');
        $pat_occupation = trim($_POST['pat_occupation'] ?? '');
        $pat_religion = trim($_POST['pat_religion'] ?? '');
        $pat_nationality = trim($_POST['pat_nationality'] ?? '');
        $pat_nok = trim($_POST['pat_nok'] ?? '');
        $pat_nok_address = trim($_POST['pat_nok_address'] ?? '');
        $pat_nok_phone = trim($_POST['pat_nok_phone'] ?? '');
        $pat_sex = $_POST['pat_sex'] ?? '';
        $pat_jamb_regno = trim($_POST['pat_jamb_regno'] ?? '');
        $pat_hostel_address = trim($_POST['pat_hostel_address'] ?? '');
        $pat_faculty = trim($_POST['pat_faculty'] ?? '');
        $pat_relation_nok = trim($_POST['pat_relation_nok'] ?? '');
        
        // Payment fields
        $payment_status = $_POST['payment_status'] ?? 'pending';
        $payment_method = $_POST['payment_method'] ?? '';
        $transaction_id = trim($_POST['transaction_id'] ?? '');
        $discount = floatval($_POST['discount'] ?? 0);
        
        // Initialize discharge status
        $pat_discharge_status = 'Active';
        
        // Calculate age
        try {
            $birthdate = new DateTime($pat_dob);
            $today = new DateTime("today");
            $pat_age = $today->diff($birthdate)->y;
        } catch (Exception $e) {
            $pat_age = 0;
        }
        
        $pat_ailment = ''; // Initialize empty for now
        
        // Set patient type-specific fees
        switch($pat_type) {
            case 'Student (100%)':
                $registration_fee = 0; // Fully covered
                break;
            case 'NHIA Staff (90%)':
                $registration_fee = 50; // 10% of 500
                break;
            case 'NON-NHIA Staff (0%)':
                $registration_fee = 500; // Full fee
                break;
            case 'Casual Staff (100%)':
                $registration_fee = 0; // Fully covered
                break;
            case 'Public Patient (0%)':
                $registration_fee = 500; // Full fee
                break;
            default:
                $registration_fee = 500;
        }
        
        // Calculate final amount after discount
        $final_amount = $registration_fee - $discount;
        if ($final_amount < 0) $final_amount = 0;
        
        // Start transaction
        $mysqli->begin_transaction();
        
        try {
            // First check if patient number already exists
            $check_query = "SELECT pat_id FROM his_patients WHERE pat_number = ?";
            $check_stmt = $mysqli->prepare($check_query);
            $check_stmt->bind_param('s', $pat_number);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                throw new Exception("Patient number already exists. Please use a different number.");
            }
            $check_stmt->close();
            
            // Insert patient record
            $query="INSERT INTO his_patients (
                pat_fname, pat_ailment, pat_lname, pat_age, pat_dob, pat_number, 
                pat_phone, pat_type, pat_addr, pat_date_joined, pat_discharge_status, 
                pat_file_number, pat_state, pat_tribe, pat_occupation, pat_religion, 
                pat_nationality, pat_nok, pat_nok_address, pat_nok_phone, pat_title, 
                pat_department, pat_sex, pat_jamb_regno, pat_hostel_address, 
                pat_faculty, pat_relation_nok
            ) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('sssssssssssssssssssssssssss', 
                $pat_fname, $pat_ailment, $pat_lname, $pat_age, $pat_dob, $pat_number, 
                $pat_phone, $pat_type, $pat_addr, $pat_date_joined, $pat_discharge_status, 
                $pat_number, $pat_state, $pat_tribe, $pat_occupation, $pat_religion, 
                $pat_nationality, $pat_nok, $pat_nok_address, $pat_nok_phone, $pat_title, 
                $pat_department, $pat_sex, $pat_jamb_regno, $pat_hostel_address, 
                $pat_faculty, $pat_relation_nok
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert patient: " . $stmt->error);
            }
            
            $pat_id = $stmt->insert_id;
            $stmt->close();
            
            // Generate a unique bill reference if needed
            $bill_ref = generateBillReference($pat_number);
            
            // Insert billing record - WITHOUT bill_ref column
            $billing_query = "INSERT INTO his_patient_billing (
                pat_id, pat_number, bill_type, description, amount, discount, 
                final_amount, payment_status, payment_method, transaction_id, 
                created_by, paid_date
            ) VALUES (?, ?, 'Registration', 'Registration Fee', ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $billing_stmt = $mysqli->prepare($billing_query);
            $created_by = $_SESSION['ad_id'] ?? 0;
            
            // Set paid_date if payment is completed
            $paid_date = null;
            if ($payment_status == 'paid') {
                $paid_date = date('Y-m-d H:i:s');
            }
            
            $billing_stmt->bind_param('isddssssis', 
                $pat_id, $pat_number, $registration_fee, $discount, $final_amount, 
                $payment_status, $payment_method, $transaction_id, $created_by, 
                $paid_date
            );
            
            if (!$billing_stmt->execute()) {
                throw new Exception("Failed to create billing record: " . $billing_stmt->error);
            }
            
            $billing_stmt->close();
            
            // Commit transaction
            $mysqli->commit();
            
            // Store success message in session for display
            $_SESSION['success'] = "Patient registered successfully! Payment Status: " . ucfirst($payment_status);
            $_SESSION['patient_number'] = $pat_number;
            
            // Redirect to patient view
            header("Location: his_admin_view_single_patient.php?pat_id=$pat_id&pat_number=" . urlencode($pat_number));
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $mysqli->rollback();
            $err = "Error: " . $e->getMessage();
        }
    }
}
?>
<!--Rest of your HTML remains exactly the same until the script section-->

<!DOCTYPE html>
<html lang="en">
    
    <!--Head-->
    <?php include('assets/inc/head.php');?>
    <body>

        <!-- Begin page -->
        <div id="wrapper">

            <!-- Topbar Start -->
            <?php include("assets/inc/nav.php");?>
            <!-- end Topbar -->

            <!-- ========== Left Sidebar Start ========== -->
            <?php include("assets/inc/sidebar.php");?>
            <!-- Left Sidebar End -->

            <!-- ============================================================== -->
            <!-- Start Page Content here -->
            <!-- ============================================================== -->

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
                                            <li class="breadcrumb-item"><a href="javascript: void(0);">Patients</a></li>
                                            <li class="breadcrumb-item active">Add Patient</li>
                                        </ol>
                                    </div>
                                    <h4 class="page-title">Add Patient Details</h4>
                                </div>
                            </div>
                        </div>     
                        <!-- end page title --> 
                        
                        <!-- Display messages -->
                        <?php if(isset($err)): ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="mdi mdi-alert-circle"></i> <?php echo htmlspecialchars($err); ?>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(isset($_SESSION['success'])): ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="mdi mdi-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); ?>
                                    <?php if(isset($_SESSION['patient_number'])): ?>
                                    <br>
                                    <strong>Patient Number:</strong> <?php echo htmlspecialchars($_SESSION['patient_number']); ?>
                                    <br>
                                    <a href="his_admin_print_receipt.php?pat_number=<?php echo urlencode($_SESSION['patient_number']); ?>" class="btn btn-sm btn-info mt-2">
                                        <i class="fas fa-print"></i> Print Receipt
                                    </a>
                                    <?php endif; ?>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php 
                        unset($_SESSION['success']);
                        unset($_SESSION['patient_number']);
                        endif; 
                        ?>
                        
                        <!-- Form row -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="header-title">Fill all fields</h4>
                                        <!--Add Patient Form-->
                                        <form method="post" id="patientForm">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <!-- Personal Information -->
                                                    <div class="card mb-3">
                                                        <div class="card-header bg-primary text-white">
                                                            <h5 class="mb-0">Personal Information</h5>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="form-row">
                                                                <div class="form-group col-md-2">
                                                                    <label for="pat_title" class="col-form-label">Title <span class="text-danger">*</span></label>
                                                                    <select id="pat_title" required="required" name="pat_title" class="form-control">
                                                                        <option value="">Choose</option>
                                                                        <option value="Mr">Mr</option>
                                                                        <option value="Miss">Miss</option>
                                                                        <option value="Mrs">Mrs</option>
                                                                        <option value="Dr">Dr</option>
                                                                        <option value="Prof">Prof</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group col-md-5">
                                                                    <label for="pat_lname" class="col-form-label">Last Name <span class="text-danger">*</span></label>
                                                                    <input type="text" required="required" name="pat_lname" class="form-control" id="pat_lname" placeholder="Patient's Last Name">
                                                                </div>
                                                                <div class="form-group col-md-5">
                                                                    <label for="pat_fname" class="col-form-label">Other Names <span class="text-danger">*</span></label>
                                                                    <input required="required" type="text" name="pat_fname" class="form-control" id="pat_fname" placeholder="Patient`s Other Names">
                                                                </div>
                                                            </div>

                                                            <div class="form-row">
                                                                <div class="form-group col-md-4">
                                                                    <label for="pat_dob" class="col-form-label">Date Of Birth <span class="text-danger">*</span></label>
                                                                    <input type="date" required="required" name="pat_dob" class="form-control" id="pat_dob" max="<?php echo date('Y-m-d'); ?>">
                                                                </div>

                                                                <div class="form-group col-md-2">
                                                                    <label for="pat_sex" class="col-form-label">Sex <span class="text-danger">*</span></label>
                                                                    <select id="pat_sex" required="required" name="pat_sex" class="form-control">
                                                                        <option value="">Choose</option>
                                                                        <option value="Male">Male</option>
                                                                        <option value="Female">Female</option>
                                                                    </select>
                                                                </div>
                                                               
                                                                <div class="form-group col-md-6">
                                                                    <label for="pat_file_number" class="col-form-label">Matric Number <span class="text-danger">*</span></label>
                                                                    <input required="required" type="text" name="pat_file_number" class="form-control" id="pat_file_number" placeholder="Matric Number">
                                                                </div>
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="pat_addr" class="col-form-label">Address</label>
                                                                <input type="text" class="form-control" name="pat_addr" id="pat_addr" placeholder="Patient's Address">
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="pat_hostel_address" class="col-form-label">Hostel Address</label>
                                                                <input type="text" class="form-control" name="pat_hostel_address" id="pat_hostel_address" placeholder="Patient's Hostel Address">
                                                            </div>

                                                            <div class="form-row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="pat_phone" class="col-form-label">Mobile Number <span class="text-danger">*</span></label>
                                                                    <input required="required" type="text" name="pat_phone" class="form-control" id="pat_phone" placeholder="Mobile Number">
                                                                </div>

                                                                <div class="form-group col-md-6">
                                                                    <label for="pat_jamb_regno" class="col-form-label">Jamb Registration Number</label>
                                                                    <input type="text" name="pat_jamb_regno" class="form-control" id="pat_jamb_regno" placeholder="Jamb Registration Number">
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="form-row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="pat_date_joined" class="col-form-label">Date of Registration</label>
                                                                    <input type="date" name="pat_date_joined" class="form-control" id="pat_date_joined" value="<?php echo date('Y-m-d'); ?>">
                                                                </div>

                                                                <div class="form-group col-md-6">
                                                                    <label for="pat_faculty" class="col-form-label">Faculty</label>
                                                                    <input type="text" name="pat_faculty" class="form-control" id="pat_faculty" placeholder="Faculty">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Additional Information -->
                                                    <div class="card mb-3">
                                                        <div class="card-header bg-info text-white">
                                                            <h5 class="mb-0">Additional Information</h5>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="form-row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="pat_department" class="col-form-label">Department</label>
                                                                    <input type="text" name="pat_department" class="form-control" id="pat_department" placeholder="Department">
                                                                </div>
                                                                <div class="form-group col-md-6">
                                                                    <label for="pat_state" class="col-form-label">State</label>
                                                                    <input type="text" name="pat_state" class="form-control" id="pat_state" placeholder="Patient`s State">
                                                                </div>
                                                            </div>
                                                            <div class="form-row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="pat_tribe" class="col-form-label">Tribe</label>
                                                                    <input type="text" name="pat_tribe" class="form-control" id="pat_tribe" placeholder="Patient's Tribe">
                                                                </div>
                                                                <div class="form-group col-md-6">
                                                                    <label for="pat_occupation" class="col-form-label">Occupation</label>
                                                                    <input type="text" name="pat_occupation" class="form-control" id="pat_occupation" placeholder="Patient`s Occupation">
                                                                </div>
                                                            </div>
                                                            <div class="form-row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="pat_religion" class="col-form-label">Religion</label>
                                                                    <input type="text" name="pat_religion" class="form-control" id="pat_religion" placeholder="Patient's Religion">
                                                                </div>
                                                                <div class="form-group col-md-6">
                                                                    <label for="pat_nationality" class="col-form-label">Nationality</label>
                                                                    <input type="text" name="pat_nationality" class="form-control" id="pat_nationality" placeholder="Patient`s Nationality" value="Nigerian">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Next of Kin Information -->
                                                    <div class="card mb-3">
                                                        <div class="card-header bg-warning text-dark">
                                                            <h5 class="mb-0">Next of Kin Information</h5>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="form-row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="pat_nok" class="col-form-label">Next of kin</label>
                                                                    <input type="text" name="pat_nok" class="form-control" id="pat_nok" placeholder="Patient's Next of Kin">
                                                                </div>
                                                                <div class="form-group col-md-6">
                                                                    <label for="pat_nok_address" class="col-form-label">Next of Kin Address</label>
                                                                    <input type="text" name="pat_nok_address" class="form-control" id="pat_nok_address" placeholder="Patient Next of kin's Address">
                                                                </div>
                                                            </div>
                                                            <div class="form-row">
                                                                <div class="form-group col-md-6">
                                                                    <label for="pat_nok_phone" class="col-form-label">Next of Kin Phone</label>
                                                                    <input type="text" name="pat_nok_phone" class="form-control" id="pat_nok_phone" placeholder="Patient Next of Kin's phone">
                                                                </div>

                                                                <div class="form-group col-md-6">
                                                                    <label for="pat_relation_nok" class="col-form-label">Next of Kin Relationship</label>
                                                                    <input type="text" name="pat_relation_nok" class="form-control" id="pat_relation_nok" placeholder="Next of Kin Relationship">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-4">
                                                    <!-- Payment Information -->
                                                    <div class="card mb-3">
                                                        <div class="card-header bg-success text-white">
                                                            <h5 class="mb-0">Payment Information</h5>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="form-group">
                                                                <label for="pat_type" class="col-form-label">Patient Type <span class="text-danger">*</span></label>
                                                                <select id="pat_type" required="required" name="pat_type" class="form-control" onchange="updateFee()">
                                                                    <option value="Student (100%)">Student (100% Coverage)</option>
                                                                    <option value="NHIA Staff (90%)">NHIA Staff (90% Coverage)</option>
                                                                    <option value="NON-NHIA Staff (0%)">NON-NHIA Staff (0% Coverage)</option>
                                                                    <option value="Casual Staff (100%)">Casual Staff (100% Coverage)</option>
                                                                    <option value="Public Patient (0%)">Public Patient (0% Coverage)</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <label class="col-form-label">Registration Fee</label>
                                                                <div class="input-group">
                                                                    <div class="input-group-prepend">
                                                                        <span class="input-group-text">₦</span>
                                                                    </div>
                                                                    <input type="text" class="form-control" id="registration_fee" value="0.00" readonly>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <label for="discount" class="col-form-label">Discount (₦)</label>
                                                                <input type="number" name="discount" id="discount" class="form-control" min="0" step="0.01" value="0" onchange="updateFinalAmount()">
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <label class="col-form-label">Final Amount</label>
                                                                <div class="input-group">
                                                                    <div class="input-group-prepend">
                                                                        <span class="input-group-text">₦</span>
                                                                    </div>
                                                                    <input type="text" class="form-control" id="final_amount" value="0.00" readonly>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="form-group">
                                                                <label for="payment_status" class="col-form-label">Payment Status <span class="text-danger">*</span></label>
                                                                <select id="payment_status" name="payment_status" class="form-control" onchange="togglePaymentDetails()">
                                                                    <option value="pending">Pending</option>
                                                                    <option value="partial">Partial</option>
                                                                    <option value="paid">Paid</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div id="payment_details" style="display: none;">
                                                                <div class="form-group">
                                                                    <label for="payment_method" class="col-form-label">Payment Method</label>
                                                                    <select id="payment_method" name="payment_method" class="form-control">
                                                                        <option value="">Select Method</option>
                                                                        <option value="Cash">Cash</option>
                                                                        <option value="Card">Card</option>
                                                                        <option value="Bank Transfer">Bank Transfer</option>
                                                                        <option value="POS">POS</option>
                                                                        <option value="Mobile Money">Mobile Money</option>
                                                                    </select>
                                                                </div>
                                                                
                                                                <div class="form-group">
                                                                    <label for="transaction_id" class="col-form-label">Transaction ID</label>
                                                                    <input type="text" name="transaction_id" id="transaction_id" class="form-control" placeholder="Enter transaction reference">
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="alert alert-info mt-3">
                                                                <i class="mdi mdi-information"></i>
                                                                <strong>Note:</strong> Registration will be completed after payment confirmation.
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="text-center mt-3">
                                                <button type="submit" name="add_patient" class="btn btn-primary btn-lg">
                                                    <i class="fas fa-user-plus"></i> Register Patient
                                                </button>
                                                <button type="reset" class="btn btn-secondary btn-lg">
                                                    <i class="fas fa-redo"></i> Reset Form
                                                </button>
                                            </div>

                                        </form>
                                        <!--End Patient Form-->
                                    </div> <!-- end card-body -->
                                </div> <!-- end card-->
                            </div> <!-- end col -->
                        </div>
                        <!-- end row -->

                    </div> <!-- container -->

                </div> <!-- content -->

                <!-- Footer Start -->
                <?php include('assets/inc/footer.php');?>
                <!-- end Footer -->

            </div>

            <!-- ============================================================== -->
            <!-- End Page content -->
            <!-- ============================================================== -->

        </div>
        <!-- END wrapper -->

       
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
        
        <script>
        // Fee calculation based on patient type
        const feeStructure = {
            'Student (100%)': 0,
            'NHIA Staff (90%)': 50,
            'NON-NHIA Staff (0%)': 500,
            'Casual Staff (100%)': 0,
            'Public Patient (0%)': 500
        };
        
        function updateFee() {
            const patientType = document.getElementById('pat_type').value;
            const fee = feeStructure[patientType] || 500;
            document.getElementById('registration_fee').value = fee.toFixed(2);
            updateFinalAmount();
        }
        
        function updateFinalAmount() {
            const fee = parseFloat(document.getElementById('registration_fee').value) || 0;
            const discount = parseFloat(document.getElementById('discount').value) || 0;
            const finalAmount = Math.max(0, fee - discount);
            document.getElementById('final_amount').value = finalAmount.toFixed(2);
        }
        
        function togglePaymentDetails() {
            const paymentStatus = document.getElementById('payment_status').value;
            const paymentDetails = document.getElementById('payment_details');
            
            if (paymentStatus === 'paid' || paymentStatus === 'partial') {
                paymentDetails.style.display = 'block';
            } else {
                paymentDetails.style.display = 'none';
            }
        }
        
        // Form validation
        document.getElementById('patientForm').addEventListener('submit', function(e) {
            const patientType = document.getElementById('pat_type').value;
            const paymentStatus = document.getElementById('payment_status').value;
            
            if ((paymentStatus === 'paid' || paymentStatus === 'partial') && patientType !== 'Student (100%)' && patientType !== 'Casual Staff (100%)') {
                const paymentMethod = document.getElementById('payment_method').value;
                const transactionId = document.getElementById('transaction_id').value;
                
                if (!paymentMethod) {
                    e.preventDefault();
                    alert('Please select a payment method for paid/partial payment');
                    return false;
                }
                
                if (!transactionId) {
                    e.preventDefault();
                    alert('Please enter a transaction ID for paid/partial payment');
                    return false;
                }
            }
        });
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateFee();
            togglePaymentDetails();
            
            // Set default date
            const today = new Date().toISOString().split('T')[0];
            if (!document.getElementById('pat_date_joined').value) {
                document.getElementById('pat_date_joined').value = today;
            }
        });
        </script>
        
    </body>

</html>