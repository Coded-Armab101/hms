<!--Server side code to handle  Patient Registration-->
<?php
	session_start();
	include('assets/inc/config.php');
		if(isset($_POST['book_appointment']))
		{
			//$doc_id=$_POST['ns_login'];
            $pat_number = $_POST['pat_file_number'];
			$pat_id=$_GET['pat_id'];
			$app_date=$_POST['app_date'];
            $app_status=$_POST['app_status'];
            //sql to insert captured values
			$query="insert into appointments (doc_id, pat_id, app_date, app_status) values(?,?,?,?)";
			$stmt = $mysqli->prepare($query);
			$rc=$stmt->bind_param('siss', $doc_id, $pat_id, $app_date, $app_status);
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

            <?php 
                $ret="SELECT  * FROM his_patients WHERE pat_number = pat_file_number";
                $stmt= $mysqli->prepare($ret) ;
                // $stmt->bind_param('i',$pres_pat_number );
                $stmt->execute() ;//ok
                $res=$stmt->get_result();
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
                                            <li class="breadcrumb-item"><a href="javascript: void(0);">Patients</a></li>
                                            <li class="breadcrumb-item active">Add Patient</li>
                                        </ol>
                                    </div>
                                    <h4 class="page-title">Book Appointment</h4>
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
                                            <div class="form-row" >
                                               <!-- <div class="form-group col-md-2">
                                                    <label for="inputState" class="col-form-label">Select Doctor</label>
                                                    <select id="inputState" required="required" name="doc_file_number" class="form-control">
                                                        <option>Choose</option>
                                                        <?php
                                                           // $pres_pat_number =$_GET['pat_number'];
                                                            //$ret="SELECT  * FROM his_docs";
                                                            //$stmt= $mysqli->prepare($ret) ;
                                                            // $stmt->bind_param('i',$pres_pat_number );
                                                            //$stmt->execute() ;//ok
                                                            //$res=$stmt->get_result();
                                                            //$cnt=1;
                                                    
                                                            //while($row=$res->fetch_object())
                                                        //{
                                                            //$mysqlDateTime = $row->pres_date; //trim timestamp to date

                                                    ?>
                                                        <option><?php //echo $row->doc_id;?> </option>

                                                    <?php //};?>

                                                    </select>
                                                </div>-->
                                                <div class="form-group col-md-4">
                                                    <label for="inputEmail4" class="col-form-label">Patient File Number</label>
                                                    <input type="text" required="required"  name="pat_file_number" class="form-control" id="inputEmail4" placeholder="Patient's First Name">
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label for="inputPassword4" class="col-form-label">Date</label>
                                                    <input required="required" type="date" name="app_date" class="form-control"  id="inputPassword4" placeholder="Patient`s Last Name">
                                                </div>
                                           

                                            
                                                <div class="form-group col-md-4" >
                                                    <?php 
                                                        $status = 'Pending';  
                                                    ?>
                                                    <label for="inputZip" class="col-form-label">status</label>
                                                    <input type="text"   name="app_status" value="<?php echo $status;?>" class="form-control" id="inputZip">
                                                </div>
                                            </div>

                                            <button type="submit"  name="book_appointment" class="ladda-button btn btn-primary" data-style="expand-right">Appoint</button>

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