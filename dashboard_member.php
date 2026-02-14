<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== "member") {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["id"];
$message = "";
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

// --- ENSURE DATABASE SCHEMA EXISTS ---
// Ensure attendance table exists
$attendance_sql = "CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'present',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_attendance (user_id, date)
)";
mysqli_query($link, $attendance_sql);

// Ensure appointments table exists
$appt_sql = "CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    trainer_id INT NOT NULL,
    booking_date DATE NOT NULL,
    booking_time VARCHAR(20) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    staff_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($link, $appt_sql);

// Ensure body_measurements table exists
$measure_sql = "CREATE TABLE IF NOT EXISTS body_measurements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    chest DECIMAL(5,2),
    waist DECIMAL(5,2),
    arms DECIMAL(5,2),
    thighs DECIMAL(5,2),
    recorded_at DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_measurement_date (user_id, recorded_at)
)";
mysqli_query($link, $measure_sql);

// Ensure transactions table has is_deleted_by_user column
$check_col = mysqli_query($link, "SHOW COLUMNS FROM transactions LIKE 'is_deleted_by_user'");
if (mysqli_num_rows($check_col) == 0) {
    mysqli_query($link, "ALTER TABLE transactions ADD COLUMN is_deleted_by_user TINYINT(1) DEFAULT 0");
}

// --- AJAX TASK HANDLING ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $res = ["success" => false];

    if ($_POST['ajax_action'] == 'add_task' && !empty($_POST['task_name'])) {
        $task_name = mysqli_real_escape_string($link, $_POST['task_name']);
        if (mysqli_query($link, "INSERT INTO tasks (user_id, task_name) VALUES ($user_id, '$task_name')")) {
            $res = ["success" => true, "id" => mysqli_insert_id($link), "name" => $task_name];
        }
    } elseif ($_POST['ajax_action'] == 'toggle_task') {
        $task_id = (int) $_POST['task_id'];
        if (mysqli_query($link, "UPDATE tasks SET is_done = !is_done WHERE id = $task_id AND user_id = $user_id")) {
            $res = ["success" => true];
        }
    } elseif ($_POST['ajax_action'] == 'delete_transaction') {
        $trans_id = (int) $_POST['trans_id'];
        // Soft delete: Mark as deleted by user, but keep in DB for staff
        if (mysqli_query($link, "UPDATE transactions SET is_deleted_by_user = 1 WHERE id = $trans_id AND user_id = $user_id")) {
            $res = ["success" => true];
        }
    } elseif ($_POST['ajax_action'] == 'delete_task') {
        $task_id = (int) $_POST['task_id'];
        if (mysqli_query($link, "DELETE FROM tasks WHERE id = $task_id AND user_id = $user_id")) {
            $res = ["success" => true];
        }
    } elseif ($_POST['ajax_action'] == 'edit_task') {
        $task_id = (int) $_POST['task_id'];
        $task_name = mysqli_real_escape_string($link, $_POST['task_name']);
        if (mysqli_query($link, "UPDATE tasks SET task_name = '$task_name' WHERE id = $task_id AND user_id = $user_id")) {
            $res = ["success" => true];
        }
    } elseif ($_POST['ajax_action'] == 'check_availability') {
        $trainer_id = (int) $_POST['trainer_id'];
        $date = mysqli_real_escape_string($link, $_POST['date']);
        $time = mysqli_real_escape_string($link, $_POST['time']);

        $check_stmt = mysqli_prepare($link, "SELECT id FROM appointments WHERE trainer_id = ? AND booking_date = ? AND booking_time = ? AND status != 'rejected'");
        mysqli_stmt_bind_param($check_stmt, "iss", $trainer_id, $date, $time);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);

        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $res = ["success" => true, "available" => false];
        } else {
            $res = ["success" => true, "available" => true];
        }
        mysqli_stmt_close($check_stmt);
    } elseif ($_POST['ajax_action'] == 'fetch_messages') {
        $partner_id = (int) $_POST['partner_id'];
        $chat_sql = "SELECT * FROM messages WHERE (sender_id = $user_id AND receiver_id = $partner_id) 
                     OR (sender_id = $partner_id AND receiver_id = $user_id) ORDER BY created_at ASC";

        // Mark messages as read
        mysqli_query($link, "UPDATE messages SET is_read = 1 WHERE sender_id = $partner_id AND receiver_id = $user_id");

        $chat_res = mysqli_query($link, $chat_sql);
        $msgs = [];
        while ($row = mysqli_fetch_assoc($chat_res)) {
            $msgs[] = [
                'id' => $row['id'],
                'sender_id' => $row['sender_id'],
                'message' => $row['message'],
                'time' => date('h:i A', strtotime($row['created_at'])),
                'is_me' => ($row['sender_id'] == $user_id)
            ];
        }
        echo json_encode(['success' => true, 'messages' => $msgs]);
        exit;
    } elseif ($_POST['ajax_action'] == 'send_message') {
        $receiver_id = (int) $_POST['receiver_id'];
        $message = mysqli_real_escape_string($link, $_POST['message']);
        if (!empty($message)) {
            mysqli_query($link, "INSERT INTO messages (sender_id, receiver_id, message) VALUES ($user_id, $receiver_id, '$message')");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    } elseif ($_POST['ajax_action'] == 'delete_message') {
        $msg_id = (int) $_POST['message_id'];
        if (mysqli_query($link, "DELETE FROM messages WHERE id = $msg_id AND sender_id = $user_id")) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }
    echo json_encode($res);
    exit;
}


// --- BODY MEASUREMENTS POST HANDLING ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_measurement'])) {
    $measure_id = (int) $_POST['measure_id'];
    if (mysqli_query($link, "DELETE FROM body_measurements WHERE id = $measure_id AND user_id = $user_id")) {
        $_SESSION['flash_message'] = "Measurement deleted successfully!";
    } else {
        $_SESSION['flash_message'] = "Error deleting measurement.";
    }
    header("location: dashboard_member.php?section=measurements");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['log_measurements'])) {
    $chest = (float) $_POST['chest'];
    $waist = (float) $_POST['waist'];
    $arms = (float) $_POST['arms'];
    $thighs = (float) $_POST['thighs'];
    $date = mysqli_real_escape_string($link, $_POST['date']);

    // Check if entry exists for date
    $check = mysqli_query($link, "SELECT id FROM body_measurements WHERE user_id = $user_id AND recorded_at = '$date'");
    if (mysqli_num_rows($check) > 0) {
        $sql = "UPDATE body_measurements SET chest=$chest, waist=$waist, arms=$arms, thighs=$thighs WHERE user_id=$user_id AND recorded_at='$date'";
    } else {
        $sql = "INSERT INTO body_measurements (user_id, chest, waist, arms, thighs, recorded_at) VALUES ($user_id, $chest, $waist, $arms, $thighs, '$date')";
    }

    if (mysqli_query($link, $sql)) {
        $_SESSION['flash_message'] = "Measurements logged successfully!";
    } else {
        $_SESSION['flash_message'] = "Error logging measurements.";
    }
    // We'll let it fall through to render or redirect. Ideally redirect to avoid resubmission.
    header("location: dashboard_member.php?section=measurements");
    exit;
}

// --- HANDLE GYM STORE ORDER ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_store_order'])) {
    $product_name = mysqli_real_escape_string($link, $_POST['product_name']);
    $product_price = (float) $_POST['product_price'];
    $payment_method = mysqli_real_escape_string($link, $_POST['payment_method']);

    $status = 'pending'; // Cash orders are pending until paid
    $plan_name_db = "Store: " . $product_name;

    $sql = "INSERT INTO transactions (user_id, plan_name, amount, payment_method, status) VALUES (?, ?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "isdss", $user_id, $plan_name_db, $product_price, $payment_method, $status);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['flash_message'] = "Order for $product_name placed successfully! Pay ₹$product_price upon collection.";
        } else {
            $_SESSION['flash_message'] = "Error placing order.";
        }
        mysqli_stmt_close($stmt);
    }
    header("location: dashboard_member.php?section=gym-store");
    exit;
}

// --- HANDLE PROGRESS PHOTO UPLOAD ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_progress_photo'])) {
    $date_taken = mysqli_real_escape_string($link, $_POST['date_taken']);
    $photo_type = mysqli_real_escape_string($link, $_POST['photo_type']);
    $notes = mysqli_real_escape_string($link, $_POST['notes']);

    if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] == 0) {
        $target_dir = "assets/images/progress/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_ext = pathinfo($_FILES["photo_file"]["name"], PATHINFO_EXTENSION);
        $new_filename = "progress_" . $user_id . "_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_filename;

        // Check file type
        $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        if (in_array(strtolower($file_ext), $allowed)) {
            if (move_uploaded_file($_FILES["photo_file"]["tmp_name"], $target_file)) {
                $sql = "INSERT INTO user_progress_photos (user_id, photo_path, photo_type, date_taken, notes) VALUES ($user_id, '$target_file', '$photo_type', '$date_taken', '$notes')";
                if (mysqli_query($link, $sql)) {
                    $_SESSION['flash_message'] = "Progress photo uploaded successfully!";
                } else {
                    $_SESSION['flash_message'] = "Database error: " . mysqli_error($link);
                }
            } else {
                $_SESSION['flash_message'] = "Error uploading file.";
            }
        } else {
            $_SESSION['flash_message'] = "Invalid file type. Only JPG, JPEG, PNG, GIF, WEBP allowed.";
        }
    } else {
        $_SESSION['flash_message'] = "Please select a file to upload.";
    }
    header("location: dashboard_member.php?section=progress-photos");
    exit;
}

// --- HANDLE PROGRESS PHOTO DELETION ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_progress_photo'])) {
    $photo_id = (int) $_POST['photo_id'];

    // Get path to delete file
    $path_query = mysqli_query($link, "SELECT photo_path FROM user_progress_photos WHERE id = $photo_id AND user_id = $user_id");
    if ($row = mysqli_fetch_assoc($path_query)) {
        $file_path = $row['photo_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        mysqli_query($link, "DELETE FROM user_progress_photos WHERE id = $photo_id AND user_id = $user_id");
        $_SESSION['flash_message'] = "Progress photo deleted.";
    }
    header("location: dashboard_member.php?section=progress-photos");
    exit;
}

// Refined Auto-Fix: Restore correct plan from transaction history
if (isset($user_id)) {

    // Find key last valid membership payment to sync status
    $restore_q = mysqli_query($link, "SELECT plan_name FROM transactions WHERE user_id = $user_id AND plan_name != 'Trainer Appointment' ORDER BY created_at DESC LIMIT 1");
    if ($restore_row = mysqli_fetch_assoc($restore_q)) {
        $real_plan = mysqli_real_escape_string($link, $restore_row['plan_name']);
        // Restore plan if it was incorrectly set to Trainer Appointment or fallback Standard
        mysqli_query($link, "UPDATE users SET membership_plan = '$real_plan' WHERE id = $user_id AND (membership_plan = 'Trainer Appointment' OR membership_plan = 'Standard')");
    }
}

// FETCH LATEST USER DATA
$stmt = mysqli_prepare($link, "SELECT full_name, email, profile_image, membership_plan, membership_status, membership_expiry, created_at FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_data = mysqli_stmt_get_result($stmt)->fetch_assoc();
$_SESSION["full_name"] = $user_data["full_name"];
$_SESSION["email"] = $user_data["email"];
$profile_image = $user_data["profile_image"];
$membership_plan = $user_data["membership_plan"] ?? 'Standard';
$membership_status = $user_data["membership_status"] ?? 'Active';
$membership_expiry = $user_data["membership_expiry"];
$join_date = date('Y-m-d', strtotime($user_data['created_at']));

// FETCH TRAINERS
$trainers_query = mysqli_query($link, "SELECT * FROM trainers ORDER BY created_at DESC");


// FETCH LATEST ANNOUNCEMENT
$ann_res = mysqli_query($link, "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 1");
$latest_announcement = mysqli_fetch_assoc($ann_res);

// FETCH MEMBERSHIP PLANS
$plans_res = mysqli_query($link, "SELECT * FROM membership_plans ORDER BY id ASC");

// FETCH MEASUREMENTS
$measure_res = mysqli_query($link, "SELECT * FROM body_measurements WHERE user_id = $user_id ORDER BY recorded_at DESC");

// FETCH PROGRESS PHOTOS
$progress_photos_res = mysqli_query($link, "SELECT * FROM user_progress_photos WHERE user_id = $user_id ORDER BY date_taken DESC");
$progress_photos = [];
while ($row = mysqli_fetch_assoc($progress_photos_res)) {
    $progress_photos[] = $row;
}






// Check if staff_message column exists (schema update)
$check_col = mysqli_query($link, "SHOW COLUMNS FROM appointments LIKE 'staff_message'");
if (mysqli_num_rows($check_col) == 0) {
    mysqli_query($link, "ALTER TABLE appointments ADD COLUMN staff_message TEXT AFTER status");
}

// FETCH APPOINTMENTS
$my_appts_query = "SELECT a.*, t.name as trainer_name, t.image as profile_image 
                   FROM appointments a 
                   JOIN trainers t ON a.trainer_id = t.id 
                   WHERE a.user_id = $user_id 
                   ORDER BY a.booking_date DESC, a.booking_time DESC";
$my_appts_res = mysqli_query($link, $my_appts_query);

// HANDLE ATTENDANCE


// HANDLE APPOINTMENT BOOKING
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_appointment'])) {
    $trainer_id = (int) $_POST['trainer_id'];
    $date = mysqli_real_escape_string($link, $_POST['date']);
    $time = mysqli_real_escape_string($link, $_POST['time']);

    // Check availability
    $check_stmt = mysqli_prepare($link, "SELECT id FROM appointments WHERE trainer_id = ? AND booking_date = ? AND booking_time = ? AND status != 'rejected'");
    mysqli_stmt_bind_param($check_stmt, "iss", $trainer_id, $date, $time);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);

    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        $_SESSION['flash_message'] = "Can't appoint the trainer now because the trainer is with another user. Please choose another time.";
        mysqli_stmt_close($check_stmt);
        header("Location: dashboard_member.php?section=trainers");
        exit;
    }
    mysqli_stmt_close($check_stmt);

    $stmt = mysqli_prepare($link, "INSERT INTO appointments (user_id, trainer_id, booking_date, booking_time) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iiss", $user_id, $trainer_id, $date, $time);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['flash_message'] = "Appointment booked successfully!";

        // Record Transaction if paid
        if (isset($_POST['payment_confirmed']) && $_POST['payment_confirmed'] == '1') {
            $txn_amount = 200.00;
            $txn_method = isset($_POST['payment_method']) ? mysqli_real_escape_string($link, $_POST['payment_method']) : 'Online';

            // Get trainer name for transaction record
            $t_query = mysqli_query($link, "SELECT name FROM trainers WHERE id = $trainer_id");
            $t_name = ($t_row = mysqli_fetch_assoc($t_query)) ? $t_row['name'] : 'Unknown';

            $txn_desc = "Trainer Appointment • " . $t_name;

            mysqli_query($link, "INSERT INTO transactions (user_id, plan_name, amount, payment_method) VALUES ($user_id, '$txn_desc', $txn_amount, '$txn_method')");
        }

        mysqli_stmt_close($stmt);
        // Redirect to Appointments section to show history as requested
        header("Location: dashboard_member.php?section=my-appointments");
        exit;
    } else {
        $_SESSION['flash_message'] = "Error booking appointment.";
        mysqli_stmt_close($stmt);
        header("Location: dashboard_member.php?section=trainers");
        exit;
    }
}

// HANDLE APPOINTMENT DELETION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_appointment'])) {
    $appt_id = (int) $_POST['appt_id'];
    if (mysqli_query($link, "DELETE FROM appointments WHERE id=$appt_id AND user_id=$user_id")) {
        $_SESSION['flash_message'] = "Appointment deleted successfully.";
    } else {
        $_SESSION['flash_message'] = "Error deleting appointment.";
    }
    header("Location: dashboard_member.php");
    exit;
}

// FETCH ATTENDANCE HISTORY
$attendance_res = mysqli_query($link, "SELECT date, status FROM attendance WHERE user_id = $user_id ORDER BY date DESC");
$attendance_dates = [];
$attendance_map = [];
while ($row = mysqli_fetch_assoc($attendance_res)) {
    $attendance_map[$row['date']] = $row['status'];
    // Keep attendance_dates for backward compatibility (only 'present' dates)
    if ($row['status'] === 'present') {
        $attendance_dates[] = $row['date'];
    }
}
$is_present_today = isset($attendance_map[date('Y-m-d')]) && $attendance_map[date('Y-m-d')] === 'present';

// Calculate Monthly Stats for Reports Modal (Grouped by Year)
$attendance_by_year = [];
$current_sys_year = (int) date('Y');
$join_year = (int) date('Y', strtotime($join_date));
// Start from the year user joined
$min_year = $join_year;

// Also check attendance history for even earlier dates (edge case)
if (!empty($attendance_dates)) {
    foreach ($attendance_dates as $ad) {
        $y_check = (int) date('Y', strtotime($ad));
        if ($y_check < $min_year)
            $min_year = $y_check;
    }
}

// Generate data structure: [Year][Month] => {name, count}
for ($y = $current_sys_year; $y >= $min_year; $y--) {
    $attendance_by_year[$y] = [];
    for ($m = 1; $m <= 12; $m++) {
        $prefix = sprintf('%04d-%02d', $y, $m);
        $cnt = 0;
        foreach ($attendance_dates as $d) {
            if (strpos($d, $prefix) === 0)
                $cnt++;
        }
        $attendance_by_year[$y][$m] = [
            'name' => date('F', mktime(0, 0, 0, $m, 10)),
            'short' => date('M', mktime(0, 0, 0, $m, 10)),
            'count' => $cnt
        ];
    }
}


// HANDLE PROFILE UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $full_name = mysqli_real_escape_string($link, $_POST['full_name']);
    $email = mysqli_real_escape_string($link, $_POST['email']);

    // Handle Image Upload
    if (isset($_FILES['profile_image_file']) && $_FILES['profile_image_file']['error'] == 0) {
        $target_dir = "assets/images/profiles/";
        if (!file_exists($target_dir))
            mkdir($target_dir, 0777, true);

        $file_ext = pathinfo($_FILES["profile_image_file"]["name"], PATHINFO_EXTENSION);
        $new_filename = "user_" . $user_id . "_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["profile_image_file"]["tmp_name"], $target_file)) {
            mysqli_query($link, "UPDATE users SET profile_image = '$target_file' WHERE id = $user_id");
            $profile_image = $target_file;
        }
    }

    // Handle Password
    $pass_query = "";
    if (!empty($_POST['new_password'])) {
        $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $pass_query = ", password = '$new_pass'";
    }

    // Check if email is already taken by another user
    $check_email_sql = "SELECT id FROM users WHERE email = '$email' AND id != $user_id";
    $email_result = mysqli_query($link, $check_email_sql);

    if (mysqli_num_rows($email_result) > 0) {
        $message = "Error: This email address is already registered to another account.";
        $message_type = "error";
    } else {
        $update_sql = "UPDATE users SET full_name = '$full_name', email = '$email' $pass_query WHERE id = $user_id";
        if (mysqli_query($link, $update_sql)) {
            $_SESSION["full_name"] = $full_name;
            $_SESSION["email"] = $email;
            $_SESSION['flash_message'] = "Profile updated successfully!";
            header("Location: dashboard_member.php");
            exit;
        } else {
            $message = "Error: Failed to update profile. Please try again.";
            $message_type = "error";
        }
    }
}
// HANDLE TASK ACTIONS (AJAX would be better but keeping it simple with POST for prototype)
if (isset($_POST['ajax_action'])) {
    // Update Last Activity for the current user (member)
    mysqli_query($link, "UPDATE users SET last_activity = NOW() WHERE id = $user_id");

    if ($_POST['ajax_action'] == 'add_task' && !empty($_POST['task_name'])) {
        $task_name = mysqli_real_escape_string($link, $_POST['task_name']);
        mysqli_query($link, "INSERT INTO tasks (user_id, task_name) VALUES ($user_id, '$task_name')");
    } elseif ($_POST['ajax_action'] == 'toggle_task') {
        $task_id = (int) $_POST['task_id'];
        mysqli_query($link, "UPDATE tasks SET is_done = !is_done WHERE id = $task_id AND user_id = $user_id");
    } elseif ($_POST['ajax_action'] == 'delete_task') {
        $task_id = (int) $_POST['task_id'];
        mysqli_query($link, "DELETE FROM tasks WHERE id = $task_id AND user_id = $user_id");
    } elseif ($_POST['ajax_action'] == 'edit_task') {
        $task_id = (int) $_POST['task_id'];
        $task_name = mysqli_real_escape_string($link, $_POST['task_name']);
        mysqli_query($link, "UPDATE tasks SET task_name = '$task_name' WHERE id = $task_id AND user_id = $user_id");
    }
}

// HANDLE WORKOUT PROGRESS (AJAX-ified)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['finish_workout_ajax'])) {
    header('Content-Type: application/json');
    $video_id = mysqli_real_escape_string($link, $_POST['video_id']);
    $action_type = $_POST['action_type'] ?? 'complete';
    $custom_date = $_POST['custom_date'] ?? date('Y-m-d');

    // Define date variables first for all actions
    $view_month = (int) date('n', strtotime($custom_date));
    $view_year = (int) date('Y', strtotime($custom_date));
    $days_in_m = (int) date('t', strtotime($custom_date));
    $start_m = "$view_year-" . sprintf('%02d', $view_month) . "-01";
    $end_m = "$view_year-" . sprintf('%02d', $view_month) . "-$days_in_m";

    if ($action_type == 'complete') {
        if (!empty($video_id)) {
            mysqli_query($link, "INSERT INTO completed_workouts (user_id, video_id, completed_at) VALUES ($user_id, '$video_id', '$custom_date')");
        }
    } elseif ($action_type == 'reset_all') {
        mysqli_query($link, "DELETE FROM completed_workouts WHERE user_id = $user_id AND video_id NOT LIKE 'beg_week_%' AND completed_at BETWEEN '$start_m 00:00:00' AND '$end_m 23:59:59'");
    } else {
        // Redo action: Remove all records for this specific date and user to ensure progress resets to 0 for that day
        mysqli_query($link, "DELETE FROM completed_workouts WHERE user_id = $user_id AND DATE(completed_at) = '$custom_date' AND video_id NOT LIKE 'beg_week_%'");
    }

    $cnt_query = mysqli_query($link, "SELECT COUNT(DISTINCT DATE(completed_at)) as count FROM completed_workouts WHERE user_id = $user_id AND video_id NOT LIKE 'beg_week_%' AND completed_at BETWEEN '$start_m 00:00:00' AND '$end_m 23:59:59'");
    $cnt = $cnt_query->fetch_assoc()['count'];

    $dates_res = mysqli_query($link, "SELECT DISTINCT DATE(completed_at) as cdate FROM completed_workouts WHERE user_id = $user_id AND video_id NOT LIKE 'beg_week_%' AND completed_at BETWEEN '$start_m 00:00:00' AND '$end_m 23:59:59' ORDER BY completed_at DESC LIMIT 5");
    $dates = [];
    while ($rd = mysqli_fetch_assoc($dates_res))
        $dates[] = date('M d', strtotime($rd['cdate']));

    echo json_encode(["success" => true, "count" => $cnt, "total" => $days_in_m, "percent" => round(($cnt / $days_in_m) * 100), "dates" => $dates]);
    exit;
}

// HANDLE PAYMENT RECORDING
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['complete_payment'])) {
    $plan_name = mysqli_real_escape_string($link, $_POST['plan_name']);
    $amount = (float) $_POST['amount'];
    $method = mysqli_real_escape_string($link, $_POST['method']);

    if ($plan_name === 'Trainer Appointment') {
        header("Location: dashboard_member.php?section=trainers");
        exit;
    }

    // Insert Transaction
    mysqli_query($link, "INSERT INTO transactions (user_id, plan_name, amount, payment_method) VALUES ($user_id, '$plan_name', $amount, '$method')");

    // Calculate New Expiry
    $current_exp = $user_data['membership_expiry'];
    $base_time = ($current_exp && strtotime($current_exp) > time()) ? strtotime($current_exp) : time();

    // Check if Yearly
    $duration = (strpos($plan_name, 'Yearly') !== false) ? '+1 year' : '+30 days';
    $new_expiry = date('Y-m-d', strtotime($duration, $base_time));

    // Update User Table
    $update_user = "UPDATE users SET membership_plan = '$plan_name', membership_status = 'Active', membership_expiry = '$new_expiry' WHERE id = $user_id";
    if (mysqli_query($link, $update_user)) {
        $_SESSION['flash_message'] = "Plan $plan_name renewed successfully until " . date('M d, Y', strtotime($new_expiry)) . "!";
        header("Location: dashboard_member.php");
        exit;
    }
}

// FETCH DATA FOR DISPLAY
$tasks = mysqli_query($link, "SELECT * FROM tasks WHERE user_id = $user_id ORDER BY created_at DESC");
// Split transactions
$membership_transactions = mysqli_query($link, "SELECT * FROM transactions WHERE user_id = $user_id AND plan_name NOT LIKE 'Trainer Appointment%' AND plan_name NOT LIKE 'Store:%' ORDER BY created_at DESC");
$trainer_transactions = mysqli_query($link, "SELECT * FROM transactions WHERE user_id = $user_id AND (plan_name = 'Trainer Appointment' OR plan_name LIKE 'Trainer Appointment • %') ORDER BY created_at DESC");

// --- MONTHLY NAVIGATION LOGIC ---
$view_month = isset($_GET['m']) ? (int) $_GET['m'] : (int) date('n');
$view_year = isset($_GET['y']) ? (int) $_GET['y'] : (int) date('Y');

// Date object for the first of the viewed month
$view_date = DateTime::createFromFormat('Y-n-j', "$view_year-$view_month-1");
$current_month_name = $view_date->format('F');
$days_in_month = (int) $view_date->format('t');

$prev_month = $view_month - 1;
$prev_year = $view_year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $view_month + 1;
$next_year = $view_year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// --- MONTHLY PROGRESS LOGIC ---
$start_of_view_month = "$view_year-" . sprintf('%02d', $view_month) . "-01";
$end_of_view_month = "$view_year-" . sprintf('%02d', $view_month) . "-$days_in_month";

$completed_this_month_res = mysqli_query($link, "SELECT COUNT(DISTINCT DATE(completed_at)) as count FROM completed_workouts WHERE user_id = $user_id AND video_id NOT LIKE 'beg_week_%' AND completed_at BETWEEN '$start_of_view_month 00:00:00' AND '$end_of_view_month 23:59:59'");
$completed_this_month = $completed_this_month_res->fetch_assoc()['count'];

// Also fetch specific completed dates for a 'Recently completed' list
$completed_dates_res = mysqli_query($link, "SELECT DISTINCT DATE(completed_at) as cdate FROM completed_workouts WHERE user_id = $user_id AND video_id NOT LIKE 'beg_week_%' AND completed_at BETWEEN '$start_of_view_month 00:00:00' AND '$end_of_view_month 23:59:59' ORDER BY completed_at DESC LIMIT 5");
$recent_completed_dates = [];
while ($rd = mysqli_fetch_assoc($completed_dates_res)) {
    $recent_completed_dates[] = date('M d', strtotime($rd['cdate']));
}

$total_videos_target = $days_in_month;
$progress_percent = min(100, ($completed_this_month / $total_videos_target) * 100);

// Calendar Setup
$today_day = ((int) date('n') == $view_month && (int) date('Y') == $view_year) ? (int) date('d') : 1;

// Next Workout Logic (31 unique videos for each day)
$all_videos = [
    ['id' => 'ml6cT4AZdqI', 'title' => 'Full Body HIIT', 'duration' => '20 mins', 'type' => 'Cardio', 'content' => 'High intensity interval training to boost metabolism.'],
    ['id' => 'u0mubHNo8-k', 'title' => 'Chest & Triceps', 'duration' => '35 mins', 'type' => 'Strength', 'content' => 'Focus on pushing movements and upper body strength.'],
    ['id' => '8PwoY4B6mG8', 'title' => 'Core Blast', 'duration' => '15 mins', 'type' => 'Core', 'content' => 'Short but intense routine for rock solid abs.'],
    ['id' => 'Enz98dDXQfY', 'title' => 'Yoga Flow', 'duration' => '40 mins', 'type' => 'Yoga', 'content' => 'Improve flexibility and mental clarity.'],
    ['id' => 'sqP7nO-o6vQ', 'title' => 'Leg Day Power', 'duration' => '45 mins', 'type' => 'Strength', 'content' => 'Build explosive power in your quads and glutes.'],
    ['id' => 'zzYAnOIdV_U', 'title' => 'Back & Biceps', 'duration' => '30 mins', 'type' => 'Strength', 'content' => 'Pulling focus to broaden your upper frame.'],
    ['id' => 'N7D9_A-5c14', 'title' => 'Fat Burning Cardio', 'duration' => '25 mins', 'type' => 'Cardio', 'content' => 'Sweat it out with this fast-paced cardio session.'],
    ['id' => 'gC_L9qAHVJ8', 'title' => 'Stretching Recovery', 'duration' => '20 mins', 'type' => 'Mobility', 'content' => 'Gentle movements to help muscle recovery.'],
    ['id' => 'u01_Z5jSg8c', 'title' => 'Shoulder Press', 'duration' => '30 mins', 'type' => 'Strength', 'content' => 'Build boulder shoulders with overhead volume.'],
    ['id' => 'q5Dq1Y5rN3M', 'title' => 'Deadlift Mastery', 'duration' => '40 mins', 'type' => 'Power', 'content' => 'Technical focus on pulling heavy weights safely.'],
    ['id' => '6TMmPZbe1rk', 'title' => 'Abs on Fire', 'duration' => '10 mins', 'type' => 'Core', 'content' => 'Quick abdominal circuit for maximum burn.'],
    ['id' => 'i-a_X7p2_0Q', 'title' => 'Zumba Party', 'duration' => '50 mins', 'type' => 'Cardio', 'content' => 'Dance your way to fitness with high energy.'],
    ['id' => 'Y2e-mN9X_qU', 'title' => 'Tabata Burn', 'duration' => '15 mins', 'type' => 'Cardio', 'content' => '20 seconds on, 10 seconds off. Maximum effort.'],
    ['id' => 'h_G6c_D3s8Q', 'title' => 'Kettlebell Flow', 'duration' => '30 mins', 'type' => 'Strength', 'content' => 'Functional movement with kettlebell swings.'],
    ['id' => 'L_xpDApI-w8', 'title' => 'Pilates Core', 'duration' => '25 mins', 'type' => 'Core', 'content' => 'Controlled movements for deep core engagement.'],
    ['id' => 'v7AYKMP6rOE', 'title' => 'Deep Stretch Yoga', 'duration' => '60 mins', 'type' => 'Yoga', 'content' => 'Hold poses longer for deep tissue release.'],
    ['id' => '3S26UjXNoHw', 'title' => 'Bodyweight Only', 'duration' => '30 mins', 'type' => 'HIIT', 'content' => 'No equipment needed. Just your willpower.'],
    ['id' => '2K8B-uR5U_0', 'title' => 'Arm Pump', 'duration' => '20 mins', 'type' => 'Strength', 'content' => 'Targeted isolation for biceps and triceps.'],
    ['id' => 'I9nPXEGUPo4', 'title' => 'Kickboxing HIIT', 'duration' => '40 mins', 'type' => 'Cardio', 'content' => 'Punches and kicks for a full body burn.'],
    ['id' => 'E7_U88-A_0o', 'title' => 'Morning Mobility', 'duration' => '15 mins', 'type' => 'Mobility', 'content' => 'Start your day with joint lubrication.'],
    ['id' => 'K69a4pz-O08', 'title' => 'Squat Challenge', 'duration' => '45 mins', 'type' => 'Strength', 'content' => 'Mastering the king of all exercises.'],
    ['id' => 'v-R3cM-C8Uo', 'title' => 'Glute Sculpt', 'duration' => '30 mins', 'type' => 'Strength', 'content' => 'Targeted movements for a stronger posterior.'],
    ['id' => 'p_q8-9S5XkE', 'title' => 'Plank variations', 'duration' => '12 mins', 'type' => 'Core', 'content' => 'Static holds to build endurance.'],
    ['id' => '5tkp7F6wpko', 'title' => 'Running Prep', 'duration' => '5 mins', 'type' => 'Warmup', 'content' => 'Dynamic stretches before your outdoor run.'],
    ['id' => 'MkMTJAGwLQc', 'title' => 'Swimming Dry-Land', 'duration' => '30 mins', 'type' => 'Strength', 'content' => 'Dry-land exercises for better swimming power.'],
    ['id' => 'By27MNo3pLy', 'title' => 'Olympic Lifts Intro', 'duration' => '50 mins', 'type' => 'Power', 'content' => 'Basics of the snatch and clean and jerk.'],
    ['id' => '5DAnMAJPr-e', 'title' => 'Meditation Session', 'duration' => '10 mins', 'type' => 'Recovery', 'content' => 'Clear your mind for better performance.'],
    ['id' => 'v7AYKMP6rOG', 'title' => 'Power Yoga', 'duration' => '45 mins', 'type' => 'Yoga', 'content' => 'Atmospherically intense yoga for strength.'],
    ['id' => 'zIu7YF_Lz2O', 'title' => 'Jump Rope HIIT', 'duration' => '20 mins', 'type' => 'Cardio', 'content' => 'Fast feet and high heart rate.'],
    ['id' => 'C_W8R0fM5gA', 'title' => 'Pull Up Progress', 'duration' => '30 mins', 'type' => 'Strength', 'content' => 'Master your first pull up or improve reps.'],
    ['id' => 'UBMk30rjy0q', 'title' => 'Box Jumps & Burpees', 'duration' => '35 mins', 'type' => 'Plyo', 'content' => 'High impact plyometrics for athleticism.']
];

$done_res = mysqli_query($link, "SELECT video_id FROM completed_workouts WHERE user_id = $user_id AND completed_at BETWEEN '$start_of_view_month 00:00:00' AND '$end_of_view_month 23:59:59'");
$done_ids = [];
while ($r = mysqli_fetch_assoc($done_res))
    $done_ids[] = $r['video_id'];

$next_video = $all_videos[0];
foreach ($all_videos as $v) {
    if (!in_array($v['id'], $done_ids)) {
        $next_video = $v;
        break;
    }
}

// --- FETCH CUSTOM SCHEDULED WORKOUTS (STAFF UPLOADED) ---
$custom_workouts = [];
// Fetch all workouts to make them searchable across different months
$custom_res = mysqli_query($link, "SELECT * FROM daily_workouts ORDER BY date DESC");
while ($cw = mysqli_fetch_assoc($custom_res)) {
    // Key by date YYYY-MM-DD
    $custom_workouts[$cw['date']] = [
        'id' => 'custom-' . $cw['id'],
        'real_id' => $cw['video_url'],
        'title' => $cw['title'],
        'duration' => 'Staff Pick',
        'type' => 'Daily Challenge',
        'content' => $cw['description'],
        'is_custom' => true,
        'date' => $cw['date']
    ];
}

// --- BEGINNER TIMELINE DATA & LOGIC ---
$beginner_weeks = [
    1 => [
        'title' => 'Foundation Phase',
        'goal' => 'Get used to the gym',
        'activities' => ['Light cardio (treadmill, cycling)', 'Full-body stretching', 'Basic bodyweight moves', 'Squats', 'Wall push-ups', 'Planks'],
        'topics' => ['Gym introduction for beginners', 'Warm-up & stretching routine', 'Common beginner gym mistakes']
    ],
    2 => [
        'title' => 'Light Weight Introduction',
        'goal' => 'Learn form, not heavy lifting',
        'activities' => ['Machine-based workouts', 'Very light weights', 'Full-body workout (3 days/week)'],
        'topics' => ['How to start lifting weights safely', 'Machine workouts for beginners', 'Proper breathing during exercises']
    ],
    3 => [
        'title' => 'Split Training Begins',
        'goal' => 'Muscle awareness & consistency',
        'activities' => ['Upper body / Lower body split', 'Dumbbells + machines', 'Core workouts'],
        'topics' => ['Back & biceps beginner workout', 'Chest & triceps beginner workout', 'Abs workout for beginners']
    ],
    4 => [
        'title' => 'Strength & Confidence',
        'goal' => 'Build routine & strength',
        'activities' => ['Push / Pull / Legs split', 'Slightly increased weights', 'Proper rest & recovery'],
        'topics' => ['Beginner to intermediate transition guide', 'How to increase weights safely', 'Recovery, rest days & sleep importance']
    ]
];

// Check Beginner Progress
$beg_res = mysqli_query($link, "SELECT video_id FROM completed_workouts WHERE user_id = $user_id AND video_id LIKE 'beg_week_%'");
$completed_weeks = [];
while ($row = mysqli_fetch_assoc($beg_res)) {
    $completed_weeks[] = $row['video_id'];
}
$is_beginner_completed = count($completed_weeks) >= 4;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="origin-when-cross-origin">
    <title>Member Dashboard </title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Roboto:wght@400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary-color: #ceff00;
            --secondary-color: #1a1a2e;
            --bg-dark: #0f0f1a;
            --card-bg: rgba(255, 255, 255, 0.05);
            --text-gray: #aaa;
        }

        body {
            background-color: var(--bg-dark);
            color: #fff;
            display: flex;
            min-height: 100vh;
        }

        /* Enforce dark mode for native controls */
        input,
        select,
        textarea {
            color-scheme: dark;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: var(--secondary-color);
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
            /* Prevent sidebar from shrinking */
            z-index: 100;
        }

        .sidebar .logo {
            font-family: 'Oswald', sans-serif;
            font-size: 1.5rem;
            color: var(--primary-color);
            text-decoration: none;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-menu {
            list-style: none;
            flex-grow: 1;
        }

        .sidebar-menu li {
            margin-bottom: 15px;
        }

        .sidebar-menu a {
            color: var(--text-gray);
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(206, 255, 0, 0.1);
            color: var(--primary-color);
        }

        .sidebar-footer {
            margin-top: auto;
        }

        .btn-logout {
            color: #ff4d4d;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
        }

        #plan-selector {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white' width='18px' height='18px'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            cursor: pointer;
            transition: 0.3s;
        }

        #plan-selector:hover,
        #plan-selector:focus {
            border-color: var(--primary-color) !important;
            outline: none;
            box-shadow: 0 0 10px rgba(206, 255, 0, 0.1);
        }

        #plan-selector option,
        #plan-selector optgroup {
            background-color: var(--secondary-color);
            color: #fff;
            padding: 12px;
        }

        #plan-selector optgroup {
            color: var(--primary-color);
            background-color: var(--secondary-color);
            font-weight: bold;
        }

        /* Main Content */
        .main-content {
            flex-grow: 1;
            padding: 40px;
            overflow-y: auto;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .welcome-text h1 {
            font-family: 'Oswald', sans-serif;
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .welcome-text p {
            color: var(--text-gray);
        }

        .user-profile-small {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-profile-small img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 2px solid var(--primary-color);
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .dashboard-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: 0.3s;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            border-color: rgba(206, 255, 0, 0.3);
        }

        /* Fixed heights for membership cards */
        #membership .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            align-items: start;
        }

        #membership .dashboard-card {
            min-height: 450px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        #membership .transaction-list-container {
            max-height: 320px;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            padding-right: 15px;
            margin-right: -5px;
        }

        #membership .transaction-list-container::-webkit-scrollbar {
            width: 6px;
        }

        #membership .transaction-list-container::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
            border: 1px solid var(--secondary-color);
        }

        #membership .transaction-list-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.02);
            border-radius: 10px;
        }

        #membership .dashboard-card h3 {
            font-family: 'Oswald', sans-serif;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dashboard-card h3 i {
            color: var(--primary-color);
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-active {
            background: rgba(0, 255, 0, 0.1);
            color: #00ff00;
        }

        .status-pending {
            background: rgba(255, 165, 0, 0.1);
            color: #ffa500;
        }

        .todo-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 10px;
            margin-bottom: 10px;
            transition: 0.3s;
        }

        .todo-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        /* Buttons & Icons */
        .icon-btn {
            background: none;
            border: none;
            color: var(--text-gray);
            cursor: pointer;
            transition: 0.3s;
            padding: 5px;
        }

        .icon-btn:hover {
            color: var(--primary-color);
        }

        .icon-btn.delete:hover {
            color: #ff4d4d;
        }

        .btn-action {
            background: var(--primary-color);
            color: var(--secondary-color);
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            text-transform: uppercase;
            font-size: 0.95rem;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(206, 255, 0, 0.3);
        }

        /* Profile Styles */
        .profile-img-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 30px;
        }

        .profile-img-container img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
        }

        .upload-overlay {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--primary-color);
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary-color);
            cursor: pointer;
            border: 2px solid var(--bg-dark);
        }

        /* Payment Modal Prototype */
        #payment-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--secondary-color);
            padding: 40px;
            border-radius: 20px;
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .payment-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
        }

        .pay-opt {
            border: 1px solid #333;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
        }

        .pay-opt:hover {
            border-color: var(--primary-color);
            background: rgba(206, 255, 0, 0.05);
        }

        .pay-opt.active {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: var(--secondary-color);
        }

        #processing-payment {
            display: none;
            text-align: center;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(206, 255, 0, 0.1);
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .pulse {
            animation: pulse-red 1s infinite alternate;
        }

        @keyframes pulse-red {
            from {
                transform: scale(1);
                text-shadow: 0 0 0 rgba(255, 77, 77, 0);
            }

            to {
                transform: scale(1.1);
                text-shadow: 0 0 10px rgba(255, 77, 77, 0.8);
            }
        }

        /* Training */
        .progress-bar-container {
            height: 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            margin: 20px 0;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: var(--primary-color);
            box-shadow: 0 0 15px var(--primary-color);
            transition: width 0.5s ease-in-out;
        }

        /* Hide Number Input Spinners */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }

        /* Premium Toast Notification */
        .toast {
            position: fixed;
            top: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(-100px);
            background: rgba(26, 26, 46, 0.95);
            border: 1px solid var(--primary-color);
            color: #fff;
            padding: 15px 30px;
            border-radius: 50px;
            font-family: 'Oswald';
            font-size: 1rem;
            letter-spacing: 1px;
            z-index: 9999;
            transition: 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 30px rgba(206, 255, 0, 0.1), 0 0 15px rgba(206, 255, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            backdrop-filter: blur(10px);
        }

        .toast::before {
            content: '\f058';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: var(--primary-color);
        }

        .toast.show {
            transform: translateX(-50%) translateY(0);
        }

        /* Calendar Styles */
        .calendar-strip {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding: 15px 0;
            margin-bottom: 25px;
            scrollbar-width: thin;
            scrollbar-color: var(--primary-color) transparent;
        }

        .calendar-strip::-webkit-scrollbar,
        .month-selector-tabs::-webkit-scrollbar {
            display: none;
        }

        .calendar-strip::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }

        .calendar-day {
            flex: 0 0 60px;
            height: 80px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.3s;
            border: 1px solid transparent;
            position: relative;
        }

        .calendar-day:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .calendar-day .day-num {
            font-size: 1.4rem;
            font-weight: bold;
            font-family: 'Oswald';
        }

        .calendar-day .day-name {
            font-size: 0.7rem;
            text-transform: uppercase;
            opacity: 0.8;
        }

        .calendar-day:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        /* "Today" styling - FIXED bright color */
        .calendar-day.today {
            background: var(--primary-color);
            color: #000;
            box-shadow: 0 0 15px rgba(206, 255, 0, 0.4);
        }

        /* Completed overrides Today's background - becomes border only */
        .calendar-day.completed,
        .calendar-day.today.completed {
            border-color: var(--primary-color);
            background: transparent !important;
            color: #fff !important;
            /* Reset text for completed */
            box-shadow: none;
        }

        /* Selection Styling (Active) - distinct from "Today" */
        .calendar-day.active {
            border: 1px solid #fff;
            /* No background change for selection, just border */
        }

        /* Keep the bright background if it is active AND today AND NOT completed */
        .calendar-day.active.today:not(.completed) {
            border: 2px solid #fff;
            /* White border on top of yellow bg */
        }

        .calendar-day.active.completed {
            background: rgba(206, 255, 0, 0.1) !important;
            border-color: var(--primary-color);
        }

        .calendar-day.completed::after {
            content: '\f058';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            bottom: 5px;
            right: 5px;
            font-size: 0.8rem;
            color: var(--primary-color);
        }

        .calendar-day.active.completed::after {
            color: var(--primary-color);
        }


        /* Hidden Sections Fix */
        .dashboard-section {
            display: none;
        }

        .dashboard-section.active {
            display: block;
        }

        /* Workouts Section Styles */
        #video-player-container {
            margin-bottom: 25px;
            border-radius: 15px;
            overflow: hidden;
            display: none;
            aspect-ratio: 16 / 9;
            background: #000;
            position: relative;
        }

        #video-player-container.active {
            display: block;
        }

        .video-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .video-item {
            display: flex;
            gap: 15px;
            align-items: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 12px;
            cursor: pointer;
            transition: 0.3s;
            border: 1px solid transparent;
        }

        .video-item:hover,
        .video-item.playing {
            background: rgba(206, 255, 0, 0.05);
            border-color: rgba(206, 255, 0, 0.2);
        }

        .video-item.playing {
            border-color: var(--primary-color);
        }

        .video-thumb {
            width: 100px;
            height: 60px;
            background-size: cover;
            background-position: center;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .video-thumb i {
            font-size: 1.5rem;
            color: #fff;
            text-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }

        /* Trainer Cards */
        .trainer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .trainer-card {
            background: var(--card-bg);
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: 0.3s;
            text-align: center;
        }

        .trainer-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-color);
        }

        .trainer-img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .trainer-info {
            padding: 15px;
        }

        .trainer-info h4 {
            font-family: 'Oswald', sans-serif;
            font-size: 1.2rem;
            margin-bottom: 5px;
            color: var(--primary-color);
        }

        /* Responsive Dashboard */
        @media (max-width: 992px) {
            body {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                padding: 15px 20px;
            }

            .sidebar .logo {
                margin-bottom: 20px;
            }

            .sidebar-menu {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-bottom: 10px;
            }

            .sidebar-menu li {
                margin-bottom: 0;
            }

            .sidebar-menu a {
                padding: 8px 12px;
                font-size: 0.9rem;
            }

            .sidebar-footer {
                display: none;
            }

            .main-content {
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            #workouts .dashboard-grid {
                grid-template-columns: 1fr !important;
            }
        }
        }

        /* Essential utility for icon buttons */
        .icon-btn.edit {
            color: var(--primary-color);
        }

        .icon-btn.edit:hover {
            background: rgba(206, 255, 0, 0.1);
        }

        /* PRICING STYLES */
        .pricing-section-container {
            padding: 20px 0;
            grid-column: 1 / -1;
        }

        .pricing-section-container.monthly .yearly-only {
            display: none !important;
        }

        .pricing-section-container.yearly .monthly-only {
            display: none !important;
        }

        .pricing-toggle {
            background: rgba(255, 255, 255, 0.05);
            display: flex;
            justify-content: center;
            width: fit-content;
            margin: 0 auto 30px;
            padding: 5px;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .toggle-btn {
            padding: 10px 30px;
            border-radius: 25px;
            color: var(--text-gray);
            background: transparent;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
            border: none;
        }

        .toggle-btn.active {
            background: var(--primary-color);
            color: #000;
            box-shadow: 0 0 15px rgba(206, 255, 0, 0.4);
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .pricing-card {
            background: var(--card-bg);
            padding: 30px 25px;
            border-radius: 15px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            transition: 0.4s;
            display: flex;
            flex-direction: column;
        }

        .pricing-card:hover,
        .pricing-card.selected-plan {
            transform: translateY(-5px);
            border-color: var(--primary-color);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .pricing-card.selected-plan {
            background: rgba(206, 255, 0, 0.05);
        }

        .pricing-card h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            font-family: 'Oswald', sans-serif;
            color: #fff;
        }

        .price-tag {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 25px;
        }

        .price-tag span {
            font-size: 1rem;
            color: var(--text-gray);
            font-weight: 400;
        }

        .features-list {
            list-style: none;
            text-align: left;
            margin-bottom: 30px;
            flex: 1;
        }

        .features-list li {
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #ddd;
            font-size: 0.9rem;
        }

        .features-list li i.check {
            color: var(--primary-color);
        }

        .features-list li i.cross {
            color: #555;
        }

        .badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--primary-color);
            color: #000;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
        }
    </style>
</head>

<body>
    <?php if (!empty($message)): ?>
        <div class="toast show" id="status-toast"><?php echo $message; ?></div>
        <script>setTimeout(() => document.getElementById('status-toast').classList.remove('show'), 3000);</script>
    <?php endif; ?>

    <!-- Sidebar -->
    <div class="sidebar">
        <a href="index.php" class="logo">
            <i class="fa-solid fa-dumbbell"></i>GymFit
        </a>
        <ul class="sidebar-menu">
            <li><a href="index.php" style="color: var(--primary-color);"><i class="fa-solid fa-house-user"></i> Back to
                    Website</a></li>
            <li><a href="#" onclick="showSection('overview')" id="nav-overview"><i class="fa-solid fa-chart-pie"></i>
                    Overview</a></li>
            <li><a href="#" onclick="showSection('bmi-calculator')"><i class="fa-solid fa-weight-scale"></i> BMI
                    Calculator</a></li>
            <li><a href="#" onclick="showSection('measurements')" id="nav-measurements"><i
                        class="fa-solid fa-ruler-combined"></i> Measurements</a></li>
            <li><a href="#" onclick="showSection('attendance')"><i class="fa-solid fa-calendar-check"></i>
                    Attendance</a></li>
            <li><a href="#" onclick="showSection('workouts')" id="nav-workouts"><i class="fa-solid fa-dumbbell"></i>
                    Workout Journey</a></li>
            <li><a href="#" onclick="showSection('trainers')" id="nav-trainers"><i class="fa-solid fa-user-group"></i>
                    Trainers</a></li>
            <li><a href="#" onclick="showSection('my-appointments')" id="nav-my-appointments"><i
                        class="fa-solid fa-calendar-check"></i> My Appointments</a></li>
            <li><a href="#" onclick="showSection('chat')" id="nav-chat"><i class="fa-solid fa-comments"></i> Chat with
                    Trainer</a></li>
            <li><a href="#" onclick="showSection('todo')"><i class="fa-solid fa-list-check"></i> Daily To-Do</a></li>
            <li><a href="#" onclick="showSection('membership')"><i class="fa-solid fa-id-card"></i> Membership</a></li>



            <li><a href="#" onclick="showSection('progress-photos')" id="nav-progress-photos"><i
                        class="fa-solid fa-camera"></i> Progress Photos</a></li>

            <li><a href="#" onclick="showSection('gym-store')" id="nav-gym-store"><i class="fa-solid fa-store"></i> Gym
                    Store</a></li>

            <li><a href="#" onclick="showSection('profile')" id="nav-profile"><i class="fa-solid fa-id-card"></i>
                    Profile</a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header-top" style="justify-content: flex-end;">
            <div class="user-profile-small">
                <span>
                    <?php echo htmlspecialchars($_SESSION["full_name"]); ?></span>
                <img src="<?php echo $profile_image ? $profile_image : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION["full_name"]) . '&background=ceff00&color=1a1a2e'; ?>"
                    alt="Profile"
                    onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name']); ?>&background=ceff00&color=1a1a2e';">
            </div>
        </div>

        <div id="overview" class="dashboard-section active">

            <div class="welcome-text" style="margin-bottom: 30px;">
                <h1>Hello, <?php echo htmlspecialchars($_SESSION["full_name"]); ?>!</h1>
                <p>Welcome back to your fitness portal.</p>
            </div>

            <div class="dashboard-grid">

                <!-- Membership Card -->
                <div class="dashboard-card">
                    <h3>Membership <i class="fa-solid fa-crown" style="color: var(--primary-color);"></i></h3>
                    <p style="margin-bottom: 10px;">Plan:
                        <strong><?php echo htmlspecialchars($membership_plan); ?></strong>
                    </p>
                    <p style="margin-bottom: 20px;">Status:
                        <span
                            class="status-badge <?php echo (strtotime($membership_expiry) < time()) ? 'status-expired' : 'status-active'; ?>"
                            style="background: <?php echo (strtotime($membership_expiry) < time()) ? 'rgba(255, 77, 77, 0.1)' : 'rgba(0, 255, 0, 0.1)'; ?>;
                                     color: <?php echo (strtotime($membership_expiry) < time()) ? '#ff4d4d' : '#00ff00'; ?>;
                                     padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold;">
                            <?php echo (strtotime($membership_expiry) < time()) ? 'EXPIRED' : strtoupper($membership_status); ?>
                        </span>
                    </p>
                    <p style="font-size: 0.9rem; color: var(--text-gray);">Expires on:
                        <strong><?php echo $membership_expiry ? date('M d, Y', strtotime($membership_expiry)) : 'N/A'; ?></strong>
                    </p>
                    <button class="btn-action" onclick="showSection('membership')"
                        style="margin-top: 20px; width: 100%;">
                        <i class="fa-solid fa-rotate"></i> RENEW PLAN
                    </button>
                </div>

                <!-- Announcement Section -->
                <?php if ($latest_announcement): ?>
                    <div class="dashboard-card">
                        <h3>Announcements <i class="fa-solid fa-bullhorn"></i></h3>
                        <div
                            style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 10px; border-left: 4px solid var(--primary-color);">
                            <h4
                                style="color: #fff; margin-bottom: 8px; font-family: 'Oswald', sans-serif; font-size: 1.1rem;">
                                <?php echo htmlspecialchars($latest_announcement['title']); ?>
                            </h4>
                            <p style="color: var(--text-gray); font-size: 0.95rem; line-height: 1.5;">
                                <?php echo nl2br(htmlspecialchars($latest_announcement['message'])); ?>
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Fallback if no announcement -->
                    <div class="dashboard-card">
                        <h3>Announcements <i class="fa-solid fa-bullhorn"></i></h3>
                        <div
                            style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 10px; text-align:center;">
                            <p style="color: var(--text-gray);">No new announcements.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Attendance Section -->
        <div id="attendance" class="dashboard-section">
            <h2 style="font-family: 'Oswald', sans-serif; margin-bottom: 20px;">Attendance History
                (<?php echo date('F Y'); ?>)</h2>

            <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
                <button onclick="window.open('attendance_report.php', '_blank')"
                    style="background: var(--primary-color); color: #000; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s;">
                    <i class="fa-solid fa-file-pdf"></i> View Attendance
                </button>
            </div>

            <div
                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(50px, 1fr)); gap: 10px; margin-bottom: 20px;">
                <?php
                $current_month = date('m');
                $current_year = date('Y');
                $days_in_month = date('t');

                for ($day = 1; $day <= $days_in_month; $day++):
                    $date_str = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
                    $is_future = strtotime($date_str) > time();
                    $is_before_join = $date_str < $join_date;

                    // Get status from attendance_map
                    $status = isset($attendance_map[$date_str]) ? $attendance_map[$date_str] : null;

                    $bg_color = 'rgba(255,255,255,0.05)'; // Default: Not marked
                    $text_color = '#666';
                    $border = 'none';
                    $status_key = 'notmarked'; // For JS
                
                    if ($is_before_join) {
                        $bg_color = 'rgba(100,100,100,0.2)';
                        $text_color = '#555';
                        $status_key = 'disabled';
                    } elseif ($status === 'present') {
                        $bg_color = 'var(--primary-color)'; // Green
                        $text_color = '#000';
                        $status_key = 'present';
                    } elseif ($status === 'absent') {
                        $bg_color = '#dc3545'; // Solid Red background
                        $text_color = '#fff'; // White text
                        $border = 'none';
                        $status_key = 'absent';
                    } elseif ($is_future) {
                        $bg_color = 'rgba(255,255,255,0.02)';
                        $text_color = '#444';
                        $status_key = 'disabled';
                    }
                    ?>
                    <div onclick="highlightStatus('<?php echo $status_key; ?>', this)"
                        style="background: <?php echo $bg_color; ?>; color: <?php echo $text_color; ?>; padding: 15px 10px; border-radius: 8px; text-align: center; font-weight: bold; font-size: 1.1rem; border: <?php echo $border; ?>; cursor: pointer; transition: 0.2s;">
                        <?php echo $day; ?>
                    </div>
                <?php endfor; ?>
            </div>

            <div style="display: flex; gap: 20px; justify-content: center; font-size: 0.9rem;">
                <div id="legend-present" class="status-legend"
                    style="display: flex; align-items: center; gap: 8px; padding: 5px 10px; border-radius: 6px; transition: 0.3s;">
                    <div style="width: 20px; height: 20px; background: var(--primary-color); border-radius: 4px;"></div>
                    <span>Present</span>
                </div>
                <div id="legend-absent" class="status-legend"
                    style="display: flex; align-items: center; gap: 8px; padding: 5px 10px; border-radius: 6px; transition: 0.3s;">
                    <div style="width: 20px; height: 20px; background: #dc3545; border-radius: 4px;">
                    </div>
                    <span>Absent</span>
                </div>
                <div id="legend-notmarked" class="status-legend"
                    style="display: flex; align-items: center; gap: 8px; padding: 5px 10px; border-radius: 6px; transition: 0.3s;">
                    <div style="width: 20px; height: 20px; background: rgba(255,255,255,0.05); border-radius: 4px;">
                    </div>
                    <span>Not Marked</span>
                </div>
            </div>

            <script>
                function highlightStatus(status, element) {
                    if (status === 'disabled') return;

                    // Reset all legends
                    const legends = document.querySelectorAll('.status-legend');
                    legends.forEach(el => {
                        el.style.background = 'transparent';
                        el.style.boxShadow = 'none';
                        el.style.border = 'none';
                    });

                    // Highlight target legend
                    const targetId = 'legend-' + status;
                    const target = document.getElementById(targetId);
                    if (target) {
                        target.style.background = 'rgba(255, 255, 255, 0.1)';
                        target.style.boxShadow = '0 0 10px var(--primary-color)';
                        target.style.border = '1px solid var(--primary-color)';
                    }

                    // Optional: visual feedback on clicked day
                    element.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        element.style.transform = 'scale(1)';
                    }, 100);
                }
            </script>
        </div>

        <!-- WorkoutJourney Section with Calendar -->


        <div id="workouts" class="dashboard-section">
            <div style="margin-bottom: 30px;">
                <h2 style="font-family: 'Oswald', sans-serif; margin-bottom: 20px;">Workout Journey</h2>

                <!-- BEGINNER TIMELINE SECTION -->
                <div id="beginner-timeline"
                    style="margin-bottom: 40px; background: rgba(255,255,255,0.02); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px;">
                        <h3 style="font-family: 'Oswald'; color: var(--primary-color);">🟢 Beginner Gym Timeline (0–4
                            Weeks)</h3>
                        <?php // Badge removed as per user request ?>
                    </div>

                    <?php if (!$is_beginner_completed): ?>
                        <p style="color: var(--text-gray); margin-bottom: 20px;">Complete this foundation phase to unlock
                            Daily Challenges.</p>
                    <?php endif; ?>

                    <div class="timeline-weeks" style="display: flex; flex-direction: column; gap: 15px;">
                        <?php foreach ($beginner_weeks as $num => $week):
                            $week_id = "beg_week_$num";
                            $is_done = in_array($week_id, $completed_weeks);
                            $opacity = $is_done ? '0.6' : '1';
                            ?>
                            <div class="week-card"
                                style="background: rgba(255,255,255,0.05); border-radius: 8px; overflow: hidden; opacity: <?php echo $opacity; ?>;">
                                <div class="week-header" onclick="toggleWrapper('week-content-<?php echo $num; ?>')"
                                    style="padding: 15px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.2);">
                                    <strong style="font-size: 1.1rem;">Week <?php echo $num; ?>:
                                        <?php echo $week['title']; ?></strong>
                                    <div>
                                        <?php if ($is_done): ?>
                                            <i class="fa-solid fa-circle-check" style="color: var(--primary-color);"></i>
                                        <?php else: ?>
                                            <i class="fa-solid fa-chevron-down"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div id="week-content-<?php echo $num; ?>"
                                    style="display: <?php echo $is_done ? 'none' : 'block'; ?>; padding: 15px; border-top: 1px solid rgba(255,255,255,0.05);">
                                    <p style="color: #fff; margin-bottom: 10px;"><strong>Goal:</strong>
                                        <?php echo $week['goal']; ?></p>

                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                        <div>
                                            <h5 style="color: var(--primary-color); margin-bottom: 8px;">Activities</h5>
                                            <ul
                                                style="list-style: none; padding-left: 0; font-size: 0.9rem; color: var(--text-gray);">
                                                <?php foreach ($week['activities'] as $act): ?>
                                                    <li style="margin-bottom: 5px;"><i class="fa-solid fa-angle-right"
                                                            style="color: var(--primary-color); margin-right: 5px;"></i>
                                                        <?php echo $act; ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        <div>
                                            <h5 style="color: var(--primary-color); margin-bottom: 8px;">Video Topics</h5>
                                            <ul
                                                style="list-style: none; padding-left: 0; font-size: 0.9rem; color: var(--text-gray);">
                                                <?php foreach ($week['topics'] as $topic): ?>
                                                    <li style="margin-bottom: 5px;"><i class="fa-solid fa-play"
                                                            style="font-size: 0.7rem; color: var(--primary-color); margin-right: 5px;"></i>
                                                        <?php echo $topic; ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>

                                    <?php if (!$is_done): ?>
                                        <button onclick="markBeginnerWeek('<?php echo $week_id; ?>')" class="btn-action"
                                            style="margin-top: 15px; width: auto; padding: 8px 20px; font-size: 0.9rem;">
                                            Mark Week as Complete <i class="fa-solid fa-check"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- DAILY CHALLENGES WRAPPER (Gated) -->
                <?php if (!$is_beginner_completed): ?>
                    <div
                        style="text-align: center; padding: 40px; background: rgba(0,0,0,0.3); border-radius: 12px; border: 1px dashed var(--text-gray);">
                        <i class="fa-solid fa-lock"
                            style="font-size: 3rem; color: var(--text-gray); margin-bottom: 15px;"></i>
                        <h3>Daily Challenges Locked</h3>
                        <p style="color: var(--text-gray);">Please complete the Beginner Gym Timeline to unlock your daily
                            workout schedule.</p>
                    </div>
                <?php else: ?>

                    <!-- Workout Search & Month Selector -->
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
                        <div class="month-selector-tabs"
                            style="display: flex; gap: 10px; overflow-x: auto; padding-bottom: 5px; scrollbar-width: none;">
                            <?php
                            for ($m = 1; $m <= 12; $m++) {
                                $month_name = date('M', mktime(0, 0, 0, $m, 1));
                                $is_active = ($m == $view_month);
                                $url = "?m=$m&y=$view_year";
                                ?>
                                <a href="<?php echo $url; ?>"
                                    style="flex: 0 0 auto; padding: 10px 20px; border-radius: 30px; background: <?php echo $is_active ? 'var(--primary-color)' : 'rgba(255,255,255,0.05)'; ?>; color: <?php echo $is_active ? 'var(--secondary-color)' : '#fff'; ?>; text-decoration: none; font-family: 'Oswald'; font-weight: bold; transition: 0.3s; border: 1px solid <?php echo $is_active ? 'var(--primary-color)' : 'rgba(255,255,255,0.1)'; ?>;">
                                    <?php echo strtoupper($month_name); ?>
                                </a>
                                <?php
                            }
                            ?>
                        </div>



                        <!-- Search Bar -->
                        <div style="position: relative; width: 300px;">
                            <i class="fa-solid fa-magnifying-glass"
                                style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-gray); font-size: 0.9rem;"></i>
                            <input type="text" id="video-search-input" placeholder="Search workouts"
                                style="width: 100%; padding: 12px 15px 12px 45px; border-radius: 30px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; font-size: 0.9rem; outline: none; transition: 0.3s;"
                                onkeyup="searchVideos(this.value)">

                            <!-- Search Results Dropdown -->
                            <div id="search-results-dropdown"
                                style="display: none; position: absolute; top: calc(100% + 10px); left: 0; width: 100%; background: #1a1a2e; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; z-index: 1000; max-height: 400px; overflow-y: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.5); padding: 10px;">
                            </div>
                        </div>
                    </div>



                    <!-- Calendar Strip -->
                    <div class="calendar-strip" id="calendar-strip">
                        <?php
                        for ($d = 1; $d <= $days_in_month; $d++):
                            $date_str = "$view_year-" . sprintf('%02d', $view_month) . "-" . sprintf('%02d', $d);
                            $day_name = date('D', strtotime($date_str));
                            $is_completed = mysqli_query($link, "SELECT id FROM completed_workouts WHERE user_id = $user_id AND DATE(completed_at) = '$date_str' AND video_id NOT LIKE 'beg_week_%'")->num_rows > 0;
                            $is_today = ($d == date('d') && $view_month == date('n') && $view_year == date('Y'));
                            ?>
                            <div class="calendar-day <?php echo ($d === $today_day) ? 'active' : ''; ?> <?php echo $is_completed ? 'completed' : ''; ?> <?php echo $is_today ? 'today' : ''; ?>"
                                onclick="selectDay(<?php echo $d; ?>)" id="day-<?php echo $d; ?>"
                                data-date="<?php echo $date_str; ?>">
                                <span class="day-name"><?php echo $day_name; ?></span>
                                <span class="day-num"><?php echo $d; ?></span>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="dashboard-grid" style="grid-template-columns: 2fr 1fr;">
                        <div class="video-main-area">
                            <!-- Video Player Container -->
                            <div id="video-player-container">
                                <iframe id="video-iframe" width="100%" height="100%" src="" frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen></iframe>
                                <!-- Overlay controls removed for simplicity in this step, can be re-added if needed -->
                            </div>
                            <!-- <div id="video-completion-overlay" ... (kept hidden logic if needed later) -->
                            <div id="video-completion-overlay"
                                style="display:none; position:absolute; bottom:20px; right:20px; gap: 10px; z-index: 10;">
                                <button type="button" id="mark-finished-btn" onclick="markVideoFinished('complete')"
                                    class="btn-action" style="width:auto; padding:10px 20px;">
                                    <i class="fa-solid fa-check"></i> Mark as Finished
                                </button>
                                <button type="button" id="redo-workout-btn" onclick="markVideoFinished('redo')"
                                    class="btn-action"
                                    style="width:auto; padding:10px 20px; background: #333; color: #fff; display: none;">
                                    <i class="fa-solid fa-rotate-right"></i> Redo Workout
                                </button>
                            </div>
                        </div>

                        <div class="dashboard-card" style="margin-bottom: 0;">
                            <h3 id="playlist-title">Today's Routine</h3>
                            <div class="video-list" id="video-list-container">
                                <!-- Videos will be injected here by JS -->
                            </div>
                        </div>
                    </div>

                    <div class="side-info">
                        <div class="dashboard-card" style="height: 100%; position: relative;">
                            <button onclick="markVideoFinished('reset_all')" title="Reset Month"
                                style="position: absolute; top: 20px; right: 20px; background: none; border: none; color: #ff4d4d; cursor: pointer; font-size: 0.8rem; opacity: 0.6; transition: 0.3s;">
                                <i class="fa-solid fa-trash-can"></i> Reset All
                            </button>
                            <h3>Monthly Summary</h3>
                            <p style="color: var(--text-gray); font-size: 0.9rem;">Target: Complete videos daily for
                                full
                                progress.</p>

                            <div class="progress-bar-container">
                                <div class="progress-bar-fill" id="monthly-progress-bar"
                                    style="width: <?php echo $progress_percent; ?>%;"></div>
                            </div>
                            <p
                                style="text-align: right; font-size: 0.9rem; font-weight: bold; color: var(--primary-color);">
                                <span id="completed-days-text"><?php echo $completed_this_month; ?></span> / <span
                                    id="total-days-text"><?php echo $total_videos_target; ?></span> Days Completed
                                (<span id="progress-percent-text"><?php echo round($progress_percent); ?></span>%)
                            </p>

                            <div style="margin-top:25px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px;">
                                <h4
                                    style="font-family:'Oswald'; font-size: 0.9rem; margin-bottom:10px; color: var(--text-gray);">
                                    Dates Completed:</h4>
                                <div id="completed-dates-badges" style="display:flex; flex-wrap:wrap; gap:8px;">
                                    <?php if (empty($recent_completed_dates)): ?>
                                        <p style="font-size: 0.8rem; color: #555;">No workouts completed yet.</p>
                                    <?php else: ?>
                                        <?php foreach ($recent_completed_dates as $rd_label): ?>
                                            <span
                                                style="background: rgba(0,255,0,0.1); color: #00ff00; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; border: 1px solid rgba(0,255,0,0.2);">
                                                <i class="fa-solid fa-check" style="font-size: 0.6rem;"></i>
                                                <?php echo $rd_label; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div style="margin-top:30px;">
                                <h4 style="font-family:'Oswald'; margin-bottom:15px;">Achievement Medals</h4>
                                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                    <i id="medal-half" class="fa-solid fa-medal" title="Half Month Master"
                                        style="font-size: 2.5rem; color: <?php echo $progress_percent >= 50 ? '#ffd700' : '#333'; ?>;"></i>
                                    <i id="medal-full" class="fa-solid fa-trophy" title="Month Master"
                                        style="font-size: 2.5rem; color: <?php echo $progress_percent >= 100 ? '#ceff00' : '#333'; ?>;"></i>
                                    <i id="medal-fire" class="fa-solid fa-fire" title="Dedication"
                                        style="font-size: 2.5rem; color: <?php echo $progress_percent >= 75 ? '#ff4500' : '#333'; ?>;"></i>
                                </div>
                            </div>
                        </div>
                    </div> <!-- Close side-info (line 1188) -->
                <?php endif; // End Beginner Check ?>
            </div> <!-- Close wrapper (line 1028) or grid? Let's check -->
        </div> <!-- Close workouts section (line 1027) -->

        <!-- To-Do Section -->
        <div id="todo" class="dashboard-section">
            <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 20px; flex-wrap: wrap;">
                <h2 style="font-family: 'Oswald', sans-serif; margin-bottom: 0;">Daily Fitness Checklist</h2>

                <!-- Digital Clock Component -->
                <div id="dashboard-clock"
                    style="background: var(--card-bg); padding: 5px 15px; border-radius: 8px; font-family: 'Oswald'; font-size: 1.2rem; color: var(--primary-color); border: 1px solid rgba(206, 255, 0, 0.2); min-width: 100px; text-align: center;">
                    00:00:00
                </div>

                <!-- Timer Component -->
                <div id="dashboard-timer"
                    style="background: var(--card-bg); padding: 5px 10px; border-radius: 8px; font-family: 'Oswald'; font-size: 1.1rem; display: flex; align-items: center; gap: 6px; border: 1px solid rgba(255,255,255,0.1);">
                    <i class="fa-solid fa-stopwatch" style="color: var(--primary-color);"></i>
                    <div id="timer-input-area" style="display: flex; align-items: center; gap: 5px;">
                        <input type="number" id="timer-hours-input" placeholder="00" min="0" max="23"
                            style="width: 60px; background: rgba(0,0,0,0.3); border: 1px solid #444; color: var(--primary-color); border-radius: 4px; padding: 5px 0; font-family: 'Oswald'; font-size: 1.1rem; text-align: center; box-sizing: border-box;"
                            title="Hours">
                        <span style="font-size: 0.8rem; color: #777; font-weight: bold; margin-right: 5px;">H</span>
                        <input type="number" id="timer-minutes-input" placeholder="00" min="0" max="59"
                            style="width: 60px; background: rgba(0,0,0,0.3); border: 1px solid #444; color: var(--primary-color); border-radius: 4px; padding: 5px 0; font-family: 'Oswald'; font-size: 1.1rem; text-align: center; box-sizing: border-box;"
                            title="Minutes">
                        <span style="font-size: 0.8rem; color: #777; font-weight: bold; margin-right: 5px;">M</span>
                        <input type="number" id="timer-seconds-input" placeholder="00" min="0" max="59"
                            style="width: 60px; background: rgba(0,0,0,0.3); border: 1px solid #444; color: var(--primary-color); border-radius: 4px; padding: 5px 0; font-family: 'Oswald'; font-size: 1.1rem; text-align: center; box-sizing: border-box;"
                            title="Seconds">
                        <span style="font-size: 0.8rem; color: #777; font-weight: bold;">S</span>
                    </div>
                    <span id="timer-separator" style="color: #444;">|</span>
                    <span id="timer-display"
                        style="min-width: 110px; text-align: center; font-size: 1.2rem; font-weight: bold; letter-spacing: 1px;">00:00:00</span>
                    <button onclick="toggleTimer()" id="timer-btn"
                        style="background: none; border: none; color: #fff; cursor: pointer; display: flex; align-items: center;"><i
                            class="fa-solid fa-play"></i></button>
                    <button onclick="resetTimer()"
                        style="background: none; border: none; color: #ff4d4d; cursor: pointer; display: flex; align-items: center;"><i
                            class="fa-solid fa-rotate-right"></i></button>
                </div> <!-- End of Header Flex Row -->
            </div>


            <!-- To-Do Grid -->
            <div class="dashboard-grid">
                <div class="dashboard-card" style="max-width: 800px;">
                    <div id="todo-list-container">
                        <?php while ($row = mysqli_fetch_assoc($tasks)): ?>
                            <div class="todo-item" id="task-<?php echo $row['id']; ?>">
                                <input type="checkbox" onchange="toggleTask(<?php echo $row['id']; ?>)" <?php echo $row['is_done'] ? 'checked' : ''; ?>>
                                <span
                                    style="flex-grow: 1; <?php echo $row['is_done'] ? 'text-decoration: line-through; opacity: 0.5;' : ''; ?>">
                                    <?php echo htmlspecialchars($row['task_name']); ?>
                                </span>
                                <div style="display: flex; gap: 5px;">
                                    <button type="button" class="icon-btn edit"
                                        onclick="openEditTask(<?php echo $row['id']; ?>, '<?php echo addslashes(htmlspecialchars($row['task_name'])); ?>')">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button type="button" class="icon-btn delete"
                                        onclick="deleteTask(<?php echo $row['id']; ?>)">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <form id="add-task-form" style="margin-top: 25px; display: flex; gap: 10px;">
                        <input type="text" id="new-task-name" placeholder="Add new task..." required
                            style="flex-grow: 1; background: rgba(0,0,0,0.3); border: 1px solid #333; color: #fff; padding: 12px 15px; border-radius: 8px;">
                        <button type="submit"
                            style="background: var(--primary-color); border: none; width: 45px; height: 45px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                            <i class="fa-solid fa-plus" style="color: var(--secondary-color);"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Task Modal -->
        <div id="edit-task-modal"
            style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1100; align-items:center; justify-content:center;">
            <div class="modal-content"
                style="background: var(--secondary-color); padding: 30px; border-radius: 15px; width:100%; max-width:400px;">
                <h3 style="margin-bottom:20px; font-family:'Oswald';">Edit Task</h3>
                <form onsubmit="saveEditedTask(event)">
                    <input type="hidden" id="edit-task-id">
                    <input type="text" id="edit-task-name" required
                        style="width:100%; padding:12px; border-radius:8px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; margin-bottom:20px;">
                    <div style="display:flex; gap:10px;">
                        <button type="submit" class="btn-action">Save Changes</button>
                        <button type="button" class="btn-action" style="background:#333; color:#fff;"
                            onclick="document.getElementById('edit-task-modal').style.display='none'">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Membership Section -->
        <div id="membership" class="dashboard-section">
            <h2 style="font-family: 'Oswald', sans-serif; margin-bottom: 20px;">Membership & Payments</h2>
            <div class="dashboard-grid" style="grid-template-columns: 1fr 1.5fr; gap: 30px; margin-bottom: 40px;">
                <div class="dashboard-card">
                    <h3>Current Plan</h3>
                    <div style="text-align: center; padding: 30px 0;">
                        <h2 style="color: var(--primary-color); font-size: 3rem; font-family: 'Oswald';">
                            <?php echo htmlspecialchars($membership_plan); ?>
                        </h2>
                        <p style="font-size: 1.2rem; opacity: 0.8;">₹<?php
                        if (strpos($membership_plan, 'Yearly') !== false) {
                            if (strpos($membership_plan, 'Basic') !== false)
                                echo '3999';
                            elseif (strpos($membership_plan, 'Premium') !== false)
                                echo '9999';
                            else
                                echo '8999';
                            echo ' / year';
                        } else {
                            if ($membership_plan == 'Basic')
                                echo '399';
                            elseif ($membership_plan == 'Premium')
                                echo '999';
                            else
                                echo '899';
                            echo ' / month';
                        }
                        ?></p>
                    </div>
                    <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 25px;">
                        <p style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <span>Membership Status</span>
                            <span
                                style="color: <?php echo (strtotime($membership_expiry) < time()) ? '#ff4d4d' : '#00ff00'; ?>; font-weight: bold;">
                                <?php echo (strtotime($membership_expiry) < time()) ? 'EXPIRED' : strtoupper($membership_status); ?>
                            </span>
                        </p>
                        <p style="display: flex; justify-content: space-between;">
                            <span>Valid Until</span>
                            <span
                                style="color: var(--primary-color);"><?php echo $membership_expiry ? date('M d, Y', strtotime($membership_expiry)) : 'N/A'; ?></span>
                        </p>
                    </div>
                </div>

                <!-- Recent Transactions (Moved Here) -->
                <div class="dashboard-card">
                    <h3>Recent Transactions</h3>
                    <div class="transaction-list-container" style="font-size: 0.95rem;">
                        <?php if (mysqli_num_rows($membership_transactions) > 0): ?>
                            <?php while ($txn = mysqli_fetch_assoc($membership_transactions)): ?>
                                <div class="transaction-item"
                                    style="display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,0.05); align-items: center; transition: 0.3s;">
                                    <div>
                                        <p style="font-weight: bold;">
                                            <?php echo date('M d, Y', strtotime($txn['created_at'])); ?>
                                        </p>
                                        <p style="font-size: 0.8rem; color: var(--text-gray);">
                                            <?php echo $txn['plan_name']; ?>
                                            • <?php echo $txn['payment_method']; ?>
                                        </p>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <span style="color: var(--primary-color); font-weight: bold;">-
                                            ₹<?php echo number_format($txn['amount'], 2); ?></span>
                                        <a href="invoice.php?tid=<?php echo $txn['id']; ?>" target="_blank"
                                            style="color: var(--primary-color); font-size: 1.1rem;"
                                            title="Download / Print Invoice">
                                            <i class="fa-solid fa-file-invoice"></i>
                                        </a>
                                        <button type="button"
                                            onclick="deleteTransaction(<?php echo $txn['id']; ?>, this.closest('.transaction-item'))"
                                            style="background:none; border:none; color:#ff4d4d; cursor:pointer; font-size: 1.1rem; padding: 0;"
                                            title="Delete Transaction">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: var(--text-gray); padding: 20px;">No transaction
                                history
                                yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upgrade / Renew Plans (Full Width) -->
                <div class="pricing-section-container monthly" id="pricing-container">
                    <h3 style="font-family:'Oswald'; margin-bottom:20px; text-align:center;">Renew or Upgrade Your Plan
                    </h3>

                    <div class="pricing-toggle">
                        <button class="toggle-btn active" onclick="togglePricingPeriod('monthly')">Monthly</button>
                        <button class="toggle-btn" onclick="togglePricingPeriod('yearly')">Yearly</button>
                    </div>

                    <!-- Hidden Selector for Compatibility -->
                    <div style="display:none;">
                        <select id="plan-selector">
                            <?php
                            mysqli_data_seek($plans_res, 0);
                            while ($p = mysqli_fetch_assoc($plans_res)):
                                ?>
                                <option value="<?php echo $p['price_monthly']; ?>"
                                    data-plan="<?php echo htmlspecialchars($p['name']); ?>" data-period="monthly">
                                    <?php echo htmlspecialchars($p['name']); ?> - ₹<?php echo $p['price_monthly']; ?>/mo
                                </option>
                                <option value="<?php echo $p['price_yearly']; ?>"
                                    data-plan="<?php echo htmlspecialchars($p['name']); ?> (Yearly)" data-period="yearly">
                                    <?php echo htmlspecialchars($p['name']); ?> (Yearly) -
                                    ₹<?php echo $p['price_yearly']; ?>/yr
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <span id="payment-amt-display">₹0</span>
                    </div>

                    <div class="pricing-grid">
                        <?php
                        mysqli_data_seek($plans_res, 0);
                        while ($plan = mysqli_fetch_assoc($plans_res)):
                            $labels = json_decode($plan['feature_labels'] ?? '{}', true);
                            $hidden_features = json_decode($plan['hidden_features'] ?? '[]', true);
                            ?>
                            <div class="pricing-card <?php echo $plan['is_popular'] ? 'popular' : ''; ?>">
                                <?php if ($plan['is_popular']): ?>
                                    <div class="badge">Popular</div>
                                <?php endif; ?>

                                <h3><?php echo htmlspecialchars($plan['name']); ?></h3>

                                <div class="price-tag monthly-only">
                                    ₹<?php echo number_format($plan['price_monthly']); ?> <span>/ Mo</span>
                                </div>
                                <div class="price-tag yearly-only">
                                    ₹<?php echo number_format($plan['price_yearly']); ?> <span>/ Yr</span>
                                </div>

                                <ul class="features-list">
                                    <?php if (!in_array('gym_access', $hidden_features)): ?>
                                        <li><?php echo htmlspecialchars($labels['gym_access'] ?? 'Gym Access'); ?>
                                            <?php echo $plan['gym_access'] ? '<i class="fa-solid fa-check check"></i>' : '<i class="fa-solid fa-xmark cross"></i>'; ?>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (!in_array('free_locker', $hidden_features)): ?>
                                        <li><?php echo htmlspecialchars($labels['free_locker'] ?? 'Free Locker'); ?>
                                            <?php echo $plan['free_locker'] ? '<i class="fa-solid fa-check check"></i>' : '<i class="fa-solid fa-xmark cross"></i>'; ?>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (!in_array('group_class', $hidden_features)): ?>
                                        <li><?php echo htmlspecialchars($labels['group_class'] ?? 'Group Class'); ?>
                                            <?php echo $plan['group_class'] ? '<i class="fa-solid fa-check check"></i>' : '<i class="fa-solid fa-xmark cross"></i>'; ?>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (!in_array('personal_trainer', $hidden_features)): ?>
                                        <li><?php echo htmlspecialchars($labels['personal_trainer'] ?? 'Personal Trainer'); ?>
                                            <?php echo $plan['personal_trainer'] ? '<i class="fa-solid fa-check check"></i>' : '<i class="fa-solid fa-xmark cross"></i>'; ?>
                                        </li>
                                    <?php endif; ?>
                                    <?php if ($plan['protein_drinks_monthly'] || $plan['protein_drinks_yearly']): ?>
                                        <?php if (!in_array('protein_drinks_monthly', $hidden_features)): ?>
                                            <li class="monthly-only">
                                                <?php echo htmlspecialchars($labels['protein_drinks_monthly'] ?? 'Protein Drinks'); ?>
                                                <?php echo $plan['protein_drinks_monthly'] ? '<i class="fa-solid fa-check check"></i>' : '<i class="fa-solid fa-xmark cross"></i>'; ?>
                                            </li>
                                        <?php endif; ?>
                                        <?php if (!in_array('protein_drinks_yearly', $hidden_features)): ?>
                                            <li class="yearly-only">
                                                <?php echo htmlspecialchars($labels['protein_drinks_yearly'] ?? 'Protein Drinks'); ?>
                                                <?php echo $plan['protein_drinks_yearly'] ? '<i class="fa-solid fa-check check"></i>' : '<i class="fa-solid fa-xmark cross"></i>'; ?>
                                            </li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($plan['customized_workout_plan'] && !in_array('customized_workout_plan', $hidden_features)): ?>
                                        <li><?php echo htmlspecialchars($labels['customized_workout_plan'] ?? 'Customized Workout Plan'); ?>
                                            <i class="fa-solid fa-check check"></i>
                                        </li>
                                    <?php endif; ?>

                                    <?php if ($plan['nutrition_guide_yearly'] && !in_array('nutrition_guide_yearly', $hidden_features)): ?>
                                        <li class="yearly-only">
                                            <?php echo htmlspecialchars($labels['nutrition_guide_yearly'] ?? 'Nutrition Guide'); ?>
                                            <i class="fa-solid fa-check check"></i>
                                        </li>
                                    <?php endif; ?>

                                    <?php if ($plan['diet_consultation_yearly'] && !in_array('diet_consultation_yearly', $hidden_features)): ?>
                                        <li class="yearly-only">
                                            <?php echo htmlspecialchars($labels['diet_consultation_yearly'] ?? 'Diet Consultation'); ?>
                                            <i class="fa-solid fa-check check"></i>
                                        </li>
                                    <?php endif; ?>

                                    <?php if ($plan['personal_locker_yearly'] && !in_array('personal_locker_yearly', $hidden_features)): ?>
                                        <li class="yearly-only">
                                            <?php echo htmlspecialchars($labels['personal_locker_yearly'] ?? 'Personal Locker'); ?>
                                            <i class="fa-solid fa-check check"></i>
                                        </li>
                                    <?php endif; ?>

                                    <?php if ($plan['guest_pass_yearly'] && !in_array('guest_pass_yearly', $hidden_features)): ?>
                                        <li class="yearly-only">
                                            <?php echo htmlspecialchars($labels['guest_pass_yearly'] ?? '1 Guest Pass/Mo'); ?>
                                            <i class="fa-solid fa-check check"></i>
                                        </li>
                                    <?php endif; ?>

                                    <!-- Custom Attributes -->
                                    <?php
                                    $custom_attrs = json_decode($plan['custom_attributes'] ?? '[]', true);
                                    if (!empty($custom_attrs)):
                                        foreach ($custom_attrs as $attr):
                                            $class = "";
                                            if ($attr['monthly'] && !$attr['yearly'])
                                                $class = "monthly-only";
                                            elseif (!$attr['monthly'] && $attr['yearly'])
                                                $class = "yearly-only";
                                            ?>
                                            <li class="<?php echo $class; ?>">
                                                <?php echo htmlspecialchars($attr['name']); ?>
                                                <?php echo (!isset($attr['included']) || $attr['included'] != 0) ? '<i class="fa-solid fa-check check"></i>' : '<i class="fa-solid fa-xmark cross"></i>'; ?>
                                            </li>
                                            <?php
                                        endforeach;
                                    endif;
                                    ?>
                                </ul>

                                <button class="btn-action monthly-only" style="width:100%;"
                                    onclick="selectVisualPlan('<?php echo $plan['name']; ?>', 'monthly')">
                                    Select <?php echo htmlspecialchars($plan['name']); ?> / Mo
                                </button>
                                <button class="btn-action yearly-only" style="width:100%;"
                                    onclick="selectVisualPlan('<?php echo $plan['name']; ?>', 'yearly')">
                                    Select <?php echo htmlspecialchars($plan['name']); ?> / Yr
                                </button>

                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <script>
                    function togglePricingPeriod(period) {
                        const container = document.getElementById('pricing-container');
                        const buttons = container.querySelectorAll('.toggle-btn');

                        if (period === 'monthly') {
                            container.classList.remove('yearly');
                            container.classList.add('monthly');
                            buttons[0].classList.add('active');
                            buttons[1].classList.remove('active');
                        } else {
                            container.classList.remove('monthly');
                            container.classList.add('yearly');
                            buttons[0].classList.remove('active');
                            buttons[1].classList.add('active');
                        }
                    }

                    function selectVisualPlan(planName, period) {
                        const select = document.getElementById('plan-selector');
                        const targetName = period === 'yearly' ? planName + ' (Yearly)' : planName;

                        for (let i = 0; i < select.options.length; i++) {
                            if (select.options[i].getAttribute('data-plan') === targetName) {
                                select.selectedIndex = i;
                                break;
                            }
                        }

                        openPaymentModal();
                    }
                </script>
            </div>
        </div>

        <!-- Payment Modal -->
        <div id="payment-modal">
            <div class="modal-content" id="payment-form-area"
                style="background: var(--secondary-color); padding: 40px; border-radius: 20px; width: 100%; max-width: 450px; border: 1px solid rgba(255,255,255,0.1);position: relative;">

                <!-- Gateway Header -->
                <div style="text-align: center; margin-bottom: 30px;">
                    <div
                        style="color: var(--primary-color); font-family: 'Oswald'; font-size: 1.5rem; margin-bottom: 5px;">
                        <i class="fa-solid fa-shield-halved"></i> GymFit Secure Pay
                    </div>
                    <p style="font-size: 0.8rem; color: var(--text-gray);">🔒 SSL Encrypted Checkout</p>
                </div>

                <h3
                    style="font-family:'Oswald'; margin-bottom:20px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px;">
                    Choose Payment Method</h3>

                <div class="payment-options">
                    <div class="pay-opt active" onclick="selectPayMethod(this, 'Credit Card')">
                        <i class="fa-solid fa-credit-card"></i><br>Card
                    </div>
                    <div class="pay-opt" onclick="selectPayMethod(this, 'GPay')">
                        <i class="fa-brands fa-google-pay" style="font-size: 1.5rem;"></i><br>GPay
                    </div>
                </div>

                <div id="card-fields">
                    <input type="text" id="card-num" placeholder="Card Number (16 digits)" maxlength="16"
                        style="width:100%; padding:12px; margin-bottom:15px; border-radius:8px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff;">
                    <div style="display:flex; gap:10px; margin-bottom:15px;">
                        <input type="text" id="card-exp" placeholder="MM/YY" maxlength="5"
                            style="width:60%; padding:12px; border-radius:8px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff;">
                        <input type="text" id="card-cvv" placeholder="CVV" maxlength="3"
                            style="width:40%; padding:12px; border-radius:8px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff;">
                    </div>
                </div>

                <div id="gpay-fields" style="display:none; text-align:center; padding:20px;">
                    <p style="margin-bottom: 15px; color: var(--text-gray);">Scan QR to pay with GPay or any UPI App
                    </p>
                    <div
                        style="background: #fff; padding: 15px; border-radius: 12px; display: inline-block; margin-bottom: 10px;">
                        <div id="upi-qr-code"
                            style="width: 200px; height: 200px; margin: 0 auto; background: #fff; padding: 10px; display: flex; align-items: center; justify-content: center;">
                        </div>
                    </div>
                    <p style="font-size: 0.75rem; color: var(--text-gray); margin-bottom: 15px; cursor: help;"
                        title="Make sure your phone is on the same Wi-Fi and your computer's firewall allows incoming connections on port <?php echo $_SERVER['SERVER_PORT']; ?>.">
                        <i class="fa-solid fa-circle-info"></i> Trouble scanning?
                    </p>
                    <p style="font-size: 0.8rem; color: var(--text-gray); margin-bottom: 10px;">Or enter your UPI ID
                    </p>
                    <input type="text" id="upi-id-input" placeholder="username@upi"
                        style="width:100%; padding:12px; border-radius:8px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff;">
                </div>


                <p id="payment-error"
                    style="color:#ff4d4d; font-size:0.85rem; text-align:center; margin-top:15px; display:none;"></p>


                <button type="button" class="btn-action" style="margin-top:30px;" onclick="startProcessing()">Confirm &
                    Pay</button>
                <p onclick="closePaymentModal()"
                    style="text-align:center; margin-top:20px; cursor:pointer; opacity:0.6;">
                    Cancel</p>
            </div>

            <div class="modal-content" id="processing-payment"
                style="display:none; background: var(--secondary-color); padding: 40px; border-radius: 20px; width: 100%; max-width: 450px; border: 1px solid rgba(255,255,255,0.1); text-align:center;">
                <div class="spinner"></div>
                <h3 style="font-family:'Oswald';">Processing Payment...</h3>
                <p style="opacity:0.7;">Please do not close the window.</p>
            </div>

            <div class="modal-content" id="payment-success"
                style="display:none; text-align:center; background: var(--secondary-color); padding: 50px 40px; border-radius: 20px; width: 100%; max-width: 450px; border: 1px solid rgba(255,255,255,0.1); position: relative; overflow: hidden;">
                <!-- GPay Success Aura -->
                <div
                    style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(0, 255, 133, 0.05) 0%, transparent 70%); pointer-events: none;">
                </div>

                <div
                    style="width: 100px; height: 100px; background: #008577; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; box-shadow: 0 10px 30px rgba(0,133,119,0.3); animation: popIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
                    <i class="fa-solid fa-check" style="font-size: 3.5rem; color: #fff;"></i>
                </div>

                <h2 style="font-family:'Oswald'; color: #fff; margin-bottom: 5px; font-size: 1.8rem;">₹ <span
                        id="success-amt-display">0.00</span></h2>
                <p
                    style="color: #00ff85; font-weight: bold; margin-bottom: 25px; text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem;">
                    Payment Successful</p>

                <div
                    style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 12px; margin-bottom: 30px; text-align: left; border: 1px solid rgba(255,255,255,0.05);">
                    <p style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.9rem;">
                        <span style="color: var(--text-gray);">To:</span>
                        <span style="color: #fff; font-weight: 500;">GymFit Membership</span>
                    </p>
                    <p style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.9rem;">
                        <span style="color: var(--text-gray);">Ref No:</span>
                        <span
                            style="color: #fff; font-weight: 500;"><?php echo strtoupper(bin2hex(random_bytes(4))); ?></span>
                    </p>
                    <p style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                        <span style="color: var(--text-gray);">Method:</span>
                        <span id="success-method-display" style="color: #fff; font-weight: 500;">UPI</span>
                    </p>
                </div>

                <form method="POST" id="confirm-payment-form">
                    <input type="hidden" name="plan_name" id="final-plan">
                    <input type="hidden" name="amount" id="final-amt">
                    <input type="hidden" name="method" id="final-method">
                    <button type="submit" name="complete_payment" class="btn-action"
                        style="width: 100%; border-radius: 30px;">
                        Done
                    </button>
                </form>

                <p style="margin-top: 20px; font-size: 0.75rem; color: var(--text-gray);">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/Google_Pay_%28GPay%29_Logo.svg"
                        alt="GPay" style="height: 15px; vertical-align: middle; margin-right: 5px; opacity: 0.6;">
                    Securely processed by GPay Demo
                </p>
            </div>

        </div>

        <!-- Trainers Section -->
        <div id="trainers" class="dashboard-section">
            <h2 style="font-family: 'Oswald', sans-serif; margin-bottom: 20px;">Our Expert Trainers</h2>


            <div class="trainer-grid">
                <?php if (mysqli_num_rows($trainers_query) > 0): ?>
                    <?php while ($trainer = mysqli_fetch_assoc($trainers_query)): ?>
                        <div class="trainer-card">
                            <img src="<?php echo $trainer['image'] ? $trainer['image'] : 'https://ui-avatars.com/api/?name=' . urlencode($trainer['name']) . '&background=ceff00&color=1a1a2e'; ?>"
                                alt="<?php echo htmlspecialchars($trainer['name']); ?>" class="trainer-img">
                            <div class="trainer-info">
                                <h4><?php echo htmlspecialchars($trainer['name']); ?></h4>
                                <button class="btn-action" style="margin-top:10px; width:100%; font-size:0.8rem;"
                                    onclick="openBookingModal('<?php echo $trainer['id']; ?>', '<?php echo htmlspecialchars($trainer['name'], ENT_QUOTES); ?>')">
                                    <i class="fa-solid fa-calendar-plus"></i> Book Appointment
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div
                        style="grid-column: 1 / -1; text-align: center; padding: 50px; background: var(--card-bg); border-radius: 15px;">
                        <i class="fa-solid fa-users-slash"
                            style="font-size: 3rem; color: var(--text-gray); margin-bottom: 15px;"></i>
                        <p style="color: var(--text-gray);">No trainers available at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- My Appointments Section (New Dedicated Tab) -->
        <div id="my-appointments" class="dashboard-section">
            <h2 style="font-family: 'Oswald', sans-serif; margin-bottom: 20px;">My Appointments</h2>
            <?php
            mysqli_data_seek($my_appts_res, 0);
            if (mysqli_num_rows($my_appts_res) > 0):
                ?>
                <div class="dashboard-card">
                    <div style="overflow-x: auto;">
                        <table style="width:100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <th style="text-align:left; padding:15px; color:var(--text-gray); font-size:0.85rem;">
                                        Trainer</th>
                                    <th style="text-align:left; padding:15px; color:var(--text-gray); font-size:0.85rem;">
                                        Date & Time</th>
                                    <th style="text-align:left; padding:15px; color:var(--text-gray); font-size:0.85rem;">
                                        Status</th>
                                    <th style="text-align:left; padding:15px; color:var(--text-gray); font-size:0.85rem;">
                                        Message</th>
                                    <th style="text-align:center; padding:15px; color:var(--text-gray); font-size:0.85rem;">
                                        Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($appt = mysqli_fetch_assoc($my_appts_res)):
                                    $status_color = match ($appt['status']) {
                                        'approved' => '#00ff85',
                                        'rejected' => '#ff4d4d',
                                        default => '#ffc107'
                                    };
                                    ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                        <td style="padding:20px 15px; display:flex; align-items:center; gap:15px;">
                                            <div
                                                style="width:40px; height:40px; border-radius:50%; background: #333; overflow:hidden;">
                                                <img src="<?php echo $appt['profile_image'] ? $appt['profile_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($appt['trainer_name']); ?>"
                                                    style="width:100%; height:100%; object-fit:cover;">
                                            </div>
                                            <span
                                                style="font-size:1rem; font-weight:500;"><?php echo htmlspecialchars($appt['trainer_name']); ?></span>
                                        </td>
                                        <td style="padding:20px 15px;">
                                            <div style="font-size:0.95rem; font-weight:500; margin-bottom:5px;">
                                                <?php echo date('M d, Y', strtotime($appt['booking_date'])); ?>
                                            </div>
                                            <span
                                                style="font-size:0.85rem; color: #ceff00; background: rgba(206, 255, 0, 0.1); padding: 5px 10px; border-radius: 20px;">
                                                <i class="fa-regular fa-clock"
                                                    style="margin-right:5px;"></i><?php echo $appt['booking_time']; ?>
                                            </span>
                                        </td>
                                        <td style="padding:20px 15px;">
                                            <span
                                                style="color:<?php echo $status_color; ?>; font-weight:bold; text-transform:uppercase; font-size:0.75rem; padding: 6px 12px; background: rgba(255,255,255,0.05); border-radius: 6px; letter-spacing: 0.5px;">
                                                <?php echo $appt['status']; ?>
                                            </span>
                                        </td>
                                        <td
                                            style="padding:20px 15px; font-size:0.9rem; color: #ddd; max-width: 250px; line-height: 1.5;">
                                            <?php echo $appt['staff_message'] ? htmlspecialchars($appt['staff_message']) : '<span style="color:var(--text-gray); font-style:italic; opacity:0.5;">No message</span>'; ?>
                                        </td>
                                        <td style="padding:20px 15px; text-align:center;">
                                            <form method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this appointment?');"
                                                style="margin:0;">
                                                <input type="hidden" name="delete_appointment" value="1">
                                                <input type="hidden" name="appt_id" value="<?php echo $appt['id']; ?>">
                                                <button type="submit"
                                                    style="background:rgba(255, 77, 77, 0.1); border:none; color:#ff4d4d; cursor:pointer; width:35px; height:35px; border-radius:50%; transition:0.3s; display:inline-flex; align-items:center; justify-content:center;"
                                                    title="Delete Appointment"
                                                    onmouseover="this.style.background='#ff4d4d'; this.style.color='#fff';"
                                                    onmouseout="this.style.background='rgba(255, 77, 77, 0.1)'; this.style.color='#ff4d4d';">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div
                    style="text-align: center; padding: 60px; background: var(--card-bg); border-radius: 20px; border: 1px solid rgba(255,255,255,0.05);">
                    <div
                        style="width: 80px; height: 80px; background: rgba(255,255,255,0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                        <i class="fa-regular fa-calendar-xmark" style="font-size: 2.5rem; color: var(--text-gray);"></i>
                    </div>
                    <h3 style="font-family: 'Oswald'; margin-bottom: 10px; color: #fff;">No Appointments Yet</h3>
                    <p style="color: var(--text-gray); margin-bottom: 25px;">You haven't booked any sessions with our
                        trainers.</p>
                    <button onclick="showSection('trainers')" class="btn-action" style="max-width: 200px; margin: 0 auto;">
                        Book Now
                    </button>
                </div>
            <?php endif; ?>
            <!-- Trainer Payment History -->
            <div style="margin-top: 40px;">
                <h3 style="font-family: 'Oswald', sans-serif; margin-bottom: 20px;">Appointment Payment History</h3>
                <div class="dashboard-card">
                    <div class="transaction-list-container" style="max-height: 300px; overflow-y: auto;">
                        <?php if (mysqli_num_rows($trainer_transactions) > 0): ?>
                            <?php while ($txn = mysqli_fetch_assoc($trainer_transactions)): ?>
                                <div class="transaction-item"
                                    style="display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,0.05); align-items: center; transition: 0.3s;">
                                    <div>
                                        <p style="font-weight: bold;">
                                            <?php echo date('M d, Y', strtotime($txn['created_at'])); ?>
                                        </p>
                                        <p style="font-size: 0.8rem; color: var(--text-gray);">
                                            <?php echo $txn['plan_name']; ?>
                                            • <?php echo $txn['payment_method']; ?>
                                        </p>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <span style="color: var(--primary-color); font-weight: bold;">-
                                            ₹<?php echo number_format($txn['amount'], 2); ?></span>
                                        <a href="invoice.php?tid=<?php echo $txn['id']; ?>" target="_blank"
                                            style="color: var(--primary-color); font-size: 1.1rem;"
                                            title="Download / Print Invoice">
                                            <i class="fa-solid fa-file-invoice"></i>
                                        </a>
                                        <button type="button"
                                            onclick="deleteApptTransaction(<?php echo $txn['id']; ?>, this.closest('.transaction-item'))"
                                            style="background:none; border:none; color:#ff4d4d; cursor:pointer; font-size: 1.1rem; padding: 0;"
                                            title="Delete Transaction">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: var(--text-gray); padding: 20px;">No trainer payments
                                recorded yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Body Measurement History Section -->
        <div id="measurements" class="dashboard-section">
            <h2 style="font-family: 'Oswald', sans-serif; margin-bottom: 20px;">Body Measurement History</h2>

            <div class="dashboard-grid">
                <!-- Log Form -->
                <div class="dashboard-card">
                    <h3>Log New Measurements</h3>
                    <form method="POST" onsubmit="return validateMeasurements()">
                        <input type="hidden" name="log_measurements" value="1">
                        <div style="margin-bottom: 15px;">
                            <label style="display:block; margin-bottom:5px; color:#ddd;">Date</label>
                            <input type="date" name="date" required value="<?php echo date('Y-m-d'); ?>"
                                max="<?php echo date('Y-m-d'); ?>"
                                style="width:100%; padding:12px; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff; font-family:'Roboto', sans-serif;">
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                            <div>
                                <label style="display:block; margin-bottom:5px; color:#ddd;">Chest (in)</label>
                                <input type="number" step="0.1" name="chest" placeholder="0.0" required min="10"
                                    max="100"
                                    style="width:100%; padding:12px; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;">
                            </div>
                            <div>
                                <label style="display:block; margin-bottom:5px; color:#ddd;">Waist (in)</label>
                                <input type="number" step="0.1" name="waist" placeholder="0.0" required min="10"
                                    max="100"
                                    style="width:100%; padding:12px; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;">
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:20px;">
                            <div>
                                <label style="display:block; margin-bottom:5px; color:#ddd;">Arms (in)</label>
                                <input type="number" step="0.1" name="arms" placeholder="0.0" required min="5" max="30"
                                    style="width:100%; padding:12px; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;">
                            </div>
                            <div>
                                <label style="display:block; margin-bottom:5px; color:#ddd;">Thighs (in)</label>
                                <input type="number" step="0.1" name="thighs" placeholder="0.0" required min="10"
                                    max="50"
                                    style="width:100%; padding:12px; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;">
                            </div>
                        </div>
                        <button type="submit" class="btn-action">Save Log</button>
                    </form>
                </div>

                <!-- History Table -->
                <div class="dashboard-card">
                    <h3>History (Monthly View)</h3>
                    <style>
                        /* Custom Scrollbar for History Table */
                        .history-scroll::-webkit-scrollbar {
                            width: 8px;
                        }

                        .history-scroll::-webkit-scrollbar-track {
                            background: rgba(255, 255, 255, 0.05);
                            border-radius: 4px;
                        }

                        .history-scroll::-webkit-scrollbar-thumb {
                            background: var(--primary-color);
                            border-radius: 4px;
                        }

                        .history-scroll::-webkit-scrollbar-thumb:hover {
                            background: #b3dd00;
                        }
                    </style>
                    <div class="history-scroll" style="overflow-y:auto; max-height: 400px; padding-right: 5px;">
                        <table style="width:100%; border-collapse:collapse; color:#ddd; font-size:0.9rem;">
                            <thead style="position: sticky; top: 0; background: var(--card-bg); z-index: 10;">
                                <tr style="border-bottom:1px solid rgba(255,255,255,0.1); text-align:left;">
                                    <th style="padding:15px; color:var(--text-gray);">Date</th>
                                    <th style="padding:15px; color:var(--text-gray);">Chest</th>
                                    <th style="padding:15px; color:var(--text-gray);">Waist</th>
                                    <th style="padding:15px; color:var(--text-gray);">Arms</th>
                                    <th style="padding:15px; color:var(--text-gray);">Thighs</th>
                                    <th style="padding:15px; color:var(--text-gray); text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($measure_res) > 0): ?>
                                    <?php
                                    mysqli_data_seek($measure_res, 0);
                                    while ($row = mysqli_fetch_assoc($measure_res)):
                                        ?>
                                        <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                                            <td style="padding:15px; color:var(--primary-color); font-weight:bold;">
                                                <?php echo date('M d, Y', strtotime($row['recorded_at'])); ?>
                                            </td>
                                            <td style="padding:15px;"><?php echo $row['chest']; ?>"</td>
                                            <td style="padding:15px;"><?php echo $row['waist']; ?>"</td>
                                            <td style="padding:15px;"><?php echo $row['arms']; ?>"</td>
                                            <td style="padding:15px;"><?php echo $row['thighs']; ?>"</td>
                                            <td style="padding:15px; text-align:center;">
                                                <button
                                                    onclick="editMeasurement('<?php echo $row['recorded_at']; ?>', '<?php echo $row['chest']; ?>', '<?php echo $row['waist']; ?>', '<?php echo $row['arms']; ?>', '<?php echo $row['thighs']; ?>')"
                                                    style="background:none; border:none; color:var(--text-gray); cursor:pointer; margin-right: 10px; transition: 0.3s;"
                                                    onmouseover="this.style.color='#fff'"
                                                    onmouseout="this.style.color='var(--text-gray)'" title="Edit">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                                <form method="POST" onsubmit="return confirm('Delete this entry?');"
                                                    style="display:inline;">
                                                    <input type="hidden" name="delete_measurement" value="1">
                                                    <input type="hidden" name="measure_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit"
                                                        style="background:none; border:none; color:#ff4d4d; cursor:pointer; transition: 0.3s;"
                                                        title="Delete">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="padding:30px; text-align:center; color:var(--text-gray);">No
                                            logs yet. Start tracking today!</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Section -->
        <div id="chat" class="dashboard-section">
            <h2 style="font-family: 'Oswald', sans-serif; margin-bottom: 20px;">Chat with Trainer</h2>
            <div class="dashboard-grid" style="grid-template-columns: 300px 1fr; min-height: 500px; gap: 0;">
                <!-- Chat Sidebar (Trainers List) -->
                <div class="dashboard-card"
                    style="padding: 0; overflow: hidden; display: flex; flex-direction: column; border-right: 1px solid rgba(255,255,255,0.05); border-radius: 15px 0 0 15px;">
                    <div
                        style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); background: rgba(0,0,0,0.2);">
                        <h4 style="margin:0; color:var(--primary-color);">Trainers</h4>
                    </div>
                    <div style="padding: 10px 20px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <input type="text" id="chat-search"
                            onkeyup="filterChatUsers('chat-search', 'chat-trainers-list')"
                            placeholder="Search Trainer..."
                            style="width: 100%; padding: 8px 12px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.3); color: #fff; font-size: 0.9rem;">
                    </div>
                    <div id="chat-trainers-list" style="flex:1; overflow-y: auto;">
                        <?php
                        $chat_trainers = mysqli_query($link, "SELECT * FROM trainers WHERE user_id IS NOT NULL");
                        if (mysqli_num_rows($chat_trainers) > 0):
                            while ($ct = mysqli_fetch_assoc($chat_trainers)):
                                ?>
                                <div class="chat-user-item"
                                    onclick="openChatWith(this, <?php echo $ct['user_id']; ?>, '<?php echo htmlspecialchars($ct['name']); ?>', '<?php echo $ct['image']; ?>')"
                                    style="padding:15px 20px; cursor:pointer; display:flex; align-items:center; gap:15px; border-bottom:1px solid rgba(255,255,255,0.05); transition:0.2s;">
                                    <img src="<?php echo $ct['image']; ?>"
                                        style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                                    <div>
                                        <h5 style="margin:0; font-size:0.95rem; color:#fff;" class="chat-user-name">
                                            <?php echo htmlspecialchars($ct['name']); ?>
                                            <?php
                                            // Check for unread messages from this trainer
                                            $t_id = $ct['user_id'];
                                            $unread_check = mysqli_query($link, "SELECT COUNT(*) as cnt FROM messages WHERE sender_id = $t_id AND receiver_id = $user_id AND is_read = 0");
                                            $unread_data = mysqli_fetch_assoc($unread_check);
                                            if ($unread_data['cnt'] > 0):
                                                ?>
                                                <i class="fa-solid fa-circle"
                                                    style="color: #ceff00; font-size: 8px; margin-left: 8px; vertical-align: middle; box-shadow: 0 0 5px #ceff00;"></i>
                                            <?php endif; ?>
                                        </h5>
                                        <small style="color:#aaa;">Personal Trainer</small>
                                    </div>
                                </div>
                            <?php endwhile;
                        else: ?>
                            <p style="padding:20px; text-align:center; color:#666;">No trainers available for chat.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Chat Window -->
                <div class="dashboard-card"
                    style="padding: 0; display: flex; flex-direction: column; overflow: hidden; border-radius: 0 15px 15px 0;">
                    <div id="chat-header"
                        style="padding: 15px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); background: rgba(0,0,0,0.2); display:none;">
                        <div style="display:flex; align-items:center; gap:15px;">
                            <img id="chat-partner-img" src=""
                                style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                            <div>
                                <h4 id="chat-partner-name" style="margin:0; font-family:'Oswald';">Trainer Name</h4>
                            </div>
                        </div>
                    </div>

                    <div id="chat-messages"
                        style="flex:1; padding: 20px; overflow-y: auto; display:flex; flex-direction:column; gap:10px; background: rgba(0,0,0,0.2);">
                        <div style="text-align:center; color:#666; margin-top:50px;">
                            <i class="fa-regular fa-comments"
                                style="font-size:3rem; margin-bottom:15px; opacity:0.5;"></i>
                            <p>Select a trainer to start chatting</p>
                        </div>
                    </div>

                    <div id="chat-input-area"
                        style="padding: 20px; border-top: 1px solid rgba(255,255,255,0.05); background: rgba(0,0,0,0.2); display:none;">
                        <form onsubmit="event.preventDefault(); sendChatMessage();" style="display:flex; gap:15px;">
                            <input type="text" id="chat-input" placeholder="Type a message..."
                                style="flex:1; padding:12px 20px; border-radius:30px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.05); color:#fff; outline:none;">
                            <button type="submit"
                                style="width:45px; height:45px; border-radius:50%; border:none; background:var(--primary-color); color:#000; font-size:1.1rem; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:0.3s;">
                                <i class="fa-solid fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>


            <script>
                function editMeasurement(date, chest, waist, arms, thighs) {
                    const form = document.querySelector('#measurements form');
                    form.querySelector('[name="date"]').value = date;
                    form.querySelector('[name="chest"]').value = chest;
                    form.querySelector('[name="waist"]').value = waist;
                    form.querySelector('[name="arms"]').value = arms;
                    form.querySelector('[name="thighs"]').value = thighs;

                    // Scroll to top of form
                    form.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    // Highlight form temporarily
                    form.style.transition = 'box-shadow 0.5s';
                    form.style.boxShadow = '0 0 20px rgba(206, 255, 0, 0.3)';
                    setTimeout(() => {
                        form.style.boxShadow = 'none';
                    }, 1500);
                }
                function validateMeasurements() {
                    const form = document.querySelector('#measurements form');
                    const chest = parseFloat(form.querySelector('[name="chest"]').value);
                    const waist = parseFloat(form.querySelector('[name="waist"]').value);
                    const arms = parseFloat(form.querySelector('[name="arms"]').value);
                    const thighs = parseFloat(form.querySelector('[name="thighs"]').value);

                    if (chest <= 0 || waist <= 0 || arms <= 0 || thighs <= 0) {
                        alert('All measurements must be positive numbers!');
                        return false;
                    }
                    // Optional: stricter checks here if needed
                    return true;
                }
            </script>
        </div>

        <!-- Attendance Section -->
        <div id="attendance" class="dashboard-section">


            <div class="dashboard-card">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="margin-bottom:0;">Attendance History (<?php echo date('F Y'); ?>)</h3>
                    <button onclick="document.getElementById('reports-modal').style.display='flex'"
                        class="icon-btn edit"
                        style="background:none; color:#fff; cursor:pointer; font-size:1rem; border:1px solid rgba(255,255,255,0.2); padding:5px 10px; border-radius:5px;"
                        title="View Past Reports">
                        <i class="fa-solid fa-folder-open"></i> View Attendance
                    </button>
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:12px; justify-content: flex-start; margin-top: 20px;">
                    <?php
                    $att_view_month = date('m');
                    $att_view_year = date('Y');
                    $att_days_in_month = date('t');

                    for ($d = 1; $d <= $att_days_in_month; $d++):
                        $d_str = sprintf('%04d-%02d-%02d', $att_view_year, $att_view_month, $d);
                        $is_att = in_array($d_str, $attendance_dates);
                        $is_future = strtotime($d_str) > time();
                        $is_before_join = $d_str < $join_date;
                        $is_today = ($d_str == date('Y-m-d'));

                        $bg_color = $is_att ? 'var(--primary-color)' : 'rgba(255,255,255,0.05)';
                        $text_color = $is_att ? '#000' : '#fff';
                        $border = $is_today ? '2px solid #fff' : '1px solid transparent';

                        $opacity = '1';
                        $status_js = $is_att ? 'Present' : 'Absent';
                        $is_future_js = 'false';

                        if ($is_future) {
                            $opacity = '0.3';
                            $is_future_js = 'true';
                        } elseif ($is_before_join) {
                            $opacity = '0.1';
                            $status_js = 'Not Joined';
                            $bg_color = 'transparent'; // clearer distinction
                        }

                        ?>
                        <div style="
                        width: 45px; height: 45px; 
                        background: <?php echo $bg_color; ?>; 
                        color: <?php echo $text_color; ?>;
                        border-radius: 10px; 
                        display:flex; align-items:center; justify-content:center;
                        font-weight:bold; font-size:1rem; font-family: 'Oswald';
                        border: <?php echo $border; ?>;
                        cursor: <?php echo ($is_before_join || $is_future) ? 'default' : 'pointer'; ?>;
                        opacity: <?php echo $opacity; ?>;
                    " onclick="<?php echo ($is_before_join || $is_future) ? '' : "showAttendanceStatus('" . date('M d, Y', strtotime($d_str)) . "', '$status_js', $is_future_js)"; ?>"
                            title="<?php echo date('M d, Y', strtotime($d_str)) . ($is_before_join ? ' - Not Joined' : ($is_att ? ' - Present' : ($is_future ? '' : ' - Absent'))); ?>">
                            <?php echo $d; ?>
                        </div>
                    <?php endfor; ?>
                </div>
                <div style="margin-top:20px; display:flex; gap:20px; font-size:0.85rem; color:var(--text-gray);">
                    <span id="legend-present"
                        style="display:flex; align-items:center; gap:8px; padding: 5px 10px; border-radius: 8px; transition: 0.3s; border: 1px solid transparent;">
                        <div id="legend-present-box"
                            style="width:12px; height:12px; background:var(--primary-color); border-radius:4px; transition: 0.3s;">
                        </div>
                        Present
                    </span>
                    <span id="legend-absent"
                        style="display:flex; align-items:center; gap:8px; padding: 5px 10px; border-radius: 8px; transition: 0.3s; border: 1px solid transparent;">
                        <div id="legend-absent-box"
                            style="width:12px; height:12px; background:rgba(255,255,255,0.1); border-radius:4px; transition: 0.3s;">
                        </div> Absent
                    </span>
                </div>
            </div>
        </div>

        <!-- Profile Section -->
        <div id="profile" class="dashboard-section">
            <h2 style="font-family: 'Oswald', sans-serif; margin-bottom: 20px;">Profile Settings</h2>
            <div class="dashboard-card" style="max-width: 650px;">
                <form method="POST" enctype="multipart/form-data" id="profile-form">
                    <div class="profile-img-container">
                        <img id="profile-preview"
                            src="<?php echo $profile_image ? $profile_image : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION["full_name"]) . '&background=ceff00&color=1a1a2e'; ?>"
                            onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name']); ?>&background=ceff00&color=1a1a2e';">
                        <label for="profile_image_file" class="upload-overlay">
                            <i class="fa-solid fa-camera"></i>
                        </label>
                        <input type="file" id="profile_image_file" name="profile_image_file" accept="image/*"
                            style="display:none;" onchange="previewImage(this)">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                        <div>
                            <label
                                style="display: block; font-size: 0.85rem; color: var(--text-gray); margin-bottom: 8px;">Full
                                Name</label>
                            <input type="text" name="full_name"
                                value="<?php echo htmlspecialchars($user_data["full_name"]); ?>" required
                                style="width: 100%; padding: 12px; border-radius: 8px; background: rgba(0,0,0,0.3); color: #fff; border: 1px solid #333;">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: 0.85rem; color: var(--text-gray); margin-bottom: 8px;">Email
                                Address</label>
                            <input type="email" name="email"
                                value="<?php echo htmlspecialchars($user_data["email"]); ?>" required
                                style="width: 100%; padding: 12px; border-radius: 8px; background: rgba(0,0,0,0.3); color: #fff; border: 1px solid #333; opacity: 0.8;">
                        </div>
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label
                            style="display: block; font-size: 0.85rem; color: var(--text-gray); margin-bottom: 8px;">Change
                            Password (leave blank to keep current)</label>
                        <div style="position: relative;">
                            <input type="password" name="new_password" id="new_password_input"
                                placeholder="Enter new password"
                                style="width: 100%; padding: 12px; padding-right: 40px; border-radius: 8px; background: rgba(0,0,0,0.3); color: #fff; border: 1px solid #333;">
                            <i class="fa-solid fa-eye" id="toggle-password"
                                style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #aaa;"></i>
                        </div>
                    </div>

                    <button type="submit" name="update_profile" class="btn-action">
                        <i class="fa-solid fa-floppy-disk"></i> Update Profile Details
                    </button>
                    <p style="text-align: center; font-size: 0.8rem; color: var(--text-gray); margin-top: 15px;">
                        Changes
                        will be saved permanently to your account.</p>
                </form>
            </div>
        </div>

        <!-- Progress Photos Section -->
        <div id="progress-photos" class="dashboard-section">
            <h2 style="font-family: 'Oswald', sans-serif; margin-bottom: 20px;">My Progress Photos</h2>

            <div class="dashboard-grid">
                <!-- Upload Card -->
                <div class="dashboard-card">
                    <h3>Upload New Photo</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="upload_progress_photo" value="1">

                        <div style="margin-bottom: 15px;">
                            <label style="display:block; margin-bottom:5px; color:#ddd;">Date Taken</label>
                            <input type="date" name="date_taken" required value="<?php echo date('Y-m-d'); ?>"
                                max="<?php echo date('Y-m-d'); ?>"
                                style="width:100%; padding:12px; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;">
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label style="display:block; margin-bottom:5px; color:#ddd;">Photo Type</label>
                            <select name="photo_type"
                                style="width:100%; padding:12px; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;">
                                <option value="progress" style="background: #2b2b2b; color: #fff;">Progress Update
                                </option>
                                <option value="before" style="background: #2b2b2b; color: #fff;">Before Transformation
                                </option>
                                <option value="after" style="background: #2b2b2b; color: #fff;">After Transformation
                                </option>
                            </select>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label style="display:block; margin-bottom:5px; color:#ddd;">Notes (Optional)</label>
                            <textarea name="notes" placeholder="Weight, feelings, etc." rows="3"
                                style="width:100%; padding:12px; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff;"></textarea>
                        </div>

                        <div class="profile-img-container"
                            style="margin-bottom: 20px; height: 200px; background: rgba(0,0,0,0.2); border: 2px dashed rgba(255,255,255,0.1); position: relative; overflow: hidden;">
                            <label for="progress_photo_file" class="upload-overlay" id="upload-label"
                                style="opacity: 1; background: none; flex-direction: column; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; position: absolute; top:0; left:0; cursor: pointer; z-index: 2;color: #fff;">
                                <i class="fa-solid fa-cloud-arrow-up" style="font-size: 2rem; margin-bottom: 10px;"></i>
                                <span>Click to Select Photo</span>
                            </label>
                            <img id="progress-preview" src=""
                                style="display:none; width: 100%; height: 100%; object-fit: contain; position: absolute; top:0; left:0; z-index: 1;">
                            <input type="file" id="progress_photo_file" name="photo_file" accept="image/*"
                                style="display:none;" onchange="previewProgressImage(this)">
                        </div>

                        <button type="submit" class="btn-action">
                            <i class="fa-solid fa-upload"></i> Upload Photo
                        </button>
                    </form>
                </div>

                <!-- Timeline / Gallery -->
                <div class="dashboard-card">
                    <h3>Photo Timeline</h3>
                    <?php if (empty($progress_photos)): ?>
                        <div style="text-align: center; padding: 40px; color: var(--text-gray);">
                            <i class="fa-regular fa-images" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                            <p>No photos uploaded yet. Start tracking your visual progress!</p>
                        </div>
                    <?php else: ?>
                        <div class="timeline-container"
                            style="display: flex; flex-direction: column; gap: 20px; max-height: 600px; overflow-y: auto; padding-right: 10px;">
                            <?php foreach ($progress_photos as $photo): ?>
                                <div class="photo-item"
                                    style="display: flex; gap: 15px; background: rgba(255,255,255,0.03); padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                                    <div style="width: 120px; height: 120px; flex-shrink: 0; background: #000; border-radius: 8px; overflow: hidden; cursor: pointer;"
                                        onclick="viewFullImage('<?php echo htmlspecialchars($photo['photo_path']); ?>')">
                                        <img src="<?php echo htmlspecialchars($photo['photo_path']); ?>"
                                            style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <div style="flex-grow: 1;">
                                        <div
                                            style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                                            <div>
                                                <span style="
                                                    text-transform: uppercase; 
                                                    font-size: 0.7rem; 
                                                    font-weight: bold; 
                                                    padding: 2px 8px; 
                                                    border-radius: 4px;
                                                    background: <?php
                                                    if ($photo['photo_type'] == 'before')
                                                        echo 'rgba(255, 77, 77, 0.2)';
                                                    elseif ($photo['photo_type'] == 'after')
                                                        echo 'rgba(0, 255, 0, 0.2)';
                                                    else
                                                        echo 'rgba(206, 255, 0, 0.2)';
                                                    ?>;
                                                    color: <?php
                                                    if ($photo['photo_type'] == 'before')
                                                        echo '#ff4d4d';
                                                    elseif ($photo['photo_type'] == 'after')
                                                        echo '#00ff00';
                                                    else
                                                        echo 'var(--primary-color)';
                                                    ?>;
                                                ">
                                                    <?php echo strtoupper($photo['photo_type']); ?>
                                                </span>
                                                <span
                                                    style="color: #fff; font-weight: bold; font-size: 0.95rem; margin-left: 10px;">
                                                    <?php echo date('M d, Y', strtotime($photo['date_taken'])); ?>
                                                </span>
                                            </div>
                                            <form method="POST" onsubmit="return confirm('Delete this photo permanently?');">
                                                <input type="hidden" name="delete_progress_photo" value="1">
                                                <input type="hidden" name="photo_id" value="<?php echo $photo['id']; ?>">
                                                <button type="submit"
                                                    style="background: none; border: none; color: #ff4d4d; cursor: pointer; padding: 5px;">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                        <?php if (!empty($photo['notes'])): ?>
                                            <p
                                                style="color: var(--text-gray); font-size: 0.9rem; background: rgba(0,0,0,0.2); padding: 8px; border-radius: 6px; margin: 0;">
                                                <i class="fa-solid fa-quote-left"
                                                    style="font-size: 0.7rem; opacity: 0.5; vertical-align: top; margin-right: 5px;"></i>
                                                <?php echo nl2br(htmlspecialchars($photo['notes'])); ?>
                                            </p>
                                        <?php else: ?>
                                            <p style="color: #555; font-size: 0.85rem; font-style: italic;">No notes added.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <script>
                function previewProgressImage(input) {
                    if (input.files && input.files[0]) {
                        var reader = new FileReader();
                        reader.onload = function (e) {
                            const preview = document.getElementById('progress-preview');
                            const label = document.getElementById('upload-label');

                            preview.src = e.target.result;
                            preview.style.display = 'block';

                            // Adjust label to overlap nicely or hide
                            label.style.opacity = '0';
                            label.onmouseover = function () { this.style.opacity = '1'; this.style.background = 'rgba(0,0,0,0.5)'; };
                            label.onmouseout = function () { this.style.opacity = '0'; this.style.background = 'none'; };
                        }
                        reader.readAsDataURL(input.files[0]);
                    }
                }

                function viewFullImage(src) {
                    // Create simple modal for full view
                    const modal = document.createElement('div');
                    modal.style.position = 'fixed';
                    modal.style.top = '0';
                    modal.style.left = '0';
                    modal.style.width = '100%';
                    modal.style.height = '100%';
                    modal.style.background = 'rgba(0,0,0,0.9)';
                    modal.style.zIndex = '2000';
                    modal.style.display = 'flex';
                    modal.style.alignItems = 'center';
                    modal.style.justifyContent = 'center';
                    modal.style.cursor = 'zoom-out';

                    modal.onclick = function () { document.body.removeChild(modal); };

                    const img = document.createElement('img');
                    img.src = src;
                    img.style.maxHeight = '90%';
                    img.style.maxWidth = '90%';
                    img.style.objectFit = 'contain';
                    img.style.borderRadius = '10px';
                    img.style.boxShadow = '0 0 50px rgba(0,0,0,0.5)';
                    img.style.animation = 'scaleIn 0.3s ease';

                    const style = document.createElement('style');
                    style.innerHTML = '@keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }';
                    document.head.appendChild(style);

                    modal.appendChild(img);
                    document.body.appendChild(modal);
                }
            </script>




        </div>

        <!-- BMI Calculator Section -->
        <div id="bmi-calculator" class="dashboard-section">
            <h2 style="font-family: 'Oswald', sans-serif; margin-bottom: 20px;">BMI Calculator & Health Guide</h2>
            <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr; gap:30px;">
                <!-- Calculator Card -->
                <div class="dashboard-card">
                    <h3 style="font-family:'Oswald'; color: var(--primary-color); margin-bottom: 20px;">Calculate
                        Your
                        BMI</h3>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 8px; font-weight: 500;">Weight (kg)</label>
                        <input type="number" id="bmi-weight-input" placeholder="e.g. 70" min="20" max="300"
                            style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 8px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 25px;">
                        <label style="display:block; margin-bottom: 8px; font-weight: 500;">Height (cm)</label>
                        <input type="number" id="bmi-height-input" placeholder="e.g. 175" min="50" max="300"
                            style="width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 8px;">
                    </div>
                    <button class="btn-action" onclick="calculateBMI()" style="width: 100%; border-radius: 8px;">
                        <i class="fa-solid fa-calculator"></i> Calculate BMI
                    </button>
                </div>

                <!-- Result Card -->
                <div class="dashboard-card" id="bmi-result-card"
                    style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; opacity: 0.5; pointer-events: none;">

                    <div style="position: relative; width: 250px; height: 150px; margin-bottom: 20px;">
                        <!-- Gauge Background -->
                        <div
                            style="width: 100%; height: 100%; border-radius: 150px 150px 0 0; background: linear-gradient(to right, #7fa8d1 0%, #7fa8d1 25%, #8bc34a 25%, #8bc34a 50%, #ffb74d 50%, #ffb74d 75%, #e57373 75%, #e57373 100%); opacity: 0.3;">
                        </div>
                        <!-- Needle -->
                        <div id="bmi-needle"
                            style="position: absolute; bottom: 0; left: 50%; width: 4px; height: 110px; background: #fff; transform-origin: bottom center; transform: rotate(-90deg); transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 0 10px rgba(0,0,0,0.5);">
                        </div>
                        <div
                            style="position: absolute; bottom: -10px; left: 50%; transform: translateX(-50%); width: 20px; height: 20px; background: #fff; border-radius: 50%;">
                        </div>
                    </div>

                    <h1 id="bmi-val-display" style="font-family: 'Oswald'; font-size: 4rem; margin: 0; color: #fff;">
                        --.-</h1>
                    <h4 id="bmi-cat-display"
                        style="font-family: 'Oswald'; letter-spacing: 1px; color: var(--text-gray); text-transform: uppercase; margin-bottom: 20px;">
                        Enter Details</h4>

                    <p id="bmi-advice" style="font-size: 0.9rem; color: #ccc; line-height: 1.5; max-width: 90%;">
                        Start by entering your weight and height to get a personalized health assessment.
                    </p>
                </div>
            </div>

            <!-- Suggestion Chart -->
            <div class="dashboard-card" style="margin-top: 30px;">
                <h3 style="font-family:'Oswald'; margin-bottom: 20px;">BMI Classification Reference</h3>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <thead>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <th style="text-align: left; padding: 12px; color: var(--text-gray);">Category</th>
                                <th style="text-align: left; padding: 12px; color: var(--text-gray);">BMI Range</th>
                                <th style="text-align: left; padding: 12px; color: var(--text-gray);">Health
                                    Suggestion
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 12px; color: #7fa8d1;"><i class="fa-solid fa-circle"></i>
                                    Underweight</td>
                                <td style="padding: 12px;">&lt; 18.5</td>
                                <td style="padding: 12px; color: #ccc;">Focus on nutrient-rich foods and strength
                                    training to build muscle mass safely.</td>
                            </tr>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 12px; color: #8bc34a;"><i class="fa-solid fa-circle"></i> Normal
                                    Weight</td>
                                <td style="padding: 12px;">18.5 – 24.9</td>
                                <td style="padding: 12px; color: #ccc;">Great job! Maintain a balanced diet and
                                    regular
                                    exercise routine.</td>
                            </tr>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 12px; color: #ffb74d;"><i class="fa-solid fa-circle"></i>
                                    Overweight
                                </td>
                                <td style="padding: 12px;">25 – 29.9</td>
                                <td style="padding: 12px; color: #ccc;">Consider a slight caloric deficit and
                                    increased
                                    cardio activity.</td>
                            </tr>
                            <tr>
                                <td style="padding: 12px; color: #e57373;"><i class="fa-solid fa-circle"></i> Obese
                                </td>
                                <td style="padding: 12px;">30 & Above</td>
                                <td style="padding: 12px; color: #ccc;">Consult a professional provided at gym.
                                    Focus on
                                    low-impact cardio and dietary changes.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <script>
                function calculateBMI() {
                    const weight = parseFloat(document.getElementById('bmi-weight-input').value);
                    const height = parseFloat(document.getElementById('bmi-height-input').value);

                    if (!weight || !height || weight <= 0 || height <= 0) {
                        alert("Please enter valid positive numbers for weight and height.");
                        return;
                    }

                    // Calculate BMI
                    const heightInMeters = height / 100;
                    const bmi = (weight / (heightInMeters * heightInMeters)).toFixed(1);

                    // UI Updates
                    const resultCard = document.getElementById('bmi-result-card');
                    const valDisplay = document.getElementById('bmi-val-display');
                    const catDisplay = document.getElementById('bmi-cat-display');
                    const adviceDisplay = document.getElementById('bmi-advice');
                    const needle = document.getElementById('bmi-needle');

                    resultCard.style.opacity = '1';
                    resultCard.style.pointerEvents = 'all';
                    valDisplay.innerText = bmi;

                    // Determine Category
                    let category = '';
                    let color = '';
                    let rotation = -90; // Start at left
                    let advice = '';

                    // Rotation logic: Map BMI 10-40 to -90 to +90 degrees (approx)
                    // Min 15, Max 40 for gauge visual
                    let clampedBMI = Math.max(15, Math.min(40, bmi));
                    // 15 = -90deg, 40 = 90deg -> Range 25 units, 180 degrees
                    // degrees = (bmi - 15) * (180/25) - 90
                    rotation = ((clampedBMI - 15) * (180 / 25)) - 90;


                    if (bmi < 18.5) {
                        category = 'Underweight';
                        color = '#7fa8d1';
                        advice = 'You are in the underweight range. It is recommended to eat more frequently, choose nutrient-rich foods, and incorporate strength training to build muscle.';
                    } else if (bmi >= 18.5 && bmi < 25) {
                        category = 'Normal Weight';
                        color = '#8bc34a'; // Primary brand color is visually clearer
                        advice = 'You have a healthy body weight! Keep maintaining your balanced diet and regular physical activity to stay fit.';
                    } else if (bmi >= 25 && bmi < 30) {
                        category = 'Overweight';
                        color = '#ffb74d';
                        advice = 'You are in the overweight range. A combination of a healthy, controlled diet and increased daily physical activity can help you reach a healthy weight.';
                    } else {
                        category = 'Obese';
                        color = '#e57373';
                        advice = 'Your BMI indicates obesity. It is advisable to consult a healthcare provider or a professional trainer for a safe and effective weight loss plan.';
                    }

                    valDisplay.style.color = color;
                    catDisplay.innerText = category;
                    catDisplay.style.color = color;
                    adviceDisplay.innerText = advice;

                    // Animate Needle
                    needle.style.transform = `rotate(${rotation}deg)`;
                    needle.style.background = color;
                }
            </script>
        </div>

        <!-- Gym Store Section -->
        <div id="gym-store" class="dashboard-section">
            <?php
            // Fetch Store History
            $store_history = [];
            // Assuming 'created_at' exists. If not, we might need to check DB schema. 
            // Using 'id' for ordering as fallback if created_at is not standard, but usually it is.
            // Let's rely on checking if created_at exists or just select *
            $hist_sql = "SELECT plan_name, amount, payment_method, status, created_at FROM transactions WHERE user_id = ? AND plan_name LIKE 'Store:%' ORDER BY created_at DESC";

            // Fallback if created_at doesn't exist? No, let's try.
            if ($stmt = mysqli_prepare($link, $hist_sql)) {
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                while ($row = mysqli_fetch_assoc($res)) {
                    $store_history[] = $row;
                }
                mysqli_stmt_close($stmt);
            }
            ?>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="font-family: 'Oswald', sans-serif; margin: 0;">Gym Store</h2>
                <button onclick="document.getElementById('history-modal').style.display='flex'" class="btn-action"
                    style="width: auto; display: inline-flex; align-items: center; justify-content: center; padding: 10px 20px; font-size: 0.9rem; border-radius: 5px;">
                    <i class="fa-solid fa-clock-rotate-left" style="margin-right: 8px;"></i> Payment History
                </button>
            </div>

            <!-- History Modal -->
            <div id="history-modal"
                style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 2200; align-items: center; justify-content: center;">
                <div
                    style="background: #1a1a2e; border-radius: 15px; width: 95%; max-width: 700px; max-height: 80vh; display: flex; flex-direction: column; position: relative; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 0 30px rgba(0,0,0,0.5);">

                    <!-- Fixed Header Section -->
                    <div
                        style="padding: 30px 30px 15px 30px; flex-shrink: 0; border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <button onclick="document.getElementById('history-modal').style.display='none'"
                            style="position: absolute; top: 15px; right: 15px; background: none; border: none; color: #fff; cursor: pointer; font-size: 1.2rem;"><i
                                class="fa-solid fa-xmark"></i></button>

                        <h3 style="font-family: 'Oswald'; margin-bottom: 15px; color: var(--primary-color);">Purchase
                            History</h3>

                        <!-- Search Bar -->
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="store-history-search" placeholder="Search items..."
                                onkeyup="filterStoreHistory()"
                                style="flex-grow: 1; padding: 10px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.3); color: #fff;">
                        </div>
                    </div>

                    <?php
                    // Update query to include ID and filter out deleted items
                    $store_history = [];
                    $hist_sql = "SELECT id, plan_name, amount, payment_method, status, created_at FROM transactions WHERE user_id = ? AND plan_name LIKE 'Store:%' AND is_deleted_by_user = 0 ORDER BY created_at DESC";

                    if ($stmt = mysqli_prepare($link, $hist_sql)) {
                        mysqli_stmt_bind_param($stmt, "i", $user_id);
                        mysqli_stmt_execute($stmt);
                        $res = mysqli_stmt_get_result($stmt);
                        while ($row = mysqli_fetch_assoc($res)) {
                            $store_history[] = $row;
                        }
                        mysqli_stmt_close($stmt);
                    }
                    ?>

                    <!-- Scrollable List Area -->
                    <div class="custom-scrollbar"
                        style="max-height: 320px; overflow-y: auto; padding: 0 30px 30px 30px;">
                        <?php if (empty($store_history)): ?>
                                <p style="color: var(--text-gray); text-align: center; margin-top: 30px;">No store purchases
                                    found.</p>
                        <?php else: ?>
                                <div style="overflow-x: auto; margin-top: 15px;">
                                    <table id="store-history-table"
                                        style="width: 100%; border-collapse: collapse; min-width: 600px;">
                                        <thead style="position: sticky; top: 0; background: #1a1a2e; z-index: 10;">
                                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); text-align: left;">
                                                <th style="padding: 10px; color: var(--text-gray);">Item</th>
                                                <th style="padding: 10px; color: var(--text-gray);">Date</th>
                                                <th style="padding: 10px; color: var(--text-gray);">Amount</th>
                                                <th style="padding: 10px; color: var(--text-gray);">Status</th>
                                                <th style="padding: 10px; color: var(--text-gray); text-align: right;">Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($store_history as $hist):
                                                $item_name = str_replace('Store: ', '', $hist['plan_name']);
                                                $date = date('d M Y', strtotime($hist['created_at']));
                                                $status_color = $hist['status'] == 'completed' ? '#00ff88' : '#ffb74d';
                                                ?>
                                                    <tr class="history-row" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                                        <td style="padding: 10px; font-weight: 500;" class="item-name">
                                                            <?php echo htmlspecialchars($item_name); ?>
                                                        </td>
                                                        <td style="padding: 10px; color: var(--text-gray); font-size: 0.9rem;">
                                                            <?php echo $date; ?>
                                                        </td>
                                                        <td style="padding: 10px;">₹<?php echo number_format($hist['amount']); ?></td>
                                                        <td
                                                            style="padding: 10px; color: <?php echo $status_color; ?>; text-transform: capitalize;">
                                                            <?php echo $hist['status']; ?>
                                                        </td>
                                                        <td style="padding: 10px; text-align: right;">
                                                            <a href="invoice.php?tid=<?php echo $hist['id']; ?>" target="_blank"
                                                                title="Download Invoice"
                                                                style="color: var(--primary-color); margin-right: 10px; font-size: 1.1rem;">
                                                                <i class="fa-solid fa-file-pdf"></i>
                                                            </a>
                                                            <button
                                                                onclick="deleteTransaction(<?php echo $hist['id']; ?>, this.closest('tr'))"
                                                                title="Delete Record"
                                                                style="background: none; border: none; color: #ff4d4d; cursor: pointer; font-size: 1rem;">
                                                                <i class="fa-solid fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                        <?php endif; ?>
                    </div>
                </div>
                <style>
                    /* Custom Scrollbar for Modal List */
                    .custom-scrollbar::-webkit-scrollbar {
                        width: 8px;
                    }

                    .custom-scrollbar::-webkit-scrollbar-track {
                        background: rgba(255, 255, 255, 0.02);
                        border-radius: 4px;
                    }

                    .custom-scrollbar::-webkit-scrollbar-thumb {
                        background: rgba(206, 255, 0, 0.3);
                        border-radius: 4px;
                    }

                    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
                        background: rgba(206, 255, 0, 0.6);
                    }
                </style>
            </div>

            <script>
                function filterStoreHistory() {
                    const input = document.getElementById('store-history-search');
                    const filter = input.value.toLowerCase();
                    const rows = document.querySelectorAll('.history-row');

                    rows.forEach(row => {
                        // Get text from item name, date, and amount columns
                        const text = row.innerText.toLowerCase();
                        if (text.includes(filter)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                }
            </script>
            <div class="dashboard-grid"
                style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 20px;">

                <!-- Category 1: Protein -->
                <div class="dashboard-card" style="text-align: center; padding: 20px;">
                    <div
                        style="height: 150px; background: rgba(255,255,255,0.05); margin-bottom: 15px; display: flex; align-items: center; justify-content: center; border-radius: 10px; overflow: hidden;">
                        <img src="assets/images/protein display.png" alt="Protein"
                            style="width: 65%; height: 100%; object-fit: cover;object-position: center 15%;">
                    </div>
                    <h3 style="font-family: 'Oswald'; margin-bottom: 5px;">Proteins</h3>
                    <p style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 15px;">Whey, Isolate, Vegan &
                        more.</p>
                    <button onclick="openCategoryModal('protein')" class="btn-action" style="width: 100%;">View
                        Products</button>
                </div>

                <!-- Category 2: Gloves -->
                <div class="dashboard-card" style="text-align: center; padding: 20px;">
                    <div
                        style="height: 150px; background: rgba(255,255,255,0.05); margin-bottom: 15px; display: flex; align-items: center; justify-content: center; border-radius: 10px;overflow: hidden;">
                        <img src="assets/images/Gym Glove.png" alt="Gym Gloves"
                            style="width: 70%; height: 100%; object-fit: cover;">
                    </div>
                    <h3 style="font-family: 'Oswald'; margin-bottom: 5px;">Gym Gloves</h3>
                    <p style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 15px;">Grip support &
                        protection.</p>
                    <button onclick="openCategoryModal('gloves')" class="btn-action" style="width: 100%;">View
                        Products</button>
                </div>

                <!-- Category 3: Shakers -->
                <div class="dashboard-card" style="text-align: center; padding: 20px;">
                    <div
                        style="height: 150px; background: rgba(255,255,255,0.05); margin-bottom: 15px; display: flex; align-items: center; justify-content: center; border-radius: 10px; overflow: hidden;">
                        <img src="assets/images/Protein Shaker.png" alt="Shakers"
                            style="width: 65%; height: 100%; object-fit: cover;">
                    </div>
                    <h3 style="font-family: 'Oswald'; margin-bottom: 5px;">Shakers</h3>
                    <p style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 15px;">Leak-proof & durable
                        bottles.</p>
                    <button onclick="openCategoryModal('shakers')" class="btn-action" style="width: 100%;">View
                        Products</button>
                </div>

                <!-- Category 4: Belts -->
                <div class="dashboard-card" style="text-align: center; padding: 20px;">
                    <div
                        style="height: 150px; background: rgba(255,255,255,0.05); margin-bottom: 15px; display: flex; align-items: center; justify-content: center; border-radius: 10px; overflow: hidden;">
                        <img src="assets/images/Lifting Belts.png" alt="Lifting Belts"
                            style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <h3 style="font-family: 'Oswald'; margin-bottom: 5px;">Lifting Belts</h3>
                    <p style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 15px;">Support for heavy lifts.
                    </p>
                    <button onclick="openCategoryModal('belts')" class="btn-action" style="width: 100%;">View
                        Products</button>
                </div>

                <!-- Category 5: Healthy Snacks -->
                <div class="dashboard-card" style="text-align: center; padding: 20px;">
                    <div
                        style="height: 150px; background: rgba(255,255,255,0.05); margin-bottom: 15px; display: flex; align-items: center; justify-content: center; border-radius: 10px;">
                        <img src="assets/images/snacks display.jpg" alt="Healthy Snacks"
                            style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <h3 style="font-family: 'Oswald'; margin-bottom: 5px;">Healthy Snacks</h3>
                    <p style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 15px;">Clean fuel for your
                        body.
                    </p>
                    <button onclick="openCategoryModal('snacks')" class="btn-action" style="width: 100%;">View
                        Products</button>
                </div>

                <!-- Category 6: Fitness Accessories -->
                <div class="dashboard-card" style="text-align: center; padding: 20px;">
                    <div
                        style="height: 150px; background: rgba(255,255,255,0.05); margin-bottom: 15px; display: flex; align-items: center; justify-content: center; border-radius: 10px;">
                        <img src="assets/images/fitness accesories display.jpg" alt="Fitness Accessories"
                            style="width: 70%; height: 100%; object-fit: cover;">
                    </div>
                    <h3 style="font-family: 'Oswald'; margin-bottom: 5px;">Fitness Accessories</h3>
                    <p style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 15px;">Small tools, big
                        results.
                    </p>
                    <button onclick="openCategoryModal('accessories')" class="btn-action" style="width: 100%;">View
                        Products</button>
                </div>

                <!-- Category 7: Hygiene Essentials -->
                <div class="dashboard-card" style="text-align: center; padding: 20px;">
                    <div
                        style="height: 150px; background: rgba(255,255,255,0.05); margin-bottom: 15px; display: flex; align-items: center; justify-content: center; border-radius: 10px;">
                        <img src="assets/images/Hygiene Essentials.png" alt="Hygiene Essentials"
                            style="width: 120%; height: 100%; object-fit: cover;">
                    </div>
                    <h3 style="font-family: 'Oswald'; margin-bottom: 5px;">Hygiene Essentials</h3>
                    <p style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 15px;">Stay fresh after every
                        session.</p>
                    <button onclick="openCategoryModal('hygiene')" class="btn-action" style="width: 100%;">View
                        Products</button>
                </div>
            </div>
        </div>

        <!-- Category Modal -->
        <div id="category-modal"
            style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #000; z-index: 2000; align-items: center; justify-content: center;">
            <div
                style="background: var(--card-bg); padding: 30px; border-radius: 15px; width: 90%; max-width: 800px; max-height: 90vh; overflow-y: auto; position: relative; border: 1px solid rgba(255,255,255,0.1);">
                <button onclick="document.getElementById('category-modal').style.display='none'"
                    style="position: absolute; top: 15px; right: 15px; background: none; border: none; color: #fff; cursor: pointer; font-size: 1.2rem;"><i
                        class="fa-solid fa-xmark"></i></button>

                <h3 id="cat-modal-title"
                    style="font-family: 'Oswald'; margin-bottom: 20px; color: var(--primary-color); font-size: 1.8rem;">
                    Category</h3>

                <div id="cat-modal-grid"
                    style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px;">
                    <!-- Products injected via JS -->
                </div>
            </div>
        </div>

        <!-- Purchase Modal -->
        <div id="store-modal"
            style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 2100; align-items: center; justify-content: center;">
            <div
                style="background: #1a1a2e; padding: 30px; border-radius: 15px; width: 90%; max-width: 400px; position: relative; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 0 30px rgba(0,0,0,0.5);">
                <button onclick="document.getElementById('store-modal').style.display='none'"
                    style="position: absolute; top: 15px; right: 15px; background: none; border: none; color: #fff; cursor: pointer; font-size: 1.2rem;"><i
                        class="fa-solid fa-xmark"></i></button>

                <h3 style="font-family: 'Oswald'; margin-bottom: 20px;">Confirm Purchase</h3>

                <form method="POST" id="store-form">
                    <input type="hidden" name="confirm_store_order" value="1">
                    <input type="hidden" id="store_product_name" name="product_name">
                    <input type="hidden" id="store_product_price" name="product_price">

                    <div style="margin-bottom: 20px; text-align: center;">
                        <h4 id="disp_prod_name" style="color: var(--primary-color);">--</h4>
                        <p style="font-size: 1.5rem; font-weight: bold;">₹<span id="disp_prod_price">0</span></p>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 10px; color: var(--text-gray);">Payment
                            Method</label>
                        <div style="display: flex; gap: 15px;">
                            <label style="flex: 1; cursor: pointer;">
                                <input type="radio" name="payment_method" value="GPay" checked
                                    onclick="toggleStoreBtn('GPay')">
                                GPay
                            </label>
                        </div>
                    </div>

                    <button type="button" id="store-submit-btn" onclick="processStoreOrder()" class="btn-action"
                        style="width: 100%;">Pay with GPay</button>
                </form>
            </div>
        </div>

        <?php
        // Get Server IP for QR Code (so phone can scan)
        $server_ip = getHostByName(getHostName());
        // Fallback if it returns localhost
        if ($server_ip == '127.0.0.1' || $server_ip == '::1') {
            // Try simpler method or just default to localhost (user might need to fix)
            $server_ip = $_SERVER['SERVER_NAME'];
        }
        $base_url = "http://" . $server_ip . "/Gym-Fit-master";
        ?>

        <!-- NEW GPay QR Modal -->
        <div id="gpay-qr-modal"
            style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); z-index: 2200; align-items: center; justify-content: center; flex-direction: column;">
            <div
                style="background: #fff; padding: 30px; border-radius: 20px; width: 90%; max-width: 350px; text-align: center; position: relative;">
                <button onclick="closeGPayModal()"
                    style="position: absolute; top: 10px; right: 15px; background: none; border: none; font-size: 1.5rem; color: #333; cursor: pointer;">&times;</button>

                <h3 style="color: #333; margin-bottom: 10px; font-family: 'Roboto', sans-serif;">Scan to Pay</h3>
                <p style="color: #666; font-size: 0.9rem; margin-bottom: 20px;">Open GPay and scan this QR code</p>

                <div style="margin-bottom: 20px; position: relative; width: 200px; height: 200px; margin: 0 auto 20px;">
                    <!-- QR Code Image -->
                    <img id="gpay-qr-code" src="" alt="GPay QR" style="width: 100%; height: 100%;">

                    <!-- Scanning Overlay Animation -->
                    <div id="scan-overlay"
                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.8); display: none; align-items: center; justify-content: center; flex-direction: column;">
                        <div class="spinner"
                            style="border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin-bottom: 10px;">
                        </div>
                        <p style="color: #333; font-weight: bold;">Processing Payment...</p>
                    </div>
                    <div id="success-overlay"
                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.95); display: none; align-items: center; justify-content: center; flex-direction: column;">
                        <i class="fa-solid fa-circle-check"
                            style="font-size: 4rem; color: #00ff88; margin-bottom: 15px;"></i>
                        <h4 style="color: #333; margin: 0;">Payment Successful!</h4>
                    </div>
                </div>

                <div
                    style="display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 10px;">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/f2/Google_Pay_Logo.svg/2560px-Google_Pay_Logo.svg.png"
                        alt="GPay" style="height: 25px;">
                </div>

                <p id="qr-amount-display" style="font-size: 1.2rem; font-weight: bold; color: #333;">₹0</p>
                <div style="font-size: 0.9rem; color: #666; width: 100%; text-align: center; margin-top: 15px;">
                    <i class="fa-solid fa-spinner fa-spin"></i> Waiting for payment...
                </div>
            </div>
            <style>
                @keyframes spin {
                    0% {
                        transform: rotate(0deg);
                    }

                    100% {
                        transform: rotate(360deg);
                    }
                }
            </style>
        </div>

        <script>
            // Store variables
            const serverBaseUrl = "<?php echo $base_url; ?>";
            const currentUserId = <?php echo $user_id; ?>;
            let paymentPollInterval;

            // Product Data
            const storeProducts = {
                'protein': [
                    { name: 'Gold Standard Whey', price: 4500, image: 'assets/images/gold-standard.png' },
                    { name: 'MuscleBlaze Biozyme', price: 3200, image: 'assets/images/MuscleBlaze Biozyme.png' },
                    { name: 'Isopure Zero Carb', price: 6500, image: 'assets/images/isopure.png' },
                    { name: 'Vegan Plant Protein', price: 2800, image: 'assets/images/plant protein.png' }
                ],
                'gloves': [
                    { name: 'Nivia Basic Gloves', price: 400, image: 'assets/images/nivia basic glove.png' },
                    { name: 'Nike Training Gloves', price: 1200, image: 'assets/images/nike gym glove.png' },
                    { name: 'Under Armour Grip', price: 1500, image: 'assets/images/under armour.png' },
                    { name: 'Wrist Support Gloves', price: 800, image: 'assets/images/wrist support.png' }
                ],
                'shakers': [
                    { name: 'Classic Shaker 500ml', price: 200, image: 'assets/images/classic shaker.png' },
                    { name: 'Spider Shaker', price: 500, image: 'assets/images/spider shaker.png' },
                    { name: 'Steel Shaker 700ml', price: 800, image: 'assets/images/steel shaker.png' }
                ],
                'belts': [
                    { name: 'Nylon Weight Belt', price: 800, image: 'assets/images/nylon weight.png' },
                    { name: 'Leather Power Belt', price: 2500, image: 'assets/images/leather.png' },
                    { name: 'Lever Buckle Belt', price: 5000, image: 'assets/images/Lever Buckle Belt.png' },
                    { name: 'Dip Belt with Chain', price: 1500, image: 'assets/images/Dip Belt with Chain.png' }
                ],
                'snacks': [
                    { name: 'Protein Bar (Pack of 6)', price: 720, image: 'assets/images/Protein Bar.png' },
                    { name: 'Peanut Butter 1kg', price: 450, image: 'assets/images/Peanut Butter.png' },
                    { name: 'Instant Oats 1kg', price: 200, image: 'assets/images/instant oats.png' },
                    { name: 'Energy Bites (Pack of 10)', price: 350, image: 'assets/images/Energy Bites.png' }
                ],
                'accessories': [
                    { name: 'Wrist Wraps', price: 400, image: 'assets/images/Wrist Wraps.png' },
                    { name: 'Knee Sleeves (Pair)', price: 1500, image: 'assets/images/Knee Sleeves.png' },
                    { name: 'Resistance Bands Set', price: 800, image: 'assets/images/Resistance Bands Set.png' },
                    { name: 'Speed Jump Rope', price: 300, image: 'assets/images/Speed Jump Rope.png' }
                ],
                'hygiene': [
                    { name: 'Microfiber Gym Towel', price: 300, image: 'assets/images/Microfiber Gym Towel.png' },
                    { name: 'Hand Sanitizer 500ml', price: 150, image: 'assets/images/Hand Sanitizer.png' }
                ]
            };

            function openCategoryModal(catKey) {
                const modal = document.getElementById('category-modal');
                const title = document.getElementById('cat-modal-title');
                const grid = document.getElementById('cat-modal-grid');

                // Set Title
                const titles = {
                    'protein': 'Proteins',
                    'gloves': 'Gym Gloves',
                    'shakers': 'Shakers',
                    'belts': 'Lifting Belts',
                    'snacks': 'Healthy Snacks',
                    'accessories': 'Fitness Accessories',
                    'hygiene': 'Hygiene Essentials'
                };
                title.innerText = titles[catKey];

                // Clear grid
                grid.innerHTML = '';

                // Populate Products
                if (storeProducts[catKey]) {
                    storeProducts[catKey].forEach(prod => {
                        const card = document.createElement('div');
                        card.style.background = 'rgba(255,255,255,0.05)';
                        card.style.padding = '15px';
                        card.style.borderRadius = '10px';
                        card.style.textAlign = 'center';
                        card.style.border = '1px solid rgba(255,255,255,0.05)';

                        // Check for image or fallback icon
                        let imgHtml = '';
                        if (prod.image) {
                            imgHtml = `<img src="${prod.image}" alt="${prod.name}" style="height: 120px; width: auto; max-width: 100%; object-fit: contain; margin-bottom: 15px;">`;
                        } else {
                            // Default icon based on category? Simplified fallback
                            imgHtml = `<div style="height: 120px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px;"><i class="fa-solid fa-box-open" style="font-size: 3rem; color: var(--primary-color);"></i></div>`;
                        }

                        card.innerHTML = `
                            ${imgHtml}
                            <h4 style="margin-bottom: 5px; color: #fff;">${prod.name}</h4>
                            <p style="color: var(--text-gray); font-size: 0.85rem; margin-bottom: 10px;">${prod.desc || ''}</p>
                            <h3 style="color: var(--primary-color); margin-bottom: 15px;">₹${prod.price}</h3>
                            <button onclick="openPurchaseModal('${prod.name}', ${prod.price})" class="btn-action" style="width: 100%; font-size: 0.9rem; padding: 10px;">Buy Now</button>
                        `;
                        grid.appendChild(card);
                    });
                }

                modal.style.display = 'flex';
            }

            let currentStoreProduct = {};

            function openPurchaseModal(name, price) {
                currentStoreProduct = { name, price };
                document.getElementById('store_product_name').value = name;
                document.getElementById('store_product_price').value = price;
                document.getElementById('disp_prod_name').innerText = name;
                document.getElementById('disp_prod_price').innerText = price;

                // Reset to GPay default
                document.querySelector('input[name="payment_method"][value="GPay"]').checked = true;
                toggleStoreBtn('GPay');

                document.getElementById('store-modal').style.display = 'flex';
            }

            function toggleStoreBtn(method) {
                const btn = document.getElementById('store-submit-btn');
                if (method === 'GPay') {
                    btn.innerText = 'Pay with GPay';
                } else {
                    btn.innerText = 'Confirm Order (Cash)';
                }
            }

            function processStoreOrder() {
                const method = document.querySelector('input[name="payment_method"]:checked').value;
                if (method === 'GPay') {
                    // Open QR Modal
                    document.getElementById('store-modal').style.display = 'none';
                    document.getElementById('qr-amount-display').innerText = '₹' + currentStoreProduct.price;
                    document.getElementById('gpay-qr-modal').style.display = 'flex';

                    // Generate QR Code URL
                    const payUrl = `${serverBaseUrl}/gpay_mock.php?amt=${currentStoreProduct.price}&plan=${encodeURIComponent('Store: ' + currentStoreProduct.name)}&uid=${currentUserId}`;
                    const qrApiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(payUrl)}`;
                    document.getElementById('gpay-qr-code').src = qrApiUrl;

                    // Start polling for actual payment
                    startPaymentPolling();
                } else {
                    document.getElementById('store-form').submit();
                }
            }

            function startPaymentPolling() {
                if (paymentPollInterval) clearInterval(paymentPollInterval);

                paymentPollInterval = setInterval(() => {
                    const planName = 'Store: ' + currentStoreProduct.name;
                    fetch(`check_payment_status.php?uid=${currentUserId}&plan=${encodeURIComponent(planName)}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'paid') {
                                clearInterval(paymentPollInterval);
                                showSuccessAndRedirect();
                            }
                        })
                        .catch(err => console.error("Polling error:", err));
                }, 2000);
            }

            function showSuccessAndRedirect() {
                document.getElementById('scan-overlay').style.display = 'none'; // Hide if stuck
                document.getElementById('success-overlay').style.display = 'flex';

                setTimeout(() => {
                    closeGPayModal();
                    document.getElementById('category-modal').style.display = 'none';
                    window.location.href = 'dashboard_member.php?section=gym-store';
                }, 2000);
            }

            function closeGPayModal() {
                if (paymentPollInterval) clearInterval(paymentPollInterval);
                document.getElementById('gpay-qr-modal').style.display = 'none';
                document.getElementById('scan-overlay').style.display = 'none';
                document.getElementById('success-overlay').style.display = 'none';
            }
        </script>

    </div>

    <script>
        function showSection(sectionId) {
            localStorage.setItem('activeSection', sectionId);
            document.querySelectorAll('.dashboard-section').forEach(section => section.classList.remove('active'));
            document.querySelectorAll('.sidebar-menu a').forEach(link => link.classList.remove('active'));

            const targetSection = document.getElementById(sectionId);
            if (targetSection) targetSection.classList.add('active');

            // Sidebar Highlighting Logic
            let found = false;

            // 1. If triggered by a user click on a sidebar link
            if (typeof event !== 'undefined' && event && event.type === 'click' && event.currentTarget && event.currentTarget.classList && event.currentTarget.closest('.sidebar-menu')) {
                event.currentTarget.classList.add('active');
                found = true;
            }

            // 2. Fallback: Search for the link (e.g. on page load)
            if (!found) {
                document.querySelectorAll('.sidebar-menu a').forEach(a => {
                    const attr = a.getAttribute('onclick');
                    if (attr && attr.includes(sectionId)) {
                        a.classList.add('active');
                    }
                });
            }
        }

        // TASK MANAGEMENT (AJAX)
        const addTaskForm = document.getElementById('add-task-form');
        if (addTaskForm) {
            addTaskForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const nameInput = document.getElementById('new-task-name');
                const name = nameInput.value;
                const formData = new FormData();
                formData.append('ajax_action', 'add_task');
                formData.append('task_name', name);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const container = document.getElementById('todo-list-container');
                            const div = document.createElement('div');
                            div.className = 'todo-item';
                            div.id = 'task-' + data.id;
                            div.innerHTML = `
                            <input type="checkbox" onchange="toggleTask(${data.id})">
                            <span style="flex-grow: 1;">${data.name}</span>
                            <div style="display: flex; gap: 5px;">
                                <button type="button" class="icon-btn edit" onclick="openEditTask(${data.id}, '${data.name.replace(/'/g, "\\'")}')">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button type="button" class="icon-btn delete" onclick="deleteTask(${data.id})">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        `;
                            container.prepend(div);
                            nameInput.value = '';
                        }
                    });
            });
        }

        function toggleTask(id) {
            const formData = new FormData();
            formData.append('ajax_action', 'toggle_task');
            formData.append('task_id', id);
            fetch(window.location.href, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const span = document.querySelector(`#task-${id} span`);
                        if (span.style.textDecoration === 'line-through') {
                            span.style.textDecoration = 'none';
                            span.style.opacity = '1';
                        } else {
                            span.style.textDecoration = 'line-through';
                            span.style.opacity = '0.5';
                        }
                    }
                });
        }

        function deleteTask(id) {
            if (!confirm('Delete this task?')) return;
            const formData = new FormData();
            formData.append('ajax_action', 'delete_task');
            formData.append('task_id', id);
            fetch(window.location.href, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('task-' + id).remove();
                    }
                });
        }

        function deleteTransaction(id, rowEl) {
            if (!confirm('Permanently delete this transaction record?')) return;
            const formData = new FormData();
            formData.append('ajax_action', 'delete_transaction');
            formData.append('trans_id', id);
            fetch(window.location.href, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        rowEl.style.opacity = '0';
                        setTimeout(() => rowEl.remove(), 300);
                    }
                });
        }

        function openEditTask(id, name) {
            document.getElementById('edit-task-id').value = id;
            document.getElementById('edit-task-name').value = name;
            document.getElementById('edit-task-modal').style.display = 'flex';
        }

        function saveEditedTask(e) {
            e.preventDefault();
            const id = document.getElementById('edit-task-id').value;
            const name = document.getElementById('edit-task-name').value;

            const formData = new FormData();
            formData.append('ajax_action', 'edit_task');
            formData.append('task_id', id);
            formData.append('task_name', name);

            fetch(window.location.href, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Update UI without reload
                        const taskSpan = document.querySelector(`#task-${id} span`);
                        if (taskSpan) taskSpan.innerText = name;

                        // Update the onclick of the edit button too, so next edit has new name
                        const editBtn = document.querySelector(`#task-${id} .edit`);
                        if (editBtn) {
                            editBtn.setAttribute('onclick', `openEditTask(${id}, '${name.replace(/'/g, "\\'")}')`);
                        }

                        document.getElementById('edit-task-modal').style.display = 'none';
                    }
                });
        }

        // PROFILE
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('profile-preview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // BMI CALCULATOR
        function calculateBMI() {
            const weightInput = document.getElementById('bmi-weight-input');
            const heightInput = document.getElementById('bmi-height-input');
            const weight = parseFloat(weightInput.value);
            const height = parseFloat(heightInput.value);

            if (!weight || !height || weight <= 0 || height <= 0) {
                alert("Please enter valid positive numbers for weight and height.");
                return;
            }
            // Additional bounds check
            if (weight < 20 || weight > 500) {
                alert("Please enter a realistic weight (20kg - 500kg).");
                return;
            }
            if (height < 50 || height > 300) {
                alert("Please enter a realistic height (50cm - 300cm).");
                return;
            }

            const bmi = (weight / ((height / 100) ** 2)).toFixed(1);

            // Update UI
            const resultCard = document.getElementById('bmi-result-card');
            const needle = document.getElementById('bmi-needle');
            const valueText = document.getElementById('bmi-val-display'); // Correct code id
            const categoryText = document.getElementById('bmi-cat-display'); // Correct code id

            resultCard.style.opacity = '1';
            resultCard.style.pointerEvents = 'auto';
            valueText.innerText = bmi; // Use value defined earlier

            let category = '';
            let color = '';
            let rotation = -90; // Default start

            if (bmi < 18.5) {
                category = 'Underweight';
                color = '#7fa8d1';
            } else if (bmi < 25) {
                category = 'Normal Weight';
                color = '#8bc34a';
            } else if (bmi < 30) {
                category = 'Overweight';
                color = '#ffb74d';
            } else {
                category = 'Obese';
                color = '#e57373';
            }

            categoryText.innerText = category;
            categoryText.style.color = color;
            valueText.style.color = color;

            // map BMI 15-40 to -90 to 90 deg
            // 15 -> -90
            // 40 -> 90
            let clampedBMI = Math.max(15, Math.min(bmi, 40));
            rotation = ((clampedBMI - 15) / (40 - 15)) * 180 - 90;

            needle.style.transform = `translateX(-50%) rotate(${rotation}deg)`;
        }

        // VIDEO Logic
        const allVideos = <?php echo json_encode($all_videos); ?>;
        const customWorkouts = <?php echo json_encode($custom_workouts); ?>;
        const viewMonth = <?php echo $view_month; ?>;
        const viewYear = <?php echo $view_year; ?>;
        let selectedVideoId = null;
        let selectedDate = null;

        // HELPERS
        function getYoutubeEmbedUrl(rawInput, autoplay = false) {
            if (!rawInput) return '';
            let videoId = null;

            // 1. Extract Video ID first
            const regExp = /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts|live)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/;
            const match = rawInput.trim().match(regExp);

            if (match && match[1]) {
                videoId = match[1];
            } else if (rawInput.length > 5 && !rawInput.includes('/') && !rawInput.includes('.')) {
                videoId = rawInput.trim();
            }

            // 2. Build URL with params for clean playback
            // rel=0: Show related videos from same channel only (stops random videos)
            // modestbranding=1: Remove logo
            // iv_load_policy=3: Hide annotations
            if (videoId) {
                let params = ['rel=0', 'modestbranding=1', 'iv_load_policy=3'];
                if (autoplay) params.push('autoplay=1');

                return `https://www.youtube.com/embed/${videoId}?${params.join('&')}`;
            }

            // 3. Fallback for existing embed URLs
            if (rawInput.includes("youtube.com/embed/")) {
                let url = rawInput;
                // Append params if missing
                if (!url.includes('rel=0')) url += (url.includes('?') ? '&' : '?') + 'rel=0';
                if (!url.includes('modestbranding=1')) url += '&modestbranding=1';

                if (autoplay) {
                    if (!url.includes('autoplay=1')) url += '&autoplay=1';
                } else {
                    url = url.replace('autoplay=1', 'autoplay=0');
                }
                return url;
            }

            return rawInput.includes("http") ? rawInput : '';
        }

        // VIDEO SEARCH LOGIC
        function searchVideos(query) {
            const dropdown = document.getElementById('search-results-dropdown');
            if (!query || query.trim().length < 2) {
                dropdown.style.display = 'none';
                return;
            }

            const q = query.toLowerCase();

            // PRIORITIZE Custom Uploads over Default Library in the search results
            const searchPool = [
                ...Object.values(customWorkouts).map(v => ({ ...v, source: 'custom' })),
                ...allVideos.map(v => ({ ...v, source: 'default' }))
            ];

            const results = searchPool.filter(v =>
                v.title.toLowerCase().includes(q) ||
                v.type.toLowerCase().includes(q) ||
                (v.content && v.content.toLowerCase().includes(q))
            );

            if (results.length > 0) {
                dropdown.innerHTML = results.map(v => {
                    let displayDate = v.source === 'custom' ? new Date(v.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '';
                    let sourceLabel = v.source === 'custom' ? 'SCHEDULED' : 'LIBRARY';
                    let sourceColor = v.source === 'custom' ? '#ff4d4d' : 'var(--primary-color)';
                    let sourceBg = v.source === 'custom' ? 'rgba(255, 77, 77, 0.15)' : 'rgba(206, 255, 0, 0.15)';

                    return `
                        <div class="video-item" onclick="playSearchedVideo('${v.id}', '${v.source}')" style="padding: 12px; margin-bottom: 8px; border-radius: 12px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 15px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                            <div style="width: 45px; height: 45px; border-radius: 10px; background: ${sourceBg}; display: flex; align-items: center; justify-content: center; color: ${sourceColor}; border: 1px solid ${sourceColor}44;">
                                <i class="fa-solid ${v.source === 'custom' ? 'fa-calendar-check' : 'fa-play'}" style="font-size: 1.1rem;"></i>
                            </div>
                            <div style="flex-grow: 1;">
                                <h5 style="font-size: 0.95rem; margin: 0 0 4px 0; color: #fff; font-family: 'Oswald'; letter-spacing: 0.5px;">${v.title}</h5>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-size: 0.7rem; font-weight: bold; color: ${sourceColor}; background: ${sourceBg}; padding: 2px 6px; border-radius: 4px; border: 1px solid ${sourceColor}33;">${sourceLabel}</span>
                                    ${displayDate ? `<span style="font-size: 0.75rem; color: #fff; opacity: 0.8;">${displayDate}</span>` : `<span style="font-size: 0.75rem; color: var(--text-gray);">${v.type}</span>`}
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
                dropdown.style.display = 'block';
            } else {
                dropdown.innerHTML = '<p style="text-align: center; color: var(--text-gray); font-size: 0.85rem; padding: 10px;">No workouts found.</p>';
                dropdown.style.display = 'block';
            }
        }

        function playSearchedVideo(videoId, source) {
            let video;
            if (source === 'custom') {
                video = Object.values(customWorkouts).find(v => v.id === videoId);
            } else {
                video = allVideos.find(v => v.id === videoId);
            }

            if (!video) return;

            // Hide search results
            document.getElementById('search-results-dropdown').style.display = 'none';
            document.getElementById('video-search-input').value = '';

            // Update display
            const playerContainer = document.getElementById('video-player-container');
            const iframe = document.getElementById('video-iframe');

            selectedVideoId = source === 'custom' ? video.real_id : video.id;
            selectedDate = source === 'custom' ? video.date : null;

            iframe.src = getYoutubeEmbedUrl(source === 'custom' ? video.real_id : video.id, true);
            playerContainer.style.display = 'block';
            playerContainer.classList.add('active');

            // Render video details in the right card
            const container = document.getElementById('video-list-container');
            const displayDate = source === 'custom' ? new Date(video.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '';
            document.getElementById('playlist-title').innerText = source === 'custom' ? `Scheduled Workout` : `Library Workout`;

            // Mark as Finished button for custom videos in search
            let buttonsHtml = '';
            if (source === 'custom') {
                buttonsHtml = `
                <div style="margin-top: 15px; text-align: right;">
                    <button type="button" onclick="markVideoFinished('complete')" class="btn-action" style="width: auto; padding: 10px 20px; display: inline-flex; background: var(--primary-color); color: var(--secondary-color);">
                        <i class="fa-solid fa-check"></i> Mark Day Finished
                    </button>
                </div>`;
            }

            container.innerHTML = `
                <div class="video-item playing" style="cursor: pointer; border: 1px solid ${source === 'custom' ? '#ff4d4d' : 'var(--primary-color)'};">
                    <div class="video-thumb" style="background: rgba(255, 255, 255, 0.1); display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-play" style="font-size: 2rem; color: ${source === 'custom' ? '#ff4d4d' : 'var(--primary-color)'};"></i>
                    </div>
                    <div style="flex-grow: 1;">
                        <h4 class="v-title">${video.title}</h4>
                        <p style="font-size: 0.8rem; color: var(--text-gray);">${source === 'custom' ? '<i class="fa-solid fa-calendar-day"></i> For ' + displayDate : (video.duration || '') + ' • ' + video.type}</p>
                    </div>
                </div>
                <div style="margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.03); border-radius: 10px; border-left: 3px solid ${source === 'custom' ? '#ff4d4d' : 'var(--primary-color)'};">
                    <h5 style="color: ${source === 'custom' ? '#ff4d4d' : 'var(--primary-color)'}; font-family: 'Oswald'; margin-bottom: 5px;">Instructor's Plan:</h5>
                    <p style="font-size: 0.9rem; line-height: 1.4; color: #fff; white-space: pre-line;">
                        ${video.content}
                    </p>
                    ${buttonsHtml}
                    ${source === 'default' ? '<p style="font-size: 0.8rem; color: #ffcc00; margin-top: 10px;"><i class="fa-solid fa-circle-info"></i> Search mode active. Progress tracking is disabled for non-scheduled library workouts.</p>' : ''}
                </div>
            `;

            playerContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // Close search list on click outside
        document.addEventListener('click', function (e) {
            const dropdown = document.getElementById('search-results-dropdown');
            const input = document.getElementById('video-search-input');
            if (dropdown && e.target !== dropdown && e.target !== input && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });

        function selectDay(day) {
            document.querySelectorAll('.calendar-day').forEach(d => d.classList.remove('active'));
            const dayEl = document.getElementById('day-' + day);
            if (dayEl) {
                dayEl.classList.add('active');
                selectedDate = dayEl.getAttribute('data-date');
                // Save state
                localStorage.setItem('lastSelectedDay', day);
            }

            // Construct date string YYYY-MM-DD for lookup
            // Ensure zero-padding
            const m = viewMonth < 10 ? '0' + viewMonth : viewMonth;
            const d = day < 10 ? '0' + day : day;
            const fullDate = `${viewYear}-${m}-${d}`;

            let video;
            let isCustom = false;

            // 1. Check if Staff has uploaded a specific video for this date
            if (customWorkouts[fullDate]) {
                video = customWorkouts[fullDate];
                isCustom = true;
            } else {
                // 2. Fallback to default array
                const videoIdx = (day - 1 + (viewMonth * 5)) % allVideos.length;
                video = allVideos[videoIdx];
                isCustom = false;
            }

            document.getElementById('playlist-title').innerText = `Day ${day}: ${video.title}`;
            renderVideoList(video, day, isCustom);

            // If it is a custom video (Staff uploaded), assume it has a valid link and play it
            // If it is a custom video (Staff uploaded), assume it has a valid link and play it
            if (isCustom) {
                selectedVideoId = video.real_id;
                const playerContainer = document.getElementById('video-player-container');
                const iframe = document.getElementById('video-iframe');

                iframe.src = getYoutubeEmbedUrl(video.real_id, false);
                playerContainer.style.display = 'block';
                playerContainer.classList.add('active');
            } else {
                const playerContainer = document.getElementById('video-player-container');
                playerContainer.style.display = 'none';
                playerContainer.classList.remove('active');
                document.getElementById('video-iframe').src = '';
            }
        }

        function renderVideoList(video, day, isCustom) {
            const container = document.getElementById('video-list-container');
            const dayEl = document.getElementById('day-' + day);
            const isDone = dayEl.classList.contains('completed');

            // Icon: Use Play icon if custom/playable, otherwise dumbbell
            const iconClass = isCustom ? (isDone ? 'fa-check-circle' : 'fa-play') : (isDone ? 'fa-check-circle' : 'fa-dumbbell');
            const cursorStyle = isCustom ? 'pointer' : 'default';

            // Buttons Logic
            let buttonsHtml = '';
            if (isCustom) {
                if (isDone) {
                    buttonsHtml = `
                    <div style="margin-top: 15px; text-align: right;">
                        <button type="button" onclick="markVideoFinished('redo')" class="btn-action" style="width: auto; padding: 10px 20px; background: #333; color: #fff; display: inline-flex;">
                            <i class="fa-solid fa-rotate-right"></i> Redo Workout
                        </button>
                    </div>`;
                } else {
                    buttonsHtml = `
                    <div style="margin-top: 15px; text-align: right;">
                        <button type="button" onclick="markVideoFinished('complete')" class="btn-action" style="width: auto; padding: 10px 20px; display: inline-flex;">
                            <i class="fa-solid fa-check"></i> Mark as Finished
                        </button>
                    </div>`;
                }
            }

            // Calculate URL for onclick handler
            const rawId = isCustom ? video.real_id : video.id;
            const embedUrl = getYoutubeEmbedUrl(rawId, false);

            container.innerHTML = `
                <div class="video-item ${isDone ? 'video-done' : ''}" id="current-video-item" style="cursor: pointer;" onclick="playVideo(this, '${embedUrl}', '${video.id}')">
                    <div class="video-thumb" style="background: rgba(255, 255, 255, 0.1); display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid ${iconClass}" style="font-size: 2rem; color: var(--text-gray);"></i>
                    </div>
                    <div style="flex-grow: 1;">
                        <h4 class="v-title">${video.title}</h4>
                        <p style="font-size: 0.8rem; color: var(--text-gray);">${video.duration ? video.duration + ' • ' : ''}${video.type}</p>
                    </div>
                    ${isDone ? '<i class="fa-solid fa-circle-check check-icon" style="color: var(--primary-color);"></i>' : ''}
                </div>
                <div style="margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.03); border-radius: 10px;">
                    <h5 style="color: var(--primary-color); font-family: 'Oswald'; margin-bottom: 5px;">Instructor's Tip:</h5>
                    <p style="font-size: 0.9rem; line-height: 1.4; color: var(--text-gray); white-space: pre-line;">
                        ${video.content}
                    </p>
                    ${buttonsHtml}
                </div>
            `;
        }

        function playVideo(element, videoUrl, videoId) {
            selectedVideoId = videoId;
            const playerContainer = document.getElementById('video-player-container');
            const iframe = document.getElementById('video-iframe');
            const overlay = document.getElementById('video-completion-overlay');
            const markBtn = document.getElementById('mark-finished-btn');
            const redoBtn = document.getElementById('redo-workout-btn');

            document.querySelectorAll('.video-item').forEach(item => item.classList.remove('playing'));
            element.classList.add('playing');

            // Robustly append autoplay
            const finalUrl = videoUrl + (videoUrl.includes('?') ? '&' : '?') + "autoplay=1";
            iframe.src = finalUrl;
            playerContainer.classList.add('active');
            overlay.style.display = 'flex';

            if (element.classList.contains('video-done')) {
                markBtn.style.display = 'none';
                redoBtn.style.display = 'block';
            } else {
                markBtn.style.display = 'block';
                redoBtn.style.display = 'none';
            }
        }

        function markVideoFinished(type) {
            if (type === 'reset_all' && !confirm('Are you sure you want to reset all progress for this month?')) return; if (type !== 'reset_all' && (!selectedVideoId || !selectedDate)) return;
            const formData = new FormData();
            formData.append('finish_workout_ajax', '1');
            formData.append('video_id', selectedVideoId);
            formData.append('action_type', type);
            formData.append('custom_date', selectedDate);

            fetch(window.location.href, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const dayNum = parseInt(selectedDate.split('-')[2]);
                        const dayEl = document.getElementById('day-' + dayNum);
                        const videoItem = document.getElementById('current-video-item');
                        const markBtn = document.getElementById('mark-finished-btn');
                        const redoBtn = document.getElementById('redo-workout-btn');

                        if (type === 'complete') {
                            dayEl.classList.add('completed');
                            if (videoItem) {
                                videoItem.classList.add('video-done');
                                videoItem.querySelector('.fa-play')?.classList.replace('fa-play', 'fa-check-circle');
                                if (!videoItem.querySelector('.check-icon')) {
                                    const icon = document.createElement('i');
                                    icon.className = 'fa-solid fa-circle-check check-icon';
                                    icon.style.color = 'var(--primary-color)';
                                    videoItem.appendChild(icon);
                                }
                            }
                            markBtn.style.display = 'none';
                            redoBtn.style.display = 'block';
                        } else if (type === 'reset_all') {
                            // Clear all calendar highlights
                            document.querySelectorAll('.calendar-day.completed').forEach(el => el.classList.remove('completed'));
                            // Reset current video UI if open
                            if (videoItem) {
                                videoItem.classList.remove('video-done');
                                videoItem.querySelector('.fa-check-circle')?.classList.replace('fa-check-circle', 'fa-play');
                                videoItem.querySelector('.check-icon')?.remove();
                            }
                            markBtn.style.display = 'block';
                            redoBtn.style.display = 'none';
                            resetTimer();
                        } else {
                            dayEl.classList.remove('completed');
                            if (videoItem) {
                                videoItem.classList.remove('video-done');
                                videoItem.querySelector('.fa-check-circle')?.classList.replace('fa-check-circle', 'fa-play');
                                videoItem.querySelector('.check-icon')?.remove();
                            }
                            markBtn.style.display = 'block';
                            redoBtn.style.display = 'none';
                            resetTimer(); // Reset timer on individual redo too
                        }

                        // Update Progress Bar
                        document.getElementById('monthly-progress-bar').style.width = data.percent + '%';
                        document.getElementById('completed-days-text').innerText = data.count || 0;
                        document.getElementById('progress-percent-text').innerText = data.percent || 0;
                        document.getElementById('monthly-progress-bar').style.width = (data.percent || 0) + '%';

                        // Update Completed Dates Badges
                        const badgeContainer = document.getElementById('completed-dates-badges');
                        if (badgeContainer && data.dates) {
                            if (data.dates.length === 0) {
                                badgeContainer.innerHTML = '<p style="font-size: 0.8rem; color: #555;">No workouts completed yet.</p>';
                            } else {
                                badgeContainer.innerHTML = data.dates.map(date => `
                                    <span style="background: rgba(0,255,0,0.1); color: #00ff00; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; border: 1px solid rgba(0,255,0,0.2);">
                                        <i class="fa-solid fa-check" style="font-size: 0.6rem;"></i> ${date}
                                    </span>
                                `).join('');
                            }
                        }

                        // Achievement Medal Color Updates
                        const p = parseInt(data.percent);
                        document.getElementById('medal-half').style.color = p >= 50 ? '#ffd700' : '#333';
                        document.getElementById('medal-fire').style.color = p >= 75 ? '#ff4500' : '#333';
                        document.getElementById('medal-full').style.color = p >= 100 ? '#ceff00' : '#333';

                        // Refresh the view to update buttons (Mark Finished <-> Redo)
                        selectDay(dayNum);
                    }
                });
        }

        // Timer & Clock Logic
        let timerSeconds = 0;
        let timerInterval = null;
        let isCountdown = false;

        function updateClock() {
            const now = new Date();
            const h = now.getHours().toString().padStart(2, '0');
            const m = now.getMinutes().toString().padStart(2, '0');
            const s = now.getSeconds().toString().padStart(2, '0');
            const timeStr = `${h}:${m}:${s}`;

            const clockEl = document.getElementById('dashboard-clock');
            if (clockEl) clockEl.innerText = timeStr;
        }
        setInterval(updateClock, 1000);
        updateClock();

        function toggleTimer() {
            const btn = document.getElementById('timer-btn');
            const hInput = document.getElementById('timer-hours-input');
            const mInput = document.getElementById('timer-minutes-input');
            const sInput = document.getElementById('timer-seconds-input');
            const display = document.getElementById('timer-display');

            if (timerInterval) {
                // Pause
                clearInterval(timerInterval);
                timerInterval = null;
                btn.innerHTML = '<i class="fa-solid fa-play"></i>';
                hInput.disabled = false;
                mInput.disabled = false;
                sInput.disabled = false;
            } else {
                // Start
                if (timerSeconds === 0) {
                    const h = parseInt(hInput.value) || 0;
                    const m = parseInt(mInput.value) || 0;
                    const s = parseInt(sInput.value) || 0;
                    const totalSecs = (h * 3600) + (m * 60) + s;

                    if (totalSecs > 0) {
                        timerSeconds = totalSecs;
                        isCountdown = true;
                    } else {
                        isCountdown = false;
                    }
                }

                hInput.disabled = true;
                mInput.disabled = true;
                sInput.disabled = true;

                timerInterval = setInterval(() => {
                    if (isCountdown) {
                        if (timerSeconds > 0) {
                            timerSeconds--;
                        } else {
                            clearInterval(timerInterval);
                            timerInterval = null;
                            btn.innerHTML = '<i class="fa-solid fa-play"></i>';
                            hInput.disabled = false;
                            mInput.disabled = false;
                            sInput.disabled = false;
                            display.style.color = '#ff4d4d';
                            display.classList.add('pulse');
                            playAlarm();
                            setTimeout(() => {
                                display.style.color = '#fff';
                                display.classList.remove('pulse');
                            }, 5000);
                            return;
                        }
                    } else {
                        timerSeconds++;
                    }

                    const hours = Math.floor(timerSeconds / 3600).toString().padStart(2, '0');
                    const minutes = Math.floor((timerSeconds % 3600) / 60).toString().padStart(2, '0');
                    const seconds = (timerSeconds % 60).toString().padStart(2, '0');
                    display.innerText = `${hours}:${minutes}:${seconds}`;
                }, 1000);
                btn.innerHTML = '<i class="fa-solid fa-pause"></i>';
            }
        }

        function playAlarm() {
            const context = new (window.AudioContext || window.webkitAudioContext)();
            const playBeep = (time) => {
                const osc = context.createOscillator();
                const gain = context.createGain();
                osc.connect(gain);
                gain.connect(context.destination);
                osc.type = 'sine';
                osc.frequency.value = 880; // A5 note
                gain.gain.setValueAtTime(0.1, time);
                gain.gain.exponentialRampToValueAtTime(0.01, time + 0.5);
                osc.start(time);
                osc.stop(time + 0.5);
            };
            // Play 3 beeps
            playBeep(context.currentTime);
            playBeep(context.currentTime + 0.7);
            playBeep(context.currentTime + 1.4);
        }

        function resetTimer() {
            clearInterval(timerInterval);
            timerInterval = null;
            timerSeconds = 0;
            isCountdown = false;
            const display = document.getElementById('timer-display');
            display.innerText = '00:00:00';
            display.style.color = '#fff';
            display.classList.remove('pulse');
            document.getElementById('timer-btn').innerHTML = '<i class="fa-solid fa-play"></i>';

            const hInput = document.getElementById('timer-hours-input');
            const mInput = document.getElementById('timer-minutes-input');
            sInput.disabled = false;
            hInput.value = '';
            mInput.value = '';
            sInput.value = '';
        }

        function playNextVideoFromOverview(videoId) {
            showSection('workouts');
            setTimeout(() => {
                selectDay(<?php echo $today_day; ?>);
                const videoItems = document.querySelectorAll('.video-item');
                if (videoItems.length) videoItems[0].click();
            }, 300);
        }

        // Initialize today's workout
        window.addEventListener('load', () => {
            // Keep section persistent on month change
            const urlParams = new URLSearchParams(window.location.search);
            const isMonthChange = urlParams.has('m');

            const sectionParam = urlParams.get('section');

            // Check for temporary section redirect (e.g. from completion actions)
            const tempSection = localStorage.getItem('temp_redirect_section');
            localStorage.removeItem('temp_redirect_section'); // Clear immediately so normal refresh goes to Overview

            if (sectionParam) {
                showSection(sectionParam);
            } else if (tempSection) {
                showSection(tempSection);
            } else if (isMonthChange) {
                showSection('workouts');
            } else {
                showSection('overview');
            }

            // Restore last selected day (runs in background for Workouts tab)
            const storedDay = localStorage.getItem('lastSelectedDay');

            if (storedDay && document.getElementById('day-' + storedDay)) {
                selectDay(parseInt(storedDay));
            } else if (<?php echo $today_day; ?> > 0) {
                selectDay(<?php echo $today_day; ?>);
            }

            // Only auto-scroll on month change
            const activeDay = storedDay || <?php echo $today_day; ?>;
            const scrollEl = document.getElementById('day-' + activeDay);
            if (scrollEl && isMonthChange) {
                scrollEl.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }
        });


        // PAYMENT Logic
        let currentMethod = 'Credit Card';
        let paymentContext = 'membership'; // 'membership' or 'appointment'
        let currentPayAmount = 0;
        let currentPayDesc = '';
        let appointmentData = null; // Store temp data

        const planSelector = document.getElementById('plan-selector');
        if (planSelector) {
            planSelector.addEventListener('change', function () {
                if (paymentContext === 'membership') {
                    document.getElementById('payment-amt-display').innerText = "₹" + this.value + ".00";
                    updateUPI_QR();
                }
            });
        }

        function updateUPI_QR() {
            let amt, plan;

            if (paymentContext === 'appointment') {
                amt = currentPayAmount;
                plan = currentPayDesc;
            } else {
                // Membership context
                amt = document.getElementById('plan-selector').value;
                plan = document.getElementById('plan-selector').options[document.getElementById('plan-selector').selectedIndex].text.split(' - ')[0];
            }

            let host = window.location.hostname;
            let port = window.location.port ? ':' + window.location.port : '';

            // If on localhost, use the server's internal IP so mobile can reach it
            if (host === 'localhost' || host === '127.0.0.1') {
                host = '<?php echo gethostbyname(gethostname()); ?>';
            }

            const baseUrl = window.location.protocol + "//" + host + port + window.location.pathname.replace('dashboard_member.php', '');
            // Append timestamp to ensure QR is unique every time
            const mockUrl = `${baseUrl}gpay_mock.php?amt=${amt}&plan=${encodeURIComponent(plan)}&uid=<?php echo $user_id; ?>&ts=${Date.now()}`;

            // Generate QR Locally
            const qrContainer = document.getElementById('upi-qr-code');
            if (qrContainer && typeof QRCode !== 'undefined') {
                qrContainer.innerHTML = ''; // Clear previous
                new QRCode(qrContainer, {
                    text: mockUrl,
                    width: 180,
                    height: 180,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            } else if (qrContainer) {
                // Fallback if lib failed to load
                qrContainer.innerHTML = 'QR Lib loading...';
            }
        }

        function openPaymentModal(context = 'membership', amount = 0, desc = '') {
            paymentContext = context;
            currentPayAmount = amount;
            currentPayDesc = desc;

            if (context === 'membership') {
                // Default logic pulls from selector
                updateUPI_QR();
            } else if (context === 'appointment') {
                // Custom fixed amount
                document.getElementById('payment-amt-display').innerText = "₹" + amount + ".00";
                updateUPI_QR();
            }

            document.getElementById('payment-modal').style.display = 'flex';
        }



        let membershipPollInterval;

        function startMembershipPolling() {
            if (membershipPollInterval) clearInterval(membershipPollInterval);

            // Determine plan name based on context
            let planName = '';
            if (paymentContext === 'membership') {
                const selector = document.getElementById('plan-selector');
                if (selector) {
                    planName = selector.options[selector.selectedIndex].text.split(' - ')[0];
                }
            } else {
                planName = currentPayDesc; // e.g. "Trainer Appointment"
            }

            if (!planName) return;

            // Poll every 3 seconds for payment confirmation
            membershipPollInterval = setInterval(() => {
                // Use current user ID injected via PHP
                const uid = <?php echo $user_id; ?>;
                const url = `check_payment_status.php?uid=${uid}&plan=${encodeURIComponent(planName)}`;

                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'paid') {
                            clearInterval(membershipPollInterval);
                            startProcessing(true); // Auto-confirm payment
                        }
                    })
                    .catch(console.error);
            }, 3000);
        }

        function closePaymentModal() {
            document.getElementById('payment-modal').style.display = 'none';
            if (membershipPollInterval) clearInterval(membershipPollInterval);
        }

        function selectPayMethod(el, method) {
            currentMethod = method;
            document.querySelectorAll('.pay-opt').forEach(opt => opt.classList.remove('active'));
            el.classList.add('active');

            if (method === 'GPay') {
                document.getElementById('card-fields').style.display = 'none';
                document.getElementById('gpay-fields').style.display = 'block';
                updateUPI_QR();
                startMembershipPolling(); // Start listening for GPay scan completion
            } else {
                document.getElementById('card-fields').style.display = 'block';
                document.getElementById('gpay-fields').style.display = 'none';
                if (membershipPollInterval) clearInterval(membershipPollInterval);
            }
        }

        function startProcessing(fromScan = false) {
            if (membershipPollInterval) clearInterval(membershipPollInterval);

            const errorEl = document.getElementById('payment-error');
            errorEl.style.display = 'none';

            // Validation (skip if confirming from scan)
            if (!fromScan) {
                if (currentMethod === 'Credit Card') {
                    const cardNum = document.getElementById('card-num').value.trim();
                    const cardExp = document.getElementById('card-exp').value.trim();
                    const cardCvv = document.getElementById('card-cvv').value.trim();

                    if (cardNum.length !== 16 || isNaN(cardNum)) {
                        errorEl.innerText = "Please enter a valid 16-digit card number.";
                        errorEl.style.display = 'block';
                        return;
                    }
                    if (!/^\d{2}\/\d{2}$/.test(cardExp)) {
                        errorEl.innerText = "Please enter expiry in MM/YY format.";
                        errorEl.style.display = 'block';
                        return;
                    }
                    if (cardCvv.length !== 3 || isNaN(cardCvv)) {
                        errorEl.innerText = "Please enter a valid 3-digit CVV.";
                        errorEl.style.display = 'block';
                        return;
                    }
                } else {
                    const upiId = document.getElementById('upi-id-input').value.trim();
                    // Strict UPI format: username@bank (Letters only, no numbers allowed as per request)
                    if (!/^[a-zA-Z][a-zA-Z.-]*@[a-zA-Z]+$/.test(upiId)) {
                        errorEl.innerText = "Please enter a valid format: username@bank (only letters allowed, e.g. user@okaxis)";
                        errorEl.style.display = 'block';
                        return;
                    }
                }
            }

            document.getElementById('payment-form-area').style.display = 'none';
            document.getElementById('processing-payment').style.display = 'block';

            setTimeout(() => {
                document.getElementById('processing-payment').style.display = 'none';
                document.getElementById('payment-success').style.display = 'block';

                if (paymentContext === 'membership') {
                    const planName = planSelector.options[planSelector.selectedIndex].text.split(' - ')[0];
                    const amt = planSelector.value;

                    document.getElementById('final-plan').value = planName;
                    document.getElementById('final-amt').value = amt;
                    document.getElementById('final-method').value = currentMethod;

                    // Allow user to click Done to submit membership form
                } else if (paymentContext === 'appointment') {
                    // Update Appointment Success UI
                    document.getElementById('success-amt-display').innerText = "200.00";
                    document.getElementById('success-method-display').innerText = currentMethod;

                    // Change the Done button behavior or auto-submit
                    // We will attach a specific handler or just trigger the booking form submit

                    // Change the form action or use JS to submit the original booking form
                    const confirmForm = document.getElementById('confirm-payment-form');
                    // Remove existing hidden inputs to avoid conflict if any, or just repurpose?
                    // Actually, for appointments, we just need to submit the booking form data

                    // Helper: Hide the membership submit button, show a "Finish Booking" button
                    confirmForm.innerHTML = `
                        <button type="button" class="btn-action" style="width: 100%; border-radius: 30px;" onclick="finalizeBooking()">
                            Finish Booking
                        </button>
                     `;
                }

                // Update Success UI Common Parts
                if (paymentContext === 'membership') {
                    const amt = planSelector.value;
                    document.getElementById('success-amt-display').innerText = parseFloat(amt).toFixed(2);
                }
                document.getElementById('success-method-display').innerText = currentMethod;
            }, 2500);
        }

        function finalizeBooking() {
            // Submit the booking form programmatically
            const bookingForm = document.querySelector('#booking-modal form');

            // Append confirmed flag
            const inputConfirmed = document.createElement('input');
            inputConfirmed.type = 'hidden';
            inputConfirmed.name = 'payment_confirmed';
            inputConfirmed.value = '1';
            bookingForm.appendChild(inputConfirmed);

            // Append Payment Method
            const inputMethod = document.createElement('input');
            inputMethod.type = 'hidden';
            inputMethod.name = 'payment_method';
            inputMethod.value = currentMethod; // defined globally
            bookingForm.appendChild(inputMethod);

            // Append Amount (for recording transaction)
            const inputAmount = document.createElement('input');
            inputAmount.type = 'hidden';
            inputAmount.name = 'payment_amount';
            inputAmount.value = '200';
            bookingForm.appendChild(inputAmount);

            // Submit
            bookingForm.submit();
        }

        // Add popIn animation for success screen
        const style = document.createElement('style');
        style.textContent = `
            @keyframes popIn {
                0% { transform: scale(0); opacity: 0; }
                80% { transform: scale(1.1); }
                100% { transform: scale(1); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
        // Toggle Password Visibility
        const togglePassword = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('new_password_input');
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function () {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }

        // Beginner Timeline Functions
        function toggleWrapper(id) {
            const el = document.getElementById(id);
            if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
        }

        function markBeginnerWeek(weekId) {
            if (!confirm('Are you sure you have completed all activities for this week?')) return;

            const formData = new FormData();
            formData.append('finish_workout_ajax', 1);
            formData.append('video_id', weekId);
            formData.append('action_type', 'complete');
            // Use a unique date for tracking if needed, or today
            formData.append('custom_date', new Date().toISOString().split('T')[0]);

            fetch('dashboard_member.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload to update UI and unlock if done
                        localStorage.setItem('temp_redirect_section', 'workouts');
                        location.reload();
                    } else {
                        alert('Error updating progress.');
                    }
                });
        }

        function showToast(msg) {
            let toast = document.getElementById('status-toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'status-toast';
                toast.className = 'toast';
                document.body.appendChild(toast);
            }
            toast.innerText = msg;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        function showAttendanceStatus(dateStr, status, isFuture) {
            if (isFuture) return;
            showToast(`On ${dateStr}, you were ${status}.`);

            // Reset surrounding styles
            ['legend-present', 'legend-absent'].forEach(id => {
                const el = document.getElementById(id);
                el.style.background = 'transparent';
                el.style.borderColor = 'transparent';
                el.style.transform = 'scale(1)';
                el.style.boxShadow = 'none';
                el.style.color = 'var(--text-gray)';
            });

            const pBox = document.getElementById('legend-present-box');
            const aBox = document.getElementById('legend-absent-box');

            if (status === 'Present') {
                pBox.style.background = 'var(--primary-color)';
                pBox.style.boxShadow = '0 0 10px var(--primary-color)';
                pBox.style.transform = 'scale(1.2)';

                aBox.style.background = 'rgba(255,255,255,0.1)';
                aBox.style.boxShadow = 'none';
                aBox.style.transform = 'scale(1)';
            } else {
                pBox.style.background = 'rgba(255,255,255,0.1)';
                pBox.style.boxShadow = 'none';
                pBox.style.transform = 'scale(1)';

                aBox.style.background = 'var(--primary-color)';
                aBox.style.boxShadow = '0 0 10px var(--primary-color)';
                aBox.style.transform = 'scale(1.2)';
            }
        }
    </script>
    <!-- Booking Modal (Moved to Bottom) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        .time-slots-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .time-slot {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: 0.3s;
            width: 100%;
            white-space: nowrap;
        }

        .time-slot:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: #fff;
        }

        .time-slot.selected {
            background: var(--primary-color);
            color: #000;
            border-color: var(--primary-color);
            font-weight: bold;
            box-shadow: 0 0 10px rgba(206, 255, 0, 0.4);
        }

        .flatpickr-calendar {
            background: #1a1a2e;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .flatpickr-day.selected {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: #000;
        }

        #booking-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1100;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }
    </style>
    <div id="booking-modal">
        <div class="card"
            style="width:100%; max-width:450px; background: #0f0f1a; border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); padding: 0; overflow: hidden;">
            <div class="card-header"
                style="background: rgba(255,255,255,0.02); padding: 20px 25px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <h3 style="margin:0; font-family:'Oswald'; display:flex; align-items:center; gap:10px;">Book
                    Appointment
                </h3>
                <button onclick="document.getElementById('booking-modal').style.display='none'"
                    style="background:none; border:none; color:var(--text-gray); cursor:pointer; font-size:1.2rem; transition:0.3s;"
                    onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-gray)'"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
            <div style="padding: 25px;">
                <p style="margin-bottom:20px; color:var(--text-gray); font-size: 0.9rem;">With: <strong
                        id="booking-trainer-name"
                        style="color:var(--primary-color); font-size: 1.1rem; margin-left: 5px;"></strong></p>
                <form method="POST">
                    <input type="hidden" name="book_appointment" value="1">
                    <input type="hidden" name="trainer_id" id="booking-trainer-id">
                    <div style="margin-bottom: 25px;">
                        <label
                            style="display:block; margin-bottom: 10px; color: #fff; font-weight: 500; font-size: 0.95rem;">Select
                            Date</label>
                        <div style="position: relative;">
                            <input type="text" id="flatpickr-date" name="date" required placeholder="yyyy-mm-dd"
                                style="width:100%; padding:14px 15px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#fff; border-radius:10px; font-size: 1rem; outline: none; cursor: pointer;">
                            <i class="fa-regular fa-calendar-days"
                                style="position:absolute; right:15px; top:50%; transform:translateY(-50%); color: var(--text-gray); pointer-events: none;"></i>
                        </div>
                    </div>
                    <div style="margin-bottom: 30px;">
                        <label
                            style="display:block; margin-bottom: 10px; color: #fff; font-weight: 500; font-size: 0.95rem;">Select
                            Time</label>
                        <input type="hidden" name="time" id="selected-time-input" required>
                        <div class="time-slots-grid">
                            <button type="button" class="time-slot" onclick="selectTime(this, '06:00 AM')">06:00
                                AM</button>
                            <button type="button" class="time-slot" onclick="selectTime(this, '07:00 AM')">07:00
                                AM</button>
                            <button type="button" class="time-slot" onclick="selectTime(this, '08:00 AM')">08:00
                                AM</button>
                            <button type="button" class="time-slot" onclick="selectTime(this, '09:00 AM')">09:00
                                AM</button>
                            <button type="button" class="time-slot" onclick="selectTime(this, '10:00 AM')">10:00
                                AM</button>
                            <button type="button" class="time-slot" onclick="selectTime(this, '04:00 PM')">04:00
                                PM</button>
                            <button type="button" class="time-slot" onclick="selectTime(this, '05:00 PM')">05:00
                                PM</button>
                            <button type="button" class="time-slot" onclick="selectTime(this, '06:00 PM')">06:00
                                PM</button>
                            <button type="button" class="time-slot" onclick="selectTime(this, '07:00 PM')">07:00
                                PM</button>
                        </div>
                        <div
                            style="margin-top: 30px; margin-bottom: 20px; padding: 15px 20px; background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #bbb; font-size: 0.95rem;">Appointment Fee</span>
                            <span
                                style="color: var(--primary-color); font-weight: bold; font-size: 1.2rem; font-family:'Oswald', sans-serif;">₹
                                200.00</span>
                        </div>
                        <button type="button" class="btn-action" onclick="initiateAppointmentPayment()"
                            style="width:100%; border-radius: 30px; font-weight: bold; padding: 14px; font-size: 1rem; text-transform: uppercase;">
                            Pay Additional ₹200
                        </button>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof flatpickr !== "undefined") {
                flatpickr("#flatpickr-date", { minDate: "today", dateFormat: "Y-m-d", theme: "dark", disableMobile: "true" });
            }
        });
        function openBookingModal(id, name) {
            document.getElementById('booking-trainer-id').value = id;
            document.getElementById('booking-trainer-name').innerText = name;
            document.getElementById('booking-modal').style.display = 'flex';
            document.getElementById('selected-time-input').value = '';
            document.querySelectorAll('.time-slot').forEach(el => el.classList.remove('selected'));
            if (document.getElementById('flatpickr-date')._flatpickr) document.getElementById('flatpickr-date')._flatpickr.clear();
        }
        function selectTime(btn, time) {
            document.querySelectorAll('.time-slot').forEach(el => el.classList.remove('selected'));
            btn.classList.add('selected');
            document.getElementById('selected-time-input').value = time;
        }
        function initiateAppointmentPayment() {
            const date = document.getElementById('flatpickr-date').value;
            const time = document.getElementById('selected-time-input').value;

            const trainerId = document.getElementById('booking-trainer-id').value;

            if (!date || !time) {
                alert("Please select both a date and a time slot.");
                return;
            }

            // Check Availability via AJAX
            const formData = new FormData();
            formData.append('ajax_action', 'check_availability');
            formData.append('trainer_id', trainerId);
            formData.append('date', date);
            formData.append('time', time);

            // Show loading state if desired, or just wait
            const btn = document.querySelector('#booking-modal .btn-action');
            const originalText = btn.innerText;
            btn.innerText = "Checking Availability...";
            btn.disabled = true;

            fetch('dashboard_member.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    btn.innerText = originalText;
                    btn.disabled = false;

                    if (data.success && data.available === false) {
                        alert("Can't appoint the trainer now because the trainer is with another user. Please choose another time.");
                    } else {
                        // Proceed to payment
                        document.getElementById('booking-modal').style.display = 'none';
                        openPaymentModal('appointment', 200, 'Trainer Appointment');
                    }
                })
                .catch(err => {
                    console.error('Error checking availability:', err);
                    btn.innerText = originalText;
                    btn.disabled = false;
                    alert("Error checking availability. Please try again.");
                });
        }
    </script>

    <script>
        let chatPollInterval;
        let currentChatPartnerId = null;

        function openChatWith(el, userId, name, image) {
            currentChatPartnerId = userId;
            document.getElementById('chat-partner-name').innerText = name;
            document.getElementById('chat-partner-img').src = image;

            document.getElementById('chat-header').style.display = 'block';
            document.getElementById('chat-input-area').style.display = 'block';

            // Highlight active trainer
            document.querySelectorAll('.chat-user-item').forEach(e => e.style.background = 'transparent');
            if (el) {
                el.style.background = 'rgba(255, 255, 255, 0.05)';
                // Remove yellow dot if exists
                const dot = el.querySelector('.fa-circle');
                if (dot) dot.remove();
            }

            fetchMessages();
            if (chatPollInterval) clearInterval(chatPollInterval);
            chatPollInterval = setInterval(fetchMessages, 3000);
        }

        function fetchMessages() {
            if (!currentChatPartnerId) return;

            const formData = new FormData();
            formData.append('ajax_action', 'fetch_messages');
            formData.append('partner_id', currentChatPartnerId);

            fetch('dashboard_member.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const chatBox = document.getElementById('chat-messages');

                        if (data.messages.length === 0) {
                            chatBox.innerHTML = '<div style="text-align:center; color:#666; margin-top:50px;"><i class="fa-regular fa-comments" style="font-size:3rem; margin-bottom:15px; opacity:0.5;"></i><p>No messages yet. Say hi!</p></div>';
                        } else {
                            // Preserve scroll if not near bottom? No, simple auto-scroll for now
                            const wasAtBottom = chatBox.scrollHeight - chatBox.scrollTop === chatBox.clientHeight;

                            // Clear and rebuild (simple) - for production, append only new would be better but this is MVP
                            chatBox.innerHTML = '';
                            data.messages.forEach(msg => {
                                const isMe = msg.is_me;
                                const div = document.createElement('div');
                                div.style.alignSelf = isMe ? 'flex-end' : 'flex-start';
                                div.style.maxWidth = '70%';
                                div.style.padding = '10px 15px';
                                div.style.borderRadius = isMe ? '15px 15px 0 15px' : '15px 15px 15px 0';
                                div.style.fontSize = '0.9rem';
                                div.style.background = isMe ? 'var(--primary-color)' : 'rgba(255,255,255,0.1)';
                                div.style.color = isMe ? '#000' : '#fff';
                                div.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
                                div.innerHTML = `
                                ${msg.message}
                                <div style="font-size:0.7rem; opacity:0.7; text-align:right; margin-top:5px; display:flex; justify-content:flex-end; align-items:center;">
                                    ${msg.time} ${isMe ? `<i class="fa-solid fa-trash" style="font-size:0.75rem; cursor:pointer; margin-left:8px; opacity:0.6; transition:0.3s;" onmouseover="this.style.opacity=1;this.style.color='#ff4d4d'" onmouseout="this.style.opacity=0.6;this.style.color='inherit'" onclick="deleteMessage(${msg.id})"></i>` : ''}
                                </div>
                            `;
                                chatBox.appendChild(div);
                            });

                            if (wasAtBottom || true) { // Always scroll for now
                                chatBox.scrollTop = chatBox.scrollHeight;
                            }
                        }
                    }
                });
        }

        function deleteMessage(id) {
            if (!confirm('Are you sure you want to delete this message?')) return;

            const formData = new FormData();
            formData.append('ajax_action', 'delete_message');
            formData.append('message_id', id);

            fetch('dashboard_member.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) fetchMessages();
                });
        }

        function sendChatMessage() {
            const input = document.getElementById('chat-input');
            const msg = input.value.trim();
            if (!msg || !currentChatPartnerId) return;

            const formData = new FormData();
            formData.append('ajax_action', 'send_message');
            formData.append('receiver_id', currentChatPartnerId);
            formData.append('message', msg);

            input.value = ''; // clear immediately

            fetch('dashboard_member.php', { method: 'POST', body: formData })
                .then(() => fetchMessages());
        }

        function filterChatUsers(inputId, listId) {
            const input = document.getElementById(inputId);
            const filter = input.value.toUpperCase();
            const list = document.getElementById(listId);
            const items = list.getElementsByClassName('chat-user-item');

            for (let i = 0; i < items.length; i++) {
                const nameEl = items[i].getElementsByClassName('chat-user-name')[0];
                if (nameEl) {
                    const txtValue = nameEl.textContent || nameEl.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        items[i].style.display = "";
                    } else {
                        items[i].style.display = "none";
                    }
                }
            }
        }
    </script>

    <!-- Reports Modal -->
    <div id="reports-modal" class="modal-overlay"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; justify-content:center; align-items:center;">
        <div class="modal-content"
            style="background:#1a1a2e; padding:0; border-radius:15px; width:95%; max-width:800px; max-height:85vh; overflow:hidden; border:1px solid rgba(255,255,255,0.1); display:flex; flex-direction:column; box-shadow: 0 0 40px rgba(0,0,0,0.6);">

            <!-- Header -->
            <div
                style="padding:20px 25px; border-bottom:1px solid rgba(255,255,255,0.1); display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.2);">
                <h3 style="font-family:'Oswald'; color:#fff; margin:0; font-size:1.4rem;"><i
                        class="fa-solid fa-calendar-days" style="color:var(--primary-color); margin-right:10px;"></i>
                    Attendance Reports</h3>
                <button onclick="document.getElementById('reports-modal').style.display='none'"
                    style="background:none; border:none; color:var(--text-gray); font-size:1.4rem; cursor:pointer;"
                    onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-gray)'"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>

            <!-- Year Selector Dropdown -->
            <div style="padding:15px 25px; background:#141424; display:flex; align-items:center; gap:15px;">
                <label style="color:#ceff00; font-family:'Oswald'; font-size:1rem; letter-spacing:1px;">
                    <i class="fa-solid fa-calendar-alt" style="margin-right:8px;"></i>SELECT YEAR:
                </label>
                <select id="member-year-selector" onchange="switchReportYear(this.value)"
                    style="padding:10px 15px; border-radius:8px; border:1px solid rgba(206,255,0,0.3); background:rgba(0,0,0,0.3); color:#fff; font-family:'Oswald'; font-size:1rem; cursor:pointer; min-width:120px; transition:0.3s;"
                    onmouseover="this.style.borderColor='#ceff00'"
                    onmouseout="this.style.borderColor='rgba(206,255,0,0.3)'">
                    <?php
                    $is_first = true;
                    foreach ($attendance_by_year as $year => $data):
                        $selected = $is_first ? 'selected' : '';
                        ?>
                    <option value="<?php echo $year; ?>" <?php echo $selected; ?>
                        style="background:#1a1a2e; color:#fff;">
                        <?php echo $year; ?>
                    </option>
                    <?php
                    $is_first = false;
                    endforeach;
                    ?>
                </select>
            </div>

            <!-- Content Area -->
            <div style="padding:25px; overflow-y:auto; flex-grow:1; background:rgba(255,255,255,0.02);">
                <?php $is_first = true;
                foreach ($attendance_by_year as $year => $months): ?>
                <div id="year-content-<?php echo $year; ?>" class="year-content-group"
                    style="display:<?php echo $is_first ? 'grid' : 'none'; ?>; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap:15px;">
                    <?php foreach ($months as $m_num => $m_data):
                        $has_data = $m_data['count'] > 0;
                        // Check if future month
                        $is_future_month = ($year == date('Y') && $m_num > date('n')) || ($year > date('Y'));
                        ?>
                    <?php if ($is_future_month): ?>
                    <div style="text-decoration:none; display:block; cursor:not-allowed;">
                        <div
                            style="background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); border-radius:12px; padding:15px; text-align:center; position:relative; overflow:hidden; opacity:0.3;">
                            <h4 style="margin:0 0 5px 0; color:#fff; font-size:1rem; opacity:0.9;">
                                <?php echo $m_data['short']; ?>
                            </h4>
                            <div style="font-size:1.8rem; font-weight:bold; color:#444; margin:5px 0;">
                                -
                            </div>
                            <div
                                style="font-size:0.7rem; color:var(--text-gray); text-transform:uppercase; letter-spacing:1px;">
                                Future</div>
                        </div>
                    </div>
                    <?php else: ?>
                    <a href="attendance_report.php?m=<?php echo $m_num; ?>&y=<?php echo $year; ?>" target="_blank"
                        style="text-decoration:none; display:block; cursor:pointer;">
                        <div style="background:<?php echo $has_data ? 'rgba(206, 255, 0, 0.05)' : 'rgba(255,255,255,0.02)'; ?>; border:1px solid <?php echo $has_data ? 'rgba(206, 255, 0, 0.2)' : 'rgba(255,255,255,0.05)'; ?>; border-radius:12px; padding:15px; text-align:center; transition:0.3s; position:relative; overflow:hidden;"
                            onmouseover="this.style.transform='translateY(-3px)'; this.style.borderColor='var(--primary-color)'; this.style.boxShadow='0 5px 15px rgba(206,255,0,0.2)'"
                            onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='<?php echo $has_data ? 'rgba(206, 255, 0, 0.2)' : 'rgba(255,255,255,0.05)'; ?>'; this.style.boxShadow='none'">

                            <h4 style="margin:0 0 5px 0; color:#fff; font-size:1rem; opacity:0.9;">
                                <?php echo $m_data['short']; ?>
                            </h4>

                            <div
                                style="font-size:1.8rem; font-weight:bold; color:<?php echo $has_data ? 'var(--primary-color)' : '#444'; ?>; margin:5px 0;">
                                <?php echo $m_data['count']; ?>
                            </div>
                            <div
                                style="font-size:0.7rem; color:var(--text-gray); text-transform:uppercase; letter-spacing:1px;">
                                Days</div>

                            <?php if ($has_data): ?>
                            <div
                                style="position:absolute; top:10px; right:10px; width:8px; height:8px; background:var(--primary-color); border-radius:50%; box-shadow:0 0 5px var(--primary-color);">
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php $is_first = false; endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        function switchReportYear(year) {
            // Hide all contents
            document.querySelectorAll('.year-content-group').forEach(el => el.style.display = 'none');
            // Show selected
            document.getElementById('year-content-' + year).style.display = 'grid';
        }

        // Close modal on outside click
        window.addEventListener('click', function (e) {
            const modal = document.getElementById('reports-modal');
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    </script>
    <script>
        function deleteApptTransaction(id, rowElement) {
            if (!confirm('Are you sure you want to delete this payment record?')) return;

            const formData = new FormData();
            formData.append('ajax_action', 'delete_transaction');
            formData.append('trans_id', id);

            fetch('dashboard_member.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        rowElement.style.opacity = '0';
                        setTimeout(() => rowElement.remove(), 300);
                    } else {
                        alert('Error deleting transaction.');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Request failed.');
                });
        }
    </script>
    <script src="assets/js/qrcode.min.js"></script>


</body>

</html>