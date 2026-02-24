<?php
require_once 'config.php';

$sql = "SELECT t1.id, t2.id as matches_id FROM transactions t1
        INNER JOIN transactions t2 
        ON t1.user_id = t2.user_id 
        AND t1.plan_name = t2.plan_name 
        AND t1.amount = t2.amount 
        AND DATE(t1.created_at) = DATE(t2.created_at)
        WHERE t1.id > t2.id";

$res = mysqli_query($link, $sql);
while ($row = mysqli_fetch_assoc($res)) {
    echo "T1 ID: " . $row['id'] . " matches T2 ID: " . $row['matches_id'] . "<br>";
}
?>