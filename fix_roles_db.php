<?php
require_once 'config.php';

// Force change role column to VARCHAR to allow 'shop_staff' and any future roles
$sql = "ALTER TABLE users MODIFY COLUMN role VARCHAR(50) DEFAULT 'member'";

if (mysqli_query($link, $sql)) {
    echo "SUCCESS: Role column converted to VARCHAR.";
} else {
    echo "ERROR: " . mysqli_error($link);
}

// Optional: Fix any recently created 'shop_staff' that got defaulted to 'member'?? 
// We can't identify them easily without email.
// But we can enable the column for future inserts.
?>