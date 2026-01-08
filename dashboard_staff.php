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
$stmt = mysqli_prepare($link, "SELECT full_name, email, profile_image FROM users WHERE id = ?");
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

// --- QUERY MANAGEMENT ---
require_once 'mailer.php';

// Handle Reply
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply_query'])) {
    $query_id = (int) $_POST['query_id'];
    $reply_text = mysqli_real_escape_string($link, $_POST['reply_content']);
    $user_email = mysqli_real_escape_string($link, $_POST['user_email']);
    $user_name = mysqli_real_escape_string($link, $_POST['user_name']);

    $subject = "GymFit Team: Reply to your inquiry";
    $email_body = "Hello $user_name,<br><br>Thank you for reaching out to GymFit. Here is our reply to your inquiry:<br><hr><br>$reply_text<br><br><hr>Best regards,<br>GymFit Staff Team";

    if (sendMail($user_email, $subject, $email_body)) {
        $update_sql = "UPDATE member_queries SET reply = '$reply_text', status = 'resolved' WHERE id = $query_id";
        if (mysqli_query($link, $update_sql)) {
            $_SESSION['msg'] = "Reply sent and email delivered successfully!";
        } else {
            $_SESSION['msg'] = "Reply sent but failed to update status in data.";
        }
    } else {
        $_SESSION['msg'] = "Error: Failed to send email reply.";
    }
    header("Location: dashboard_staff.php");
    exit;
}

// Handle Delete Query
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_query'])) {
    $id = (int) $_POST['query_id'];
    if (mysqli_query($link, "DELETE FROM member_queries WHERE id = $id")) {
        $_SESSION['msg'] = "Inquiry deleted successfully.";
    } else {
        $_SESSION['msg'] = "Error deleting inquiry.";
    }
    header("Location: dashboard_staff.php");
    exit;
}

// Fetch Queries
$queries_res = mysqli_query($link, "SELECT * FROM member_queries ORDER BY created_at DESC");

// Fetch All Payments
$payments_sql = "SELECT t.*, u.full_name, u.email as user_email 
                FROM transactions t 
                JOIN users u ON t.user_id = u.id 
                ORDER BY t.created_at DESC";
$payments_res = mysqli_query($link, $payments_sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            <li><a href="#" class="active" onclick="showSection('members')"><i class="fa-solid fa-users"></i>
                    Members</a></li>
            <li><a href="#" onclick="showSection('queries')"><i class="fa-solid fa-comments"></i> Member Queries</a>
            </li>
            <li><a href="#" onclick="showSection('content')"><i class="fa-solid fa-cloud-arrow-up"></i> Upload
                    Content</a></li>
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

        <div id="members" class="dashboard-section active">
            <div class="dashboard-card">
                <h3>Member Directory</h3>
                <a href="#" class="btn-add" onclick="openAddMemberModal()">+ Add New Member</a>

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

        <div id="queries" class="dashboard-section">
            <div class="dashboard-card">
                <h3>Member Inquiries</h3>
                <div class="video-list" style="display: flex; flex-direction: column; gap: 15px;">
                    <?php if (mysqli_num_rows($queries_res) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($queries_res)): ?>
                            <div
                                style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 12px; border-left: 5px solid <?php echo $row['status'] == 'pending' ? 'var(--primary-color)' : '#00ff00'; ?>; position: relative;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                                    <div>
                                        <h4 style="color: #fff; margin-bottom: 3px; font-family: 'Oswald', sans-serif;">
                                            <?php echo htmlspecialchars($row['name']); ?>
                                        </h4>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <i class="fa-solid fa-envelope"
                                                style="font-size: 0.8rem; color: var(--primary-color);"></i>
                                            <span
                                                style="color: var(--text-gray); font-size: 0.85rem;"><?php echo htmlspecialchars($row['email']); ?></span>
                                        </div>
                                    </div>
                                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                                        <span
                                            class="badge <?php echo $row['status'] == 'pending' ? 'badge-warning' : 'badge-success'; ?>"
                                            style="font-size: 0.7rem; padding: 4px 10px; border-radius: 20px;">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                        <form method="POST" style="display: inline;"
                                            onsubmit="return confirm('Permanently delete this inquiry?');">
                                            <input type="hidden" name="delete_query" value="1">
                                            <input type="hidden" name="query_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit"
                                                style="background: none; border: none; color: #ff4d4d; cursor: pointer; font-size: 0.9rem; padding: 5px;"
                                                title="Delete Inquiry">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div
                                    style="background: rgba(0,0,0,0.2); padding: 12px; border-radius: 8px; margin-bottom: 15px;">
                                    <p style="font-size: 0.95rem; color: #eee; line-height: 1.5; font-style: italic;">
                                        "<?php echo htmlspecialchars($row['message']); ?>"</p>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <small style="color: var(--text-gray); font-size: 0.8rem;">
                                        <i class="fa-regular fa-clock" style="margin-right: 5px;"></i>
                                        <?php echo date('M d, Y | g:i A', strtotime($row['created_at'])); ?>
                                    </small>

                                    <?php if ($row['status'] == 'pending'): ?>
                                        <button class="btn-sm btn-edit"
                                            style="margin: 0; padding: 8px 15px; border-radius: 6px; display: flex; align-items: center; gap: 6px;"
                                            onclick='openReplyModal(<?php echo json_encode($row); ?>)'>
                                            <i class="fa-solid fa-reply"></i> Reply via Email
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <?php if ($row['status'] == 'resolved'): ?>
                                    <div
                                        style="margin-top: 15px; padding: 12px; background: rgba(161, 212, 35, 0.05); border: 1px dashed rgba(161, 212, 35, 0.3); border-radius: 8px;">
                                        <strong
                                            style="color: var(--primary-color); display: block; font-size: 0.8rem; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 1px;">
                                            <i class="fa-solid fa-check-double"></i> Staff Response:
                                        </strong>
                                        <p style="font-size: 0.9rem; color: #ddd; line-height: 1.4;">
                                            "<?php echo htmlspecialchars($row['reply']); ?>"</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: var(--text-gray); font-style: italic;">No inquiries found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Reply Modal -->
        <div id="reply-modal" class="modal">
            <div class="modal-content">
                <h3 style="margin-bottom: 20px;">Reply to Member</h3>
                <div style="margin-bottom: 15px; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                    <p style="color: var(--text-gray); font-size: 0.85rem; margin-bottom: 5px;">Member's Question:</p>
                    <p id="reply-question" style="font-size: 0.9rem; line-height: 1.4;"></p>
                </div>
                <form method="POST">
                    <input type="hidden" name="reply_query" value="1">
                    <input type="hidden" name="query_id" id="reply-id">
                    <input type="hidden" name="user_email" id="reply-email">
                    <input type="hidden" name="user_name" id="reply-name">
                    <div class="form-group">
                        <label>Your Reply (Will be sent to their email)</label>
                        <textarea name="reply_content" class="form-control" rows="5" required
                            placeholder="Type your response here..."></textarea>
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" class="btn-add" style="float: none; margin: 0; flex-grow: 1;">Send
                            Reply</button>
                        <button type="button" class="btn-sm" style="background: #333; color: #fff;"
                            onclick="closeModal('reply-modal')">Cancel</button>
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
                        $display_style = $is_first ? 'block' : 'none';
                        $icon_class = $is_first ? 'fa-chevron-up' : 'fa-chevron-down';
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
                <h3 style="margin-bottom: 20px;">Member Payment History</h3>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Plan</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th style="text-align: right;">Invoice</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($payments_res) > 0): ?>
                                <?php while ($payment = mysqli_fetch_assoc($payments_res)): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; flex-direction: column;">
                                                <span
                                                    style="font-weight: bold;"><?php echo htmlspecialchars($payment['full_name']); ?></span>
                                                <small
                                                    style="color: var(--text-gray); font-size: 0.75rem;"><?php echo htmlspecialchars($payment['user_email']); ?></small>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($payment['plan_name']); ?></td>
                                        <td style="color: var(--primary-color); font-weight: bold;">
                                            ₹<?php echo number_format($payment['amount'], 2); ?></td>
                                        <td style="text-transform: capitalize;">
                                            <?php echo htmlspecialchars($payment['payment_method']); ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($payment['created_at'])); ?></td>
                                        <td>
                                            <span class="badge <?php
                                            echo $payment['status'] == 'completed' ? 'badge-success' : ($payment['status'] == 'pending' ? 'badge-warning' : '');
                                            ?>" style="background: <?php
                                            echo $payment['status'] == 'failed' ? 'rgba(255, 77, 77, 0.1)' : '';
                                            ?>; color: <?php
                                            echo $payment['status'] == 'failed' ? '#ff4d4d' : '';
                                            ?>;">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <a href="invoice.php?tid=<?php echo $payment['id']; ?>" target="_blank"
                                                style="color: var(--primary-color); font-size: 1.1rem;"
                                                title="Download Invoice">
                                                <i class="fa-solid fa-file-pdf"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-gray); padding: 30px;">
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
        <!-- Profile Section -->
        <div id="profile" class="dashboard-section">
            <h2 style="font-family: 'Oswald', sans-serif; margin-bottom: 20px;">Profile Settings</h2>
            <div class="dashboard-card" style="max-width: 650px; margin-left: auto; margin-right: auto;">
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
                    <p style="text-align: center; font-size: 0.8rem; color: var(--text-gray); margin-top: 15px;">Changes
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

        function openReplyModal(data) {
            document.getElementById('reply-id').value = data.id;
            document.getElementById('reply-email').value = data.email;
            document.getElementById('reply-name').value = data.name;
            document.getElementById('reply-question').innerText = `"${data.message}"`;
            document.getElementById('reply-modal').style.display = 'flex';
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
    </script>
</body>

</html>