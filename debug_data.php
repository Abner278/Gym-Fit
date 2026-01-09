<?php
require_once 'config.php';
session_start();
// Since I can't easily get the user's session ID here without them being logged in, 
// I'll just check the last few entries in the table.
$res = mysqli_query($link, "SELECT * FROM completed_workouts ORDER BY id DESC LIMIT 10");
while ($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>