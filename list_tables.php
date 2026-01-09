<?php
require_once 'config.php';
$result = mysqli_query($link, "SHOW TABLES");
while ($row = mysqli_fetch_array($result)) {
    echo $row[0] . "\n";
}
?>