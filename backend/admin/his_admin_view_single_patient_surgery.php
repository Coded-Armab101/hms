<?php
  session_start();
  include('assets/inc/config.php');
  include('assets/inc/checklogin.php');
  check_login();
  $aid=$_SESSION['ad_id'];
?>
<!DOCTYPE html>
<html lang="en">
    
<?php include ('assets/inc/head.php');?>

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
            <?php
                $s_number = $_GET['s_number'];
                $ret = "SELECT  * FROM his_surgery WHERE s_number = ?";
                $stmt = $mysqli->prepare($ret);
                // s_number column is varchar - bind as string
                $stmt->bind_param('s', $s_number);
                $stmt->execute(); //ok
                $res=$stmt->get_result();
                //$cnt=1;
                while($row=$res->fetch_object())
                {
                    $mysqlDateTime = $row->s_pat_date; //trim timestamp to dd/mm/yyyy formart

                    // Fetch latest prescription for this patient (if any)
                    $prescription = null;
                    if (!empty($row->s_pat_number)) {
                        $param = $row->s_pat_number;

                        // Check whether prescriptions table links to docs via doc_id
                        $check_doc_col = $mysqli->query("SHOW COLUMNS FROM his_prescriptions LIKE 'doc_id'");
                        $has_doc_id = $check_doc_col && $check_doc_col->num_rows > 0;

                        $queries = [
                            "SELECT p.* FROM his_prescriptions p WHERE p.pat_number = ? ORDER BY p.pres_date DESC LIMIT 1",
                            "SELECT p.* FROM his_prescriptions p WHERE p.pres_pat_number = ? ORDER BY p.pres_date DESC LIMIT 1",
                            "SELECT p.* FROM his_prescriptions p WHERE p.pres_number = ? ORDER BY p.pres_date DESC LIMIT 1"
                        ];

                        if ($has_doc_id) {
                            array_unshift($queries, "SELECT p.*, CONCAT(d.doc_fname, ' ', d.doc_lname) AS doctor_name FROM his_prescriptions p LEFT JOIN his_docs d ON p.doc_id = d.doc_id WHERE p.pat_number = ? ORDER BY p.pres_date DESC LIMIT 1");
                        }

                        foreach ($queries as $q) {
                            try {
                                $stmtP = $mysqli->prepare($q);
                            } catch (mysqli_sql_exception $e) {
                                // prepare failed (e.g., unknown column) — try next query
                                $stmtP = false;
                            }

                            if ($stmtP) {
                                $stmtP->bind_param('s', $param);
                                $stmtP->execute();
                                $resP = $stmtP->get_result();
                                if ($presRow = $resP->fetch_object()) {
                                    $prescription = $presRow;
                                    $stmtP->close();
                                    break;
                                }
                                $stmtP->close();
                            }
                        }
                    }
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
                                                <li class="breadcrumb-item"><a href="javascript: void(0);">Surgery</a></li>
                                                <li class="breadcrumb-item active">View Single Records</li>
                                            </ol>
                                        </div>
                                        <h4 class="page-title">#<?php echo $row->s_number;?></h4>
                                    </div>
                                </div>
                            </div>     
                            <!-- end page title --> 

                            <div class="row">
                                <div class="col-12">
                                    <div class="card-box">
                                        <div class="row">
                                            <div class="col-xl-5">

                                                <div class="tab-content pt-0">

                                                    <div class="tab-pane active show" id="product-1-item">
                                                        <img src="assets/images/surg.png" alt="" class="img-fluid mx-auto d-block rounded">
                                                    </div>
                            
                                                </div>
                                            </div> <!-- end col -->
                                            <div class="col-xl-7">
                                            
                                                <div class="pl-xl-3 mt-3 mt-xl-0">
                                                    <h2 class="mb-3">Patient's Name : <?php echo $row->s_pat_name;?></h2>
                                                    <hr>
                                                    <h3 class="align-centre ">Patient Number : <?php echo $row->s_pat_number;?></h3>
                                                    <hr>
                                                    <h3 class="align-centre ">Patient Ailment : <?php echo $row->s_pat_ailment;?></h3>
                                                    <hr>
                                                    <h3 class="align-centre ">Date Surgery Conducted : <?php echo date("d/m/Y", strtotime($mysqlDateTime));?></h3>
                                                    <hr>
                                                    <h2 class="align-centre">Surgeon :  <?php echo htmlspecialchars($row->s_doc);?> </h2>
                                                    <hr>
                                                    <?php if (!empty($prescription)) { ?>
                                                    <h2 class="align-centre">Related Prescription</h2>
                                                    <p class="text-muted mb-4">
                                                        <strong><?php echo htmlspecialchars($prescription->pres_number ?? ''); ?></strong><br>
                                                        <?php echo nl2br(htmlspecialchars(strip_tags($prescription->pres_ins ?? ''))); ?>
                                                        <?php if (!empty($prescription->doctor_name)) { echo '<br><em>Prescribed by: ' . htmlspecialchars($prescription->doctor_name) . '</em>'; } ?>
                                                    </p>
                                                    <hr>
                                                    <?php } ?>
                                                    <h2 class="align-centre">Surgery Status : <span class ="btn btn-success"> <?php echo htmlspecialchars($row->s_pat_status);?></span> </h2>
                                                    <hr>
                                                    
                                                    
                                                   <!--
                                                    <form class="form-inline mb-4">
                                                        <label class="my-1 mr-2" for="quantityinput">Quantity</label>
                                                        <select class="custom-select my-1 mr-sm-3" id="quantityinput">
                                                            <option value="1">1</option>
                                                            <option value="2">2</option>
                                                            <option value="3">3</option>
                                                            <option value="4">4</option>
                                                            <option value="5">5</option>
                                                            <option value="6">6</option>
                                                            <option value="7">7</option>
                                                        </select>

                                                        <label class="my-1 mr-2" for="sizeinput">Size</label>
                                                        <select class="custom-select my-1 mr-sm-3" id="sizeinput">
                                                            <option selected>Small</option>
                                                            <option value="1">Medium</option>
                                                            <option value="2">Large</option>
                                                            <option value="3">X-large</option>
                                                        </select>
                                                    </form>

                                                    <div>
                                                        <button type="button" class="btn btn-danger mr-2"><i class="mdi mdi-heart-outline"></i></button>
                                                        <button type="button" class="btn btn-success waves-effect waves-light">
                                                            <span class="btn-label"><i class="mdi mdi-cart"></i></span>Add to cart
                                                        </button>
                                                    </div> -->
                                                </div>
                                            </div> <!-- end col -->
                                        </div>
                                        <!-- end row -->

                                        <!--
                                        <div class="table-responsive mt-4">
                                            <table class="table table-bordered table-centered mb-0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Outlets</th>
                                                        <th>Price</th>
                                                        <th>Stock</th>
                                                        <th>Revenue</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>ASOS Ridley Outlet - NYC</td>
                                                        <td>$139.58</td>
                                                        <td>
                                                            <div class="progress-w-percent mb-0">
                                                                <span class="progress-value">478 </span>
                                                                <div class="progress progress-sm">
                                                                    <div class="progress-bar bg-success" role="progressbar" style="width: 56%;" aria-valuenow="56" aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>$1,89,547</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Marco Outlet - SRT</td>
                                                        <td>$149.99</td>
                                                        <td>
                                                            <div class="progress-w-percent mb-0">
                                                                <span class="progress-value">73 </span>
                                                                <div class="progress progress-sm">
                                                                    <div class="progress-bar bg-danger" role="progressbar" style="width: 16%;" aria-valuenow="16" aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>$87,245</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Chairtest Outlet - HY</td>
                                                        <td>$135.87</td>
                                                        <td>
                                                            <div class="progress-w-percent mb-0">
                                                                <span class="progress-value">781 </span>
                                                                <div class="progress progress-sm">
                                                                    <div class="progress-bar bg-success" role="progressbar" style="width: 72%;" aria-valuenow="72" aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>$5,87,478</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Nworld Group - India</td>
                                                        <td>$159.89</td>
                                                        <td>
                                                            <div class="progress-w-percent mb-0">
                                                                <span class="progress-value">815 </span>
                                                                <div class="progress progress-sm">
                                                                    <div class="progress-bar bg-success" role="progressbar" style="width: 89%;" aria-valuenow="89" aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>$55,781</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div> -->

                                    </div> <!-- end card-->
                                </div> <!-- end col-->
                            </div>
                            <!-- end row-->
                            
                        </div> <!-- container -->

                    </div> <!-- content -->

                    <!-- Footer Start -->
                        <?php include('assets/inc/footer.php');?>
                    <!-- end Footer -->

                </div>
            <?php }?>

            <!-- ============================================================== -->
            <!-- End Page content -->
            <!-- ============================================================== -->


        </div>
        <!-- END wrapper -->

        

        <!-- Right bar overlay-->
        <div class="rightbar-overlay"></div>

        <!-- Vendor js -->
        <script src="assets/js/vendor.min.js"></script>

        <!-- App js -->
        <script src="assets/js/app.min.js"></script>
        
    </body>

</html>