<?php
require 'config.php';
$res = mysqli_query($link, "SHOW TABLES");
while ($row = mysqli_fetch_array($res)) {
    echo $row[0] . "\n";
    $cols = mysqli_query($link, "SHOW COLUMNS FROM " . $row[0]);
    while ($c = mysqli_fetch_assoc($cols)) {
        echo "  - " . $c['Field'] . " (" . $c['Type'] . ")\n";
    }
}
