<?php
require_once 'config.php';

$sql = "SELECT t1.id FROM transactions t1
        INNER JOIN transactions t2 
        ON t1.user_id = t2.user_id 
        AND t1.plan_name = t2.plan_name 
        AND t1.amount = t2.amount 
        AND DATE(t1.created_at) = DATE(t2.created_at)
        WHERE t1.id > t2.id";

$res = mysqli_query($link, $sql);
$ids = [];
while ($row = mysqli_fetch_assoc($res)) {
    $ids[] = $row['id'];
}

if (!empty($ids)) {
    $id_list = implode(',', $ids);
    $del_sql = "DELETE FROM transactions WHERE id IN ($id_list)";
    if (mysqli_query($link, $del_sql)) {
        echo "Cleaned up " . mysqli_affected_rows($link) . " duplicate transactions (IDs: $id_list).";
    } else {
        echo "Error deleting: " . mysqli_error($link);
    }
} else {
    echo "No duplicates found to delete.";
}
?>
