<?php
// Start session FIRST
session_start();

// Include configuration and login check
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();

$aid = $_SESSION['ad_id'];

// Handle record deletion
if(isset($_GET['delete_mdr_number'])) {
    $id = intval($_GET['delete_mdr_number']);
    
    // Add confirmation before delete
    if(isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
        $adn = "DELETE FROM his_medical_records WHERE mdr_number = ?";
        $stmt = $mysqli->prepare($adn);
        $stmt->bind_param('i', $id);
        
        if($stmt->execute()) {
            $success = "Medical Record Deleted Successfully";
            // Redirect to avoid resubmission
            header("Location: his_admin_manage_medical_record.php?success=Medical+Record+Deleted+Successfully");
            exit();
        } else {
            $err = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        // Show confirmation dialog
        header("Location: his_admin_manage_medical_record.php?confirm_delete=$id");
        exit();
    }
}

// First, let's check what tables exist in your database
// Comment this out after checking
/*
$tables_query = "SHOW TABLES";
$tables_result = $mysqli->query($tables_query);
echo "<pre>Available Tables:\n";
while($table = $tables_result->fetch_array()) {
    echo $table[0] . "\n";
}
echo "</pre>";
*/

// Modified query without joining non-existent tables
$ret = "SELECT * FROM his_medical_records ORDER BY mdr_id DESC";
        
$stmt = $mysqli->prepare($ret);
if($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    $cnt = 1;
} else {
    $err = "Database query error: " . $mysqli->error;
}
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
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Medical Records</a></li>
                                        <li class="breadcrumb-item active">Manage Medical Records</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Manage Medical Records</h4>
                            </div>
                        </div>
                    </div>     
                    <!-- end page title --> 

                    <!-- Display Success/Error Messages -->
                    <?php if(isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($_GET['success']); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($err)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($err); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Confirmation Dialog for Delete -->
                    <?php if(isset($_GET['confirm_delete'])): 
                        $delete_id = intval($_GET['confirm_delete']);
                    ?>
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <h4 class="alert-heading">Confirm Delete</h4>
                            <p>Are you sure you want to delete this medical record? This action cannot be undone.</p>
                            <hr>
                            <div class="mb-0">
                                <a href="his_admin_manage_medical_record.php?delete_mdr_number=<?php echo $delete_id; ?>&confirm=yes" 
                                   class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash-alt"></i> Yes, Delete It
                                </a>
                                <a href="his_admin_manage_medical_record.php" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="card-box">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <h4 class="header-title">All Medical Records</h4>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <a href="his_admin_add_medical_record.php" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Add New Record
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <div class="row">
                                        <div class="col-12 text-sm-center form-inline">
                                            <div class="form-group">
                                                <input id="demo-foo-search" type="text" placeholder="Search records..." class="form-control form-control-sm" autocomplete="on">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table id="demo-foo-filtering" class="table table-bordered table-hover toggle-circle mb-0" data-page-size="10">
                                        <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th data-toggle="true">Patient Name</th>
                                            <th data-hide="phone">Patient Number</th>
                                            <th data-hide="phone">Ailment</th>
                                            <th data-hide="phone">Prescription</th>
                                            <th data-hide="phone">Age</th>
                                            <th data-hide="phone">Address</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        if(isset($res) && $res->num_rows > 0) {
                                            while($row = $res->fetch_object()) {
                                                // Check what columns actually exist in your table
                                                $patient_name = isset($row->mdr_pat_name) ? $row->mdr_pat_name : 
                                                              (isset($row->patient_name) ? $row->patient_name : 'N/A');
                                                $patient_number = isset($row->mdr_pat_number) ? $row->mdr_pat_number : 
                                                                 (isset($row->patient_number) ? $row->patient_number : 'N/A');
                                                $ailment = isset($row->mdr_pat_ailment) ? $row->mdr_pat_ailment : 
                                                          (isset($row->ailment) ? $row->ailment : 'N/A');
                                                $age = isset($row->mdr_pat_age) ? $row->mdr_pat_age : 
                                                      (isset($row->age) ? $row->age : 'N/A');
                                                $address = isset($row->mdr_pat_adr) ? $row->mdr_pat_adr : 
                                                          (isset($row->address) ? $row->address : 'N/A');
                                                $prescription = isset($row->mdr_prescription) ? $row->mdr_prescription : 
                                                               (isset($row->prescription) ? $row->prescription : '');
                                        ?>
                                                <tr>
                                                    <td><?php echo $cnt; ?></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($patient_name); ?></strong>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($patient_number); ?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($ailment); ?>
                                                    </td>
                                                    <td>
                                                        <?php if(!empty($prescription)): ?>
                                                            <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#prescriptionModal<?php echo $row->mdr_id; ?>">
                                                                View Prescription
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="badge badge-secondary">No prescription</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($age); ?> yrs</td>
                                                    <td>
                                                        <?php echo htmlspecialchars(substr($address, 0, 30)); ?>
                                                        <?php if(strlen($address) > 30): ?>...<?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="his_admin_view_single_medical_record.php?mdr_id=<?php echo $row->mdr_id; ?>&mdr_number=<?php echo $row->mdr_number; ?>" 
                                                               class="btn btn-sm btn-success" title="View Details">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="his_admin_update_single_medical_record.php?mdr_number=<?php echo $row->mdr_number; ?>" 
                                                               class="btn btn-sm btn-warning" title="Edit Record">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="his_admin_manage_medical_record.php?delete_mdr_number=<?php echo $row->mdr_number; ?>" 
                                                               class="btn btn-sm btn-danger" title="Delete Record"
                                                               onclick="return confirm('Are you sure you want to delete this record?');">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                                
                                                <!-- Prescription Modal -->
                                                <?php if(!empty($prescription)): ?>
                                                <div class="modal fade" id="prescriptionModal<?php echo $row->mdr_id; ?>" tabindex="-1" role="dialog" aria-labelledby="prescriptionModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="prescriptionModalLabel">
                                                                    Medical Prescription
                                                                </h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <p><strong>Patient:</strong> <?php echo htmlspecialchars($patient_name); ?></p>
                                                                        <p><strong>Age:</strong> <?php echo htmlspecialchars($age); ?> years</p>
                                                                        <p><strong>Patient ID:</strong> <?php echo htmlspecialchars($patient_number); ?></p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <p><strong>Ailment:</strong> <?php echo htmlspecialchars($ailment); ?></p>
                                                                        <p><strong>Address:</strong> <?php echo htmlspecialchars($address); ?></p>
                                                                    </div>
                                                                </div>
                                                                <hr>
                                                                <h6>Prescription Details:</h6>
                                                                <div class="prescription-content p-3 bg-light rounded">
                                                                    <?php echo nl2br(htmlspecialchars($prescription)); ?>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                                <button type="button" class="btn btn-primary" onclick="printPrescription(<?php echo $row->mdr_id; ?>)">
                                                                    <i class="fas fa-print"></i> Print
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                        <?php 
                                                $cnt++;
                                            }
                                        } else {
                                            echo '<tr><td colspan="8" class="text-center">No medical records found</td></tr>';
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
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- container -->

            </div> <!-- content -->

            <!-- Footer Start -->
            <?php include('assets/inc/footer.php');?>
            <!-- end Footer -->

        </div>
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
    
    <script>
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
    
    // Print prescription function
    function printPrescription(recordId) {
        var printContent = $('#prescriptionModal' + recordId + ' .modal-body').html();
        var originalContent = $('body').html();
        
        $('body').empty().html(printContent);
        window.print();
        $('body').html(originalContent);
        $('#prescriptionModal' + recordId).modal('show');
    }
    
    // Enhanced search functionality
    $(document).ready(function() {
        $('#demo-foo-search').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('table tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    });
    </script>
    
</body>
</html>