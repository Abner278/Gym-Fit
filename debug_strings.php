<?php
require_once 'config.php';

$sql = "SELECT id, plan_name, LENGTH(plan_name) as len FROM transactions WHERE DATE(created_at) = '2026-02-24'";
$res = mysqli_query($link, $sql);

while ($row = mysqli_fetch_assoc($res)) {
    echo "ID: " . $row['id'] . " | Plan: '" . $row['plan_name'] . "' | Len: " . $row['len'] . "<br>";
}
?>