<?php
require_once 'config.php';
$sql = "ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS membership_plan VARCHAR(50) DEFAULT 'Standard', 
        ADD COLUMN IF NOT EXISTS membership_status VARCHAR(20) DEFAULT 'Active', 
        ADD COLUMN IF NOT EXISTS membership_expiry DATE";
if (mysqli_query($link, $sql)) {
    echo "Table altered successfully\n";
    // Initialize expiry for users who don't have it
    mysqli_query($link, "UPDATE users SET membership_expiry = DATE_ADD(CURDATE(), INTERVAL 29 DAY) WHERE membership_expiry IS NULL");
} else {
    echo "Error: " . mysqli_error($link) . "\n";
}
?>