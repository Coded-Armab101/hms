<?php
session_start();
include('../assets/inc/config.php');

if (isset($_SESSION['student_id'])) {
  header('Location: his_doc_dashboard.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $matric = trim($_POST['matric_no']);
  $pw     = $_POST['password'];

  if (empty($matric) || empty($pw)) {
    $err = "All fields are required.";
  } else {
    $stmt = $mysqli->prepare("SELECT student_id, password_hash FROM his_student WHERE matric_no=?");
    $stmt->bind_param('s', $matric);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($student_id, $hash);

    if ($stmt->num_rows === 1) {
      $stmt->fetch();
      if (password_verify($pw, $hash)) {
        $_SESSION['student_id']    = $student_id;
        $_SESSION['student_matric'] = $matric;
        header('Location: his_doc_dashboard.php');
        exit;
      } else {
        $err = "Incorrect password.";
      }
    } else {
      $err = "Matric number not found.";
    }
    $stmt->close();
  }
}
?>
<!DOCTYPE html>
<html>
<head><title>Student Login</title></head>
<body>
  <h2>Student Login</h2>
  <?php if (!empty($err)): ?><div style="color:red;"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <form method="post">
    <label>Matric No: <input name="matric_no" required></label><br>
    <label>Password: <input type="password" name="password" required></label><br>
    <button type="submit">Login</button>
  </form>
  <p>Don't have an account? <a href="index.php">Sign up here</a></p>
</body>
</html>
