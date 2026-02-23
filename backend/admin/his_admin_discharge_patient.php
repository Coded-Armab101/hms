<?php
  session_start();
  include('assets/inc/config.php');
  include('assets/inc/checklogin.php');
  check_login();
  $aid=$_SESSION['ad_id'];
?>

<!DOCTYPE html>
<html lang="en">
    
<?php include('assets/inc/head.php');?>

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
                                            <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                            <li class="breadcrumb-item"><a href="javascript: void(0);">Patients</a></li>
                                            <li class="breadcrumb-item active">Discharge Patients</li>
                                        </ol>
                                    </div>
                                    <h4 class="page-title">Discharge Patients</h4>
                                </div>
                            </div>
                        </div>     
                        <!-- end page title --> 

                        <div class="row">
                            <div class="col-12">
                                <div class="card-box">
                                    <h4 class="header-title"></h4>
                                    <div class="mb-2">
                                        <div class="row">
                                            <div class="col-12 text-sm-center form-inline" >
                                                <div class="form-group mr-2" style="display:none">
                                                    <select id="demo-foo-filter-status" class="custom-select custom-select-sm">
                                                        <option value="">Show all</option>
                                                        <option value="Discharged">Discharged</option>
                                                        <option value="OutPatients">OutPatients</option>
                                                        <option value="InPatients">InPatients</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <input id="demo-foo-search" type="text" placeholder="Search patients..." class="form-control form-control-sm" autocomplete="on">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table id="demo-foo-filtering" class="table table-bordered toggle-circle mb-0" data-page-size="7">
                                            <thead>
                                            <tr>
                                                <th>#</th>
                                                <th data-toggle="true">Patient Name</th>
                                                <th data-hide="phone">Patient Number</th>
                                                <th data-hide="phone">Patient Address</th>
                                                <th data-hide="phone">Patient Category</th>
                                                <th data-hide="phone">Date Joined</th>
                                                <th data-hide="phone">Status</th>
                                                <th data-hide="phone">Action</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            /*
                                                * Get details of all patients eligible for discharge
                                                * Only InPatients who are not discharged yet
                                                * Using pat_date_joined instead of pat_admission_date
                                            */
                                            try {
                                                $ret = "SELECT pat_id, pat_fname, pat_lname, pat_number, 
                                                               pat_addr, pat_type, pat_discharge_status, pat_date_joined
                                                        FROM his_patients  
                                                        WHERE pat_discharge_status != 'Discharged' 
                                                        AND pat_type = 'InPatient'
                                                        ORDER BY pat_id DESC"; 
                                                
                                                $stmt = $mysqli->prepare($ret);
                                                $stmt->execute();
                                                $res = $stmt->get_result();
                                                $cnt = 1;
                                                
                                                if($res->num_rows > 0) {
                                                    while($row = $res->fetch_object())
                                                    {
                                            ?>
                                                <tr>
                                                    <td><?php echo $cnt;?></td>
                                                    <td><?php echo htmlspecialchars($row->pat_fname);?> <?php echo htmlspecialchars($row->pat_lname);?></td>
                                                    <td><span class="badge badge-info"><?php echo htmlspecialchars($row->pat_number);?></span></td>
                                                    <td><?php echo htmlspecialchars($row->pat_addr);?></td>
                                                    <td><span class="badge badge-warning"><?php echo htmlspecialchars($row->pat_type);?></span></td>
                                                    <td>
                                                        <?php 
                                                        // Handle null or empty date_joined
                                                        if(!empty($row->pat_date_joined) && $row->pat_date_joined != '0000-00-00') {
                                                            echo date('d M Y', strtotime($row->pat_date_joined));
                                                        } else {
                                                            echo '<span class="text-muted">N/A</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><span class="badge badge-success">Active</span></td>
                                                    <td>
                                                        <a href="his_admin_discharge_single_patient.php?pat_id=<?php echo $row->pat_id;?>" 
                                                           class="badge badge-primary p-2" 
                                                           onclick="return confirm('Are you sure you want to discharge <?php echo htmlspecialchars($row->pat_fname);?> <?php echo htmlspecialchars($row->pat_lname);?>?');">
                                                            <i class="mdi mdi-check-box-outline"></i> Discharge
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php  
                                                    $cnt++;
                                                    }
                                                } else {
                                                    echo '<tr><td colspan="8" class="text-center text-info">No patients found for discharge. All in-patients have been discharged.</td></tr>';
                                                }
                                            } catch(Exception $e) {
                                                echo '<tr><td colspan="8" class="text-center text-danger">Error loading patients: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                                            }
                                            ?>
                                            </tbody>
                                            <tfoot>
                                            <tr class="active">
                                                <td colspan="8">
                                                    <div class="text-right">
                                                        <ul class="pagination pagination-rounded justify-content-end footable-pagination m-t-10 mb-0"></ul>
                                                    </div>
                                                </td>
                                            </tr>
                                            </tfoot>
                                        </table>
                                    </div> <!-- end .table-responsive-->
                                    
                                    <!-- Summary Card -->
                                    <div class="row mt-3">
                                        <div class="col-md-12">
                                            <div class="alert alert-info mb-0">
                                                <i class="mdi mdi-information-outline mr-2"></i>
                                                <strong>Note:</strong> Only In-Patients who are not discharged yet are shown here. 
                                                <?php
                                                // Quick count query to show total eligible patients
                                                $countQuery = "SELECT COUNT(*) as total FROM his_patients WHERE pat_discharge_status != 'Discharged' AND pat_type = 'InPatient'";
                                                $countStmt = $mysqli->prepare($countQuery);
                                                $countStmt->execute();
                                                $countResult = $countStmt->get_result();
                                                $countRow = $countResult->fetch_object();
                                                echo '<strong>Total patients ready for discharge: ' . $countRow->total . '</strong>';
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div> <!-- end card-box -->
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

        <!-- Footable js -->
        <script src="assets/libs/footable/footable.all.min.js"></script>

        <!-- Init js -->
        <script src="assets/js/pages/foo-tables.init.js"></script>

        <!-- App js -->
        <script src="assets/js/app.min.js"></script>
        
    </body>

</html>