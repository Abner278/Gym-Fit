<?php
require_once "config.php";
$res = mysqli_query($link, 'DESCRIBE users');
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . "\n";
}
?>