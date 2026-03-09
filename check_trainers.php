<?php
include 'config.php';
$res = mysqli_query($link, "SELECT name FROM trainers");
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['name'] . "\n";
}
?>