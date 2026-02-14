<?php
require_once 'config.php';

$sql = "ALTER TABLE transactions ADD COLUMN is_deleted_by_user TINYINT(1) DEFAULT 0";
$check = mysqli_query($link, "SHOW COLUMNS FROM transactions LIKE 'is_deleted_by_user'");

if (mysqli_num_rows($check) == 0) {
    if (mysqli_query($link, $sql)) {
        echo "Column 'is_deleted_by_user' added successfully.";
    } else {
        echo "Error adding column: " . mysqli_error($link);
    }
} else {
    echo "Column 'is_deleted_by_user' already exists.";
}
?>