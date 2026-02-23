<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$aid = $_SESSION['pharm_id'];

if(isset($_GET['delete_pharm_name'])) {
    $id = intval($_GET['delete_pharm_name']);
    $adn = "DELETE FROM his_pharmaceuticals WHERE phar_id = ?";
    $stmt_del = $mysqli->prepare($adn);
    $stmt_del->bind_param('i', $id);
    $stmt_del->execute();
    $stmt_del->close();	 
    $success = "Pharmaceutical Records Deleted";
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include('assets/inc/head.php'); ?>
<body>
    <!-- Begin page -->
    <div id="wrapper">
        <!-- Topbar Start -->
        <?php include("assets/inc/nav.php"); ?>
        <!-- End Topbar -->

        <!-- ========== Left Sidebar Start ========== -->
        <?php include("assets/inc/sidebar.php"); ?>
        <!-- End Left Sidebar -->

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
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Pharmaceuticals</a></li>
                                        <li class="breadcrumb-item active">Manage Pharmaceuticals</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Manage Pharmaceuticals</h4>
                            </div>
                        </div>
                    </div>
                    <!-- end page title --> 

                    <div class="row">
                        <div class="col-12">
                            <div class="card-box">
                                <!-- Optional: display success/error messages -->
                                <?php if(isset($success)) { ?>
                                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                                <?php } ?>
                                <?php if(isset($err)) { ?>
                                    <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
                                <?php } ?>
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
                                                <input id="demo-foo-search" type="text" placeholder="Search" class="form-control form-control-sm" autocomplete="on">
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
                                                <th data-hide="phone">Barcode</th>
                                                <th data-hide="phone">Vendor</th>
                                                <th data-hide="phone">Category</th>
                                                <th data-hide="phone">Quantity</th>
                                                <th data-hide="phone">Attribute</th>
                                                <th data-hide="phone">Unit/package</th>
                                                <th data-hide="phone">Price/Unit</th>
                                                <th data-hide="phone">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $query = "SELECT * FROM his_pharmaceuticals ORDER BY RAND()"; 
                                            $stmt_pharm = $mysqli->prepare($query);
                                            $stmt_pharm->execute();
                                            $res_pharm = $stmt_pharm->get_result();
                                            $cnt = 1;
                                            while($row = $res_pharm->fetch_object()) {
                                            ?>
                                            <tr>
                                                <td><?php echo $cnt; ?></td>
                                                <td><?php echo $row->phar_name; ?></td>
                                                <td><?php echo $row->phar_bcode; ?></td>
                                                <td><?php echo $row->phar_vendor; ?></td>
                                                <td><?php echo $row->phar_cat; ?></td>
                                                <td><?php echo $row->phar_qty; ?></td>
                                                <td><?php echo $row->phar_attribute; ?></td>
                                                <td><?php echo $row->phar_unit; ?></td>
                                                <td><?php echo $row->phar_price_unit; ?></td>
                                                <td>
                                                    <a href="his_pharm_view_single_pharm.php?phar_bcode=<?php echo $row->phar_bcode; ?>" class="badge badge-success">
                                                        <i class="far fa-eye"></i> View
                                                    </a>
                                                    <a href="his_pharm_update_single_pharm.php?phar_bcode=<?php echo $row->phar_bcode; ?>" class="badge badge-warning">
                                                        <i class="fas fa-clipboard-check"></i> Update
                                                    </a>
                                                    <a href="his_pharm_manage_pharmaceuticals.php?delete_pharm_name=<?php echo $row->phar_id; ?>" class="badge badge-danger">
                                                        <i class="fas fa-trash-alt"></i> Delete
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php
                                                $cnt++;
                                            }
                                            ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="active">
                                                <td colspan="10">
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
            <?php include('assets/inc/footer.php'); ?>
            <!-- end Footer -->
        </div>
        <!-- End Page content -->
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
