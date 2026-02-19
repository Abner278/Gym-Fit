<?php
require_once 'config.php';
$res = mysqli_query($link, "SELECT name FROM membership_plans");
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['name'] . "\n";
}
?>