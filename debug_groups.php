<?php
require_once 'config.php';

$sql = "SELECT user_id, plan_name, amount, DATE(created_at) as cdate, COUNT(*) as cnt 
        FROM transactions 
        GROUP BY user_id, plan_name, amount, DATE(created_at)";
$res = mysqli_query($link, $sql);

echo "<table border='1'><tr><th>UID</th><th>Plan</th><th>Amt</th><th>Date</th><th>Count</th></tr>";
while ($row = mysqli_fetch_assoc($res)) {
    echo "<tr>";
    foreach ($row as $val)
        echo "<td>$val</td>";
    echo "</tr>";
}
echo "</table>";
?>