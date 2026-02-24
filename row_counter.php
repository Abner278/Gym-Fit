<?php
require_once 'config.php';
$tables = ['users', 'trainers', 'appointments', 'transactions', 'inventory', 'membership_plans', 'announcements', 'daily_workouts'];
foreach ($tables as $table) {
    try {
        $result = mysqli_query($link, "SELECT COUNT(*) as count FROM $table");
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            echo "$table: " . $row['count'] . " rows\n";
        } else {
            echo "$table: Error - " . mysqli_error($link) . "\n";
        }
    } catch (Exception $e) {
        echo "$table: Exception - " . $e->getMessage() . "\n";
    }
}
?>