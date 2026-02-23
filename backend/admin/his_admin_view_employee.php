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
                                        <li class="breadcrumb-item"><a href="his_admin_dashboard.php">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="javascript:void(0);">Employee</a></li>
                                        <li class="breadcrumb-item active">View Employee</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Employee Details</h4>
                            </div>
                        </div>
                    </div>     
                    <!-- end page title -->

                    <!-- Display messages -->
                    <?php if(isset($_SESSION['success'])): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($_SESSION['success']); ?>
                            </div>
                        </div>
                    </div>
                    <?php unset($_SESSION['success']); endif; ?>

                    <?php if(isset($_SESSION['error'])): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($_SESSION['error']); ?>
                            </div>
                        </div>
                    </div>
                    <?php unset($_SESSION['error']); endif; ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="card-box">
                                <h4 class="header-title">All Employees</h4>
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
                                                <input id="demo-foo-search" type="text" placeholder="Search employees..." class="form-control form-control-sm" autocomplete="on">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table id="demo-foo-filtering" class="table table-bordered toggle-circle mb-0" data-page-size="7">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th data-toggle="true">Name</th>
                                            <th data-hide="phone">Employee Number</th>
                                            <th data-hide="phone">Email</th>
                                            <th data-hide="phone">Type</th>
                                            <th data-hide="phone">Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        // CORRECTED UNION QUERY - Each table uses its own ID
                                        $ret = "SELECT 
                                                doc_id AS real_id,
                                                CONCAT('DOC-', doc_id) AS staff_id,
                                                doc_fname AS staff_fname,
                                                doc_lname AS staff_lname,
                                                doc_number AS staff_number,
                                                doc_email AS staff_email,
                                                'Doctor' AS staff_type
                                            FROM his_docs 
                                            
                                            UNION ALL 
                                            
                                            SELECT 
                                                ns_id AS real_id,
                                                CONCAT('NUR-', ns_id) AS staff_id,
                                                ns_fname AS staff_fname,
                                                ns_lname AS staff_lname,
                                                ns_number AS staff_number,
                                                ns_email AS staff_email,
                                                'Nurse' AS staff_type
                                            FROM his_nurse 
                                            
                                            UNION ALL
                                            
                                            SELECT 
                                                rec_id AS real_id,
                                                CONCAT('REC-', rec_id) AS staff_id,
                                                rec_fname AS staff_fname,
                                                rec_lname AS staff_lname,
                                                rec_number AS staff_number,
                                                rec_email AS staff_email,
                                                'Receptionist' AS staff_type
                                            FROM his_record 
                                            
                                            UNION ALL 
                                            
                                            SELECT 
                                                pharm_id AS real_id,
                                                CONCAT('PHARM-', pharm_id) AS staff_id,
                                                pharm_fname AS staff_fname,
                                                pharm_lname AS staff_lname,
                                                pharm_number AS staff_number,
                                                pharm_email AS staff_email,
                                                'Pharmacist' AS staff_type
                                            FROM his_pharm
                                            
                                            UNION ALL 
                                            
                                            SELECT 
                                                lab_id AS real_id,
                                                CONCAT('LAB-', lab_id) AS staff_id,
                                                lab_fname AS staff_fname,
                                                lab_lname AS staff_lname,
                                                lab_number AS staff_number,
                                                lab_email AS staff_email,
                                                'Lab Scientist' AS staff_type
                                            FROM his_lab_scist 
                                            
                                            ORDER BY staff_type, staff_fname, staff_lname";

                                        $stmt = $mysqli->prepare($ret);
                                        if ($stmt) {
                                            $stmt->execute();
                                            $res = $stmt->get_result();
                                            $cnt = 1;
                                            
                                            while($row = $res->fetch_object()) {
                                                // DEBUG: Check what's being generated
                                                error_log("Employee {$cnt}: Type={$row->staff_type}, Staff_ID={$row->staff_id}, Real_ID={$row->real_id}");
                                        ?>
                                        <tr>
                                            <td><?php echo $cnt;?></td>
                                            <td><?php echo htmlspecialchars($row->staff_fname . ' ' . $row->staff_lname); ?></td>
                                            <td><?php echo htmlspecialchars($row->staff_number); ?></td>
                                            <td><?php echo htmlspecialchars($row->staff_email); ?></td>
                                            <td>
                                                <?php 
                                                $badge_color = '';
                                                switch($row->staff_type) {
                                                    case 'Doctor': $badge_color = 'success'; break;
                                                    case 'Nurse': $badge_color = 'primary'; break;
                                                    case 'Receptionist': $badge_color = 'info'; break;
                                                    case 'Pharmacist': $badge_color = 'warning'; break;
                                                    case 'Lab Scientist': $badge_color = 'danger'; break;
                                                    default: $badge_color = 'secondary';
                                                }
                                                ?>
                                                <span class="badge badge-<?php echo $badge_color; ?>">
                                                    <?php echo htmlspecialchars($row->staff_type); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="his_admin_view_single_employee.php?staff_id=<?php 
                                                    // Use the prefixed ID from the query
                                                    echo urlencode($row->staff_id); 
                                                ?>&staff_number=<?php echo urlencode($row->staff_number); ?>" 
                                                   class="badge badge-success">
                                                    <i class="mdi mdi-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php  
                                                $cnt++;
                                            }
                                            $stmt->close();
                                        } else {
                                            echo '<tr><td colspan="6" class="text-center text-danger">Error loading employee data: ' . htmlspecialchars($mysqli->error) . '</td></tr>';
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
    
    <style>
        .badge {
            padding: 5px 10px;
            font-size: 12px;
        }
        table th {
            font-weight: 600;
        }
    </style>
</body>
</html>