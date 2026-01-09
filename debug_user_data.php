<?php
require_once 'config.php';
session_start();
$user_id = $_SESSION['id'] ?? 'Not Logged In';
echo "Current User ID: $user_id\n";
if ($user_id !== 'Not Logged In') {
    $res = mysqli_query($link, "SELECT * FROM completed_workouts WHERE user_id = $user_id ORDER BY id DESC LIMIT 5");
    while ($row = mysqli_fetch_assoc($res)) {
        print_r($row);
    }
}
?>