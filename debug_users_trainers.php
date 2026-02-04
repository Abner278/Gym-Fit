<?php
require_once 'config.php';
echo "USERS:\n";
$u = mysqli_query($link, "SELECT id, full_name, role, email FROM users WHERE role='staff'");
while ($row = mysqli_fetch_assoc($u)) {
    print_r($row);
}
echo "\nTRAINERS:\n";
$t = mysqli_query($link, "SELECT * FROM trainers");
while ($row = mysqli_fetch_assoc($t)) {
    print_r($row);
}
?>