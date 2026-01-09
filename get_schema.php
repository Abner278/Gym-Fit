<?php
require_once 'config.php';
$result = mysqli_query($link, "DESCRIBE users");
while ($row = mysqli_fetch_assoc($result)) {
    print_r($row);
}
?>