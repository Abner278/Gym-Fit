<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== "staff") {
    header("location: login.php");
    exit;
}

// Ensure table exists
$table_sql = "CREATE TABLE IF NOT EXISTS daily_workouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    video_url VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($link, $table_sql);

$msg = "";
if (isset($_SESSION['msg'])) {
    $msg = $_SESSION['msg'];
    unset($_SESSION['msg']);
}

$user_id = $_SESSION["id"];

// FETCH LATEST USER DATA
$stmt = mysqli_prepare($link, "SELECT full_name, email, profile_image, created_at FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_data = mysqli_stmt_get_result($stmt)->fetch_assoc();
$_SESSION["full_name"] = $user_data["full_name"];
$_SESSION["email"] = $user_data["email"];
$profile_image = $user_data["profile_image"];

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
        $msg = "Error: This email address is already registered to another account.";
    } else {
        $update_sql = "UPDATE users SET full_name = '$full_name', email = '$email' $pass_query WHERE id = $user_id";
        if (mysqli_query($link, $update_sql)) {
            $_SESSION["full_name"] = $full_name;
            $_SESSION["email"] = $email;
            $msg = "Profile updated successfully!";
        } else {
            $msg = "Error: Failed to update profile. Please try again.";
        }
    }
}

// Handle Add Content
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_content'])) {
    $title = mysqli_real_escape_string($link, $_POST['title']);
    $date = mysqli_real_escape_string($link, $_POST['schedule_date']);
    $video = mysqli_real_escape_string($link, $_POST['video_url']);
    $desc = mysqli_real_escape_string($link, $_POST['description']);

    $sql = "INSERT INTO daily_workouts (date, video_url, title, description) VALUES ('$date', '$video', '$title', '$desc')";
    if (mysqli_query($link, $sql)) {
        $_SESSION['msg'] = "Content uploaded successfully!";
    } else {
        $_SESSION['msg'] = "Error uploading content: " . mysqli_error($link);
    }
    header("Location: dashboard_staff.php");
    exit;
}

// Handle Appointment Status Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_appointment'])) {
    $appt_id = (int) $_POST['appt_id'];
    $status = $_POST['status']; // approved / rejected
    $reply = mysqli_real_escape_string($link, $_POST['reply']);

    if (mysqli_query($link, "UPDATE appointments SET status='$status', staff_message='$reply' WHERE id=$appt_id")) {
        $_SESSION['msg'] = "Appointment $status successfully!";
    } else {
        $_SESSION['msg'] = "Error updating appointment.";
    }
    header("Location: dashboard_staff.php");
    exit;
}

// Handle Delete Appointment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_appointment'])) {
    $appt_id = (int) $_POST['appt_id'];
    if (mysqli_query($link, "DELETE FROM appointments WHERE id=$appt_id")) {
        $_SESSION['msg'] = "Appointment deleted successfully.";
    } else {
        $_SESSION['msg'] = "Error deleting appointment.";
    }
    header("Location: dashboard_staff.php");
    exit;
}

// Fetch Staff Appointments
// Link Staff User to Trainer ID by Name
$my_name_safe = mysqli_real_escape_string($link, $_SESSION["full_name"]);
$linked_trainer_id = $user_id; // Default Fallback

$t_check = mysqli_query($link, "SELECT id FROM trainers WHERE name = '$my_name_safe' OR '$my_name_safe' LIKE CONCAT('%', name, '%') OR name LIKE CONCAT('%', '$my_name_safe', '%') LIMIT 1");
if ($t_check && mysqli_num_rows($t_check) > 0) {
    $linked_trainer_id = mysqli_fetch_assoc($t_check)['id'];
}

// Fetch Staff Appointments using Linked ID
$staff_appts = mysqli_query($link, "SELECT a.*, u.full_name as member_name, u.email as member_email, u.profile_image 
                                    FROM appointments a 
                                    JOIN users u ON a.user_id = u.id 
                                    WHERE a.trainer_id = $linked_trainer_id 
                                    ORDER BY FIELD(a.status, 'pending') DESC, a.booking_date ASC");

// Handle Update Content
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_content'])) {
    $id = (int) $_POST['content_id'];
    $title = mysqli_real_escape_string($link, $_POST['title']);
    $date = mysqli_real_escape_string($link, $_POST['schedule_date']);
    $video = mysqli_real_escape_string($link, $_POST['video_url']);
    $desc = mysqli_real_escape_string($link, $_POST['description']);

    $sql = "UPDATE daily_workouts SET date='$date', video_url='$video', title='$title', description='$desc' WHERE id=$id";
    if (mysqli_query($link, $sql)) {
        $_SESSION['msg'] = "Content updated successfully!";
    } else {
        $_SESSION['msg'] = "Error updating content: " . mysqli_error($link);
    }
    header("Location: dashboard_staff.php");
    exit;
}

// Handle Delete Content
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_content'])) {
    $id = (int) $_POST['content_id'];
    if (mysqli_query($link, "DELETE FROM daily_workouts WHERE id = $id")) {
        $_SESSION['msg'] = "Content removed successfully.";
    } else {
        $_SESSION['msg'] = "Error removing content.";
    }
    header("Location: dashboard_staff.php");
    exit;
}

// --- MEMBER MANAGEMENT ---

// Add Member
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_member'])) {
    $full_name = trim(mysqli_real_escape_string($link, $_POST['full_name']));
    $email = trim(mysqli_real_escape_string($link, $_POST['email']));
    $password_raw = $_POST['password'];

    // Validation
    if (empty($full_name) || empty($email) || empty($password_raw)) {
        $_SESSION['msg'] = "Error: All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['msg'] = "Error: Invalid email format.";
    } elseif (strlen($password_raw) < 6) {
        $_SESSION['msg'] = "Error: Password must be at least 6 characters.";
    } else {
        // Check if email exists
        $check_email = mysqli_query($link, "SELECT id FROM users WHERE email = '$email'");
        if (mysqli_num_rows($check_email) > 0) {
            $_SESSION['msg'] = "Error: Email is already registered.";
        } else {
            $password = password_hash($password_raw, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (full_name, email, password, role) VALUES ('$full_name', '$email', '$password', 'member')";
            if (mysqli_query($link, $sql)) {
                $_SESSION['msg'] = "New member added successfully!";
            } else {
                $_SESSION['msg'] = "Error adding member: " . mysqli_error($link);
            }
        }
    }
    header("Location: dashboard_staff.php");
    exit;
}

// Update Member
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_member'])) {
    $id = (int) $_POST['member_id'];
    $full_name = trim(mysqli_real_escape_string($link, $_POST['full_name']));
    $email = trim(mysqli_real_escape_string($link, $_POST['email']));
    $password_raw = $_POST['password'];

    // Validation
    if (empty($full_name) || empty($email)) {
        $_SESSION['msg'] = "Error: Name and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['msg'] = "Error: Invalid email format.";
    } elseif (!empty($password_raw) && strlen($password_raw) < 6) {
        $_SESSION['msg'] = "Error: New password must be at least 6 characters.";
    } else {
        // Check if email exists for other users
        $check_email = mysqli_query($link, "SELECT id FROM users WHERE email = '$email' AND id != $id");
        if (mysqli_num_rows($check_email) > 0) {
            $_SESSION['msg'] = "Error: Email is already in use by another user.";
        } else {
            $pass_query = "";
            if (!empty($password_raw)) {
                $password = password_hash($password_raw, PASSWORD_DEFAULT);
                $pass_query = ", password='$password'";
            }

            $sql = "UPDATE users SET full_name='$full_name', email='$email' $pass_query WHERE id=$id AND role='member'";
            if (mysqli_query($link, $sql)) {
                $_SESSION['msg'] = "Member updated successfully!";
            } else {
                $_SESSION['msg'] = "Error updating member: " . mysqli_error($link);
            }
        }
    }
    header("Location: dashboard_staff.php");
    exit;
}

// Delete Member
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_member'])) {
    $id = (int) $_POST['member_id'];
    if (mysqli_query($link, "DELETE FROM users WHERE id = $id AND role='member'")) {
        $_SESSION['msg'] = "Member removed successfully.";
    } else {
        $_SESSION['msg'] = "Error removing member.";
    }
    header("Location: dashboard_staff.php");
    exit;
}

// Fetch all members
$members_res = mysqli_query($link, "SELECT * FROM users WHERE role = 'member' ORDER BY created_at DESC");



// --- INVENTORY MANAGEMENT ---

// Handle Add Item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_inventory'])) {
    $name = mysqli_real_escape_string($link, $_POST['item_name']);
    $qty = (int) $_POST['quantity'];
    $status = mysqli_real_escape_string($link, $_POST['status']);
    $last_m = mysqli_real_escape_string($link, $_POST['last_maintenance']);
    $next_s = mysqli_real_escape_string($link, $_POST['next_service']);

    $sql = "INSERT INTO inventory (item_name, quantity, status, last_maintenance, next_service) VALUES ('$name', $qty, '$status', '$last_m', '$next_s')";
    if (mysqli_query($link, $sql)) {
        $_SESSION['msg'] = "Item added successfully!";
    } else {
        $_SESSION['msg'] = "Error adding item.";
    }
    header("Location: dashboard_staff.php");
    exit;
}

// Handle Update Item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_inventory'])) {
    $id = (int) $_POST['item_id'];
    $name = mysqli_real_escape_string($link, $_POST['item_name']);
    $qty = (int) $_POST['quantity'];
    $status = mysqli_real_escape_string($link, $_POST['status']);
    $last_m = mysqli_real_escape_string($link, $_POST['last_maintenance']);
    $next_s = mysqli_real_escape_string($link, $_POST['next_service']);

    $sql = "UPDATE inventory SET item_name='$name', quantity=$qty, status='$status', last_maintenance='$last_m', next_service='$next_s' WHERE id=$id";
    if (mysqli_query($link, $sql)) {
        $_SESSION['msg'] = "Item updated successfully!";
    } else {
        $_SESSION['msg'] = "Error updating item.";
    }
    header("Location: dashboard_staff.php");
    exit;
}

// Handle Delete Item
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_inventory'])) {
    $id = (int) $_POST['item_id'];
    if (mysqli_query($link, "DELETE FROM inventory WHERE id=$id")) {
        $_SESSION['msg'] = "Item deleted successfully!";
    } else {
        $_SESSION['msg'] = "Error deleting item.";
    }
    header("Location: dashboard_staff.php");
    exit;
}

// Fetch Inventory
$inventory_res = mysqli_query($link, "SELECT * FROM inventory ORDER BY created_at ASC");
$inventory_count = mysqli_num_rows($inventory_res);

// Fetch All Payments
$payments_sql = "SELECT t.*, u.full_name, u.email as user_email 
                FROM transactions t 
                JOIN users u ON t.user_id = u.id 
                ORDER BY t.created_at DESC";
$payments_res = mysqli_query($link, $payments_sql);

// Group by User for cleaner display
$grouped_payments = [];
while ($row = mysqli_fetch_assoc($payments_res)) {
    $uid = $row['user_id'];
    if (!isset($grouped_payments[$uid])) {
        $grouped_payments[$uid] = [
            'user' => $row,
            'history' => []
        ];
    }
    $grouped_payments[$uid]['history'][] = $row;
}

// --- STAFF ATTENDANCE LOGIC ---
// 1. Handle Self-Attendance
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_staff_attendance'])) {
    $date = date('Y-m-d');
    $check = mysqli_query($link, "SELECT id FROM attendance WHERE user_id = $user_id AND date = '$date'");
    if (mysqli_num_rows($check) == 0) {
        if (mysqli_query($link, "INSERT INTO attendance (user_id, date, status) VALUES ($user_id, '$date', 'present')")) {
            $_SESSION['msg'] = "Attendance marked successfully!";
        }
    } else {
        $_SESSION['msg'] = "Attendance already marked for today.";
    }
    header("Location: dashboard_staff.php");
    exit;
}

// 2. Fetch Own History
$staff_att_res = mysqli_query($link, "SELECT date FROM attendance WHERE user_id = $user_id ORDER BY date DESC");
$staff_dates = [];
while ($r = mysqli_fetch_assoc($staff_att_res))
    $staff_dates[] = $r['date'];
$is_staff_present = in_array(date('Y-m-d'), $staff_dates);

// Calculate Monthly Stats for Staff Reports Modal (Grouped by Year)
$staff_attendance_by_year = [];
$current_sys_year = (int) date('Y');
$staff_join_year = (int) date('Y', strtotime($user_data['created_at']));
// Start from the year staff joined
$staff_min_year = $staff_join_year;

// Also check attendance history for even earlier dates (edge case)
if (!empty($staff_dates)) {
    foreach ($staff_dates as $ad) {
        $y_check = (int) date('Y', strtotime($ad));
        if ($y_check < $staff_min_year)
            $staff_min_year = $y_check;
    }
}

// Generate data structure: [Year][Month] => {name, count}
for ($y = $current_sys_year; $y >= $staff_min_year; $y--) {
    $staff_attendance_by_year[$y] = [];
    for ($m = 1; $m <= 12; $m++) {
        $prefix = sprintf('%04d-%02d', $y, $m);
        $cnt = 0;
        foreach ($staff_dates as $d) {
            if (strpos($d, $prefix) === 0)
                $cnt++;
        }
        $staff_attendance_by_year[$y][$m] = [
            'name' => date('F', mktime(0, 0, 0, $m, 10)),
            'short' => date('M', mktime(0, 0, 0, $m, 10)),
            'count' => $cnt
        ];
    }
}

// 3. Member Daily Stats
$today_date = date('Y-m-d');
$mem_att_query = mysqli_query($link, "SELECT COUNT(*) as cnt FROM attendance a JOIN users u ON a.user_id = u.id WHERE u.role='member' AND a.date='$today_date'");
$mem_present_count = $mem_att_query->fetch_assoc()['cnt'];

$total_mem_query = mysqli_query($link, "SELECT COUNT(*) as cnt FROM users WHERE role='member'");
$total_mem_count = $total_mem_query->fetch_assoc()['cnt'];
$mem_absent_count = $total_mem_count - $mem_present_count;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title>Staff Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Roboto:wght@400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Flatpickr for Date Selection -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://npmcdn.com/flatpickr/dist/themes/dark.css">
    <style>
        :root {
            --primary-color: #ceff00;
            --secondary-color: #1a1a2e;
            --bg-dark: #0f0f1a;
            --card-bg: rgba(255, 255, 255, 0.05);
            --text-gray: #aaa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-dark);
            color: #fff;
            font-family: 'Roboto', sans-serif;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles (Same as Member) */
        .sidebar {
            width: 260px;
            background: var(--secondary-color);
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
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

        .header-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-left: auto;
        }

        .header-profile span {
            font-family: 'Oswald', sans-serif;
            font-size: 1.2rem;
            color: #fff;
            text-transform: lowercase;
            letter-spacing: 1px;
        }

        .header-profile-img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 2px solid var(--primary-color);
            object-fit: cover;
            box-shadow: 0 0 10px rgba(161, 212, 35, 0.2);
        }

        .welcome-text h1 {
            font-family: 'Oswald', sans-serif;
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .welcome-text p {
            color: var(--text-gray);
        }

        /* Cards and Tables */
        .dashboard-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 25px;
        }

        .dashboard-card h3 {
            font-family: 'Oswald', sans-serif;
            margin-bottom: 20px;
            color: var(--primary-color);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .data-table th,
        .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .data-table th {
            color: var(--text-gray);
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .btn-sm {
            padding: 5px 12px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 0.8rem;
            margin-right: 5px;
        }

        .btn-edit {
            background: rgba(206, 255, 0, 0.1);
            color: var(--primary-color);
        }

        .btn-delete {
            background: rgba(255, 77, 77, 0.1);
            color: #ff4d4d;
        }

        .btn-add {
            background: var(--primary-color);
            color: var(--secondary-color);
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            float: right;
            margin-top: -50px;
        }

        .btn-action {
            background: var(--primary-color);
            color: var(--secondary-color);
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            width: 100%;
            font-family: 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: 0.3s;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(206, 255, 0, 0.3);
        }

        /* Profile Styles */
        .profile-img-container {
            width: 150px;
            height: 150px;
            margin: 0 auto 30px;
            position: relative;
        }

        #profile-preview {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
            box-shadow: 0 0 20px rgba(206, 255, 0, 0.2);
        }

        .upload-overlay {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: var(--primary-color);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 3px solid #1a1a2e;
            color: #1a1a2e;
            transition: 0.3s;
        }

        .upload-overlay:hover {
            transform: scale(1.1);
        }

        /* Forms */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--text-gray);
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #333;
            color: #fff;
        }

        select.form-control {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23ceff00'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.2em;
            padding-right: 2.5rem;
            cursor: pointer;
            border-color: var(--primary-color) !important;
        }

        select.form-control option {
            background-color: var(--secondary-color);
            color: #fff;
            padding: 10px;
        }

        .dashboard-section {
            display: none;
        }

        .dashboard-section.active {
            display: block;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .badge-success {
            background: rgba(0, 255, 0, 0.1);
            color: #00ff00;
        }

        .badge-warning {
            background: rgba(255, 255, 0, 0.1);
            color: #ffff00;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: var(--secondary-color);
            padding: 30px;
            border-radius: 15px;
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .pass-wrapper {
            position: relative;
        }

        .pass-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-gray);
            transition: 0.3s;
        }

        .pass-toggle:hover {
            color: var(--primary-color);
        }

        .edit-member-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
            margin: 0 auto 15px;
            display: block;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .pass-wrapper {
            position: relative;
        }

        .pass-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-gray);
            transition: 0.3s;
        }

        .pass-toggle:hover {
            color: var(--primary-color);
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <a href="index.php" class="logo"><i class="fa-solid fa-dumbbell"></i>GymFit</a>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fa-solid fa-house"></i> Back to Website</a></li>
            <li style="border-bottom: 1px solid rgba(255,255,255,0.05); margin-bottom: 10px; padding-bottom: 10px;">
            </li>
            <li><a href="#" class="active" onclick="showSection('attendance')"><i
                        class="fa-solid fa-calendar-check"></i> Attendance
                </a></li>
            <li><a href="#" onclick="showSection('appointments')"><i class="fa-solid fa-calendar-days"></i> Appointments
                    <span class="badge badge-warning"
                        style="margin-left:auto; display:<?php echo (mysqli_num_rows($staff_appts) > 0) ? 'inline-block' : 'none'; ?>">!</span></a>
            </li>
            <li><a href="#" onclick="showSection('members')"><i class="fa-solid fa-users"></i>
                    Members</a></li>
            <li><a href="#" onclick="showSection('reports')"><i class="fa-solid fa-users-rectangle"></i> Member
                    Attendance Reports</a></li>

            <li><a href="#" onclick="showSection('content')"><i class="fa-solid fa-cloud-arrow-up"></i> Upload
                    Content</a></li>
            <li><a href="#" onclick="showSection('inventory')"><i class="fa-solid fa-boxes-stacked"></i> Inventory</a>
            </li>
            <li><a href="#" onclick="showSection('payments')"><i class="fa-solid fa-file-invoice-dollar"></i>
                    Payments</a></li>


            <li><a href="#" onclick="showSection('profile')"><i class="fa-solid fa-user-gear"></i> Profile Settings</a>
            </li>
        </ul>
        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header-top">
            <div class="header-profile">
                <span><?php echo htmlspecialchars(strtolower($_SESSION['full_name'])); ?></span>
                <img src="<?php echo $profile_image ? $profile_image : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION["full_name"]) . '&background=ceff00&color=1a1a2e'; ?>"
                    class="header-profile-img" alt="Profile">
            </div>
        </div>

        <div id="inventory" class="dashboard-section">
            <div class="dashboard-card">
                <h3>Gym Inventory & Equipment</h3>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div style="position: relative; width: 300px;">
                        <i class="fa-solid fa-magnifying-glass"
                            style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-gray); font-size: 0.9rem;"></i>
                        <input type="text" id="inventory-search" onkeyup="searchInventory()" placeholder="Search items"
                            style="width: 100%; padding: 10px 15px 10px 40px; border-radius: 30px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff; font-size: 0.9rem; outline: none; transition: 0.3s;">
                    </div>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span
                            style="background: #000; color: #fff; padding: 10px 20px; border-radius: 5px; font-weight: bold; font-size: 0.9rem; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">Total
                            Items: <?php echo $inventory_count; ?></span>
                        <a href="#" class="btn-add" onclick="openAddInventoryModal()" style="margin: 0; float: none;">+
                            Add Item</a>
                    </div>
                </div>

                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Last Maintenance</th>
                                <th>Next Service</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($inventory_res) > 0): ?>
                                <?php while ($item = mysqli_fetch_assoc($inventory_res)):
                                    $status_color = ($item['status'] == 'Functional' || $item['status'] == 'Good') ? '#ceff00' : '#ff4d4d';
                                    ?>
                                    <tr>
                                        <td style="font-weight: bold;">
                                            <?php echo htmlspecialchars($item['item_name']); ?>
                                        </td>
                                        <td>
                                            <?php echo $item['quantity']; ?>
                                        </td>
                                        <td style="color: <?php echo $status_color; ?>">
                                            <?php echo htmlspecialchars($item['status']); ?>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($item['last_maintenance'])); ?>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($item['next_service'])); ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <button class="btn-sm btn-edit"
                                                    onclick='openEditInventoryModal(<?php echo json_encode($item); ?>)'>Edit</button>

                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-gray);">No items found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Appointments Section -->
        <div id="appointments" class="dashboard-section">
            <div class="dashboard-card">
                <h3>Appointment Requests</h3>

                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($staff_appts) > 0): ?>
                                <?php while ($appt = mysqli_fetch_assoc($staff_appts)):
                                    $status_color = match ($appt['status']) {
                                        'approved' => '#00ff85',
                                        'rejected' => '#ff4d4d',
                                        default => '#ffc107'
                                    };
                                    ?>
                                    <tr>
                                        <td style="display:flex; align-items:center; gap:10px; padding: 15px;">
                                            <img src="<?php echo $appt['profile_image'] ? $appt['profile_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($appt['member_name']); ?>"
                                                style="width:35px; height:35px; border-radius:50%; object-fit:cover;">
                                            <div>
                                                <strong><?php echo htmlspecialchars($appt['member_name']); ?></strong><br>
                                                <small
                                                    style="color:var(--text-gray);"><?php echo $appt['member_email']; ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($appt['booking_date'])); ?> <br>
                                            <small
                                                style="color:var(--primary-color);"><?php echo $appt['booking_time']; ?></small>
                                        </td>
                                        <td>
                                            <span
                                                style="color:<?php echo $status_color; ?>; font-weight:bold; text-transform:uppercase; font-size:0.8rem; padding: 4px 8px; background: rgba(255,255,255,0.05); border-radius: 4px;">
                                                <?php echo $appt['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($appt['status'] == 'pending'): ?>
                                                <div style="display:flex; gap:10px;">
                                                    <button class="btn-sm" style="background:rgba(0,255,133,0.2); color:#00ff85;"
                                                        onclick="openApptModal(<?php echo $appt['id']; ?>, 'approved')">
                                                        <i class="fa-solid fa-check"></i> Accept
                                                    </button>
                                                    <button class="btn-sm" style="background:rgba(255,77,77,0.2); color:#ff4d4d;"
                                                        onclick="openApptModal(<?php echo $appt['id']; ?>, 'rejected')">
                                                        <i class="fa-solid fa-xmark"></i> Reject
                                                    </button>
                                                    <form method="POST"
                                                        onsubmit="return confirm('Delete this appointment request?');"
                                                        style="margin:0;">
                                                        <input type="hidden" name="delete_appointment" value="1">
                                                        <input type="hidden" name="appt_id" value="<?php echo $appt['id']; ?>">
                                                        <button type="submit" class="btn-sm"
                                                            style="background:rgba(255, 255, 255, 0.1); color:#ccc;" title="Delete">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                "<?php echo substr(htmlspecialchars($appt['staff_message']), 0, 20) . '...'; ?>"
                                                </small>
                                                <form method="POST" onsubmit="return confirm('Delete this record?');"
                                                    style="display:inline-block; margin-left:10px;">
                                                    <input type="hidden" name="delete_appointment" value="1">
                                                    <input type="hidden" name="appt_id" value="<?php echo $appt['id']; ?>">
                                                    <button type="submit" class="btn-sm"
                                                        style="background:rgba(255, 77, 77, 0.1); color:#ff4d4d; padding:2px 8px; font-size:0.7rem;"
                                                        title="Delete">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align:center; color:var(--text-gray);">No appointments
                                        found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Appointment Action Modal -->
        <div id="appt-modal" class="modal">
            <div class="modal-content"
                style="background: #1a1a2e; border-radius: 20px; padding: 30px; box-shadow: 0 25px 50px rgba(0,0,0,0.5); max-width: 480px; border: 1px solid rgba(255,255,255,0.1);">
                <h3 id="appt-modal-title"
                    style="font-family: 'Oswald', sans-serif; text-align: center; font-size: 1.8rem; margin-bottom: 5px; color: #fff; letter-spacing: 1px;">
                    Respond to Request
                </h3>
                <p style="text-align: center; color: var(--text-gray); font-size: 0.9rem; margin-bottom: 25px;">
                    Send a message to the member regarding their request.
                </p>

                <form method="POST">
                    <input type="hidden" name="update_appointment" value="1">
                    <input type="hidden" name="appt_id" id="appt-id-input">
                    <input type="hidden" name="status" id="appt-status-input">

                    <div class="form-group" style="margin-bottom: 25px;">
                        <label style="color: #fff; font-weight: 500; font-size: 0.9rem; margin-bottom: 8px;">Message /
                            Reply (Optional)</label>
                        <textarea name="reply" class="form-control" rows="4" placeholder="Type your message here..."
                            style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 15px; color: #fff; font-size: 0.95rem; resize: none; transition: 0.3s; width: 100%; outline: none; font-family: inherit;"
                            onfocus="this.style.borderColor='var(--primary-color)'; this.style.background='rgba(255,255,255,0.05)';"
                            onblur="this.style.borderColor='rgba(255,255,255,0.1)'; this.style.background='rgba(255,255,255,0.03)';"></textarea>
                    </div>

                    <div style="display: flex; gap: 15px;">
                        <button type="button" class="btn-action"
                            onclick="document.getElementById('appt-modal').style.display='none'"
                            style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1); border-radius: 30px; flex: 1;">
                            Cancel
                        </button>
                        <button type="submit" class="btn-action" id="appt-submit-btn"
                            style="border-radius: 30px; flex: 1.5; box-shadow: 0 4px 15px rgba(0,0,0,0.3);">
                            Confirm
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openApptModal(id, status) {
                document.getElementById('appt-id-input').value = id;
                document.getElementById('appt-status-input').value = status;

                const title = status === 'approved' ? 'Accept Appointment' : 'Reject Appointment';
                const btnText = status === 'approved' ? 'Confirm Acceptance' : 'Confirm Rejection';
                const btnBg = status === 'approved' ? 'var(--primary-color)' : '#ff4d4d';
                const btnColor = status === 'approved' ? '#000' : '#fff';

                document.getElementById('appt-modal-title').innerText = title;
                const btn = document.getElementById('appt-submit-btn');
                btn.innerText = btnText;
                btn.style.background = btnBg;
                btn.style.color = btnColor;

                document.getElementById('appt-modal').style.display = 'flex';
            }
        </script>

        <div id="members" class="dashboard-section">

            <div class="dashboard-card">
                <h3>Member Directory</h3>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div style="position: relative; width: 300px;">
                        <i class="fa-solid fa-magnifying-glass"
                            style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-gray); font-size: 0.9rem;"></i>
                        <input type="text" id="member-search" onkeyup="searchMembers()" placeholder="Search members"
                            style="width: 100%; padding: 10px 15px 10px 40px; border-radius: 30px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff; font-size: 0.9rem; outline: none; transition: 0.3s;">
                    </div>
                    <a href="#" class="btn-add" onclick="openAddMemberModal()" style="margin: 0;">+ Add New Member</a>
                </div>

                <!-- Success/Error Message for Members -->
                <?php if (!empty($msg)): ?>
                        <p id="staff-msg"
                            style='color: <?php echo (strpos($msg, "Error") !== false ? "#ff4d4d" : "var(--primary-color)"); ?>; margin-bottom: 20px; padding: 10px; background: rgba(255,255,255,0.03); border-radius: 8px; border-left: 4px solid <?php echo (strpos($msg, "Error") !== false ? "#ff4d4d" : "var(--primary-color)"); ?>;'>
                            <?php echo $msg; ?>
                        </p>
                        <script>
                            setTimeout(() => {
                                const msgBox = document.getElementById("staff-msg");
                                if (msgBox) {
                                    msgBox.style.transition = "opacity 0.5s";
                                    msgBox.style.opacity = "0";
                                    setTimeout(() => msgBox.remove(), 500);
                                }
                            }, 4000);
                        </script>
                <?php endif; ?>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Join Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($members_res) > 0): ?>
                                <?php while ($member = mysqli_fetch_assoc($members_res)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($member['created_at'])); ?></td>
                                            <td>
                                                <div style="display: flex; gap: 5px;">
                                                    <button class="btn-sm btn-edit"
                                                        onclick='openEditMemberModal(<?php echo json_encode($member); ?>)'>Edit</button>
                                                    <form method="POST" onsubmit="return confirm('Remove this member permanentely?');"
                                                        style="margin:0;">
                                                        <input type="hidden" name="delete_member" value="1">
                                                        <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                                        <button type="submit" class="btn-sm btn-delete">Remove</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                <?php endwhile; ?>
                        <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: var(--text-gray);">No members found.</td>
                                </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Member Modal -->
        <div id="add-member-modal" class="modal">
            <div class="modal-content">
                <h3 style="margin-bottom: 25px; text-align: center;">Add New Member</h3>
                <form method="POST" autocomplete="off">
                    <input type="hidden" name="add_member" value="1">
                    <!-- Dummy field to trick browser autofill -->
                    <input type="text" style="display:none">
                    <input type="password" style="display:none">

                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control" required
                            placeholder="Member's full name" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" required placeholder="member@gmail.com"
                            autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <div class="pass-wrapper">
                            <input type="password" name="password" id="add-pass" class="form-control" required
                                placeholder="Minimum 6 characters" autocomplete="new-password" minlength="6">
                            <i class="fa-solid fa-eye pass-toggle" onclick="togglePass('add-pass', this)"></i>
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 30px;">
                        <button type="submit" class="btn-add" style="float: none; margin: 0; flex-grow: 1;">Create
                            Account</button>
                        <button type="button" class="btn-sm" style="background: #333; color: #fff;"
                            onclick="closeModal('add-member-modal')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Member Modal -->
        <div id="edit-member-modal" class="modal">
            <div class="modal-content">
                <h3 style="margin-bottom: 20px; text-align: center;">Edit Member Details</h3>
                <img id="edit-member-preview" src="" alt="Profile" class="edit-member-img">
                <form method="POST" autocomplete="off">
                    <input type="hidden" name="update_member" value="1">
                    <input type="hidden" name="member_id" id="edit-member-id">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" id="edit-member-name" class="form-control" required
                            autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" id="edit-member-email" class="form-control" required
                            autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Reset Password (leave blank to keep current)</label>
                        <div class="pass-wrapper">
                            <input type="password" name="password" id="edit-pass" class="form-control"
                                placeholder="New password" autocomplete="new-password" minlength="6">
                            <i class="fa-solid fa-eye pass-toggle" onclick="togglePass('edit-pass', this)"></i>
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 30px;">
                        <button type="submit" class="btn-add" style="float: none; margin: 0; flex-grow: 1;">Save
                            Changes</button>
                        <button type="button" class="btn-sm" style="background: #333; color: #fff;"
                            onclick="closeModal('edit-member-modal')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>



        <!-- Content Management -->
        <div id="content" class="dashboard-section">
            <div class="dashboard-card">
                <h3>Upload Daily Content</h3>
                <!-- Message Display -->
                <?php if (!empty($msg)): ?>
                        <p id="msg-box" style='color: #ceff00; margin-bottom: 15px;'><?php echo $msg; ?></p>
                        <script>
                            setTimeout(() => {
                                const box = document.getElementById('msg-box');
                                if (box) { box.style.transition = 'opacity 0.5s'; box.style.opacity = '0'; setTimeout(() => box.remove(), 500); }
                            }, 3000);
                        </script>
                <?php endif; ?>

                <form method="POST" action="dashboard_staff.php" id="content-form">
                    <input type="hidden" name="add_content" id="action_input" value="1">
                    <input type="hidden" name="content_id" id="content_id_input">
                    <div class="form-group">
                        <label>Content Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Advanced Leg Workout"
                            required>
                    </div>
                    <div class="form-group">
                        <label>Scheduled Date</label>
                        <input type="text" id="schedule_date" name="schedule_date" class="form-control"
                            placeholder="Select Date..." required>
                    </div>
                    <div class="form-group">
                        <label>Video URL (YouTube Embed or Link)</label>
                        <input type="text" name="video_url" class="form-control"
                            placeholder="https://www.youtube.com/embed/..." required>
                    </div>
                    <div class="form-group">
                        <label>Description / Instructor Tips</label>
                        <textarea name="description" class="form-control"
                            placeholder="Brief details about the workout..." rows="3"></textarea>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <button type="submit" id="submit-btn"
                            style="background: var(--primary-color); border: none; padding: 12px 25px; border-radius: 5px; font-weight: bold; cursor: pointer; color: var(--secondary-color);">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Upload to Schedule
                        </button>
                        <button type="button" id="cancel-btn" onclick="resetForm()"
                            style="display: none; background: #333; border: 1px solid #555; padding: 12px 25px; border-radius: 5px; color: #fff; cursor: pointer;">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <!-- List of Scheduled Content -->
            <div class="dashboard-card">
                <h3>Scheduled Content</h3>
                <?php
                // Fetch and Group Content by Month
                $content_query = "SELECT * FROM daily_workouts ORDER BY date DESC";
                $content_res = mysqli_query($link, $content_query);

                $grouped_content = [];
                while ($row = mysqli_fetch_assoc($content_res)) {
                    $month_key = date('F Y', strtotime($row['date']));
                    if (!isset($grouped_content[$month_key])) {
                        $grouped_content[$month_key] = [];
                    }
                    $grouped_content[$month_key][] = $row;
                }

                if (count($grouped_content) > 0):
                    $is_first = true;
                    foreach ($grouped_content as $month => $items):
                        $display_style = 'none';
                        $icon_class = 'fa-chevron-down';
                        ?>

                                <div class="month-group"
                                    style="margin-bottom: 15px; border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; overflow: hidden;">
                                    <div class="month-header" onclick="toggleMonth(this)"
                                        style="background: rgba(255,255,255,0.03); padding: 15px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-weight: bold;">
                                        <span><?php echo $month; ?> <span
                                                style="font-size: 0.8rem; color: var(--text-gray); margin-left: 10px; font-weight: normal;">(<?php echo count($items); ?>
                                                items)</span></span>
                                        <i class="fa-solid <?php echo $icon_class; ?>"
                                            style="font-size: 0.9rem; color: var(--text-gray);"></i>
                                    </div>
                                    <div class="month-content" style="display: <?php echo $display_style; ?>;">
                                        <table class="data-table" style="margin-top: 0;">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Title</th>
                                                    <th>Video Link</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($items as $row): ?>
                                                        <tr>
                                                            <td style="color: var(--primary-color); font-weight: bold; width: 15%;">
                                                                <?php echo date('D d', strtotime($row['date'])); ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                                                            <td>
                                                                <a href="<?php echo htmlspecialchars($row['video_url']); ?>" target="_blank"
                                                                    style="color: var(--text-gray); text-decoration: none; font-size: 0.9rem;">
                                                                    <i class="fa-brands fa-youtube" style="color: #ff0000;"></i> View
                                                                </a>
                                                            </td>
                                                            <td>
                                                                <div style="display: flex; gap: 5px; align-items: center;">
                                                                    <button type="button" class="btn-sm btn-edit"
                                                                        onclick='editContent(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>)'>
                                                                        Edit
                                                                    </button>
                                                                    <form method="POST" action="dashboard_staff.php"
                                                                        onsubmit="return confirm('Are you sure you want to remove this video?');"
                                                                        style="margin:0;">
                                                                        <input type="hidden" name="delete_content" value="1">
                                                                        <input type="hidden" name="content_id"
                                                                            value="<?php echo $row['id']; ?>">
                                                                        <button type="submit" class="btn-sm btn-delete">
                                                                            <i class="fa-solid fa-trash"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <?php
                                $is_first = false;
                    endforeach;
                else: ?>
                        <p style="color: var(--text-gray); font-style: italic;">No content scheduled yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payments Section -->
        <div id="payments" class="dashboard-section">
            <div class="dashboard-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0;">Member Payment History</h3>
                    <div style="position: relative; width: 300px;">
                        <i class="fa-solid fa-magnifying-glass"
                            style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-gray); font-size: 0.9rem;"></i>
                        <input type="text" id="payment-search" onkeyup="searchPayments()" placeholder="Search payments"
                            style="width: 100%; padding: 10px 15px 10px 40px; border-radius: 30px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff; font-size: 0.9rem; outline: none; transition: 0.3s;">
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Latest Plan</th>
                                <th>Last Payment</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($grouped_payments) > 0): ?>
                                    <?php foreach ($grouped_payments as $uid => $data):
                                        $latest = $data['history'][0]; // First item is latest
                                        $count = count($data['history']);
                                        ?>
                                            <!-- Main User Row -->
                                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                                <td>
                                                    <div style="display: flex; flex-direction: column;">
                                                        <span
                                                            style="font-weight: bold; font-size: 1rem; color: #fff;"><?php echo htmlspecialchars($data['user']['full_name']); ?></span>
                                                        <small
                                                            style="color: var(--text-gray); font-size: 0.8rem;"><?php echo htmlspecialchars($data['user']['user_email']); ?></small>
                                                    </div>
                                                </td>
                                                <td><span
                                                        style="color: var(--primary-color);"><?php echo htmlspecialchars($latest['plan_name']); ?></span>
                                                </td>
                                                <td>
                                                    <?php echo date('M d, Y', strtotime($latest['created_at'])); ?>
                                                    <span
                                                        class="badge <?php echo $latest['status'] == 'completed' ? 'badge-success' : ($latest['status'] == 'pending' ? 'badge-warning' : ''); ?>"
                                                        style="margin-left: 5px; font-size: 0.7rem;">
                                                        <?php echo ucfirst($latest['status']); ?>
                                                    </span>
                                                </td>
                                                <td style="text-align: right;">
                                                    <button onclick="toggleHistory(<?php echo $uid; ?>)" class="btn-sm"
                                                        style="background: rgba(255, 255, 255, 0.1); color: #fff; display: inline-flex; align-items: center; gap: 5px; cursor: pointer; border: 1px solid rgba(255,255,255,0.1);">
                                                        <i class="fa-solid fa-clock-rotate-left"></i> History (<?php echo $count; ?>) <i
                                                            class="fa-solid fa-chevron-down" id="icon-<?php echo $uid; ?>"></i>
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Hidden History Row -->
                                            <tr id="history-<?php echo $uid; ?>" style="display: none; background: rgba(0,0,0,0.15);">
                                                <td colspan="4" style="padding: 0;">
                                                    <div style="padding: 15px 20px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                                                        <div style="background: rgba(0,0,0,0.2); border-radius: 8px; overflow: hidden;">
                                                            <table style="width: 100%; border-collapse: collapse;">
                                                                <thead>
                                                                    <tr style="background: rgba(255,255,255,0.03);">
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: left; color: var(--text-gray); text-transform: uppercase;">
                                                                            Plan</th>
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: left; color: var(--text-gray); text-transform: uppercase;">
                                                                            Amount</th>
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: left; color: var(--text-gray); text-transform: uppercase;">
                                                                            Method</th>
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: left; color: var(--text-gray); text-transform: uppercase;">
                                                                            Date</th>
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: left; color: var(--text-gray); text-transform: uppercase;">
                                                                            Status</th>
                                                                        <th
                                                                            style="font-size: 0.75rem; padding: 10px; text-align: right; color: var(--text-gray); text-transform: uppercase;">
                                                                            Invoice</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($data['history'] as $payment): ?>
                                                                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                                                                                <td style="padding: 10px; font-size: 0.9rem;">
                                                                                    <?php echo htmlspecialchars($payment['plan_name']); ?>
                                                                                </td>
                                                                                <td
                                                                                    style="padding: 10px; font-size: 0.9rem; color: var(--primary-color);">
                                                                                    ₹<?php echo number_format($payment['amount'], 2); ?></td>
                                                                                <td
                                                                                    style="padding: 10px; font-size: 0.9rem; text-transform: capitalize;">
                                                                                    <?php echo htmlspecialchars($payment['payment_method']); ?>
                                                                                </td>
                                                                                <td style="padding: 10px; font-size: 0.9rem; color: #ddd;">
                                                                                    <?php echo date('M d, Y', strtotime($payment['created_at'])); ?>
                                                                                </td>
                                                                                <td style="padding: 10px;">
                                                                                    <span
                                                                                        class="badge <?php echo $payment['status'] == 'completed' ? 'badge-success' : ($payment['status'] == 'pending' ? 'badge-warning' : ''); ?>"
                                                                                        style="font-size: 0.65rem;">
                                                                                        <?php echo ucfirst($payment['status']); ?>
                                                                                    </span>
                                                                                </td>
                                                                                <td style="padding: 10px; text-align: right;">
                                                                                    <a href="invoice.php?tid=<?php echo $payment['id']; ?>"
                                                                                        target="_blank"
                                                                                        style="color: var(--text-gray); font-size: 1rem;"
                                                                                        title="Download Invoice">
                                                                                        <i class="fa-solid fa-file-pdf"></i>
                                                                                    </a>
                                                                                </td>
                                                                            </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                    <?php endforeach; ?>
                            <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; color: var(--text-gray); padding: 30px;">
                                            <i class="fa-solid fa-receipt"
                                                style="font-size: 2rem; display: block; margin-bottom: 10px; opacity: 0.3;"></i>
                                            No payment records found.
                                        </td>
                                    </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Attendance Section -->
        <div id="attendance" class="dashboard-section active">
            <h2 style="font-family: 'Oswald', sans-serif; margin-bottom: 20px;">Attendance Management</h2>

            <div class="dashboard-grid">
                <!-- Staff Self Attendance -->
                <div class="dashboard-card" style="padding: 40px;">
                    <h3><i class="fa-solid fa-user-check"></i> My Attendance</h3>
                    <div style="text-align: center; padding: 30px 0;">
                        <h2 style="font-family:'Oswald'; margin-bottom:10px;">Daily Attendance</h2>
                        <p style="color: var(--text-gray); margin-bottom: 20px;">Mark your daily attendance here.</p>
                        <form method="POST" style="display:flex; justify-content:center;">
                            <input type="hidden" name="mark_staff_attendance" value="1">
                            <?php if ($is_staff_present): ?>
                                    <button type="button" class="btn-action"
                                        style="width: auto; padding: 15px 40px; background:rgba(255,255,255,0.05); cursor:not-allowed; border: 1px solid var(--primary-color); color: var(--primary-color);">
                                        <i class="fa-solid fa-check-double"></i> Checked In Today
                                    </button>
                            <?php else: ?>
                                    <button type="submit" class="btn-action"
                                        style="width: auto; padding: 15px 50px; font-size: 1.1rem; transform: scale(1.05);">
                                        <i class="fa-solid fa-hand-point-up"></i> Check In Now
                                    </button>
                            <?php endif; ?>
                        </form>

                        <div style="margin-top: 50px; padding-top: 30px; border-top: 1px solid rgba(255,255,255,0.05);">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <h4
                                    style="font-size: 1rem; color: #fff; margin: 0; font-family: 'Oswald', sans-serif; letter-spacing: 1px;">
                                    <i class="fa-solid fa-calendar-days"
                                        style="color: var(--primary-color); margin-right: 10px;"></i>
                                    ATTENDANCE HISTORY (<?php echo date('F Y'); ?>)
                                </h4>
                                <button onclick="document.getElementById('staff-reports-modal').style.display='flex'"
                                    style="background:none; color:var(--primary-color); cursor:pointer; font-size:0.8rem; border:1px solid var(--primary-color); padding:6px 15px; border-radius:4px; transition:0.3s; background:rgba(206, 255, 0, 0.05);">
                                    <i class="fa-solid fa-folder-open"></i> View Attendance
                                </button>
                            </div>
                            <div
                                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(45px, 1fr)); gap: 15px; justify-content: start;">
                                <?php
                                $s_month = date('m');
                                $s_year = date('Y');
                                $s_days = date('t');
                                for ($d = 1; $d <= $s_days; $d++):
                                    $s_date = sprintf('%04d-%02d-%02d', $s_year, $s_month, $d);
                                    $s_is_pres = in_array($s_date, $staff_dates);
                                    $s_is_fut = strtotime($s_date) > time();
                                    $s_bg = $s_is_pres ? 'var(--primary-color)' : 'rgba(255,255,255,0.05)';
                                    $s_col = $s_is_pres ? '#000' : '#fff';
                                    $s_op = $s_is_fut ? '0.2' : '1';
                                    $s_border = $s_is_pres ? 'none' : '1px solid rgba(255,255,255,0.1)';
                                    ?>
                                        <div style="height:45px; display:flex; align-items:center; justify-content:center; background:<?php echo $s_bg; ?>; color:<?php echo $s_col; ?>; border-radius:10px; font-weight:bold; font-size:0.9rem; opacity:<?php echo $s_op; ?>; border: <?php echo $s_border; ?>; transition: 0.3s; cursor: pointer;"
                                            onclick="showAttendanceStatus('<?php echo date('M d, Y', strtotime($s_date)); ?>', '<?php echo $s_is_pres ? 'Present' : 'Absent'; ?>', <?php echo $s_is_fut ? 'true' : 'false'; ?>)"
                                            title="<?php echo $s_date; ?>">
                                            <?php echo $d; ?>
                                        </div>
                                <?php endfor; ?>
                            </div>
                            <div
                                style="margin-top:20px; display:flex; gap:20px; font-size:0.85rem; color:var(--text-gray);">
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
                </div>




            </div>
        </div>

        <!-- Member Reports Section -->
        <div id="reports" class="dashboard-section">
            <h2 style="font-family: 'Oswald', sans-serif; margin-bottom: 20px;">Member Attendance Reports</h2>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <div style="position: relative; width: 300px;">
                    <i class="fa-solid fa-magnifying-glass"
                        style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-gray); font-size: 0.9rem;"></i>
                    <input type="text" id="report-search" onkeyup="searchReports()" placeholder="Search members"
                        style="width: 100%; padding: 10px 15px 10px 40px; border-radius: 30px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff; font-size: 0.9rem; outline: none; transition: 0.3s;">
                </div>
            </div>

            <div style="background: rgba(255,255,255,0.05); border-radius: 8px; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: rgba(0,0,0,0.2); text-align: left;">
                            <th
                                style="padding: 15px 20px; color: var(--text-gray); font-size: 0.85rem; text-transform: uppercase;">
                                Member Name</th>
                            <th
                                style="padding: 15px 20px; color: var(--text-gray); font-size: 0.85rem; text-transform: uppercase;">
                                Email</th>
                            <th
                                style="padding: 15px 20px; text-align: right; color: var(--text-gray); font-size: 0.85rem; text-transform: uppercase;">
                                Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $report_members_res = mysqli_query($link, "SELECT * FROM users WHERE role = 'member' ORDER BY full_name ASC");

                        if (mysqli_num_rows($report_members_res) > 0):
                            while ($m = mysqli_fetch_assoc($report_members_res)):
                                ?>
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                            <td style="padding: 15px 20px; font-family: 'Oswald', sans-serif; letter-spacing: 0.5px;">
                                                <?php echo htmlspecialchars($m['full_name']); ?>
                                            </td>
                                            <td style="padding: 15px 20px; color: var(--text-gray); font-size: 0.9rem;">
                                                <?php echo htmlspecialchars($m['email']); ?>
                                            </td>
                                            <td style="padding: 15px 20px; text-align: right;">
                                                <a href="staff_view_member_report.php?uid=<?php echo $m['id']; ?>" target="_blank"
                                                    style="display: inline-block; padding: 6px 12px; font-size: 0.8rem; color: var(--primary-color); border: 1px solid var(--primary-color); border-radius: 4px; text-decoration: none; transition: 0.3s; background: rgba(206, 255, 0, 0.05);">
                                                    <i class="fa-solid fa-file-invoice"></i> View Report
                                                </a>
                                            </td>
                                        </tr>
                                        <?php
                            endwhile;
                        else:
                            ?>
                                <tr>
                                    <td colspan="3" style="padding: 30px; text-align: center; color: var(--text-gray);">
                                        No members found.
                                    </td>
                                </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>


        <!-- Profile Section -->
        <div id="profile" class="dashboard-section">
            <h2 style="font-family: 'Oswald', sans-serif; margin-bottom: 20px;">Profile Settings</h2>
            <div class="dashboard-card" style="max-width: 650px;">
                <form method="POST" enctype="multipart/form-data" id="profile-form">
                    <div class="profile-img-container">
                        <img id="profile-preview"
                            src="<?php echo $profile_image ? $profile_image : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION["full_name"]) . '&background=ceff00&color=1a1a2e'; ?>">
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
                            <i class="fa-solid fa-eye" id="toggle-password-staff"
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize Flatpickr for custom date format
        flatpickr("#schedule_date", {
            altInput: true,
            altFormat: "M D j", // Formats as: Jan Mon 5
            dateFormat: "Y-m-d", // Sends to server as: 2025-01-05
            theme: "dark",
            disableMobile: "true" // Ensures the custom look is used on mobile attributes
        });

        function showSection(sectionId) {
            document.querySelectorAll('.dashboard-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.sidebar-menu a').forEach(a => a.classList.remove('active'));
            document.getElementById(sectionId).classList.add('active');
            if (event && event.currentTarget) event.currentTarget.classList.add('active');
        }

        function editContent(data) {
            // Populate form fields
            document.querySelector('input[name="title"]').value = data.title;

            // Set date in Flatpickr
            const fp = document.querySelector("#schedule_date")._flatpickr;
            if (fp) fp.setDate(data.date);

            document.querySelector('input[name="video_url"]').value = data.video_url;
            document.querySelector('textarea[name="description"]').value = data.description;

            // Switch mode to Update
            document.getElementById('action_input').name = 'update_content';
            document.getElementById('content_id_input').value = data.id;

            // Update UI
            const btn = document.getElementById('submit-btn');
            btn.innerHTML = '<i class="fa-solid fa-rotate"></i> Update Content';
            document.getElementById('cancel-btn').style.display = 'block';

            // Scroll to form
            document.querySelector('.dashboard-card h3').scrollIntoView({ behavior: 'smooth' });
        }

        function resetForm() {
            document.getElementById('content-form').reset();
            const fp = document.querySelector("#schedule_date")._flatpickr;
            if (fp) fp.clear();

            document.getElementById('action_input').name = 'add_content';
            document.getElementById('content_id_input').value = '';

            const btn = document.getElementById('submit-btn');
            btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Upload to Schedule';
            document.getElementById('cancel-btn').style.display = 'none';
        }

        function toggleMonth(header) {
            const content = header.nextElementSibling;
            const icon = header.querySelector('i');

            if (content.style.display === 'none') {
                content.style.display = 'block';
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                content.style.display = 'none';
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }

        // --- MEMBER MODALS ---
        function openAddMemberModal() {
            document.getElementById('add-member-modal').style.display = 'flex';
        }

        function openEditMemberModal(member) {
            document.getElementById('edit-member-id').value = member.id;
            document.getElementById('edit-member-name').value = member.full_name;
            document.getElementById('edit-member-email').value = member.email;

            // Set image preview
            const img = member.profile_image ? member.profile_image : `https://ui-avatars.com/api/?name=${encodeURIComponent(member.full_name)}&background=ceff00&color=1a1a2e`;
            document.getElementById('edit-member-preview').src = img;

            document.getElementById('edit-member-modal').style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }



        // --- INVENTORY MODALS ---
        function openAddInventoryModal() {
            document.getElementById('add-inventory-modal').style.display = 'flex';
        }

        function openEditInventoryModal(item) {
            document.getElementById('edit-inventory-id').value = item.id;
            document.getElementById('edit-inventory-name').value = item.item_name;
            document.getElementById('edit-inventory-qty').value = item.quantity;
            document.getElementById('edit-inventory-status').value = item.status;

            // Set values for date inputs
            const lastPickr = document.querySelector('#edit-inventory-last')._flatpickr;
            const nextPickr = document.querySelector('#edit-inventory-next')._flatpickr;

            if (lastPickr) lastPickr.setDate(item.last_maintenance);
            if (nextPickr) nextPickr.setDate(item.next_service);

            document.getElementById('edit-inventory-modal').style.display = 'flex';
        }


        function togglePass(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Profile Image Preview
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('profile-preview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Toggle Password for Profile
        // Toggle Password for Profile
        const toggleProfilePass = document.getElementById('toggle-password-staff');
        if (toggleProfilePass) {
            toggleProfilePass.addEventListener('click', function () {
                const passInput = document.getElementById('new_password_input');
                const type = passInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }

        function toggleHistory(uid) {
            const row = document.getElementById('history-' + uid);
            const icon = document.getElementById('icon-' + uid);

            if (row.style.display === 'none' || row.style.display === '') {
                row.style.display = 'table-row';
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                row.style.display = 'none';
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }

        // Attendance Toast & Legend Logic
        function showToast(msg) {
            let toast = document.getElementById('status-toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'status-toast';
                toast.className = 'toast';
                // Add CSS for toast dynamically if it doesn't exist in stylesheet
                if (!document.getElementById('toast-style')) {
                    const style = document.createElement('style');
                    style.id = 'toast-style';
                    style.innerHTML = `
                        .toast {
                            position: fixed; top: 30px; left: 50%; transform: translateX(-50%) translateY(-100px);
                            background: rgba(26, 26, 46, 0.95); border: 1px solid var(--primary-color); color: #fff;
                            padding: 15px 30px; border-radius: 50px; font-family: 'Oswald'; font-size: 1rem;
                            letter-spacing: 1px; z-index: 9999; transition: 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                            box-shadow: 0 10px 30px rgba(206, 255, 0, 0.1); display: flex; align-items: center; gap: 12px;
                            backdrop-filter: blur(10px); opacity: 0;
                        }
                        .toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
                        .toast::before { content: '\\f058'; font-family: 'Font Awesome 6 Free'; font-weight: 900; color: var(--primary-color); }
                    `;
                    document.head.appendChild(style);
                }
                document.body.appendChild(toast);
            }
            toast.innerText = msg;
            // Force reflow
            void toast.offsetWidth;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        function showAttendanceStatus(dateStr, status, isFuture) {
            if (isFuture) return;
            showToast(`On ${dateStr}, you were ${status}.`);

            // Reset surrounding styles
            ['legend-present', 'legend-absent'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.style.background = 'transparent';
                    el.style.borderColor = 'transparent';
                    el.style.transform = 'scale(1)';
                    el.style.boxShadow = 'none';
                    el.style.color = 'var(--text-gray)';
                }
            });

            const pBox = document.getElementById('legend-present-box');
            const aBox = document.getElementById('legend-absent-box');

            if (pBox && aBox) {
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
        }
    </script>

    <!-- Add Inventory Modal -->
    <div id="add-inventory-modal" class="modal">
        <div class="modal-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="font-family:'Oswald'; color:var(--primary-color);">Add Inventory Item</h3>
                <span onclick="closeModal('add-inventory-modal')"
                    style="cursor:pointer; font-size:1.5rem; color:#fff;">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="add_inventory" value="1">
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" name="item_name" class="form-control" required placeholder="e.g. Treadmill">
                </div>
                <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" name="quantity" class="form-control" required min="1">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control" style="background:rgba(0,0,0,0.3); color:#fff;">
                        <option value="Functional">Functional</option>
                        <option value="Good">Good</option>
                        <option value="Service Due">Service Due</option>
                        <option value="Broken">Broken</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Last Maintenance</label>
                    <input type="text" name="last_maintenance" class="form-control date-picker" required
                        placeholder="Select Date">
                </div>
                <div class="form-group">
                    <label>Next Service</label>
                    <input type="text" name="next_service" class="form-control date-picker" required
                        placeholder="Select Date">
                </div>
                <button type="submit" class="btn-action">Add Item</button>
            </form>
        </div>
    </div>

    <!-- Edit Inventory Modal -->
    <div id="edit-inventory-modal" class="modal">
        <div class="modal-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="font-family:'Oswald'; color:var(--primary-color);">Edit Inventory Item</h3>
                <span onclick="closeModal('edit-inventory-modal')"
                    style="cursor:pointer; font-size:1.5rem; color:#fff;">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="update_inventory" value="1">
                <input type="hidden" name="item_id" id="edit-inventory-id">
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" name="item_name" id="edit-inventory-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" name="quantity" id="edit-inventory-qty" class="form-control" required min="1">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit-inventory-status" class="form-control"
                        style="background:rgba(0,0,0,0.3); color:#fff;">
                        <option value="Functional">Functional</option>
                        <option value="Good">Good</option>
                        <option value="Service Due">Service Due</option>
                        <option value="Broken">Broken</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Last Maintenance</label>
                    <input type="text" name="last_maintenance" id="edit-inventory-last" class="form-control date-picker"
                        required>
                </div>
                <div class="form-group">
                    <label>Next Service</label>
                    <input type="text" name="next_service" id="edit-inventory-next" class="form-control date-picker"
                        required>
                </div>
                <button type="submit" class="btn-action">Update Item</button>
            </form>
        </div>
    </div>

    <script>
        // Initialize all date pickers used in modals
        flatpickr(".date-picker", {
            dateFormat: "Y-m-d",
            theme: "dark"
        });
        function searchInventory() {
            let input = document.getElementById("inventory-search");
            let filter = input.value.toUpperCase();
            let table = document.querySelector("#inventory .data-table");
            let tr = table.getElementsByTagName("tr");

            // Loop through all table rows, and hide those who don't match the search query
            for (let i = 1; i < tr.length; i++) {
                let td = tr[i].getElementsByTagName("td")[0]; // Column 0 is Item Name
                if (td) {
                    let txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
        function searchMembers() {
            let input = document.getElementById("member-search");
            let filter = input.value.toUpperCase();
            let table = document.querySelector("#members .data-table");
            let tr = table.getElementsByTagName("tr");
            for (let i = 1; i < tr.length; i++) {
                let tdName = tr[i].getElementsByTagName("td")[0];
                let tdEmail = tr[i].getElementsByTagName("td")[1];
                if (tdName || tdEmail) {
                    let txtValueName = tdName.textContent || tdName.innerText;
                    let txtValueEmail = tdEmail.textContent || tdEmail.innerText;
                    if (txtValueName.toUpperCase().indexOf(filter) > -1 || txtValueEmail.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
        function searchReports() {
            let input = document.getElementById("report-search");
            let filter = input.value.toUpperCase();
            let table = document.querySelector("#reports table"); // Selector based on container ID
            let tr = table.getElementsByTagName("tr");
            for (let i = 1; i < tr.length; i++) {
                let tdName = tr[i].getElementsByTagName("td")[0];
                let tdEmail = tr[i].getElementsByTagName("td")[1];
                if (tdName || tdEmail) {
                    let txtValueName = tdName.textContent || tdName.innerText;
                    let txtValueEmail = tdEmail.textContent || tdEmail.innerText;
                    if (txtValueName.toUpperCase().indexOf(filter) > -1 || txtValueEmail.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
        function searchPayments() {
            let input = document.getElementById("payment-search");
            let filter = input.value.toUpperCase();
            let table = document.querySelector("#payments .data-table");
            let tr = table.getElementsByTagName("tr");
            for (let i = 1; i < tr.length; i++) {
                // Skip if it's a detail row (starts with history-)
                if (tr[i].id && tr[i].id.startsWith('history-')) continue;

                let tdUser = tr[i].getElementsByTagName("td")[0]; // Contains Name and Email
                if (tdUser) {
                    let txtValue = tdUser.textContent || tdUser.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                        // Also hide the next sibling if it's a history row
                        let nextRow = tr[i].nextElementSibling;
                        if (nextRow && nextRow.id && nextRow.id.startsWith('history-')) {
                            nextRow.style.display = "none";
                        }
                    }
                }
            }
        }
    </script>
    <!-- Staff Reports Modal -->
    <div id="staff-reports-modal" class="modal-overlay"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; justify-content:center; align-items:center;">
        <div class="modal-content"
            style="background:#1a1a2e; padding:0; border-radius:15px; width:95%; max-width:800px; max-height:85vh; overflow:hidden; border:1px solid rgba(255,255,255,0.1); display:flex; flex-direction:column; box-shadow: 0 0 40px rgba(0,0,0,0.6);">

            <!-- Header -->
            <div
                style="padding:20px 25px; border-bottom:1px solid rgba(255,255,255,0.1); display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.2);">
                <h3 style="font-family:'Oswald'; color:#fff; margin:0; font-size:1.4rem;"><i
                        class="fa-solid fa-calendar-days" style="color:var(--primary-color); margin-right:10px;"></i>
                    Attendance Reports</h3>
                <button onclick="document.getElementById('staff-reports-modal').style.display='none'"
                    style="background:none; border:none; color:var(--text-gray); font-size:1.4rem; cursor:pointer;"
                    onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-gray)'"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>

            <!-- Year Selector Dropdown -->
            <div style="padding:15px 25px; background:#141424; display:flex; align-items:center; gap:15px;">
                <label style="color:#ceff00; font-family:'Oswald'; font-size:1rem; letter-spacing:1px;">
                    <i class="fa-solid fa-calendar-alt" style="margin-right:8px;"></i>SELECT YEAR:
                </label>
                <select id="staff-year-selector" onchange="switchStaffReportYear(this.value)"
                    style="padding:10px 15px; border-radius:8px; border:1px solid rgba(206,255,0,0.3); background:rgba(0,0,0,0.3); color:#fff; font-family:'Oswald'; font-size:1rem; cursor:pointer; min-width:120px; transition:0.3s;"
                    onmouseover="this.style.borderColor='#ceff00'"
                    onmouseout="this.style.borderColor='rgba(206,255,0,0.3)'">
                    <?php
                    $is_first = true;
                    foreach ($staff_attendance_by_year as $year => $data):
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
                foreach ($staff_attendance_by_year as $year => $months): ?>
                        <div id="staff-year-content-<?php echo $year; ?>" class="staff-year-content-group"
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
                                            <a href="staff_attendance_report.php?m=<?php echo $m_num; ?>&y=<?php echo $year; ?>" target="_blank"
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
        function switchStaffReportYear(year) {
            // Hide all contents
            document.querySelectorAll('.staff-year-content-group').forEach(el => el.style.display = 'none');
            // Show selected
            document.getElementById('staff-year-content-' + year).style.display = 'grid';
        }

        // Close modal on outside click
        window.addEventListener('click', function (e) {
            const modal = document.getElementById('staff-reports-modal');
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    </script>
</body>

</html>