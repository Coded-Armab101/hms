<?php
  session_start();
  include('assets/inc/config.php');
  include('assets/inc/checklogin.php');
  check_login();
  $aid=$_SESSION['pharm_id'];
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
                        <div class="row">
                            <div class="col-12">
                                <div class="page-title-box">
                                    
                                    <h4 class="page-title">Hospital Management System Dashboard</h4>
                                </div>
                            </div>
                        </div>     
                        <!-- end page title --> 
                        
<div class="row">
    <div class="col-md-6 col-xl-4 lg:py-0 py-2">
        <div class="border-none shadow-sm rounded-xl p-3" style="background-color: #f0fdf4; border-radius: 12px;">
            <div class="row flex items-center">
                <div class="col-5">
                    <div class="avatar-lg w-full flex justify-center">
                        <i class="fab fa-accessible-icon" style="font-size: 3rem; color: #16a34a;"></i>
                    </div>
                </div>
                <div class="col-7">
                    <div class="text-right">
                        <?php
                            $result ="SELECT count(*) FROM his_patients WHERE pat_type = 'OutPatient' ";
                            $stmt = $mysqli->prepare($result);
                            $stmt->execute();
                            $stmt->bind_result($outpatient);
                            $stmt->fetch();
                            $stmt->close();
                        ?>
                        <h3 class="font-bold mt-1" style="color: #16a34a; font-size: 1.8rem; margin-bottom: 0;">
                            <span data-plugin="counterup"><?php echo $outpatient;?></span>
                        </h3>
                        <p class="text-muted mb-0 text-truncate font-medium" style="font-family: 'Segoe UI', sans-serif; color: #64748b;">Out Patients</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4 lg:py-0 py-2">
        <div class="border-none shadow-sm rounded-xl p-3" style="background-color: #ecfeff; border-radius: 12px;">
            <div class="row align-items-center">
                <div class="col-5">
                    <div class="avatar-lg w-full flex justify-center">
                        <i class="mdi mdi-hotel" style="font-size: 3.5rem; color: #0891b2;"></i>
                    </div>
                </div>
                <div class="col-7">
                    <div class="text-right">
                        <?php
                            $result ="SELECT count(*) FROM his_patients WHERE pat_type = 'InPatient' ";
                            $stmt = $mysqli->prepare($result);
                            $stmt->execute();
                            $stmt->bind_result($inpatient);
                            $stmt->fetch();
                            $stmt->close();
                        ?>
                        <h3 class="font-bold mt-1" style="color: #0891b2; font-size: 1.8rem; margin-bottom: 0;">
                            <span data-plugin="counterup"><?php echo $inpatient;?></span>
                        </h3>
                        <p class="text-muted mb-0 text-truncate font-medium" style="font-family: 'Segoe UI', sans-serif; color: #64748b;">In Patients</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4 lg:py-0 py-2">
        <div class="border-none shadow-sm rounded-xl p-3" style="background-color: #eef2ff; border-radius: 12px;">
            <div class="row align-items-center">
                <div class="col-5">
                    <div class="avatar-lg w-full flex justify-center">
                        <i class="mdi mdi-doctor" style="font-size: 3.5rem; color: #4f46e5;"></i>
                    </div>
                </div>
                <div class="col-7">
                    <div class="text-right">
                        <?php
                            $result ="SELECT count(*) FROM his_docs ";
                            $stmt = $mysqli->prepare($result);
                            $stmt->execute();
                            $stmt->bind_result($doc);
                            $stmt->fetch();
                            $stmt->close();
                        ?>
                        <h3 class="font-bold mt-1" style="color: #4f46e5; font-size: 1.8rem; margin-bottom: 0;">
                            <span data-plugin="counterup"><?php echo $doc;?></span>
                        </h3>
                        <p class="text-muted mb-0 text-truncate font-medium" style="font-family: 'Segoe UI', sans-serif; color: #64748b;">Hospital Employees</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 col-xl-4 mb-3">
        <div class="card-box shadow-sm border-0 p-3" style="background-color: #ecfdf5; border-radius: 12px;">
            <div class="row align-items-center">
                <div class="col-5">
                    <div class="avatar-lg">
                        <img src="https://crm-admin-dashboard-template.multipurposethemes.com/images/svg-icon/medical/icon-3.svg" class="img-fluid" alt="Vendor Icon">
                    </div>
                </div>
                <div class="col-7 text-right">
                    <h3 class="font-weight-bold mt-1" style="color: #10b981; font-size: 2rem;">
                        <span data-plugin="counterup"><?php echo $vendor; ?></span>
                    </h3>
                    <p class="text-muted mb-1 text-truncate font-weight-medium">Total Vendors</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4 mb-3">
        <div class="card-box shadow-sm border-0 p-3" style="background-color: #f5f3ff; border-radius: 12px;">
            <div class="row align-items-center">
                <div class="col-5">
                    <div class="avatar-lg">
                        <img src="https://crm-admin-dashboard-template.multipurposethemes.com/images/svg-icon/medical/icon-4.svg" class="img-fluid" alt="Assets Icon">
                    </div>
                </div>
                <div class="col-7 text-right">
                    <h3 class="font-weight-bold mt-1" style="color: #8b5cf6; font-size: 2rem;">
                        <span data-plugin="counterup"><?php echo $assets; ?></span>
                    </h3>
                    <p class="text-muted mb-1 text-truncate font-weight-medium">Corp. Assets</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4 mb-3">
        <div class="card-box shadow-sm border-0 p-3" style="background-color: #fff1f2; border-radius: 12px;">
            <div class="row align-items-center">
                <div class="col-5">
                    <div class="avatar-lg">
                        <img src="https://crm-admin-dashboard-template.multipurposethemes.com/images/svg-icon/medical/icon-2.svg" class="img-fluid" alt="Pharma Icon">
                    </div>
                </div>
                <div class="col-7 text-right">
                    <h3 class="font-weight-bold mt-1" style="color: #f43f5e; font-size: 2rem;">
                        <span data-plugin="counterup"><?php echo $phar; ?></span>
                    </h3>
                    <p class="text-muted mb-1 text-truncate font-weight-medium">Pharmaceuticals</p>
                </div>
            </div>
        </div>
    </div>
</div>
                        
                        <!--Recently Employed Employees-->
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="card-box">
                                    <h4 class="header-title mb-3">Hospital Employees</h4>

                                    <div class="table-responsive">
                                        <table class="table table-borderless table-hover table-centered m-0">

                                            <thead class="thead-light">
                                                <tr>
                                                    <th colspan="2">Picture</th>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Department</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <?php
                                                $ret="SELECT * FROM his_docs ORDER BY RAND() LIMIT 10 "; 
                                                //sql code to get to ten docs  randomly
                                                $stmt= $mysqli->prepare($ret) ;
                                                $stmt->execute() ;//ok
                                                $res=$stmt->get_result();
                                                $cnt=1;
                                                while($row=$res->fetch_object())
                                                {
                                            ?>
                                            <tbody>
                                                <tr>
                                                    <td style="width: 36px;">
                                                        <img src="../doc/assets/images/users/<?php echo $row->doc_dpic;?>" alt="img" title="contact-img" class="rounded-circle avatar-sm" />
                                                    </td>
                                                    <td>
                                                    </td>
                                                    <td>
                                                        <?php echo $row->doc_fname;?> <?php echo $row->doc_lname;?>
                                                    </td>    
                                                    <td>
                                                        <?php echo $row->doc_email;?>
                                                    </td>
                                                    <td>
                                                        <?php echo $row->doc_dept;?>
                                                    </td>
                                                    <td>
                                                        <a href="his_pharm_view_single_employee.php?doc_id=<?php echo $row->doc_id;?>&&doc_number=<?php echo $row->doc_number;?>" class="btn btn-xs btn-primary"><i class="mdi mdi-eye"></i> View</a>
                                                    </td>                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   
                                                </tr>
                                            </tbody>
                                            <?php }?>
                                        </table>
                                    </div>
                                </div>
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