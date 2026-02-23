<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
// Suppress the startup warnings if possible by not relying on them being output to stdout
// But we want to see runtime errors.

$dbuser="root";
$dbpass="";
$host="localhost";
$db="hmisphp";

$mysqli = new mysqli($host, $dbuser, $dbpass, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Create his_bill_types table
$sql1 = "CREATE TABLE IF NOT EXISTS `his_bill_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(200) NOT NULL,
  `type_desc` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if ($mysqli->query($sql1) === TRUE) {
    echo "Table his_bill_types created successfully.\n";
} else {
    echo "Error creating table his_bill_types: " . $mysqli->error . "\n";
}

// Seed default data
$defaults = [
    ['name' => 'Consultancy', 'desc' => 'General and Specialist Consultancy'],
    ['name' => 'Laboratory', 'desc' => 'Lab tests and diagnostics'],
    ['name' => 'Pharmaceuticals', 'desc' => 'Drugs and medication'],
    ['name' => 'Surgery', 'desc' => 'Surgical procedures'],
    ['name' => 'Other', 'desc' => 'Miscellaneous charges']
];

foreach ($defaults as $type) {
    $name = $type['name'];
    $desc = $type['desc'];
    
    // Check if exists
    $check_query = "SELECT * FROM his_bill_types WHERE type_name = '$name'";
    $check = $mysqli->query($check_query);
    
    if (!$check) {
         echo "Error checking type $name: " . $mysqli->error . "\n";
         continue;
    }

    if ($check->num_rows == 0) {
        $stmt = $mysqli->prepare("INSERT INTO his_bill_types (type_name, type_desc) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("ss", $name, $desc);
            if ($stmt->execute()) {
                echo "Added bill type: $name\n";
            } else {
                echo "Error adding $name (execute): " . $stmt->error . "\n";
            }
            $stmt->close();
        } else {
            echo "Error adding $name (prepare): " . $mysqli->error . "\n";
        }
    } else {
        echo "Bill type $name already exists.\n";
    }
}

$mysqli->close();
?>
