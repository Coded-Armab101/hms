<!--Server side code to handle  Patient Registration-->
<?php
	session_start();
	include('assets/inc/config.php');
		if(isset($_POST['add_patient']))
		{
			$pat_fname=$_POST['pat_fname'];
			$pat_lname=$_POST['pat_lname'];
			$pat_number=$_POST['pat_file_number'];
            $pat_phone=$_POST['pat_phone'];
            $pat_type=$_POST['pat_type'];
            $pat_addr=$_POST['pat_addr'];
            $pat_dob = $_POST['pat_dob'];
            $pat_ailment = $_POST['pat_ailment'];
            $pat_date_joined = $_POST['pat_date_joined'];
            //$pat_discharge_status = $_POST['pat_discharge_status'];
            $pat_title = $_POST['pat_title'];
            $pat_department = $_POST['pat_department'];
            $pat_state = $_POST['pat_state'];
            $pat_tribe = $_POST['pat_tribe'];
            $pat_occupation = $_POST['pat_occupation'];
            $pat_religion = $_POST['pat_religion'];
            $pat_nationality = $_POST['pat_nationality'];
            $pat_nok = $_POST['pat_nok'];
            $pat_nok_address = $_POST['pat_nok_address'];
            $pat_nok_phone = $_POST['pat_nok_phone'];
            //$pat_file_number = $_POST['pat_file_number'];
            // Calculate age
            $pat_dob =$_POST['pat_dob'] ;
            $birthdate = new DateTime($pat_dob);
            $today = new DateTime("today");
            $pat_age = $today->diff($birthdate)->y;
            //sql to insert captured values
			$query="insert into his_patients (pat_fname, pat_ailment, pat_lname, pat_age, pat_dob, pat_number, pat_phone, pat_type, pat_addr, pat_date_joined, pat_discharge_status, pat_file_number, pat_state, pat_tribe, pat_occupation, pat_religion, pat_nationality, pat_nok, pat_nok_address, pat_nok_phone, pat_title, pat_department ) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
			$stmt = $mysqli->prepare($query);
			$rc=$stmt->bind_param('ssssssssssssssssssssss', $pat_fname, $pat_ailment, $pat_lname, $pat_age, $pat_dob, $pat_number, $pat_phone, $pat_type, $pat_addr, $pat_date_joined, $pat_discharge_status, $pat_file_number, $pat_state, $pat_tribe, $pat_occupation, $pat_religion, $pat_nationality, $pat_nok, $pat_nok_address, $pat_nok_phone, $pat_title, $pat_department);
			$stmt->execute();
			/*
			*Use Sweet Alerts Instead Of This Fucked Up Javascript Alerts
			*echo"<script>alert('Successfully Created Account Proceed To Log In ');</script>";
			*/ 
			//declare a varible which will be passed to alert function
			if($stmt)
			{
				$success = "Patient Details Added";
			}
			else {
				$err = "Please Try Again Or Try Later";
			}
            

			
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
                                                    <input type="text" required="required" name="pat_lname" class="form-control" id="inputEmail4" placeholder="Patient's First Name">
                                                </div>
                                                <div class="form-group col-md-5">
                                                    <label for="inputPassword4" class="col-form-label">Other Names</label>
                                                    <input required="required" type="text" name="pat_fname" class="form-control"  id="inputPassword4" placeholder="Patient`s Last Name">
                                                </div>
                                            </div>

                                            <div class="form-row">
                                                
                                                <div class="form-group col-md-6">
                                                    <label for="inputEmail4" class="col-form-label">Date Of Birth</label>
                                                    <input type="date" required="required" name="pat_dob" class="form-control datepicker" id="inputEmail4">
                                                </div>
                                               
                                                <div class="form-group col-md-6">
                                                    <label for="inputPassword4" class="col-form-label">Patient File Number</label>
                                                    <input required="required" type="text" name="pat_file_number" class="form-control"  id="inputPassword4" placeholder="Patient File Number">
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label for="inputAddress" class="col-form-label">Address</label>
                                                <input required="required" type="text" class="form-control" name="pat_addr" id="inputAddress" placeholder="Patient's Addresss">
                                            </div>

                                            <div class="form-row">
                                                <div class="form-group col-md-4">
                                                    <label for="inputCity" class="col-form-label">Mobile Number</label>
                                                    <input required="required" type="text" name="pat_phone" class="form-control" id="inputCity">
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label for="inputCity" class="col-form-label">Patient Ailment</label>
                                                    <input required="required" type="text" name="pat_ailment" class="form-control" id="inputCity">
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label for="inputState" class="col-form-label">Patient's Type</label>
                                                    <select id="inputState" required="required" name="pat_type" class="form-control">
                                                        <option>Choose</option>
                                                        <option>Student (100%)</option>
                                                        <option>NHIA Staff (90%)</option>
                                                        <option>NON-NHIA Staff (0%)</option>
                                                        <option>Casual Staff (100%)</option>
                                                        <option>Public Patient (0%)</option>
                                                    </select>
                                                </div>
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
                                                <div class="form-group col-md-12">
                                                    <label for="inputEmail4" class="col-form-label">Next of Kin Phone</label>
                                                    <input type="text" required="required" name="pat_nok_phone" class="form-control" id="inputEmail4" placeholder="Patient Next of Kin's phone">
                                                </div>
                                            </div>

                                            <button type="submit" name="add_patient" class="ladda-button btn btn-primary" data-style="expand-right">Add Patient</button>

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