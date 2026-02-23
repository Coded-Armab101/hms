<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include('assets/inc/config.php');

// Ensure session variables exist
$student_id = $_SESSION['student_id'] ?? null;
$pat_number = $_SESSION['pat_number'] ?? null;

if (!$student_id || !$pat_number) {
    die("Session data missing.");
}

$ret = "SELECT * FROM his_student WHERE student_id = ? AND pat_number = ?";
$stmt = $mysqli->prepare($ret);
$stmt->bind_param('is', $student_id, $pat_number);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_object()) {
?>
<!-- Start Navbar -->
<div class="navbar-custom">
    <ul class="list-unstyled topnav-menu float-right mb-0">

        <!-- Search Bar (optional) -->
        <li class="d-none d-sm-block">
            <form class="app-search">
                <div class="app-search-box">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search...">
                        <div class="input-group-append">
                            <button class="btn" type="submit"><i class="fe-search"></i></button>
                        </div>
                    </div>
                </div>
            </form>
        </li>

        <!-- Dropdown User Menu -->
        <li class="nav-item dropdown notification-list">
            <a class="nav-link dropdown-toggle nav-user mr-0 waves-effect waves-light"
               href="#" id="dropdownUser"
               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="pro-user-name ml-1">
                    <?php echo htmlspecialchars($row->pat_number); ?> <i class="mdi mdi-chevron-down"></i>
                </span>
            </a>
            <div class="dropdown-menu dropdown-menu-right profile-dropdown" aria-labelledby="dropdownUser">
                <a href="his_student_logout_partial.php" class="dropdown-item notify-item">
                    <i class="fe-log-out"></i>
                    <span>Logout</span>
                </a>
            </div>
        </li>
    </ul>

    <!-- Logo Box -->
    <div class="logo-box">
        <a href="#" class="logo text-center">
            <span class="logo-lg">
                <img src="assets/images/logo.png" alt="Logo" height="18">
            </span>
            <span class="logo-sm">
                <img src="assets/images/logo-sm-white.png" alt="Logo Small" height="18">
            </span>
        </a>
    </div>

    <!-- Menu Button -->
    <ul class="list-unstyled topnav-menu topnav-menu-left m-0">
        <li>
            <button class="button-menu-mobile waves-effect waves-light">
                <i class="fe-menu"></i>
            </button>
        </li>
    </ul>
</div>

<!-- Scripts to ensure dropdown works -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" />
<?php
} // end while
?>
