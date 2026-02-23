<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$aid = $_SESSION['ad_id'];

// Handle delete
if (isset($_GET['delete_pres_id'])) {
    $id = intval($_GET['delete_pres_id']);
    $delete_query = "DELETE FROM his_prescriptions WHERE pres_id = ?";
    $delete_stmt = $mysqli->prepare($delete_query);
    $delete_stmt->bind_param('i', $id);
    
    if ($delete_stmt->execute()) {
        $success = "Prescription deleted successfully!";
    } else {
        $err = "Error deleting record: " . $delete_stmt->error;
    }
    $delete_stmt->close();
}

// Handle update of prescription via AJAX/modal
if (isset($_POST['update_prescription_modal'])) {
    $pres_id = intval($_POST['pres_id']);
    $pres_pat_ailment = $_POST['pres_pat_ailment'];
    $pres_ins = $_POST['pres_ins'];
    $pres_status = $_POST['pres_status'];
    
    $update_query = "UPDATE his_prescriptions SET 
                     pres_pat_ailment = ?,
                     pres_ins = ?,
                     pres_status = ?,
                     pres_date = NOW()
                     WHERE pres_id = ?";
    $update_stmt = $mysqli->prepare($update_query);
    $update_stmt->bind_param('sssi', $pres_pat_ailment, $pres_ins, $pres_status, $pres_id);
    
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Prescription updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating record: ' . $update_stmt->error]);
    }
    $update_stmt->close();
    exit();
}

// Handle delete of prescription via AJAX
if (isset($_POST['delete_prescription'])) {
    $id = intval($_POST['id']);
    $delete_query = "DELETE FROM his_prescriptions WHERE pres_id = ?";
    $delete_stmt = $mysqli->prepare($delete_query);
    $delete_stmt->bind_param('i', $id);
    
    if ($delete_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Prescription deleted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting record: ' . $delete_stmt->error]);
    }
    $delete_stmt->close();
    exit();
}

// Fetch all prescriptions
$query_prescriptions = "SELECT p.*, 
                        CONCAT(pat.pat_fname, ' ', pat.pat_lname) as patient_registered_name,
                        pat.pat_phone,
                        pat.pat_addr
                        FROM his_prescriptions p
                        LEFT JOIN his_patients pat ON p.pat_number = pat.pat_number
                        ORDER BY p.pres_date DESC";
$stmt_prescriptions = $mysqli->prepare($query_prescriptions);
$stmt_prescriptions->execute();
$res_prescriptions = $stmt_prescriptions->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<?php include('assets/inc/head.php'); ?>

<style>
    .edit-btn, .delete-btn, .view-btn {
        cursor: pointer;
        margin: 0 2px;
    }
    .modal-content {
        border-radius: 10px;
    }
    .modal-header {
        background-color: #007bff;
        color: white;
        border-radius: 10px 10px 0 0;
    }
    .modal-header.bg-warning {
        background-color: #ffc107 !important;
        color: #212529;
    }
    .modal-header.bg-info {
        background-color: #17a2b8 !important;
    }
    .close {
        color: white;
    }
    .close:hover {
        color: #f8f9fa;
    }
    .badge-pending {
        background-color: #ffc107;
        color: #212529;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
    }
    .badge-dispensed {
        background-color: #28a745;
        color: white;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
    }
    .badge-cancelled {
        background-color: #dc3545;
        color: white;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
    }
    .table th {
        background-color: #f8f9fa;
    }
    .action-btn {
        margin: 0 2px;
    }
    .prescription-ins {
        max-width: 200px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

<body>
    <div id="wrapper">
        <?php include('assets/inc/nav.php'); ?>
        <?php include('assets/inc/sidebar.php'); ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">

                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title">
                                    <i class="mdi mdi-pill"></i> Manage Prescriptions
                                </h4>
                                <div class="page-title-right">
                                    <a href="his_admin_add_single_pres.php" class="btn btn-primary btn-sm">
                                        <i class="mdi mdi-plus"></i> New Prescription
                                    </a>
                                    <a href="his_admin_dashboard.php" class="btn btn-secondary btn-sm">
                                        <i class="mdi mdi-arrow-left"></i> Dashboard
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Success/Error Messages -->
                    <?php if(isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="mdi mdi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php endif; ?>

                    <?php if(isset($err)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="mdi mdi-alert-circle"></i> <?php echo htmlspecialchars($err); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php endif; ?>

                    <!-- Search and Filter Bar -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Search</label>
                                                <input type="text" id="searchInput" class="form-control" placeholder="Search by patient name or number...">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Filter by Status</label>
                                                <select id="statusFilter" class="form-control">
                                                    <option value="">All Status</option>
                                                    <option value="pending">Pending</option>
                                                    <option value="dispensed">Dispensed</option>
                                                    <option value="cancelled">Cancelled</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Sort By</label>
                                                <select id="sortBy" class="form-control">
                                                    <option value="date_desc">Newest First</option>
                                                    <option value="date_asc">Oldest First</option>
                                                    <option value="name_asc">Patient Name A-Z</option>
                                                    <option value="name_desc">Patient Name Z-A</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>&nbsp;</label>
                                                <button id="clearFilters" class="btn btn-secondary btn-block">
                                                    <i class="mdi mdi-close"></i> Clear
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Prescriptions Table -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="mdi mdi-format-list-bulleted"></i> Prescriptions List</h5>
                                    <span class="badge badge-light">Total: <?php echo $res_prescriptions->num_rows; ?></span>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover" id="prescriptions-table">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Patient Name</th>
                                                    <th>Patient No</th>
                                                    <th>Contact</th>
                                                    <th>Ailment</th>
                                                    <th>Instructions</th>
                                                    <th>Date</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($res_prescriptions && $res_prescriptions->num_rows > 0) { 
                                                    $cnt = 1;
                                                    while ($row = $res_prescriptions->fetch_object()) { 
                                                        // Determine status badge class
                                                        $status_class = 'badge-pending';
                                                        $status_text = 'Pending';
                                                        if(isset($row->pres_status)) {
                                                            if($row->pres_status == 'dispensed') {
                                                                $status_class = 'badge-dispensed';
                                                                $status_text = 'Dispensed';
                                                            } elseif($row->pres_status == 'cancelled') {
                                                                $status_class = 'badge-cancelled';
                                                                $status_text = 'Cancelled';
                                                            }
                                                        }
                                                ?>
                                                <tr id="prescription-row-<?php echo $row->pres_id; ?>">
                                                    <td><?php echo $cnt++; ?></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($row->pres_pat_name ?? 'N/A'); ?></strong>
                                                        <?php if(!empty($row->patient_registered_name) && $row->patient_registered_name != $row->pres_pat_name): ?>
                                                            <br><small class="text-muted">(Registered: <?php echo htmlspecialchars($row->patient_registered_name); ?>)</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info"><?php echo htmlspecialchars($row->pat_number ?? 'N/A'); ?></span>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($row->pat_phone ?? 'N/A'); ?>
                                                        <?php if(!empty($row->pat_addr)): ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($row->pat_addr, 0, 20)); ?>...</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="prescription-ins">
                                                        <?php 
                                                        $ailment = $row->pres_pat_ailment ?? '';
                                                        echo htmlspecialchars(substr($ailment, 0, 30));
                                                        if(strlen($ailment) > 30) echo '...';
                                                        ?>
                                                    </td>
                                                    <td class="prescription-ins">
                                                        <?php 
                                                        $ins = $row->pres_ins ?? '';
                                                        echo htmlspecialchars(substr($ins, 0, 30));
                                                        if(strlen($ins) > 30) echo '...';
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        if(!empty($row->pres_date)) {
                                                            echo date('d/m/Y H:i', strtotime($row->pres_date));
                                                        } else {
                                                            echo 'N/A';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-success btn-sm view-btn" 
                                                                onclick="viewPrescription(<?php echo $row->pres_id; ?>, '<?php echo htmlspecialchars($row->pat_number ?? ''); ?>')"
                                                                title="View Details"
                                                                <?php echo empty($row->pat_number) ? 'disabled' : ''; ?>>
                                                            <i class="mdi mdi-eye"></i>
                                                        </button>
                                                        <button class="btn btn-warning btn-sm edit-btn" 
                                                                onclick="openEditModal(<?php echo $row->pres_id; ?>, 
                                                                                        '<?php echo htmlspecialchars(addslashes($row->pres_pat_ailment ?? '')); ?>', 
                                                                                        '<?php echo htmlspecialchars(addslashes($row->pres_ins ?? '')); ?>', 
                                                                                        '<?php echo htmlspecialchars($row->pres_status ?? 'pending'); ?>')"
                                                                title="Edit Prescription">
                                                            <i class="mdi mdi-pencil"></i>
                                                        </button>
                                                        <button class="btn btn-danger btn-sm delete-btn" 
                                                                onclick="deletePrescription(<?php echo $row->pres_id; ?>)"
                                                                title="Delete">
                                                            <i class="mdi mdi-delete"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php } 
                                                } else { ?>
                                                <tr>
                                                    <td colspan="9" class="text-center text-muted py-4">
                                                        <i class="mdi mdi-alert-circle" style="font-size: 48px;"></i>
                                                        <h5 class="mt-2">No prescriptions found</h5>
                                                        <p class="mb-0">Click the "New Prescription" button to add one.</p>
                                                    </td>
                                                </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- container -->
            </div> <!-- content -->

            <?php include('assets/inc/footer.php'); ?>
        </div>
    </div>

    <!-- Edit Prescription Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="editModalLabel">
                        <i class="mdi mdi-pencil"></i> Edit Prescription
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editForm">
                    <div class="modal-body">
                        <input type="hidden" name="pres_id" id="edit_pres_id">
                        
                        <div class="form-group">
                            <label>Diagnosis / Ailment <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="pres_pat_ailment" id="edit_pres_pat_ailment" 
                                   placeholder="Enter diagnosis or ailment" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Prescription Instructions <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="pres_ins" id="edit_pres_ins" rows="5" 
                                      placeholder="Enter prescription details and instructions" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Status</label>
                            <select class="form-control" name="pres_status" id="edit_pres_status">
                                <option value="pending">Pending</option>
                                <option value="dispensed">Dispensed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="mdi mdi-information"></i> 
                            <strong>Note:</strong> Changing status to "Dispensed" means medications have been given to the patient.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="mdi mdi-close"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="saveEditBtn">
                            <i class="mdi mdi-content-save"></i> Update Prescription
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="viewModalLabel">
                        <i class="mdi mdi-eye"></i> Prescription Details
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="viewModalBody">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="mdi mdi-close"></i> Close
                    </button>
                    <a href="#" id="viewFullDetailsBtn" class="btn btn-primary" target="_blank">
                        <i class="mdi mdi-open-in-new"></i> View Full Details
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast for notifications -->
    <div class="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>

    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
    
    <script>
        // Open edit modal
        function openEditModal(pres_id, ailment, instructions, status) {
            $('#edit_pres_id').val(pres_id);
            $('#edit_pres_pat_ailment').val(ailment);
            $('#edit_pres_ins').val(instructions);
            $('#edit_pres_status').val(status);
            
            $('#editModal').modal('show');
        }
        
        // Handle edit form submission
        $('#editForm').on('submit', function(e) {
            e.preventDefault();
            
            var formData = {
                pres_id: $('#edit_pres_id').val(),
                pres_pat_ailment: $('#edit_pres_pat_ailment').val(),
                pres_ins: $('#edit_pres_ins').val(),
                pres_status: $('#edit_pres_status').val(),
                update_prescription_modal: true
            };
            
            $('#saveEditBtn').html('<i class="mdi mdi-loading mdi-spin"></i> Updating...');
            $('#saveEditBtn').prop('disabled', true);
            
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('success', response.message);
                        $('#editModal').modal('hide');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showToast('error', response.message);
                    }
                },
                error: function() {
                    showToast('error', 'An error occurred while updating');
                },
                complete: function() {
                    $('#saveEditBtn').html('<i class="mdi mdi-content-save"></i> Update Prescription');
                    $('#saveEditBtn').prop('disabled', false);
                }
            });
        });
        
        // View prescription details
        function viewPrescription(pres_id, pat_number) {
            if (!pat_number) {
                showToast('error', 'Invalid patient number');
                return;
            }
            
            // Open in new tab
            window.open('his_admin_view_single_pres.php?pat_number=' + encodeURIComponent(pat_number), '_blank');
        }
        
        // Delete prescription
        function deletePrescription(id) {
            if (confirm('Are you sure you want to delete this prescription?')) {
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: {
                        id: id,
                        delete_prescription: true
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showToast('success', response.message);
                            $('#prescription-row-' + id).fadeOut(500, function() {
                                $(this).remove();
                                if ($('#prescriptions-table tbody tr').length === 0) {
                                    location.reload();
                                }
                            });
                        } else {
                            showToast('error', response.message);
                        }
                    },
                    error: function() {
                        showToast('error', 'An error occurred while deleting');
                    }
                });
            }
        }
        
        // Search functionality
        $('#searchInput').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('#prescriptions-table tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
        
        // Status filter
        $('#statusFilter').on('change', function() {
            var status = $(this).val().toLowerCase();
            if (status === '') {
                $('#prescriptions-table tbody tr').show();
            } else {
                $('#prescriptions-table tbody tr').each(function() {
                    var rowStatus = $(this).find('td:eq(7) span').text().toLowerCase().trim();
                    if (rowStatus === status) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        });
        
        // Sort functionality
        $('#sortBy').on('change', function() {
            var sortBy = $(this).val();
            var rows = $('#prescriptions-table tbody tr').get();
            
            rows.sort(function(a, b) {
                var aVal, bVal;
                
                switch(sortBy) {
                    case 'date_desc':
                        aVal = new Date($(a).find('td:eq(6)').text());
                        bVal = new Date($(b).find('td:eq(6)').text());
                        return bVal - aVal;
                    case 'date_asc':
                        aVal = new Date($(a).find('td:eq(6)').text());
                        bVal = new Date($(b).find('td:eq(6)').text());
                        return aVal - bVal;
                    case 'name_asc':
                        aVal = $(a).find('td:eq(1)').text().toLowerCase();
                        bVal = $(b).find('td:eq(1)').text().toLowerCase();
                        return aVal.localeCompare(bVal);
                    case 'name_desc':
                        aVal = $(a).find('td:eq(1)').text().toLowerCase();
                        bVal = $(b).find('td:eq(1)').text().toLowerCase();
                        return bVal.localeCompare(aVal);
                    default:
                        return 0;
                }
            });
            
            $.each(rows, function(index, row) {
                $('#prescriptions-table tbody').append(row);
            });
        });
        
        // Clear filters
        $('#clearFilters').on('click', function() {
            $('#searchInput').val('');
            $('#statusFilter').val('');
            $('#sortBy').val('date_desc');
            $('#prescriptions-table tbody tr').show();
        });
        
        // Show toast notification
        function showToast(type, message) {
            var bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
            var toastHtml = '<div class="toast show" role="alert" aria-live="assertive" aria-atomic="true" style="min-width: 300px;">' +
                '<div class="toast-header ' + bgClass + ' text-white">' +
                '<strong class="mr-auto">' + (type === 'success' ? 'Success' : 'Error') + '</strong>' +
                '<small>just now</small>' +
                '<button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast" aria-label="Close">' +
                '<span aria-hidden="true">&times;</span>' +
                '</button>' +
                '</div>' +
                '<div class="toast-body">' + message + '</div>' +
                '</div>';
            
            $('.toast-container').html(toastHtml);
            $('.toast').toast({ delay: 3000 });
            $('.toast').toast('show');
            
            setTimeout(function() {
                $('.toast-container').empty();
            }, 3000);
        }
    </script>
</body>
</html>