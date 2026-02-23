<?php
session_start();
include('assets/inc/config.php');
include('assets/inc/checklogin.php');
check_login();
$aid = $_SESSION['doc_id'];

// Mark as attended if GET request is made
if (isset($_GET['app_id'])) {
    $app_id = $_GET['app_id'];
    $query = "UPDATE appointments SET app_status = 'Attended' WHERE app_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $app_id);
    $stmt->execute();
    $stmt->close();

    header('Location: his_doc_view_appointments.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include('assets/inc/head.php'); ?>
<body>
    <div id="wrapper">
        <?php include("assets/inc/nav.php"); ?>
        <?php include("assets/inc/sidebar.php"); ?>

        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="#">Patients</a></li>
                                        <li class="breadcrumb-item active">View Appointments</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Upcoming Appointments</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Appointment Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card-box">
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-0">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Patient Name</th>
                                                <th>Patient File No</th>
                                                <th>Appointment Date</th>
                                                <th>Appointment Time</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql = "SELECT a.app_id, a.app_date, a.app_time, a.app_status, 
                                                           p.pat_fname, p.pat_lname, p.pat_number
                                                    FROM appointments a
                                                    JOIN his_patients p ON a.pat_id = p.pat_id
                                                    WHERE a.app_status = 'Pending'
                                                    ORDER BY a.app_date ASC, a.app_time ASC";

                                            $stmt = $mysqli->prepare($sql);
                                            $stmt->execute();
                                            $result = $stmt->get_result();

                                            $count = 1;
                                            while ($row = $result->fetch_assoc()) {
                                                ?>
                                                <tr>
                                                    <td><?php echo $count++; ?></td>
                                                    <td><?php echo htmlspecialchars($row['pat_fname'] . ' ' . $row['pat_lname']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['pat_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['app_date']); ?></td>
                                                    <td><?php echo !empty($row['app_time']) ? date("g:i A", strtotime($row['app_time'])) : 'Not set'; ?></td>
                                                    <td><?php echo htmlspecialchars($row['app_status']); ?></td>
                                                    <td><a href="his_doc_view_appointments.php?app_id=<?php echo $row['app_id']; ?>" class="badge badge-success"><i class="mdi mdi-eye"></i> Mark Attended</a></td>
                                                </tr>
                                                <?php
                                            }
                                            $stmt->close();
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Appointment Table -->

                </div>
            </div>

            <?php include("assets/inc/footer.php"); ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
</body>
</html>
