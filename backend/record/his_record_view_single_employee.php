<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();

$aid = $_SESSION['ad_id'];

// Initialize variables
$employee = null;

// Debug output
error_log("DEBUG: GET parameters: " . print_r($_GET, true));

// Determine which parameters we have
if (isset($_GET['staff_id']) && isset($_GET['staff_number'])) {
    $staff_id = $_GET['staff_id'];
    $staff_number = $_GET['staff_number'];
    
    error_log("DEBUG: Searching for staff_id: $staff_id, staff_number: $staff_number");
    
    // SIMPLE FIX: Search by employee number ONLY (most reliable)
    $employee = findEmployeeByNumberOnly($staff_number, $mysqli);
    
    if ($employee) {
        error_log("DEBUG: Found employee: {$employee->emp_type} from {$employee->emp_table}");
    } else {
        error_log("DEBUG: Employee not found by number, trying ID");
        $employee = findEmployeeByIdAndNumber($staff_id, $staff_number, $mysqli);
    }
    
    if (!$employee) {
        $_SESSION['error'] = "Employee not found! Number: $staff_number";
        header("Location: his_record_view_employees.php");
        exit();
    }
    
} else {
    $_SESSION['error'] = "Please select an employee to view details.";
    header("Location: his_record_dashboard.php");
    exit();
}

// If employee is still null, redirect
if (!$employee) {
    $_SESSION['error'] = "Unable to load employee details.";
    header("Location: his_record_dashboard.php");
    exit();
}

/**
 * SIMPLE FIX: Search by employee number ONLY
 * Employee numbers should be unique across the system
 */
function findEmployeeByNumberOnly($employee_number, $mysqli) {
    // Try each table
    $tables = [
        ['his_nurse', 'ns', 'Nurse'],
        ['his_docs', 'doc', 'Doctor'],
        ['his_record', 'rec', 'Receptionist'],
        ['his_pharm', 'pharm', 'Pharmacist'],
        ['his_lab_scist', 'lab', 'Lab Scientist']
    ];
    
    foreach ($tables as $table_info) {
        $employee = searchEmployeeByNumber($mysqli, $table_info[0], $table_info[1], $employee_number, $table_info[2]);
        if ($employee) {
            error_log("DEBUG: Found in {$table_info[0]} as {$table_info[2]}");
            return $employee;
        }
    }
    
    return null;
}

/**
 * Search employee by number in a specific table
 */
function searchEmployeeByNumber($mysqli, $table, $prefix, $number, $type) {
    $number_column = "{$prefix}_number";
    
    // Different queries for different table structures
    if ($table == 'his_docs') {
        $query = "SELECT 
                    doc_id AS emp_id,
                    doc_fname AS emp_fname,
                    doc_lname AS emp_lname,
                    doc_number AS emp_number,
                    doc_email AS emp_email,
                    doc_dpic AS emp_pic,
                    doc_dept AS emp_dept,
                    'Doctor' AS emp_type,
                    'his_docs' AS emp_table
                  FROM his_docs 
                  WHERE doc_number = ?";
    } elseif ($table == 'his_nurse') {
        $query = "SELECT 
                    ns_id AS emp_id,
                    ns_fname AS emp_fname,
                    ns_lname AS emp_lname,
                    ns_number AS emp_number,
                    ns_email AS emp_email,
                    ns_dpic AS emp_pic,
                    ns_dept AS emp_dept,
                    'Nurse' AS emp_type,
                    'his_nurse' AS emp_table
                  FROM his_nurse 
                  WHERE ns_number = ?";
    } elseif ($table == 'his_record') {
        $query = "SELECT 
                    rec_id AS emp_id,
                    rec_fname AS emp_fname,
                    rec_lname AS emp_lname,
                    rec_number AS emp_number,
                    rec_email AS emp_email,
                    '' AS emp_pic,
                    '' AS emp_dept,
                    'Receptionist' AS emp_type,
                    'his_record' AS emp_table
                  FROM his_record 
                  WHERE rec_number = ?";
    } elseif ($table == 'his_pharm') {
        $query = "SELECT 
                    pharm_id AS emp_id,
                    pharm_fname AS emp_fname,
                    pharm_lname AS emp_lname,
                    pharm_number AS emp_number,
                    pharm_email AS emp_email,
                    '' AS emp_pic,
                    '' AS emp_dept,
                    'Pharmacist' AS emp_type,
                    'his_pharm' AS emp_table
                  FROM his_pharm 
                  WHERE pharm_number = ?";
    } elseif ($table == 'his_lab_scist') {
        $query = "SELECT 
                    lab_id AS emp_id,
                    lab_fname AS emp_fname,
                    lab_lname AS emp_lname,
                    lab_number AS emp_number,
                    lab_email AS emp_email,
                    '' AS emp_pic,
                    '' AS emp_dept,
                    'Lab Scientist' AS emp_type,
                    'his_lab_scist' AS emp_table
                  FROM his_lab_scist 
                  WHERE lab_number = ?";
    } else {
        return null;
    }
    
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        error_log("Error preparing query: " . $mysqli->error);
        return null;
    }
    
    $stmt->bind_param('s', $number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $emp = $result->fetch_object();
        $stmt->close();
        return $emp;
    }
    
    $stmt->close();
    return null;
}

/**
 * Fallback: Search by both ID and number
 */
function findEmployeeByIdAndNumber($staff_id, $staff_number, $mysqli) {
    // Check if it's a prefixed ID
    if (strpos($staff_id, '-') !== false) {
        $parts = explode('-', $staff_id);
        if (count($parts) == 2) {
            $prefix = strtoupper($parts[0]);
            $id = $parts[1];
            
            $table_map = [
                'DOC' => ['his_docs', 'doc', 'Doctor'],
                'NUR' => ['his_nurse', 'ns', 'Nurse'],
                'REC' => ['his_record', 'rec', 'Receptionist'],
                'PHARM' => ['his_pharm', 'pharm', 'Pharmacist'],
                'LAB' => ['his_lab_scist', 'lab', 'Lab Scientist']
            ];
            
            if (isset($table_map[$prefix])) {
                $table_info = $table_map[$prefix];
                return searchEmployeeByIdAndNumber($mysqli, $table_info[0], $table_info[1], $id, $staff_number, $table_info[2]);
            }
        }
    }
    
    // If not prefixed, try all tables
    $tables = [
        ['his_nurse', 'ns', 'Nurse'],
        ['his_docs', 'doc', 'Doctor'],
        ['his_record', 'rec', 'Receptionist'],
        ['his_pharm', 'pharm', 'Pharmacist'],
        ['his_lab_scist', 'lab', 'Lab Scientist']
    ];
    
    foreach ($tables as $table_info) {
        $employee = searchEmployeeByIdAndNumber($mysqli, $table_info[0], $table_info[1], $staff_id, $staff_number, $table_info[2]);
        if ($employee) {
            return $employee;
        }
    }
    
    return null;
}

/**
 * Search by both ID and number in a specific table
 */
function searchEmployeeByIdAndNumber($mysqli, $table, $prefix, $id, $number, $type) {
    $id_column = "{$prefix}_id";
    $number_column = "{$prefix}_number";
    
    if ($table == 'his_docs') {
        $query = "SELECT 
                    doc_id AS emp_id,
                    doc_fname AS emp_fname,
                    doc_lname AS emp_lname,
                    doc_number AS emp_number,
                    doc_email AS emp_email,
                    doc_dpic AS emp_pic,
                    doc_dept AS emp_dept,
                    'Doctor' AS emp_type,
                    'his_docs' AS emp_table
                  FROM his_docs 
                  WHERE doc_id = ? AND doc_number = ?";
    } elseif ($table == 'his_nurse') {
        $query = "SELECT 
                    ns_id AS emp_id,
                    ns_fname AS emp_fname,
                    ns_lname AS emp_lname,
                    ns_number AS emp_number,
                    ns_email AS emp_email,
                    ns_dpic AS emp_pic,
                    ns_dept AS emp_dept,
                    'Nurse' AS emp_type,
                    'his_nurse' AS emp_table
                  FROM his_nurse 
                  WHERE ns_id = ? AND ns_number = ?";
    } else {
        $fname_column = "{$prefix}_fname";
        $lname_column = "{$prefix}_lname";
        $email_column = "{$prefix}_email";
        
        $query = "SELECT 
                    $id_column AS emp_id,
                    $fname_column AS emp_fname,
                    $lname_column AS emp_lname,
                    $number_column AS emp_number,
                    $email_column AS emp_email,
                    '' AS emp_pic,
                    '' AS emp_dept,
                    '$type' AS emp_type,
                    '$table' AS emp_table
                  FROM $table 
                  WHERE $id_column = ? AND $number_column = ?";
    }
    
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param('is', $id, $number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $emp = $result->fetch_object();
        $stmt->close();
        return $emp;
    }
    
    $stmt->close();
    return null;
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
                                        <li class="breadcrumb-item"><a href="his_admin_dashboard.php">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="his_admin_view_employees.php">Employees</a></li>
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
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($_SESSION['success']); ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php unset($_SESSION['success']); endif; ?>

                    <?php if(isset($_SESSION['error'])): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($_SESSION['error']); ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php unset($_SESSION['error']); endif; ?>

                    <!-- Debug info - Hidden by default on mobile -->
                    <?php if(isset($_GET['debug'])): ?>
                    <div class="row debug-section">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header" id="debugHeading">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link text-decoration-none" type="button" data-toggle="collapse" data-target="#debugCollapse" aria-expanded="false" aria-controls="debugCollapse">
                                            <i class="mdi mdi-bug"></i> Debug Information
                                        </button>
                                    </h5>
                                </div>
                                <div id="debugCollapse" class="collapse" aria-labelledby="debugHeading">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <tr>
                                                    <th style="width: 200px;">Received Parameters:</th>
                                                    <td><pre class="mb-0" style="white-space: pre-wrap;"><?php print_r($_GET); ?></pre></td>
                                                </tr>
                                                <tr>
                                                    <th>Employee Found:</th>
                                                    <td><?php echo $employee ? 'YES' : 'NO'; ?></td>
                                                </tr>
                                                <?php if($employee): ?>
                                                <tr>
                                                    <th>Employee Type:</th>
                                                    <td><?php echo htmlspecialchars($employee->emp_type); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>From Table:</th>
                                                    <td><?php echo htmlspecialchars($employee->emp_table); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Employee Number:</th>
                                                    <td><?php echo htmlspecialchars($employee->emp_number); ?></td>
                                                </tr>
                                                <?php endif; ?>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Employee Details - Fully Responsive -->
                    <div class="row">
                        <!-- Left Column: Profile Card -->
                        <div class="col-lg-4 col-xl-4 mb-4 mb-lg-0">
                            <div class="card text-center h-100">
                                <div class="card-body d-flex flex-column">
                                    <!-- Profile Image Section -->
                                    <div class="avatar-xl mx-auto mb-3 profile-avatar">
                                        <?php if (!empty($employee->emp_pic) && $employee->emp_pic != ''): ?>
                                            <img src="../doc/assets/images/users/<?php echo htmlspecialchars($employee->emp_pic); ?>" 
                                                 alt="Profile Picture" 
                                                 class="rounded-circle img-thumbnail avatar-xl profile-img">
                                        <?php else: ?>
                                            <div class="avatar-title rounded-circle" style="background-color: <?php echo getEmployeeColor($employee->emp_type); ?>; color: white;">
                                                <?php 
                                                $firstName = $employee->emp_fname ?? '';
                                                echo !empty($firstName) ? strtoupper(substr($firstName, 0, 1)) : 'E'; 
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Employee Name -->
                                    <h4 class="mb-1 text-truncate" title="<?php echo htmlspecialchars($employee->emp_fname . ' ' . $employee->emp_lname); ?>">
                                        <?php echo htmlspecialchars($employee->emp_fname . ' ' . $employee->emp_lname); ?>
                                    </h4>
                                    
                                    <!-- Employee Type Badge -->
                                    <span class="badge badge-pill mx-auto mb-2" style="background-color: <?php echo getEmployeeColor($employee->emp_type); ?>; color: white; padding: 8px 16px;">
                                        <i class="mdi mdi-badge-account-horizontal mr-1"></i>
                                        <?php echo htmlspecialchars($employee->emp_type); ?>
                                    </span>
                                    
                                    <!-- Department (if exists) -->
                                    <?php if (!empty($employee->emp_dept) && $employee->emp_dept != ''): ?>
                                    <p class="text-muted mb-2">
                                        <i class="mdi mdi-hospital-building mr-1"></i>
                                        <?php echo htmlspecialchars($employee->emp_dept); ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <!-- Quick Info Cards (Mobile Optimized) -->
                                    <div class="row mt-3 d-lg-none">
                                        <div class="col-6">
                                            <div class="card bg-light mb-0">
                                                <div class="card-body py-2 px-3">
                                                    <small class="text-muted d-block">Email</small>
                                                    <small class="text-truncate d-block" title="<?php echo htmlspecialchars($employee->emp_email); ?>">
                                                        <?php echo htmlspecialchars($employee->emp_email); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="card bg-light mb-0">
                                                <div class="card-body py-2 px-3">
                                                    <small class="text-muted d-block">Employee ID</small>
                                                    <small class="text-truncate d-block">
                                                        <?php echo htmlspecialchars($employee->emp_number); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Contact Information (Desktop) -->
                                    <div class="text-left mt-3 d-none d-lg-block">
                                        <h5 class="font-13 text-uppercase">Contact Information</h5>
                                        <ul class="list-unstyled mb-0">
                                            <li class="mb-2 d-flex align-items-center">
                                                <i class="mdi mdi-email mr-2 text-primary"></i>
                                                <span class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($employee->emp_email); ?>">
                                                    <?php echo htmlspecialchars($employee->emp_email); ?>
                                                </span>
                                            </li>
                                            <li class="d-flex align-items-center">
                                                <i class="mdi mdi-identifier mr-2 text-primary"></i>
                                                <span class="text-truncate" style="max-width: 200px;">
                                                    <?php echo htmlspecialchars($employee->emp_number); ?>
                                                </span>
                                            </li>
                                        </ul>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="mt-auto pt-3">
                                        <div class="btn-group btn-block" role="group">
                                            <button type="button" class="btn btn-primary" onclick="window.print()">
                                                <i class="mdi mdi-printer mr-1"></i> 
                                                <span class="d-none d-sm-inline">Print</span>
                                            </button>
                                            <a href="his_admin_dashboard.php" class="btn btn-light">
                                                <i class="mdi mdi-arrow-left mr-1"></i>
                                                <span class="d-none d-sm-inline">Dashboard</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- end col -->

                        <!-- Right Column: Details Card -->
                        <div class="col-lg-8 col-xl-8">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h4 class="header-title mb-3">
                                        <i class="mdi mdi-information-outline mr-1"></i> 
                                        Employee Information
                                    </h4>
                                    
                                    <!-- Responsive Table -->
                                    <div class="table-responsive">
                                        <table class="table table-bordered mb-0 employee-details-table">
                                            <tbody>
                                                <tr>
                                                    <th scope="row" class="bg-light" style="width: 35%; min-width: 140px;">Full Name</th>
                                                    <td style="word-break: break-word;">
                                                        <?php echo htmlspecialchars($employee->emp_fname . ' ' . $employee->emp_lname); ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row" class="bg-light">Employee Type</th>
                                                    <td>
                                                        <span class="badge d-inline-block" style="background-color: <?php echo getEmployeeColor($employee->emp_type); ?>; color: white; padding: 6px 12px;">
                                                            <i class="mdi mdi-badge-account-horizontal mr-1"></i>
                                                            <?php echo htmlspecialchars($employee->emp_type); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row" class="bg-light">Employee ID</th>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($employee->emp_number); ?></strong>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row" class="bg-light">Email Address</th>
                                                    <td style="word-break: break-word;">
                                                        <a href="mailto:<?php echo htmlspecialchars($employee->emp_email); ?>" class="text-primary">
                                                            <i class="mdi mdi-email-outline mr-1"></i>
                                                            <?php echo htmlspecialchars($employee->emp_email); ?>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php if (!empty($employee->emp_dept) && $employee->emp_dept != ''): ?>
                                                <tr>
                                                    <th scope="row" class="bg-light">Department</th>
                                                    <td>
                                                        <i class="mdi mdi-hospital-building mr-1"></i>
                                                        <?php echo htmlspecialchars($employee->emp_dept); ?>
                                                    </td>
                                                </tr>
                                                <?php endif; ?>
                                                <tr>
                                                    <th scope="row" class="bg-light">Database Record</th>
                                                    <td>
                                                        <span class="badge badge-light">
                                                            <i class="mdi mdi-database mr-1"></i>
                                                            <?php echo htmlspecialchars($employee->emp_table); ?>
                                                        </span>
                                                        <small class="text-muted ml-1">
                                                            (ID: <?php echo htmlspecialchars($employee->emp_id); ?>)
                                                        </small>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Quick Actions (Mobile Optimized) -->
                                    <div class="mt-4 d-block d-lg-none">
                                        <h5 class="font-13 text-uppercase mb-2">Quick Actions</h5>
                                        <div class="row">
                                            <div class="col-6">
                                                <a href="mailto:<?php echo htmlspecialchars($employee->emp_email); ?>" class="btn btn-outline-primary btn-block mb-2">
                                                    <i class="mdi mdi-email-outline mr-1"></i> Email
                                                </a>
                                            </div>
                                            <div class="col-6">
                                                <a href="his_record_edit_employee.php?staff_id=<?php echo urlencode($staff_id); ?>&staff_number=<?php echo urlencode($staff_number); ?>" 
                                                   class="btn btn-outline-warning btn-block mb-2">
                                                    <i class="mdi mdi-pencil-outline mr-1"></i> Edit
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons (Desktop) -->
                                    <div class="mt-4 d-none d-lg-block">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <a href="his_record_dashboard.php" class="btn btn-outline-primary btn-block">
                                                    <i class="mdi mdi-home-outline mr-1"></i> Dashboard
                                                </a>
                                            </div>
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-outline-success btn-block" onclick="window.print()">
                                                    <i class="mdi mdi-printer-outline mr-1"></i> Print
                                                </button>
                                            </div>
                                        </div>
                                    </div>
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

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- Vendor js -->
    <script src="assets/js/vendor.min.js"></script>

    <!-- App js -->
    <script src="assets/js/app.min.js"></script>
    
    <?php
    function getEmployeeColor($type) {
        $colors = [
            'Doctor' => '#4CAF50',
            'Nurse' => '#2196F3',
            'Receptionist' => '#FF9800',
            'Pharmacist' => '#9C27B0',
            'Lab Scientist' => '#F44336'
        ];
        return $colors[$type] ?? '#607D8B';
    }
    ?>
    
    <style>
        /* Responsive Design Styles */
        @media (max-width: 767.98px) {
            .avatar-xl {
                width: 100px;
                height: 100px;
            }
            .avatar-title {
                width: 100px;
                height: 100px;
                font-size: 40px;
            }
            .page-title-box .page-title {
                font-size: 16px;
            }
            .breadcrumb {
                font-size: 12px;
            }
            .employee-details-table th,
            .employee-details-table td {
                padding: 12px 8px;
            }
        }

        @media (max-width: 575.98px) {
            .avatar-xl {
                width: 80px;
                height: 80px;
            }
            .avatar-title {
                width: 80px;
                height: 80px;
                font-size: 32px;
            }
            .profile-avatar {
                margin-bottom: 12px !important;
            }
            .badge-pill {
                font-size: 12px;
                padding: 6px 12px !important;
            }
            .page-title-box {
                padding: 15px 0;
            }
            .breadcrumb {
                background: transparent;
                padding: 0;
            }
            .btn-group .btn {
                padding: 0.5rem 0.75rem;
            }
        }

        @media (min-width: 768px) and (max-width: 991.98px) {
            .avatar-xl {
                width: 100px;
                height: 100px;
            }
            .avatar-title {
                width: 100px;
                height: 100px;
                font-size: 40px;
            }
        }

        /* Print styles */
        @media print {
            #wrapper .navbar,
            #wrapper .left-side-menu,
            .content-page .content .container-fluid .row:not(:last-child) {
                display: none;
            }
            .content-page {
                margin-left: 0;
            }
            .card {
                border: 1px solid #ddd;
                box-shadow: none;
            }
            .badge {
                border: 1px solid #000;
                color: #000 !important;
                background-color: transparent !important;
            }
            .btn {
                display: none;
            }
        }

        /* Common styles */
        .avatar-xl {
            width: 120px;
            height: 120px;
        }
        .avatar-title {
            width: 120px;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
        }
        .badge-pill {
            padding: 8px 16px;
            font-size: 14px;
        }
        .profile-img {
            object-fit: cover;
            width: 120px;
            height: 120px;
        }
        .text-truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .employee-details-table th {
            background-color: #f8f9fa;
            vertical-align: middle;
        }
        .employee-details-table td {
            vertical-align: middle;
        }
        .debug-section .card-header .btn-link {
            color: #6c757d;
            padding: 0.75rem 1.25rem;
            text-decoration: none;
        }
        .debug-section .card-header .btn-link:hover {
            color: #007bff;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            font-size: 12px;
            max-height: 200px;
            overflow: auto;
        }
        @media (max-width: 576px) {
            pre {
                font-size: 10px;
            }
        }
    </style>
</body>
</html>