<?php
// razorpay_callback.php - Process Razorpay payment success
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (isset($data['razorpay_payment_id'], $data['uid'], $data['amt'], $data['plan'])) {
        $user_id = (int) $data['uid'];
        $amount = (float) $data['amt'];
        $plan_name = mysqli_real_escape_string($link, $data['plan']);
        $payment_id = mysqli_real_escape_string($link, $data['razorpay_payment_id']);
        $method = 'Razorpay';
        $status = 'completed';
        $token_number = null;

        // Generate a unique token for store purchases
        if (stripos($plan_name, 'Store:') !== false) {
            $token_number = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        }

        $sql = "INSERT INTO transactions (user_id, plan_name, amount, payment_method, status, token_number) VALUES (?, ?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "isdsss", $user_id, $plan_name, $amount, $method, $status, $token_number);

            if (mysqli_stmt_execute($stmt)) {
                $transaction_id = mysqli_insert_id($link);

                // ALSO UPDATE USER MEMBERSHIP STATUS - ONLY IF NOT A STORE PURCHASE AND NOT AN APPOINTMENT
                // Check if it's a membership plan (doesn't contain "Store:" or "Appointment")
                if (stripos($plan_name, 'Store:') === false && stripos($plan_name, 'Appointment') === false) {
                    $duration_days = 30;
                    if (stripos($plan_name, 'Yearly') !== false) {
                        $duration_days = 365;
                    } elseif (stripos($plan_name, 'Quarterly') !== false) {
                        $duration_days = 90;
                    }

                    $expiry_date = date('Y-m-d', strtotime("+$duration_days days"));

                    $upd_sql = "UPDATE users SET membership_status = 'Active', membership_plan = ?, membership_expiry = ? WHERE id = ?";
                    if ($upd = mysqli_prepare($link, $upd_sql)) {
                        mysqli_stmt_bind_param($upd, "ssi", $plan_name, $expiry_date, $user_id);
                        mysqli_stmt_execute($upd);
                    }
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Transaction recorded',
                    'token' => $token_number,
                    'transaction_id' => $transaction_id
                ]);
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