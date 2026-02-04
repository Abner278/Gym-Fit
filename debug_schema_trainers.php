<?php
require_once 'config.php';
$result = mysqli_query($link, "DESCRIBE trainers");
while ($row = mysqli_fetch_assoc($result)) {
    print_r($row);
}
?>