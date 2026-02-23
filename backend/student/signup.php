<?php
session_start();
include('assets/inc/config.php');

// Initialize error message
$err = "";

// Redirect if already logged in
if (isset($_SESSION['pat_id'])) {
    header('Location: his_patient_dashboard.php');
    exit;
}

// Handle signup form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['patient_signup'])) {
    $pat_number = trim($_POST['pat_number']);
    $pwd        = trim($_POST['password']);
    $pwd2       = trim($_POST['confirm_password']);

    if ($pat_number === '' || $pwd === '' || $pwd2 === '') {
        $err = "All fields are required.";
    } elseif ($pwd !== $pwd2) {
        $err = "Passwords do not match.";
    } else {
        // Check if pat_number exists in his_patients
        $check = $mysqli->prepare("SELECT pat_id FROM his_patients WHERE pat_number = ?");
        $check->bind_param('s', $pat_number);
        $check->execute();
        $check->store_result();
        $check->bind_result($pat_id);
        $check->fetch();

        if ($check->num_rows > 0) {
            // Update password for existing patient
             // Verify if password is already set? Maybe overwrite is fine for now (reset).
            
            $hashed_pwd = sha1(md5($pwd));
            
            $upd = $mysqli->prepare("UPDATE his_patients SET pat_pwd = ? WHERE pat_id = ?");
            $upd->bind_param('si', $hashed_pwd, $pat_id);
            
            if ($upd->execute()) {
                $_SESSION['pat_id'] = $pat_id;
                $_SESSION['pat_number'] = $pat_number;
                header('Location: his_patient_dashboard.php');
                exit;
            } else {
                $err = "Activation failed: " . $mysqli->error;
            }
        } else {
             $err = "Patient Number not found. Please contact the hospital admin.";
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Patient Portal Activation – UHC Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/css/app.min.css" rel="stylesheet" />
    <script src="assets/js/swal.js"></script>
    <?php if (!empty($err)): ?>
    <script>
        setTimeout(function(){ swal("Error", "<?= addslashes($err) ?>", "error"); }, 100);
    </script>
    <?php endif; ?>
</head>
<body class="authentication-bg authentication-bg-pattern">

<div class="account-pages mt-5 mb-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6 col-xl-5">
        <div class="card bg-pattern">
          <div class="card-body p-4">
            <div class="text-center w-75 m-auto">
              <a href="index.php"><img src="assets/images/logo.png" height="50" alt="logo"></a>
              <p class="text-muted mb-4 mt-3">Activate your Patient Portal account.</p>
            </div>
            <form method="post">
              <div class="form-group mb-3">
                <label for="matric">Patient Number</label>
                <input class="form-control" name="pat_number" type="text" id="matric" required placeholder="Enter your Patient Number">
              </div>
              <div class="form-group mb-3">
                <label for="pwd">Set Password</label>
                <input class="form-control" name="password" type="password" id="pwd" required placeholder="Enter strong password">
              </div>
              <div class="form-group mb-3">
                <label for="pwd2">Confirm Password</label>
                <input class="form-control" name="confirm_password" type="password" id="pwd2" required placeholder="Confirm password">
              </div>
              <div class="form-group mb-0 text-center">
                <button class="btn btn-success btn-block" name="patient_signup" type="submit"> Activate Account </button>
              </div>
            </form>
            <div class="text-center mt-3">
                 <p class="text-muted">Already active? <a href="index.php" class="text-primary"><b>Log In</b></a></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="assets/js/vendor.min.js"></script>
<script src="assets/js/app.min.js"></script>
</body>
</html>
