<?php
  session_start();
  include('assets/inc/config.php');
  include('assets/inc/checklogin.php');
  check_login();
  $doc_id=$_SESSION['doc_id'];
  $doc_number = $_SESSION['doc_id'];

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
        <div class="container-fluid">

            <div class="row mb-3">
                <div class="col-12">
                    <div class="page-title-box flex items-center justify-between p-2 rounded border-bottom" style="border-bottom: 2px solid #f1f5f9 !important;">
                        <div class="flex flex-col gap-1">
                            <p class="text-black text-2xl font-medium mb-0">Welcome Back Doctor </p>
                        </div>
                        <div class="d-none d-md-block">
                            <p class="text-muted mb-0 font-12 text-right">
                                <?php echo date('l, d M Y'); ?> • <span id="liveClock" class="text-primary font-weight-semibold">--:--:--</span>
                            </p>
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
                updateClock();
            </script>
            <div class="row">
                <div class="col-md-6 col-xl-4 py-2">
                    <div class="widget-rounded-circle shadow-sm bg-sky-100 rounded-lg p-3">
                        <div class="row align-items-center">
                            <div class="col-5">
                                <div class="avatar-lg">
                                    <img src="https://crm-admin-dashboard-template.multipurposethemes.com/images/svg-icon/medical/icon-1.svg" class="img-fluid" alt="Outpatient">
                                </div>
                            </div>
                            <div class="col-7">
                                <div class="text-right">
                                    <?php
                                        $result ="SELECT count(*) FROM his_patients WHERE pat_type = 'OutPatient'";
                                        $stmt = $mysqli->prepare($result);
                                        $stmt->execute();
                                        $stmt->bind_result($outpatient);
                                        $stmt->fetch();
                                        $stmt->close();
                                    ?>
                                    <h3 class="font-weight-bold mt-1" style="color: #0284c7; font-size: 1.8rem;">
                                        <span data-plugin="counterup"><?php echo $outpatient;?></span>
                                    </h3>
                                    <p class="text-muted mb-1 text-truncate font-weight-medium">Out Patients</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-4 py-2">
                    <div class="widget-rounded-circle shadow-sm bg-purple-100 rounded-lg p-3">
                        <div class="row align-items-center">
                            <div class="col-5">
                                <div class="avatar-lg">
                                    <img src="https://crm-admin-dashboard-template.multipurposethemes.com/images/svg-icon/medical/icon-3.svg" class="img-fluid" alt="Inpatient">
                                </div>
                            </div>
                            <div class="col-7">
                                <div class="text-right">
                                    <?php
                                        $result ="SELECT count(*) FROM his_patients WHERE pat_type = 'InPatient'";
                                        $stmt = $mysqli->prepare($result);
                                        $stmt->execute();
                                        $stmt->bind_result($inpatient);
                                        $stmt->fetch();
                                        $stmt->close();
                                    ?>
                                    <h3 class="font-weight-bold mt-1" style="color: #7c3aed; font-size: 1.8rem;">
                                        <span data-plugin="counterup"><?php echo $inpatient;?></span>
                                    </h3>
                                    <p class="text-muted mb-1 text-truncate font-weight-medium">In Patients</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-4 py-2">
                    <div class="widget-rounded-circle shadow-sm bg-pink-100 rounded-lg p-3">
                        <div class="row align-items-center">
                            <div class="col-5">
                                <div class="avatar-lg">
                                    <img src="https://crm-admin-dashboard-template.multipurposethemes.com/images/svg-icon/medical/icon-2.svg" class="img-fluid" alt="Pharmacy">
                                </div>
                            </div>
                            <div class="col-7">
                                <div class="text-right">
                                    <?php
                                        $result ="SELECT count(*) FROM his_pharmaceuticals";
                                        $stmt = $mysqli->prepare($result);
                                        $stmt->execute();
                                        $stmt->bind_result($phar);
                                        $stmt->fetch();
                                        $stmt->close();
                                    ?>
                                    <h3 class="font-weight-bold mt-1" style="color: #db2777; font-size: 1.8rem;">
                                        <span data-plugin="counterup"><?php echo $phar;?></span>
                                    </h3>
                                    <p class="text-muted mb-1 text-truncate font-weight-medium">Pharmaceuticals</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <div class="row mt-2">
                <div class="col-md-6 col-xl-6 py-2">
                    <a href="his_doc_account.php" class="text-decoration-none">
                        <div class="widget-rounded-circle shadow-sm bg-green-100 rounded-lg p-3">
                            <div class="row align-items-center">
                                <div class="col-5">
                                    <div class="avatar-lg bg-white rounded-circle flex items-center justify-center">
                                        <i class="fas fa-user-md font-24 text-success"></i>
                                    </div>
                                </div>
                                <div class="col-7 text-right">
                                    <h3 class="font-weight-bold text-success mt-1">View</h3>
                                    <p class="text-muted mb-1 font-weight-medium">My Profile</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 col-xl-6 py-2">
                    <a href="his_doc_view_payrolls.php" class="text-decoration-none">
                        <div class="widget-rounded-circle shadow-sm bg-amber-100 rounded-lg p-3">
                            <div class="row align-items-center">
                                <div class="col-5">
                                    <div class="avatar-lg bg-white rounded-circle flex items-center justify-center">
                                        <i class="fas fa-file-invoice-dollar font-24 text-warning"></i>
                                    </div>
                                </div>
                                <div class="col-7 text-right">
                                    <h3 class="font-weight-bold text-warning mt-1">Access</h3>
                                    <p class="text-muted mb-1 font-weight-medium">My Payroll</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

        </div> </div>   

                      <div class="row">
    <div class="col-xl-12">
        <div class="card shadow-sm rounded-lg border-0">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="text-xl font-bold text-gray-800 m-0">
                        <i class="mdi mdi-account-group mr-2 text-primary"></i> Recent Patient Records
                    </h4>
                    <span class="badge badge-soft-primary px-3 py-2 rounded-pill font-medium">
                        Live Database Feed
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-borderless table-hover table-centered m-0">
                        <thead class="bg-light text-muted uppercase font-12" style="letter-spacing: 0.05em;">
                            <tr>
                                <th class="py-3">Patient Name</th>
                                <th class="py-3">Address</th>
                                <th class="py-3">Contact</th>
                                <th class="py-3">Category</th>
                                <th class="py-3">Ailment</th>
                                <th class="py-3">Age</th>
                                <th class="py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                // Optimized query to get random sample
                                $ret="SELECT * FROM his_patients ORDER BY RAND() LIMIT 10"; 
                                $stmt= $mysqli->prepare($ret);
                                $stmt->execute();
                                $res=$stmt->get_result();
                                while($row=$res->fetch_object()) {
                                    // Logic for dynamic category badges
                                    $badge_color = ($row->pat_type == 'InPatient') ? 'badge-soft-danger' : 'badge-soft-success';
                            ?>
                            <tr class="border-bottom" style="border-color: #f8fafc !important;">
                                <td class="py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-soft-primary rounded-circle d-flex align-items-center justify-center mr-2" style="width: 35px; height: 35px;">
                                            <span class="text-primary font-weight-bold font-12">
                                                <?php echo substr($row->pat_fname, 0, 1) . substr($row->pat_lname, 0, 1); ?>
                                            </span>
                                        </div>
                                        <span class="font-bold text-slate-700"><?php echo $row->pat_fname;?> <?php echo $row->pat_lname;?></span>
                                    </div>
                                </td>
                                
                                <td class="text-slate-600 py-3">
                                    <small class="font-weight-medium"><?php echo $row->pat_addr;?></small>
                                </td>

                                <td class="py-3">
                                    <span class="text-muted"><i class="mdi mdi-phone-outline mr-1"></i><?php echo $row->pat_phone;?></span>
                                </td>

                                <td class="py-3">
                                    <span class="badge <?php echo $badge_color; ?> p-1 px-3 rounded-pill font-11">
                                        <?php echo $row->pat_type;?>
                                    </span>
                                </td>

                                <td class="py-3">
                                    <span class="text-dark font-medium"><?php echo $row->pat_ailment;?></span>
                                </td>

                                <td class="py-3">
                                    <span class="badge badge-soft-dark"><?php echo $row->pat_age;?> Years</span>
                                </td>

                                <td class="text-center py-3">
                                    <a href="his_doc_view_single_patient.php?pat_id=<?php echo $row->pat_id;?>&pat_number=<?php echo $row->pat_number;?>" 
                                       class=" rounded-lg text-black px-3 border py-1 transition-all">
                                        <i class="mdi mdi-eye-outline mr-1"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div> </div>
        </div>
    </div> </div>
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

         <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
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