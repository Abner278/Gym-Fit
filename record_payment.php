<?php
// record_payment.php - Backend to save simulated GPay transactions
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (isset($data['uid'], $data['amt'], $data['plan'])) {
        $user_id = (int) $data['uid'];
        $amount = (float) $data['amt'];
        $plan_name = mysqli_real_escape_string($link, $data['plan']);
        $method = 'GPay';
        $status = 'completed';

        $sql = "INSERT INTO transactions (user_id, plan_name, amount, payment_method, status) VALUES (?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "isdss", $user_id, $plan_name, $amount, $method, $status);

            if (mysqli_stmt_execute($stmt)) {

                // ALSO UPDATE USER MEMBERSHIP STATUS
                // Since they paid, we should update their membership plan and expiry
                // Calculate new expiry (assuming 1 month for Monthly, 12 for Yearly etc, or standard logic)
                // For simplicity, just add 30 days if it's Monthly, 365 if Yearly, else 30 days default

                $duration_days = 30;
                if (stripos($plan_name, 'Yearly') !== false) {
                    $duration_days = 365;
                } elseif (stripos($plan_name, 'Quarterly') !== false) {
                    $duration_days = 90;
                } else if (stripos($plan_name, 'Weekly') !== false) {
                    $duration_days = 7;
                }

                $expiry_date = date('Y-m-d', strtotime("+$duration_days days"));

                // Fetch current expiry to extend if active? 
                // For now, simplify: just set new expiry from today or extend current logic 
                // (Assuming simple overwrite or extend for demo)

                $upd_sql = "UPDATE users SET membership_status = 'Active', membership_plan = ?, membership_expiry = ? WHERE id = ?";
                if ($upd = mysqli_prepare($link, $upd_sql)) {
                    mysqli_stmt_bind_param($upd, "ssi", $plan_name, $expiry_date, $user_id);
                    mysqli_stmt_execute($upd);
                }

                echo json_encode(['status' => 'success', 'message' => 'Transaction recorded']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($link)]);
            }
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>