<?php
require_once 'config.php';

$sql = "SELECT id, user_id, plan_name, amount, payment_method, created_at FROM transactions ORDER BY created_at DESC LIMIT 20";
$res = mysqli_query($link, $sql);

echo "<table border='1'><tr><th>ID</th><th>UID</th><th>Plan</th><th>Amt</th><th>Method</th><th>Date</th></tr>";
while ($row = mysqli_fetch_assoc($res)) {
    echo "<tr>";
    foreach ($row as $val)
        echo "<td>$val</td>";
    echo "</tr>";
}
echo "</table>";
?>