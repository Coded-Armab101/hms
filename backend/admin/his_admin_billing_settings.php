<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$aid = $_SESSION['ad_id'];

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get active tab from URL or default to 'services'
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'services';

// --- SERVICE MANAGEMENT ---
if (isset($_POST['add_service'])) {
    $service_name = $_POST['service_name'];
    $service_desc = $_POST['service_desc'];
    $amount = $_POST['amount'];
    
    // Check if service_code column exists
    $column_check = $mysqli->query("SHOW COLUMNS FROM his_billing_settings LIKE 'service_code'");
    $has_service_code = ($column_check && $column_check->num_rows > 0);
    
    if ($has_service_code) {
        // Generate a unique service code
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $service_name), 0, 3));
        if (empty($prefix)) $prefix = 'SRV';
        $service_code = $prefix . rand(1000, 9999);
        
        // Check if code already exists
        $check_query = "SELECT id FROM his_billing_settings WHERE service_code = ?";
        $check_stmt = $mysqli->prepare($check_query);
        $check_stmt->bind_param('s', $service_code);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        // If code exists, generate a new one
        while ($check_result->num_rows > 0) {
            $service_code = $prefix . rand(1000, 9999);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
        }
        $check_stmt->close();
        
        $query = "INSERT INTO his_billing_settings (service_code, service_name, service_desc, amount) VALUES (?, ?, ?, ?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('sssd', $service_code, $service_name, $service_desc, $amount);
    } else {
        $query = "INSERT INTO his_billing_settings (service_name, service_desc, amount) VALUES (?, ?, ?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('ssd', $service_name, $service_desc, $amount);
    }
    
    if ($stmt->execute()) {
        $success = "Service Added Successfully";
        header("Location: his_admin_billing_settings.php?tab=services");
        exit;
    } else {
        $err = "Please Try Again Or Try Later: " . $stmt->error;
    }
    if ($stmt) $stmt->close();
}

if (isset($_GET['delete_service'])) {
    $id = intval($_GET['delete_service']);
    $adn = "DELETE FROM his_billing_settings WHERE id=?";
    $stmt = $mysqli->prepare($adn);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        $success = "Service Record Deleted";
        header("Location: his_admin_billing_settings.php?tab=services");
        exit;
    } else {
        $err = "Try Again Later: " . $stmt->error;
    }
    $stmt->close();
}

// --- BILL TYPE MANAGEMENT ---
if (isset($_POST['add_bill_type'])) {
    $type_name = $_POST['type_name'];
    $type_desc = $_POST['type_desc'];
    
    // Check if the `type_desc` column exists
    $col_check = $mysqli->query("SHOW COLUMNS FROM his_bill_types LIKE 'type_desc'");
    if ($col_check && $col_check->num_rows > 0) {
        $query = "INSERT INTO his_bill_types (type_name, type_desc) VALUES (?,?)";
        $stmt = $mysqli->prepare($query);
        if ($stmt) $stmt->bind_param('ss', $type_name, $type_desc);
    } else {
        // Fallback: insert only the name if `type_desc` column is not present
        $query = "INSERT INTO his_bill_types (type_name) VALUES (?)";
        $stmt = $mysqli->prepare($query);
        if ($stmt) $stmt->bind_param('s', $type_name);
    }

    if ($stmt && $stmt->execute()) {
        $success = "Bill Type Added Successfully";
        header("Location: his_admin_billing_settings.php?tab=billtypes");
        exit;
    } else {
        $err = "Please Try Again: " . ($stmt ? $stmt->error : $mysqli->error);
    }
    if ($stmt) $stmt->close();
}

if (isset($_GET['delete_bill_type'])) {
    $id = intval($_GET['delete_bill_type']);
    // Determine primary key column name (`id` or `type_id`)
    $pk = 'id';
    $col_res = $mysqli->query("SHOW COLUMNS FROM his_bill_types");
    if ($col_res) {
        $cols = [];
        while ($c = $col_res->fetch_assoc()) {
            $cols[] = $c['Field'];
        }
        if (!in_array('id', $cols) && in_array('type_id', $cols)) {
            $pk = 'type_id';
        }
    }

    $adn = "DELETE FROM his_bill_types WHERE {$pk}=?";
    $stmt = $mysqli->prepare($adn);
    if ($stmt) $stmt->bind_param('i', $id);
    
    if ($stmt) {
        if ($stmt->execute()) {
            $success = "Bill Type Deleted";
            header("Location: his_admin_billing_settings.php?tab=billtypes");
            exit;
        } else {
            $err = "Try Again Later: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $err = "Unable to prepare delete statement: " . $mysqli->error;
    }
}

// --- CSV UPLOAD ---
if (isset($_POST['upload_csv'])) {
    $file = $_FILES['service_csv']['tmp_name'];
    
    if ($file && file_exists($file)) {
        $handle = fopen($file, "r");
        $c = 0;
        $imported = 0;
        $errors = [];
        
        // Check if service_code column exists
        $column_check = $mysqli->query("SHOW COLUMNS FROM his_billing_settings LIKE 'service_code'");
        $has_service_code = ($column_check && $column_check->num_rows > 0);
        
        while (($filesop = fgetcsv($handle, 1000, ",")) !== false) {
            // Skip the first row (Header)
            if ($c > 0) { 
                $service_name = isset($filesop[0]) ? trim($filesop[0]) : '';
                $service_desc = isset($filesop[1]) ? trim($filesop[1]) : '';
                $amount = isset($filesop[2]) ? trim($filesop[2]) : 0;
                
                if (!empty($service_name) && is_numeric($amount)) {
                    if ($has_service_code) {
                        // Generate a unique service code
                        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $service_name), 0, 3));
                        if (empty($prefix)) $prefix = 'SRV';
                        $service_code = $prefix . rand(1000, 9999);
                        
                        $sql = "INSERT INTO his_billing_settings (service_code, service_name, service_desc, amount) VALUES (?, ?, ?, ?)";
                        $stmt = $mysqli->prepare($sql);
                        $stmt->bind_param('sssd', $service_code, $service_name, $service_desc, $amount);
                    } else {
                        $sql = "INSERT INTO his_billing_settings (service_name, service_desc, amount) VALUES (?, ?, ?)";
                        $stmt = $mysqli->prepare($sql);
                        $stmt->bind_param('ssd', $service_name, $service_desc, $amount);
                    }
                    
                    if ($stmt->execute()) {
                        $imported++;
                    } else {
                        $errors[] = "Failed to import: $service_name - " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    if (empty($service_name)) {
                        $errors[] = "Row " . ($c+1) . ": Service name is empty";
                    } elseif (!is_numeric($amount)) {
                        $errors[] = "Row " . ($c+1) . ": Amount must be numeric";
                    }
                }
            }
            $c++;
        }
        fclose($handle);
        
        if ($imported > 0) {
            $success = "$imported services imported successfully!";
            if (!empty($errors)) {
                $success .= " But " . count($errors) . " errors occurred.";
            }
            header("Location: his_admin_billing_settings.php?tab=csv");
            exit;
        } else {
            $err = "Failed to import services. Please check CSV format.";
            if (!empty($errors)) {
                $err .= " Errors: " . implode(", ", array_slice($errors, 0, 5));
                if (count($errors) > 5) $err .= "... and " . (count($errors)-5) . " more errors";
            }
        }
    } else {
        $err = "Please select a valid CSV file.";
    }
}

// Fetch data for display
// For services tab
$services_query = "SELECT * FROM his_billing_settings ORDER BY id DESC";
$services_result = $mysqli->query($services_query);

// For bill types tab
$bill_types_query = "SELECT * FROM his_bill_types ORDER BY id DESC";
$bill_types_result = $mysqli->query($bill_types_query);
?>

<!DOCTYPE html>
<html lang="en">
    
<?php include('assets/inc/head.php');?>

<style>
    /* Tab Content Styles */
    .tab-content {
        display: none;
        padding: 20px 0;
    }
    .tab-content.active {
        display: block;
        animation: fadeIn 0.5s;
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    /* Tab Navigation Styles */
    .nav-pills .nav-link {
        color: #6c757d;
        border-radius: 0;
        padding: 12px 20px;
        font-weight: 500;
    }
    .nav-pills .nav-link.active {
        background-color: #007bff;
        color: white;
    }
    .nav-pills .nav-link:hover:not(.active) {
        background-color: #f8f9fa;
    }
    
    /* File input styling */
    .custom-file-label::after {
        content: "Browse";
    }
    
    /* Table styles */
    .table th {
        background-color: #f8f9fa;
    }
    .service-code-badge {
        background-color: #6c757d;
        color: white;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: normal;
    }
</style>   

<body>
    <div id="wrapper">
        <?php include('assets/inc/nav.php');?>
        <?php include("assets/inc/sidebar.php");?>
        
        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="his_admin_dashboard.php">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Billing</a></li>
                                        <li class="breadcrumb-item active">Settings</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Billing Settings</h4>
                            </div>
                        </div>
                    </div>
                    
                    <?php if(isset($success)): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <strong>Success!</strong> <?php echo htmlspecialchars($success); ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(isset($err)): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error!</strong> <?php echo htmlspecialchars($err); ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="card-box">
                                
                                <!-- TAB NAVIGATION -->
                                <ul class="nav nav-pills navtab-bg nav-justified">
                                    <li class="nav-item">
                                        <a href="his_admin_billing_settings.php?tab=services" 
                                           class="nav-link <?php echo ($active_tab == 'services') ? 'active' : ''; ?>">
                                            <i class="fas fa-cogs mr-1"></i> Manage Services
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="his_admin_billing_settings.php?tab=billtypes" 
                                           class="nav-link <?php echo ($active_tab == 'billtypes') ? 'active' : ''; ?>">
                                            <i class="fas fa-file-invoice-dollar mr-1"></i> Manage Bill Types
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="his_admin_billing_settings.php?tab=csv" 
                                           class="nav-link <?php echo ($active_tab == 'csv') ? 'active' : ''; ?>">
                                            <i class="fas fa-file-upload mr-1"></i> Bulk Import
                                        </a>
                                    </li>
                                </ul>
                                
                                <!-- TAB CONTENT -->
                                <div class="tab-content-container mt-3">
                                    
                                    <!-- Services Tab -->
                                    <div class="tab-content <?php echo ($active_tab == 'services') ? 'active' : ''; ?>" id="services-content">
                                        <div class="row mt-3">
                                            <div class="col-md-4">
                                                <div class="card-box">
                                                    <h4 class="header-title mb-3">Add Service</h4>
                                                    <form method="post">
                                                        <div class="form-group">
                                                            <label>Service Name *</label>
                                                            <input type="text" required name="service_name" class="form-control" placeholder="Enter service name">
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Description</label>
                                                            <textarea name="service_desc" class="form-control" rows="3" placeholder="Optional description"></textarea>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Amount ($) *</label>
                                                            <input type="number" step="0.01" required name="amount" class="form-control" placeholder="0.00" min="0">
                                                        </div>
                                                        <button type="submit" name="add_service" class="btn btn-primary btn-block">
                                                            <i class="fas fa-save mr-1"></i> Save Service
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="card-box">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <h4 class="header-title mb-0">Existing Services</h4>
                                                        <span class="badge badge-primary">Total: 
                                                            <?php 
                                                            $count_query = "SELECT COUNT(*) as total FROM his_billing_settings";
                                                            $count_result = $mysqli->query($count_query);
                                                            $count_row = $count_result->fetch_assoc();
                                                            echo $count_row['total'];
                                                            ?>
                                                        </span>
                                                    </div>
                                                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                                        <table class="table table-bordered table-hover mb-0">
                                                            <thead class="thead-light">
                                                                <tr>
                                                                    <th width="5%">#</th>
                                                                    <th width="20%">Service Code</th>
                                                                    <th width="20%">Name</th>
                                                                    <th width="35%">Description</th>
                                                                    <th width="10%">Amount</th>
                                                                    <th width="10%">Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                if ($services_result && $services_result->num_rows > 0) {
                                                                    $cnt = 1;
                                                                    while($row = $services_result->fetch_assoc()) {
                                                                ?>
                                                                <tr>
                                                                    <td><?php echo $cnt; ?></td>
                                                                    <td>
                                                                        <?php if(isset($row['service_code']) && !empty($row['service_code'])): ?>
                                                                            <span class="service-code-badge"><?php echo htmlspecialchars($row['service_code']); ?></span>
                                                                        <?php else: ?>
                                                                            <span class="text-muted">N/A</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                                                    <td>
                                                                        <?php 
                                                                        if (!empty($row['service_desc'])) {
                                                                            echo htmlspecialchars(substr($row['service_desc'], 0, 100));
                                                                            if (strlen($row['service_desc']) > 100) echo '...';
                                                                        } else {
                                                                            echo '<span class="text-muted">No description</span>';
                                                                        }
                                                                        ?>
                                                                    </td>
                                                                    <td>$<?php echo number_format($row['amount'], 2); ?></td>
                                                                    <td>
                                                                        <a href="his_admin_billing_settings.php?delete_service=<?php echo $row['id']; ?>&tab=services" 
                                                                           class="btn btn-danger btn-sm"
                                                                           onclick="return confirm('Are you sure you want to delete this service?')">
                                                                            <i class="fas fa-trash"></i> Delete
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                                <?php 
                                                                        $cnt++;
                                                                    }
                                                                } else {
                                                                    echo '<tr><td colspan="6" class="text-center text-muted py-4">No services found. Add your first service!</td></tr>';
                                                                }
                                                                ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div> 
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Bill Types Tab -->
                                    <div class="tab-content <?php echo ($active_tab == 'billtypes') ? 'active' : ''; ?>" id="billtypes-content">
                                        <div class="row mt-3">
                                            <div class="col-md-4">
                                                <div class="card-box">
                                                    <h4 class="header-title mb-3">Add Bill Type</h4>
                                                    <form method="post">
                                                        <div class="form-group">
                                                            <label>Type Name *</label>
                                                            <input type="text" required name="type_name" class="form-control" placeholder="Enter bill type name">
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Description</label>
                                                            <textarea name="type_desc" class="form-control" rows="3" placeholder="Optional description"></textarea>
                                                        </div>
                                                        
                                                        <button type="submit" name="add_bill_type" class="btn btn-primary btn-block">
                                                            <i class="fas fa-save mr-1"></i> Save Type
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="card-box">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <h4 class="header-title mb-0">Existing Bill Types</h4>
                                                        <span class="badge badge-primary">Total: 
                                                            <?php 
                                                            $count_query = "SELECT COUNT(*) as total FROM his_bill_types";
                                                            $count_result = $mysqli->query($count_query);
                                                            $count_row = $count_result->fetch_assoc();
                                                            echo $count_row['total'];
                                                            ?>
                                                        </span>
                                                    </div>
                                                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                                        <table class="table table-bordered table-hover mb-0">
                                                            <thead class="thead-light">
                                                                <tr>
                                                                    <th width="5%">#</th>
                                                                    <th width="25%">Type Code</th>
                                                                    <th width="25%">Name</th>
                                                                    <th width="30%">Description</th>                                
                                                                    <th width="15%">Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                if ($bill_types_result && $bill_types_result->num_rows > 0) {
                                                                    $cnt = 1;
                                                                    while($row = $bill_types_result->fetch_assoc()) {
                                                                ?>
                                                                <tr>
                                                                    <td><?php echo $cnt; ?></td>
                                                                    <td>
                                                                        <?php if(isset($row['type_code']) && !empty($row['type_code'])): ?>
                                                                            <span class="service-code-badge"><?php echo htmlspecialchars($row['type_code']); ?></span>
                                                                        <?php else: ?>
                                                                            <span class="text-muted">N/A</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td><?php echo htmlspecialchars($row['type_name']); ?></td>
                                                                    <td>
                                                                        <?php 
                                                                        if (!empty($row['type_desc'])) {
                                                                            echo htmlspecialchars(substr($row['type_desc'], 0, 100));
                                                                            if (strlen($row['type_desc']) > 100) echo '...';
                                                                        } else {
                                                                            echo '<span class="text-muted">No description</span>';
                                                                        }
                                                                        ?>
                                                                    </td>
                                                                    <td>
                                                                        <a href="his_admin_billing_settings.php?delete_bill_type=<?php echo $row['id']; ?>&tab=billtypes" 
                                                                           class="btn btn-danger btn-sm"
                                                                           onclick="return confirm('Are you sure you want to delete this bill type?')">
                                                                            <i class="fas fa-trash"></i> Delete
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                                <?php 
                                                                        $cnt++;
                                                                    }
                                                                } else {
                                                                    echo '<tr><td colspan="5" class="text-center text-muted py-4">No bill types found. Add your first bill type!</td></tr>';
                                                                }
                                                                ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div> 
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- CSV Tab -->
                                    <div class="tab-content <?php echo ($active_tab == 'csv') ? 'active' : ''; ?>" id="csv-content">
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <div class="card-box">
                                                    <h4 class="header-title mb-4">Bulk Import Services</h4>
                                                    
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="card border-primary">
                                                                <div class="card-header bg-primary text-white">
                                                                    <h5 class="card-title mb-0">Upload CSV File</h5>
                                                                </div>
                                                                <div class="card-body">
                                                                    <form method="post" enctype="multipart/form-data">
                                                                        <div class="form-group">
                                                                            <label for="csvFile">Select CSV File *</label>
                                                                            <div class="custom-file">
                                                                                <input type="file" name="service_csv" class="custom-file-input" id="csvFile" required accept=".csv">
                                                                                <label class="custom-file-label" for="csvFile">Choose file...</label>
                                                                            </div>
                                                                            <small class="form-text text-muted">Only .csv files are allowed</small>
                                                                        </div>
                                                                        <button type="submit" name="upload_csv" class="btn btn-primary btn-lg btn-block mt-3">
                                                                            <i class="fas fa-upload mr-2"></i> Upload & Import CSV
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="col-md-6">
                                                            <div class="card border-info">
                                                                <div class="card-header bg-info text-white">
                                                                    <h5 class="card-title mb-0">CSV Format Requirements</h5>
                                                                </div>
                                                                <div class="card-body">
                                                                    <p><strong>File must contain these columns in order:</strong></p>
                                                                    <ol>
                                                                        <li><strong>Service Name</strong> - Required</li>
                                                                        <li><strong>Description</strong> - Optional</li>
                                                                        <li><strong>Amount</strong> - Required (numeric)</li>
                                                                    </ol>
                                                                    <p class="mb-2"><strong>First row must be headers:</strong></p>
                                                                    <code>Service Name,Description,Amount</code>
                                                                    
                                                                    <div class="mt-4">
                                                                        <h6>Sample Data:</h6>
                                                                        <pre class="bg-light p-3 rounded" style="font-size: 12px; max-height: 200px; overflow-y: auto;">
Service Name,Description,Amount
Consultation,Doctor Consultation Fee,50.00
X-Ray,Chest X-Ray Examination,120.00
Blood Test,Complete Blood Count Test,35.50
MRI Scan,Magnetic Resonance Imaging,450.00
Ultrasound,Abdominal Ultrasound,200.00
CT Scan,Computed Tomography Scan,350.00</pre>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="alert alert-info mt-4">
                                                        <h5 class="alert-heading"><i class="fas fa-info-circle mr-2"></i>Import Notes</h5>
                                                        <ul class="mb-0 pl-3">
                                                            <li>CSV file must be UTF-8 encoded</li>
                                                            <li>First row is treated as header and will be skipped</li>
                                                            <li>Service Name and Amount are required fields</li>
                                                            <li>Service codes will be automatically generated if needed</li>
                                                            <li>Maximum file size: 2MB</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div> <!-- end tabs-content -->
                                
                            </div> <!-- end card-box -->
                        </div> <!-- end col -->
                    </div> <!-- end row -->

                </div> <!-- container -->
            </div> <!-- content -->

            <?php include('assets/inc/footer.php');?>
        </div>
    </div>

    <div class="rightbar-overlay"></div>

    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
    
    <script>
        // File input label update
        document.addEventListener('DOMContentLoaded', function() {
            var csvFileInput = document.getElementById('csvFile');
            if (csvFileInput) {
                csvFileInput.addEventListener('change', function(e) {
                    var fileName = e.target.files[0] ? e.target.files[0].name : 'Choose file...';
                    var nextSibling = e.target.nextElementSibling;
                    if (nextSibling) {
                        nextSibling.innerText = fileName;
                    }
                });
            }
            
            // Add confirmation for CSV upload
            var csvForm = document.querySelector('form[action*="upload_csv"]');
            if (csvForm) {
                csvForm.addEventListener('submit', function(e) {
                    var fileInput = document.getElementById('csvFile');
                    if (fileInput && fileInput.files.length > 0) {
                        var fileName = fileInput.files[0].name;
                        if (!fileName.toLowerCase().endsWith('.csv')) {
                            alert('Please select a CSV file (.csv)');
                            e.preventDefault();
                            return false;
                        }
                        return confirm('Are you sure you want to import services from ' + fileName + '?');
                    }
                });
            }
        });
    </script>
    
</body>
</html>