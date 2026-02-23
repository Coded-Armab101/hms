<?php
session_start();
include('assets/inc/config.php');

// 1) Ensure the student is logged in
if (! isset($_SESSION['student_id'])) {
  die("Must be logged in");
}
$student_id = (int) $_SESSION['student_id'];

// 2) Pull this student’s pat_number
$stmt = $mysqli->prepare("
  SELECT pat_number
    FROM his_patients
   WHERE student_id = ?
   LIMIT 1
");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$stmt->bind_result($pat_number);
if (! $stmt->fetch()) {
  die("No patient record found for this student");
}
$stmt->close();

// 3) If the form was submitted...
if (isset($_POST['add_patient'])) {
    // Collect all other form values
    $pat_fname          = $_POST['pat_fname'];
    $pat_lname          = $_POST['pat_lname'];
    $pat_phone          = $_POST['pat_phone'];
    $pat_addr           = $_POST['pat_addr'];
    $pat_dob            = $_POST['pat_dob'];
    $pat_date_joined    = $_POST['pat_date_joined'];
    $pat_title          = $_POST['pat_title'];
    $pat_department     = $_POST['pat_department'];
    $pat_state          = $_POST['pat_state'];
    $pat_tribe          = $_POST['pat_tribe'];
    $pat_occupation     = $_POST['pat_occupation'];
    $pat_religion       = $_POST['pat_religion'];
    $marital_status     = $_POST['marital_status'];
    $pat_nationality    = $_POST['pat_nationality'];
    $pat_nok            = $_POST['pat_nok'];
    $pat_nok_address    = $_POST['pat_nok_address'];
    $pat_nok_phone      = $_POST['pat_nok_phone'];
    $pat_sex            = $_POST['pat_sex'];
    $pat_jamb_regno     = $_POST['pat_jamb_regno'];
    $pat_hostel_address = $_POST['pat_hostel_address'];
    $pat_faculty        = $_POST['pat_faculty'];
    $pat_relation_nok   = $_POST['pat_relation_nok'];
   
    //$pat_type           = $_POST['pat_type'];

    // Calculate age
    $birthdate = new DateTime($pat_dob);
    $today     = new DateTime("today");
    $pat_age   = $today->diff($birthdate)->y;

    // 4) Check if this pat_number already exists
    $chk = $mysqli->prepare("SELECT pat_id FROM his_patients WHERE pat_number = ?");
    $chk->bind_param('s', $pat_number);
    $chk->execute();
    $chk->store_result();

    if ($chk->num_rows > 0) {
        // UPDATE
        $sql = "
          UPDATE his_patients SET
            pat_fname          = ?,
            pat_lname          = ?,
            pat_age            = ?,
            pat_dob            = ?,
            pat_phone          = ?,
            pat_occupation     = ?,
            pat_addr           = ?,
            pat_date_joined    = ?,
            pat_state          = ?,
            pat_tribe          = ?,
            pat_religion       = ?,
            marital_status     = ?,
            pat_nationality    = ?,
            pat_nok            = ?,
            pat_nok_address    = ?,
            pat_nok_phone      = ?,
            pat_title          = ?,
            pat_department     = ?,
            pat_sex            = ?,
            pat_jamb_regno     = ?,
            pat_hostel_address = ?,
            pat_faculty        = ?,
            pat_relation_nok   = ?
          WHERE pat_number = ?
        ";
        $stmt = $mysqli->prepare($sql);
        $types = str_repeat('s', 2)  // fname, lname
               . 'i'                 // age
               . str_repeat('s', 20) // the remaining 20 text fields
               . 's';                // final WHERE param
        $stmt->bind_param(
          $types,
          $pat_fname, $pat_lname, $pat_age, $pat_dob, $pat_phone,
          $pat_occupation, $pat_addr, $pat_date_joined, $pat_state, $pat_tribe,
          $pat_religion, $marital_status, $pat_nationality, $pat_nok, $pat_nok_address,
          $pat_nok_phone, $pat_title,$pat_department, $pat_sex, $pat_jamb_regno,
          $pat_hostel_address, $pat_faculty, $pat_relation_nok,
          $pat_number
        );
    } else {
        // INSERT
        $sql = "
          INSERT INTO his_patients (
            pat_fname, pat_lname, pat_age, pat_dob, pat_phone,
            pat_occupation, pat_addr, pat_date_joined, pat_state, pat_tribe,
            pat_religion, marital_status, pat_nationality, pat_nok, pat_nok_address,
            pat_nok_phone, pat_title,pat_department, pat_sex, pat_jamb_regno,
            pat_hostel_address, pat_faculty, pat_relation_nok, pat_number,marital_status
          ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ";
        $stmt = $mysqli->prepare($sql);
        $types = str_repeat('s', 2)  // fname, lname
               . 'i'                 // age
               . str_repeat('s', 21); // the remaining 21 text fields including pat_number
        $stmt->bind_param(
          $types,
          $pat_fname, $pat_lname, $pat_age, $pat_dob, $pat_phone,
          $pat_occupation, $pat_addr, $pat_date_joined, $pat_state, $pat_tribe,
          $pat_religion, $marital_status, $pat_nationality, $pat_nok, $pat_nok_address,
          $pat_nok_phone, $pat_title, $pat_department, $pat_sex, $pat_jamb_regno,
          $pat_hostel_address, $pat_faculty, $pat_relation_nok,
          $pat_number
        );
    }

    // 5) Execute & feedback
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $success = "Patient record " . ($chk->num_rows > 0 ? 'updated' : 'added') . " successfully.";
    } else {
        $err = "No changes made or an error occurred.";
    }

    $stmt->close();
    $chk->close();
}
?>

<!--End Server Side-->
<!--End Patient Registration-->
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
                        <!-- Form row -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h4 class="header-title">Fill all fields</h4>
                                        <!--Add Patient Form-->
                                        <form method="post">
                                            <div class="form-row">
                                                <div class="form-group col-md-2">
                                                    <label for="inputState" class="col-form-label">Title</label>
                                                    <select id="inputState" required="required" name="pat_title" class="form-control">
                                                        <option>Choose</option>
                                                        <option>Mr</option>
                                                        <option>Miss</option>
                                                        <option>Mrs</option>
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-5">
                                                    <label for="inputEmail4" class="col-form-label">Last Name</label>
                                                    <input type="text" required="required" name="pat_lname" class="form-control" id="inputEmail4" placeholder="Patient's Last Name">
                                                </div>
                                                <div class="form-group col-md-5">
                                                    <label for="inputPassword4" class="col-form-label">Other Names</label>
                                                    <input required="required" type="text" name="pat_fname" class="form-control"  id="inputPassword4" placeholder="Patient`s Other Names">
                                                </div>
                                            </div>

                                            <div class="form-row">
                                                
                                                <div class="form-group col-md-4">
                                                    <label for="inputEmail4" class="col-form-label">Date Of Birth</label>
                                                    <input type="date" required="required" name="pat_dob" class="form-control datepicker" id="inputEmail4">
                                                </div>

                                                <div class="form-group col-md-2">
                                                    <label for="inputState" class="col-form-label">Sex</label>
                                                    <select id="inputState" required="required" name="pat_sex" class="form-control">
                                                        <option>Choose</option>
                                                        <option>Male</option>
                                                        <option>Female</option>
                                                   
                                                    </select>

                                                    
                                                </div>
                                                <div class= "form-group col-md-2">
                                                    <label for= "inputState" class= "col-form-label">Marital Status</label>    
                                                    <select id="inputState" required="required" name="marital_status" class="form-control">
                                                        <option>Choose</option>
                                                        <option>Married</option>
                                                        <option>Single</option>
                                                        <option>Divorced</option>
        
                                                    </select>

                                                </div>
                                               
                                                <!--<div class="form-group col-md-6">
                                                    <label for="inputPassword4" class="col-form-label">Matric Number</label>
                                                    <input required="required" type="text" name="pat_file_number" class="form-control"  id="inputPassword4" placeholder="Matric Number">
                                                </div>-->
                                            </div>

                                            <div class="form-group">
                                                <label for="inputAddress" class="col-form-label">Address</label>
                                                <input required="required" type="text" class="form-control" name="pat_addr" id="inputAddress" placeholder="Patient's Addresss">
                                            </div>

                                            <div class="form-group">
                                                <label for="inputAddress" class="col-form-label">Hostel Address</label>
                                                <input required="required" type="text" class="form-control" name="pat_hostel_address" id="inputAddress" placeholder="Patient's Hostel Addresss">
                                            </div>

                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label for="inputCity" class="col-form-label">Mobile Number</label>
                                                    <input required="required" type="text" name="pat_phone" class="form-control" id="inputCity" placeholder="Mobile Number">
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="inputCity" class="col-form-label">Jamb Registration Number</label>
                                                    <input required="required" type="text" name="pat_jamb_regno" class="form-control" id="inputCity" placeholder="Jamb Registration Number">
                                                </div>
                                                <!--<div class="form-group col-md-4">
                                                    <label for="inputCity" class="col-form-label">Patient Ailment</label>
                                                    <input required="required" type="text" name="pat_ailment" class="form-control" id="inputCity">
                                                </div>-->
                                                <!--<div class="form-group col-md-4">
                                                    <label for="inputState" class="col-form-label">Patient's Type</label>
                                                    <select id="inputState" required="required" name="pat_type" class="form-control">
                                                        <option>Choose</option>
                                                        <option>Student </option>
                                                        <option>Staff </option>
                                                        
                                                        <option>Public Patient</option>
                                                    </select>
                                                </div>-->
                                                <!--<div class="form-group col-md-2" style="display:none">
                                                    <label for="inputZip" class="col-form-label">Patient File Number</label>
                                                    <input type="text" name="pat_file_number" class="form-control" id="inputZip">
                                                </div>-->
                                            </div>
                                            <div class="form-row">
                                                <!--<div class="form-group col-md-6">
                                                    <label for="inputEmail4" class="col-form-label">Address</label>
                                                    <input type="text" required="required" name="pat_address" class="form-control" id="inputEmail4" placeholder="Patient's First Name">
                                                </div>-->
                                                <div class="form-group col-md-6">
                                                    <label for="inputPassword4" class="col-form-label">Date of Registration</label>
                                                    <input required="required" type="date" name="pat_date_joined" class="form-control"  id="inputPassword4" placeholder="Date of registration">
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="inputEmail4" class="col-form-label">Faculty</label>
                                                    <input type="text" required="required" name="pat_faculty" class="form-control" id="inputEmail4" placeholder="Faculty">
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label for="inputEmail4" class="col-form-label">Department</label>
                                                    <input type="text" required="required" name="pat_department" class="form-control" id="inputEmail4" placeholder="Department">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label for="inputPassword4" class="col-form-label">State</label>
                                                    <input required="required" type="text" name="pat_state" class="form-control"  id="inputPassword4" placeholder="Patient`s State">
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label for="inputEmail4" class="col-form-label">Tribe</label>
                                                    <input type="text" required="required" name="pat_tribe" class="form-control" id="inputEmail4" placeholder="Patient's Tribe">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label for="inputPassword4" class="col-form-label">Occupation</label>
                                                    <input required="required" type="text" name="pat_occupation" class="form-control"  id="inputPassword4" placeholder="Patient`s Occupation">
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label for="inputEmail4" class="col-form-label">Religion</label>
                                                    <input type="text" required="required" name="pat_religion" class="form-control" id="inputEmail4" placeholder="Patient's Religion">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label for="inputPassword4" class="col-form-label">Nationality</label>
                                                    <input required="required" type="text" name="pat_nationality" class="form-control"  id="inputPassword4" placeholder="Patient`s Nationality">
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label for="inputEmail4" class="col-form-label">Next of kin</label>
                                                    <input type="text" required="required" name="pat_nok" class="form-control" id="inputEmail4" placeholder="Patient's Next of Kin">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label for="inputPassword4" class="col-form-label">Next of Kin Address</label>
                                                    <input required="required" type="text" name="pat_nok_address" class="form-control"  id="inputPassword4" placeholder="Patient Next of kin's Address">
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label for="inputEmail4" class="col-form-label">Next of Kin Phone</label>
                                                    <input type="text" required="required" name="pat_nok_phone" class="form-control" id="inputEmail4" placeholder="Patient Next of Kin's phone">
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label for="inputEmail4" class="col-form-label">Next of Kin Relationship</label>
                                                    <input type="text" required="required" name="pat_relation_nok" class="form-control" id="inputEmail4" placeholder="Next of Kin Relationship">
                                                </div>
                                            </div>

                                            <button type="submit" name="add_patient" class="ladda-button btn btn-primary" data-style="expand-right">Submit</button>

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
        
    </body>

</html>