<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();

// Get the doctor ID from session
$doc_id = $_SESSION['doc_id'];

// Fetch doctor's details including doc_number
$doc_query = "SELECT doc_number, doc_fname, doc_lname FROM his_docs WHERE doc_id = ?";
$stmt = $mysqli->prepare($doc_query);
$stmt->bind_param('i', $doc_id);
$stmt->execute();
$stmt->bind_result($doc_number, $doc_fname, $doc_lname);
$stmt->fetch();
$stmt->close();

// Store in session if not already set
if (!isset($_SESSION['doc_number'])) {
    $_SESSION['doc_number'] = $doc_number;
    $_SESSION['doc_name'] = $doc_fname . ' ' . $doc_lname;
}

/*Doctor Can't delete their payrolls 
If you need doctors to be able to delete their payrolls,
then uncomment this code

if(isset($_GET['delete_pay_number'])) {
    $id = intval($_GET['delete_pay_number']);
    $adn = "DELETE FROM his_payrolls WHERE pay_number=?";
    $stmt = $mysqli->prepare($adn);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();	
    
    if($stmt) {
        $success = "Payroll Record Deleted";
    } else {
        $err = "Try Again Later";
    }
}
*/
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
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Payroll</a></li>
                                        <li class="breadcrumb-item active">View Payroll</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">My Payrolls</h4>
                            </div>
                        </div>
                    </div>     
                    <!-- end page title --> 

                    <div class="row">
                        <div class="col-12">
                            <div class="card-box">
                                <h4 class="header-title">Payroll Records for <?php echo htmlspecialchars($_SESSION['doc_name'] ?? 'Doctor'); ?></h4>
                                <div class="mb-2">
                                    <div class="row">
                                        <div class="col-12 text-sm-center form-inline">
                                            <div class="form-group mr-2" style="display:none">
                                                <select id="demo-foo-filter-status" class="custom-select custom-select-sm">
                                                    <option value="">Show all</option>
                                                    <option value="Discharged">Discharged</option>
                                                    <option value="OutPatients">OutPatients</option>
                                                    <option value="InPatients">InPatients</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <input id="demo-foo-search" type="text" placeholder="Search payroll records..." class="form-control form-control-sm" autocomplete="on">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table id="demo-foo-filtering" class="table table-bordered toggle-circle mb-0" data-page-size="7">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th data-toggle="true">Doctor Name</th>
                                                <th data-toggle="true">Doctor Number</th>
                                                <th data-hide="phone">Payroll Number</th>
                                                <th data-hide="phone">Salary</th>
                                                <th data-hide="phone">Status</th>
                                                <th data-hide="phone">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Use the fetched doc_number
                                            $pay_doc_number = $doc_number;
                                            $ret = "SELECT * FROM his_payrolls WHERE pay_doc_number = ? ORDER BY pay_date_generated DESC";
                                            $stmt = $mysqli->prepare($ret);
                                            $stmt->bind_param('s', $pay_doc_number);
                                            $stmt->execute();
                                            $res = $stmt->get_result();
                                            $cnt = 1;
                                            
                                            if ($res->num_rows > 0) {
                                                while($row = $res->fetch_object()) {
                                            ?>
                                            <tr>
                                                <td><?php echo $cnt; ?></td>
                                                <td><?php echo htmlspecialchars($row->pay_doc_name); ?></td>
                                                <td><?php echo htmlspecialchars($row->pay_doc_number); ?></td>
                                                <td><?php echo htmlspecialchars($row->pay_number); ?></td>   
                                                <td>$ <?php echo number_format($row->pay_emp_salary, 2); ?></td>
                                                <td>
                                                    <?php if (!empty($row->pay_status)): ?>
                                                        <span class="badge badge-<?php echo $row->pay_status == 'Paid' ? 'success' : 'warning'; ?>">
                                                            <?php echo htmlspecialchars($row->pay_status); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <!--<a href="his_admin_manage_payrolls.php?delete_pay_number=<?php echo $row->pay_number; ?>" class="badge badge-danger" onclick="return confirm('Are you sure you want to delete this payroll?');">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </a>-->
                                                    <a href="his_doc_view_single_payroll.php?pay_number=<?php echo $row->pay_number; ?>" class="badge badge-success">
                                                        <i class="fas fa-eye"></i> View | Print Payroll
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php 
                                                $cnt++;
                                                }
                                            } else {
                                            ?>
                                            <tr>
                                                <td colspan="7" class="text-center">
                                                    <div class="alert alert-info">
                                                        No payroll records found for your account.
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php 
                                            }
                                            $stmt->close();
                                            ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="active">
                                                <td colspan="7">
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
    
</body>
</html>