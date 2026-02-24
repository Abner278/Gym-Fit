<?php
require_once 'config.php';

echo "Database: " . DB_NAME . "<br>";

$check1 = mysqli_query($link, "SELECT id FROM transactions WHERE id IN (77,78)");
echo "Found " . mysqli_num_rows($check1) . " rows before delete.<br>";

if (mysqli_query($link, "DELETE FROM transactions WHERE id IN (77,78)")) {
    echo "Deleted rows. Affected: " . mysqli_affected_rows($link) . "<br>";
} else {
    echo "Error: " . mysqli_error($link) . "<br>";
}

$check2 = mysqli_query($link, "SELECT id FROM transactions WHERE id IN (77,78)");
echo "Found " . mysqli_num_rows($check2) . " rows after delete.<br>";
?>