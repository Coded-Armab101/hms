<?php
$hosts = ['127.0.0.1:3307', 'localhost:3307', 'localhost:3306', '127.0.0.1:3306'];
$users = ['root'];
$passes = ['', 'root', 'admin', 'password'];
$connected = false;

foreach ($hosts as $test_host) {
    foreach ($users as $test_user) {
        foreach ($passes as $test_pass) {
            try {
                $mysqli = new mysqli($test_host, $test_user, $test_pass, 'hmisphp');
                echo "Connected successfully to $test_host with user $test_user\n";
                $connected = true;
                break 3;
            } catch (Exception $e) {
                // Initial connection failed, continue
            }
        }
    }
}

if (!$connected) {
    die("Failed to connect to database with any common combination.\n");
}

// Create his_billing_settings table
$sql1 = "CREATE TABLE IF NOT EXISTS `his_billing_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_name` varchar(200) NOT NULL,
  `service_desc` longtext DEFAULT NULL,
  `amount` float NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if ($mysqli->query($sql1) === TRUE) {
    echo "Table his_billing_settings created successfully.\n";
} else {
    echo "Error creating table his_billing_settings: " . $mysqli->error . "\n";
}

// Create his_patient_bills table
$sql2 = "CREATE TABLE IF NOT EXISTS `his_patient_bills` (
  `bill_id` int(11) NOT NULL AUTO_INCREMENT,
  `pat_id` int(11) NOT NULL,
  `bill_type` varchar(200) NOT NULL COMMENT 'Service or Drug',
  `bill_details` varchar(255) DEFAULT NULL,
  `bill_amount` float NOT NULL,
  `date_generated` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'Unpaid',
  PRIMARY KEY (`bill_id`),
  KEY `pat_id` (`pat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if ($mysqli->query($sql2) === TRUE) {
    echo "Table his_patient_bills created successfully.\n";
} else {
    echo "Error creating table his_patient_bills: " . $mysqli->error . "\n";
}

// Alter his_patients table to add pat_pwd and pat_email if they don't exist
// Checking columns first to avoid errors
$check_pwd = $mysqli->query("SHOW COLUMNS FROM `his_patients` LIKE 'pat_pwd'");
if ($check_pwd->num_rows == 0) {
    $sql3 = "ALTER TABLE `his_patients` ADD `pat_pwd` varchar(255) DEFAULT NULL;";
    if ($mysqli->query($sql3) === TRUE) {
        echo "Column pat_pwd added to his_patients successfully.\n";
    } else {
        echo "Error adding column pat_pwd: " . $mysqli->error . "\n";
    }
} else {
    echo "Column pat_pwd already exists.\n";
}

$check_email = $mysqli->query("SHOW COLUMNS FROM `his_patients` LIKE 'pat_email'");
if ($check_email->num_rows == 0) {
    $sql4 = "ALTER TABLE `his_patients` ADD `pat_email` varchar(200) DEFAULT NULL;";
    if ($mysqli->query($sql4) === TRUE) {
        echo "Column pat_email added to his_patients successfully.\n";
    } else {
        echo "Error adding column pat_email: " . $mysqli->error . "\n";
    }
} else {
    echo "Column pat_email already exists.\n";
}

// Insert some default billing settings
$sql5 = "INSERT INTO `his_billing_settings` (`service_name`, `service_desc`, `amount`) 
         SELECT * FROM (SELECT 'Registration Fee', 'Fee for new patient registration', 500) AS tmp
         WHERE NOT EXISTS (
             SELECT service_name FROM his_billing_settings WHERE service_name = 'Registration Fee'
         ) LIMIT 1;";
$mysqli->query($sql5);

$sql6 = "INSERT INTO `his_billing_settings` (`service_name`, `service_desc`, `amount`) 
         SELECT * FROM (SELECT 'Consultancy Fee', 'General consultancy', 1500) AS tmp
         WHERE NOT EXISTS (
             SELECT service_name FROM his_billing_settings WHERE service_name = 'Consultancy Fee'
         ) LIMIT 1;";
$mysqli->query($sql6);
echo "Default billing settings inserted/verified.\n";


$mysqli->close();
?>
