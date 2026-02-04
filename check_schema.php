<?php
require_once "config.php";

$result = mysqli_query($link, "DESCRIBE messages");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "Error describing table: " . mysqli_error($link);
}
?>