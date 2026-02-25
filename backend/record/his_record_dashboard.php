<?php
  session_start();
  include('assets/inc/config.php');
  include('assets/inc/checklogin.php');
  check_login();
  $aid=$_SESSION['ad_id'];
?>
<!DOCTYPE html>
<html lang="en">
    
    <!--Head Code-->
    <?php include("assets/inc/head.php");?>

    <body>

        <!-- Begin page -->
        <div id="wrapper">

            <!-- Topbar Start -->
            <?php include('assets/inc/nav.php');?>
            <!-- end Topbar -->

            <!-- ========== Left Sidebar Start ========== -->
            <?php include('assets/inc/sidebar.php');?>
            <!-- Left Sidebar End -->

            <!-- ============================================================== -->
            <!-- Start Page Content here -->
            <!-- ============================================================== -->

            <div class="content-page">
                <div class="content">

                    <!-- Start Content-->
                    <div class="container-fluid">
                        
                        <!-- start page title -->
                        <div class="row mb-3">
    <div class="col-12">
        <div class="page-title-box flex items-center justify-between p-2   rounded  border-bottom" style="border-bottom: 2px solid #f1f5f9 !important;">
            
            <div class="flex flex-col gap-1">
                
                <p class="text-black text-2xl font-medium">Welcome back, Record Admin! Here is what's happening today.</p>   
            </div>

            <div class="d-none d-md-block">
                <div>
                    <p class="text-muted mb-0 font-12">
                        <?php echo date('l, d M Y'); ?> • <span id="liveClock" class="text-primary font-weight-semibold">--:--:--</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function updateClock() {
        const now = new Date();
        const options = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
        document.getElementById('liveClock').innerText = now.toLocaleTimeString('en-US', options);
    }
    setInterval(updateClock, 1000);
    updateClock(); // Initial call
</script>
                        <!-- end page title --> 
                        

                        <div class="row">
                            <!--Start OutPatients-->
                           <div class="col-md-6 col-xl-4 lg:py-0 py-2">
    <div class=" border-none shadow-sm bg-sky-200 rounded-lg p-3">
        <div class="row flex items-center ">
            <div class="col-5">
                <div class="avatar-lg w-90">
                    <img src="https://crm-admin-dashboard-template.multipurposethemes.com/images/svg-icon/medical/icon-1.svg" class="img-fluid" alt="Outpatient Icon">
                </div>
            </div>
            <div class="col-7">
                <div class="text-right">
                    <?php
                        // Code for summing up number of out patients 
                        $result ="SELECT count(*) FROM his_patients WHERE pat_type = 'OutPatient' ";
                        $stmt = $mysqli->prepare($result);
                        $stmt->execute();
                        $stmt->bind_result($outpatient);
                        $stmt->fetch();
                        $stmt->close();
                    ?>
                    <h3 class="text-blue-600 font-weight-bold mt-1" style="color: #3b82f6; font-size: 2rem;">
                        <span data-plugin="counterup"><?php echo $outpatient;?></span>
                    </h3>
                    <p class="text-muted mb-1 text-truncate font-weight-medium">Out Patients</p>
                </div>
            </div>
        </div> </div> </div> 




                           <div class="col-md-6 col-xl-4   lg:py-0 py-2">
    <div class="widget-rounded-circle  bg-purple-100 border-none shadow-sm rounded-lg p-3">
        <div class="row align-items-center">
            <div class="col-5">
                <div class="avatar-lg w-90">
                    <img src="https://crm-admin-dashboard-template.multipurposethemes.com/images/svg-icon/medical/icon-3.svg" class="img-fluid" alt="Inpatient Icon">
                </div>
            </div>
            <div class="col-7">
                <div class="text-right">
                    <?php
                        //code for summing up number of in / admitted patients 
                        $result ="SELECT count(*) FROM his_patients WHERE pat_type = 'InPatient' ";
                        $stmt = $mysqli->prepare($result);
                        $stmt->execute();
                        $stmt->bind_result($inpatient);
                        $stmt->fetch();
                        $stmt->close();
                    ?>
                    <h3 class="font-weight-bold mt-1" style="color: #8b5cf6; font-size: 2rem;">
                        <span data-plugin="counterup"><?php echo $inpatient;?></span>
                    </h3>
                    <p class="text-muted mb-1 text-truncate font-weight-medium">In Patients</p>
                </div>
            </div>
        </div> </div> </div> 

                            <div class="col-md-6 col-xl-4    lg:py-0 py-2">
    <div class="widget-rounded-circle  bg-pink-100 border-none shadow-sm rounded-lg p-3">
        <div class="row align-items-center">
            <div class="col-5">
                <div class="avatar-lg w-90">
                    <img src="https://crm-admin-dashboard-template.multipurposethemes.com/images/svg-icon/medical/icon-2.svg" class="img-fluid" alt="Employees Icon">
                </div>
            </div>
            <div class="col-7">
                <div class="text-right">
                    <?php
                        //code for summing up number of employees in the certain Hospital 
                        $result ="SELECT count(*) FROM his_docs ";
                        $stmt = $mysqli->prepare($result);
                        $stmt->execute();
                        $stmt->bind_result($doc);
                        $stmt->fetch();
                        $stmt->close();
                    ?>
                    <h3 class="font-weight-bold mt-1" style="color: #f43f5e; font-size: 2rem;">
                        <span data-plugin="counterup"><?php echo $doc;?></span>
                    </h3>
                    <p class="text-muted mb-1 text-truncate font-weight-medium">Hospital Staff</p>
                </div>
            </div>
        </div> </div> </div>
                        
                        </div>

                        <div class="row py-6">

                        <!--Start Vendors-->
                           <div class="col-md-6 col-xl-4  lg:py-0 py-2">
    <div class="widget-rounded-circle  bg-green-100 border-none shadow-sm rounded-lg p-3">
        <div class="row align-items-center">
            <div class="col-5">
                <div class="avatar-lg w-90">
                    <img src="https://crm-admin-dashboard-template.multipurposethemes.com/images/svg-icon/medical/icon-4.svg" class="img-fluid" alt="Vendors Icon">
                </div>
            </div>
            <div class="col-7">
                <div class="text-right">
                    <?php
                        /*code for summing up number of vendors whom supply equipment, 
                         *pharms or any other equipment
                         */ 
                        $result ="SELECT count(*) FROM his_vendor ";
                        $stmt = $mysqli->prepare($result);
                        $stmt->execute();
                        $stmt->bind_result($vendor);
                        $stmt->fetch();
                        $stmt->close();
                    ?>
                    <h3 class="font-weight-bold mt-1" style="color: #059669; font-size: 2rem;">
                        <span data-plugin="counterup"><?php echo $vendor;?></span>
                    </h3>
                    <p class="text-muted mb-1 text-truncate font-weight-medium">Vendors</p>
                </div>
            </div>
        </div> </div> </div> 
                            <!--End Vendors-->  

                           <div class="col-md-6 col-xl-4  lg:py-0 py-2">
    <div class="widget-rounded-circle bg-amber-100 border-none shadow-sm rounded-lg p-3">
        <div class="row align-items-center">
            <div class="col-5">
                <div class="avatar-lg w-90">
                    <img src="https://crm-admin-dashboard-template.multipurposethemes.com/images/svg-icon/medical/icon-2.svg" class="img-fluid" alt="Assets Icon">
                </div>
            </div>
            <div class="col-7">
                <div class="text-right">
                    <?php
                        /* * code for summing up number of assets,
                         */ 
                        $result ="SELECT count(*) FROM his_equipments ";
                        $stmt = $mysqli->prepare($result);
                        $stmt->execute();
                        $stmt->bind_result($assets);
                        $stmt->fetch();
                        $stmt->close();
                    ?>
                    <h3 class="font-weight-bold mt-1" style="color: #d97706; font-size: 2rem;">
                        <span data-plugin="counterup"><?php echo $assets;?></span>
                    </h3>
                    <p class="text-muted mb-1 text-truncate font-weight-medium">Corp. Assets</p>
                </div>
            </div>
        </div> </div> </div>

                            <div class="col-md-6 col-xl-4  lg:py-0 py-2">
    <div class="widget-rounded-circle  bg-cyan-100 border-none shadow-sm rounded-lg p-3">
        <div class="row align-items-center">
            <div class="col-5">
                <div class="avatar-lg w-90">
                    <img src="https://crm-admin-dashboard-template.multipurposethemes.com/images/svg-icon/medical/icon-4.svg" class="img-fluid" alt="Pharmacy Icon">
                </div>
            </div>
            <div class="col-7">
                <div class="text-right">
                    <?php
                        /* * code for summing up number of pharmaceuticals,
                         */ 
                        $result ="SELECT count(*) FROM his_pharmaceuticals ";
                        $stmt = $mysqli->prepare($result);
                        $stmt->execute();
                        $stmt->bind_result($phar);
                        $stmt->fetch();
                        $stmt->close();
                    ?>
                    <h3 class="font-weight-bold mt-1" style="color: #0e7490; font-size: 2rem;">
                        <span data-plugin="counterup"><?php echo $phar;?></span>
                    </h3>
                    <p class="text-muted mb-1 text-truncate font-weight-medium">Pharmaceuticals</p>
                </div>
            </div>
        </div> </div> </div> 


                        </div>
                        

                        
                        <!--Recently Employed Employees-->
                       <div class="row">
    <div class="col-xl-12">
        <div class="card shadow-lg rounded-lg border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="  text-2xl font-bold">
                        <i class="mdi mdi-doctor mr-1 text-primary"></i> Hospital Medical Staff
                    </h4>
                    <span class="font-semibold  text-black px-2 py-1">Top 10 Random Rotation</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-nowrap table-centered m-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Staff Profile</th>
                                <th>Email Address</th>
                                <th>Department</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $ret="SELECT * FROM his_docs ORDER BY RAND() LIMIT 10 "; 
                                $stmt= $mysqli->prepare($ret) ;
                                $stmt->execute() ;
                                $res=$stmt->get_result();
                                while($row=$res->fetch_object()) {
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="position-relative">
                                            <img src="../doc/assets/images/users/<?php echo $row->doc_dpic;?>" alt="img" class="rounded-circle avatar-md img-thumbnail shadow-sm" />
                                            <span class="user-status online" style="height: 12px; width: 12px; background-color: #2ecc71; border: 2px solid #fff; border-radius: 50%; position: absolute; bottom: 2px; right: 2px;"></span>
                                        </div>
                                        <div class="ml-3">
                                            <h5 class="m-0 font-weight-semibold text-dark"><?php echo $row->doc_fname;?> <?php echo $row->doc_lname;?></h5>
                                            <small class="text-muted font-weight-medium">Employee ID: #<?php echo $row->doc_number;?></small>
                                        </div>
                                    </div>
                                </td>
                                
                                <td class="text-muted">
                                    <i class="mdi mdi-email-outline mr-1"></i><?php echo $row->doc_email;?>
                                </td>

                                <td>
                                    <span class="badge badge-soft-info p-2 px-3 rounded-pill font-12">
                                        <?php echo $row->doc_dept;?>
                                    </span>
                                </td>

                                <td class="text-center">
                                    <a href="his_admin_view_single_employee.php?doc_id=<?php echo $row->doc_id;?>&doc_number=<?php echo $row->doc_number;?>" 
                                       class="btn btn-sm btn-outline-primary rounded-lg  shadow-sm">
                                        <i class="mdi mdi-eye-circle "></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div> </div>
        </div> </div> </div>

                <!-- Footer Start -->
                <?php include('assets/inc/footer.php');?>
                <!-- end Footer -->

            </div>

            <!-- ============================================================== -->
            <!-- End Page content -->
            <!-- ============================================================== -->


        </div>
        <!-- END wrapper -->

        <!-- Right Sidebar -->
        <div class="right-bar">
            <div class="rightbar-title">
                <a href="javascript:void(0);" class="right-bar-toggle float-right">
                    <i class="dripicons-cross noti-icon"></i>
                </a>
                <h5 class="m-0 text-white">Settings</h5>
            </div>
            <div class="slimscroll-menu">
                <!-- User box -->
                <div class="user-box">
                    <div class="user-img">
                        <img src="assets/images/users/user-1.jpg" alt="user-img" title="Mat Helme" class="rounded-circle img-fluid">
                        <a href="javascript:void(0);" class="user-edit"><i class="mdi mdi-pencil"></i></a>
                    </div>
            
                    <h5><a href="javascript: void(0);">Geneva Kennedy</a> </h5>
                    <p class="text-muted mb-0"><small>Admin Head</small></p>
                </div>

                <!-- Settings -->
                <hr class="mt-0" />
                <h5 class="pl-3">Basic Settings</h5>
                <hr class="mb-0" />

                <div class="p-3">
                    <div class="checkbox checkbox-primary mb-2">
                        <input id="Rcheckbox1" type="checkbox" checked>
                        <label for="Rcheckbox1">
                            Notifications
                        </label>
                    </div>
                    <div class="checkbox checkbox-primary mb-2">
                        <input id="Rcheckbox2" type="checkbox" checked>
                        <label for="Rcheckbox2">
                            API Access
                        </label>
                    </div>
                    <div class="checkbox checkbox-primary mb-2">
                        <input id="Rcheckbox3" type="checkbox">
                        <label for="Rcheckbox3">
                            Auto Updates
                        </label>
                    </div>
                    <div class="checkbox checkbox-primary mb-2">
                        <input id="Rcheckbox4" type="checkbox" checked>
                        <label for="Rcheckbox4">
                            Online Status
                        </label>
                    </div>
                    <div class="checkbox checkbox-primary mb-0">
                        <input id="Rcheckbox5" type="checkbox" checked>
                        <label for="Rcheckbox5">
                            Auto Payout
                        </label>
                    </div>
                </div>

                <!-- Timeline -->
                <hr class="mt-0" />
                <h5 class="px-3">Messages <span class="float-right badge badge-pill badge-danger">25</span></h5>
                <hr class="mb-0" />
                <div class="p-3">
                    <div class="inbox-widget">
                        <div class="inbox-item">
                            <div class="inbox-item-img"><img src="assets/images/users/user-2.jpg" class="rounded-circle" alt=""></div>
                            <p class="inbox-item-author"><a href="javascript: void(0);" class="text-dark">Tomaslau</a></p>
                            <p class="inbox-item-text">I've finished it! See you so...</p>
                        </div>
                        <div class="inbox-item">
                            <div class="inbox-item-img"><img src="assets/images/users/user-3.jpg" class="rounded-circle" alt=""></div>
                            <p class="inbox-item-author"><a href="javascript: void(0);" class="text-dark">Stillnotdavid</a></p>
                            <p class="inbox-item-text">This theme is awesome!</p>
                        </div>
                        <div class="inbox-item">
                            <div class="inbox-item-img"><img src="assets/images/users/user-4.jpg" class="rounded-circle" alt=""></div>
                            <p class="inbox-item-author"><a href="javascript: void(0);" class="text-dark">Kurafire</a></p>
                            <p class="inbox-item-text">Nice to meet you</p>
                        </div>

                        <div class="inbox-item">
                            <div class="inbox-item-img"><img src="assets/images/users/user-5.jpg" class="rounded-circle" alt=""></div>
                            <p class="inbox-item-author"><a href="javascript: void(0);" class="text-dark">Shahedk</a></p>
                            <p class="inbox-item-text">Hey! there I'm available...</p>
                        </div>
                        <div class="inbox-item">
                            <div class="inbox-item-img"><img src="assets/images/users/user-6.jpg" class="rounded-circle" alt=""></div>
                            <p class="inbox-item-author"><a href="javascript: void(0);" class="text-dark">Adhamdannaway</a></p>
                            <p class="inbox-item-text">This theme is awesome!</p>
                        </div>
                    </div> <!-- end inbox-widget -->
                </div> <!-- end .p-3-->

            </div> <!-- end slimscroll-menu-->
        </div>
        <!-- /Right-bar -->

        <!-- Right bar overlay-->
        <div class="rightbar-overlay"></div>

        <!-- Vendor js -->
        <script src="assets/js/vendor.min.js"></script>

        <!-- Plugins js-->
          <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  
        <script src="assets/libs/flatpickr/flatpickr.min.js"></script>
        <script src="assets/libs/jquery-knob/jquery.knob.min.js"></script>
        <script src="assets/libs/jquery-sparkline/jquery.sparkline.min.js"></script>
        <script src="assets/libs/flot-charts/jquery.flot.js"></script>
        <script src="assets/libs/flot-charts/jquery.flot.time.js"></script>
        <script src="assets/libs/flot-charts/jquery.flot.tooltip.min.js"></script>
        <script src="assets/libs/flot-charts/jquery.flot.selection.js"></script>
        <script src="assets/libs/flot-charts/jquery.flot.crosshair.js"></script>

        <!-- Dashboar 1 init js-->
        <script src="assets/js/pages/dashboard-1.init.js"></script>

        <!-- App js-->
        <script src="assets/js/app.min.js"></script>
        
    </body>

</html>