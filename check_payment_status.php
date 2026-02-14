<?php
require_once 'config.php';

header('Content-Type: application/json');

if (isset($_GET['uid']) && isset($_GET['plan'])) {
    $uid = (int) $_GET['uid'];
    $plan_term = "%" . $_GET['plan'] . "%"; // e.g. "Store: Gold Standard%"

    // Check for a transaction in the last 2 minutes matching this user and plan (approximate)
    // We use a short time window so we don't pick up old payments
    $sql = "SELECT id FROM transactions WHERE user_id = ? AND plan_name LIKE ? AND created_at >= NOW() - INTERVAL 2 MINUTE ORDER BY id DESC LIMIT 1";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "is", $uid, $plan_term);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            echo json_encode(['status' => 'paid']);
        } else {
            echo json_encode(['status' => 'waiting']);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($link)]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
}
?>