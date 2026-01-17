<?php
require_once 'config.php';
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== "admin") {
    header("location: login.php");
    exit;
}

$message = "";
$message_type = "";

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Ensure announcements table exists
$files_sql = "CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($link, $files_sql);

// HANDLE ANNOUNCEMENT ADDITION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_announcement'])) {
    $title = mysqli_real_escape_string($link, $_POST['title']);
    $msg_content = mysqli_real_escape_string($link, $_POST['message']);

    $sql = "INSERT INTO announcements (title, message) VALUES ('$title', '$msg_content')";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Announcement posted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error posting announcement: " . mysqli_error($link);
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// HANDLE ANNOUNCEMENT DELETION
if (isset($_GET['delete_announcement'])) {
    $id = (int) $_GET['delete_announcement'];
    if (mysqli_query($link, "DELETE FROM announcements WHERE id = $id")) {
        $_SESSION['message'] = "Announcement deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting announcement.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}


// HANDLE TRAINER ADDITION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_trainer'])) {
    $name = mysqli_real_escape_string($link, $_POST['trainer_name']);
    $image_path = "";

    if (isset($_FILES['trainer_image']) && $_FILES['trainer_image']['error'] == 0) {
        $target_dir = "assets/images/trainers/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES["trainer_image"]["name"], PATHINFO_EXTENSION);
        $file_name = time() . "_" . uniqid() . "." . $file_ext;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["trainer_image"]["tmp_name"], $target_file)) {
            $image_path = $target_file;
        }
    }

    $sql = "INSERT INTO trainers (name, image) VALUES ('$name', '$image_path')";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Trainer added successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error adding trainer: " . mysqli_error($link);
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// HANDLE TRAINER EDITING
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_trainer'])) {
    $id = (int) $_POST['trainer_id'];
    $name = mysqli_real_escape_string($link, $_POST['trainer_name']);
    $image_update = "";

    if (isset($_FILES['trainer_image']) && $_FILES['trainer_image']['error'] == 0) {
        $target_dir = "assets/images/trainers/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES["trainer_image"]["name"], PATHINFO_EXTENSION);
        $file_name = time() . "_" . uniqid() . "." . $file_ext;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["trainer_image"]["tmp_name"], $target_file)) {
            // Delete old image
            $img_res = mysqli_query($link, "SELECT image FROM trainers WHERE id = $id");
            $img_data = mysqli_fetch_assoc($img_res);
            if ($img_data && !empty($img_data['image']) && file_exists($img_data['image'])) {
                unlink($img_data['image']);
            }
            $image_update = ", image = '$target_file'";
        }
    }

    $sql = "UPDATE trainers SET name = '$name' $image_update WHERE id = $id";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Trainer updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating trainer.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// HANDLE TRAINER DELETION
if (isset($_GET['delete_trainer'])) {
    $id = (int) $_GET['delete_trainer'];

    // Get image path to delete file
    $img_res = mysqli_query($link, "SELECT image FROM trainers WHERE id = $id");
    $img_data = mysqli_fetch_assoc($img_res);
    if ($img_data && !empty($img_data['image']) && file_exists($img_data['image'])) {
        unlink($img_data['image']);
    }

    if (mysqli_query($link, "DELETE FROM trainers WHERE id = $id")) {
        $_SESSION['message'] = "Trainer deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting trainer.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// HANDLE INVENTORY ADDITION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_inventory'])) {
    $name = mysqli_real_escape_string($link, $_POST['item_name']);
    $qty = (int) $_POST['quantity'];
    $status = mysqli_real_escape_string($link, $_POST['status']);
    $last_m = mysqli_real_escape_string($link, $_POST['last_maintenance']);
    $next_s = mysqli_real_escape_string($link, $_POST['next_service']);

    $sql = "INSERT INTO inventory (item_name, quantity, status, last_maintenance, next_service) VALUES ('$name', $qty, '$status', '$last_m', '$next_s')";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Equipment added to inventory!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error adding equipment.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// HANDLE INVENTORY UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_inventory'])) {
    $id = (int) $_POST['item_id'];
    $name = mysqli_real_escape_string($link, $_POST['item_name']);
    $qty = (int) $_POST['quantity'];
    $status = mysqli_real_escape_string($link, $_POST['status']);
    $last_m = mysqli_real_escape_string($link, $_POST['last_maintenance']);
    $next_s = mysqli_real_escape_string($link, $_POST['next_service']);

    $sql = "UPDATE inventory SET item_name='$name', quantity=$qty, status='$status', last_maintenance='$last_m', next_service='$next_s' WHERE id=$id";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Inventory item updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating inventory.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// HANDLE INVENTORY DELETION
if (isset($_GET['delete_inventory'])) {
    $id = (int) $_GET['delete_inventory'];
    if (mysqli_query($link, "DELETE FROM inventory WHERE id = $id")) {
        $_SESSION['message'] = "Item removed from inventory.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting item.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// FETCH TRAINERS
$trainers_query = mysqli_query($link, "SELECT * FROM trainers ORDER BY created_at DESC");

// FETCH ANNOUNCEMENTS
$ann_query = mysqli_query($link, "SELECT * FROM announcements ORDER BY created_at DESC");

// FETCH INVENTORY
$inventory_query = mysqli_query($link, "SELECT * FROM inventory ORDER BY created_at ASC");
$inventory_count = mysqli_num_rows($inventory_query);


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
            $_SESSION['message'] = "Reply sent and email delivered successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Reply sent but failed to update status in data.";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Error: Failed to send email reply.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// Handle Delete Query
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_query'])) {
    $id = (int) $_POST['query_id'];
    if (mysqli_query($link, "DELETE FROM member_queries WHERE id = $id")) {
        $_SESSION['message'] = "Inquiry deleted successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting inquiry.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// Fetch Queries
$queries_res = mysqli_query($link, "SELECT * FROM member_queries ORDER BY created_at DESC");

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BeFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Roboto:wght@400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        :root {
            --primary-color: #ceff00;
            --secondary-color: #1a1a2e;
            --bg-dark: #080810;
            --card-bg: rgba(255, 255, 255, 0.05);
            --text-gray: #aaa;
            --admin-accent: #3498db;
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

        .sidebar {
            width: 280px;
            background: #000;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar .logo {
            font-family: 'Oswald', sans-serif;
            font-size: 1.8rem;
            color: var(--primary-color);
            text-decoration: none;
            margin-bottom: 40px;
        }

        .sidebar-menu {
            list-style: none;
            flex-grow: 1;
        }

        .sidebar-menu a {
            color: #fff;
            text-decoration: none;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: 0.3s;
            margin-bottom: 5px;
            opacity: 0.7;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: var(--primary-color);
            color: #000;
            opacity: 1;
            font-weight: bold;
        }

        .main-content {
            flex-grow: 1;
            padding: 40px;
            overflow-y: auto;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .dashboard-header h1 {
            font-family: 'Oswald', sans-serif;
            font-size: 2.2rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 15px;
            border-left: 5px solid var(--primary-color);
        }

        .stat-card h4 {
            color: var(--text-gray);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 1.8rem;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Sections */
        .dashboard-section {
            display: none;
        }

        .dashboard-section.active {
            display: block;
            animation: fadeIn 0.5s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-header h3 {
            font-family: 'Oswald', sans-serif;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .data-table th {
            font-size: 0.8rem;
            color: var(--text-gray);
            text-transform: uppercase;
        }

        .btn-add {
            background: var(--primary-color);
            color: #000;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }

        .btn-action {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            border: none;
            margin-right: 5px;
        }

        .btn-view {
            background: #3498db;
            color: #fff;
        }

        .btn-delete {
            background: #e74c3c;
            color: #fff;
        }

        .chart-container {
            height: 300px;
            margin-top: 20px;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .badge-warning {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.2);
        }

        .badge-success {
            background: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.2);
        }

        /* Modal Styles (Synced with Staff Dashboard) */
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-gray);
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #333;
            color: #fff;
            outline: none;
            transition: 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
        }

        select.form-control {
            appearance: none;
            cursor: pointer;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23ceff00'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.2em;
            padding-right: 2.5rem;
            border-color: var(--primary-color) !important;
        }

        select.form-control option {
            background-color: var(--secondary-color);
            color: #fff;
            padding: 10px;
        }

        .btn-action-modal {
            background: var(--primary-color);
            color: var(--secondary-color);
            border: none;
            padding: 15px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: bold;
            width: 100%;
            font-family: 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: 0.3s;
        }

        .btn-action-modal:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(206, 255, 0, 0.3);
        }
    </style>
</head>

<body>

    <?php if (!empty($message)): ?>
        <div style="position: fixed; top: 20px; right: 20px; background: <?php echo $message_type == 'success' ? '#27ae60' : '#e74c3c'; ?>; color: #fff; padding: 15px 25px; border-radius: 8px; z-index: 10000; box-shadow: 0 5px 15px rgba(0,0,0,0.3); animation: slideIn 0.5s ease-out;"
            id="admin-toast">
            <i class="fa-solid <?php echo $message_type == 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"
                style="margin-right: 10px;"></i>
            <?php echo $message; ?>
        </div>
        <script>setTimeout(() => { document.getElementById('admin-toast').style.opacity = '0'; setTimeout(() => document.getElementById('admin-toast').remove(), 500); }, 3000);</script>
        <style>
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }

                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        </style>
    <?php endif; ?>

    <div class="sidebar">
        <a href="index.php" class="logo">GYMFIT ADMIN</a>
        <ul class="sidebar-menu">
            <li><a href="index.php" style="color: var(--primary-color); opacity: 1;"><i
                        class="fa-solid fa-house-chimney"></i>
                    Back to Website</a></li>
            <li><a href="#" class="active" onclick="showSection('overview')"><i class="fa-solid fa-gauge"></i>
                    Overview</a></li>
            <li><a href="#" onclick="showSection('users')"><i class="fa-solid fa-user-shield"></i> Staff & Users</a>
            </li>
            <li><a href="#" onclick="showSection('plans')"><i class="fa-solid fa-tags"></i> Membership Plans</a></li>
            <li><a href="#" onclick="showSection('queries')"><i class="fa-solid fa-comments"></i> Member Queries</a>
            </li>
            <li><a href="#" onclick="showSection('trainers')"><i class="fa-solid fa-dumbbell"></i> Trainers</a></li>
            <li><a href="#" onclick="showSection('financials')"><i class="fa-solid fa-money-bill-trend-up"></i>
                    Financial Records</a></li>
            <li><a href="#" onclick="showSection('schedule')"><i class="fa-solid fa-calendar-check"></i> Class
                    Schedule</a></li>
            <li><a href="#" onclick="showSection('inventory')"><i class="fa-solid fa-boxes-stacked"></i> Inventory</a>
            </li>
            <li><a href="#" onclick="showSection('announcements')"><i class="fa-solid fa-bullhorn"></i>
                    Announcements</a></li>

        </ul>
        <div style="margin-top: auto;">
            <a href="logout.php"
                style="color: #ff4d4d; text-decoration: none; display: flex; align-items: center; gap: 10px;"><i
                    class="fa-solid fa-power-off"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="dashboard-header">
            <h1>System Control</h1>
            <div style="text-align: right;">
                <p>Welcome, Admin</p>
                <small style="color: var(--text-gray);">Full Access Granted</small>
            </div>
        </div>

        <!-- Overview -->
        <div id="overview" class="dashboard-section active">
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Total Members</h4>
                    <div class="value">1,284 <i class="fa-solid fa-users" style="color: var(--primary-color);"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <h4>Monthly Revenue</h4>
                    <div class="value">₹1.2M <i class="fa-solid fa-indian-rupee-sign"
                            style="color: var(--primary-color);"></i></div>
                </div>
                <div class="stat-card">
                    <h4>Active Staff</h4>
                    <div class="value">12 <i class="fa-solid fa-user-ninja" style="color: var(--primary-color);"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <h4>Equipment Status</h4>
                    <div class="value">98% <i class="fa-solid fa-check-double" style="color: var(--primary-color);"></i>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Revenue Growth</h3>
                </div>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Staff & Users -->
        <div id="users" class="dashboard-section">
            <div class="card">
                <div class="card-header">
                    <h3>Personnel Management</h3>
                    <button class="btn-add">+ Add Staff</button>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Admin User</td>
                            <td>Administrator</td>
                            <td>admin@gym.com</td>
                            <td><span style="color: #ceff00;">Active</span></td>
                            <td><button class="btn-action btn-view">Update</button></td>
                        </tr>
                        <tr>
                            <td>Alex Trainer</td>
                            <td>Staff (Trainer)</td>
                            <td>alex@gym.com</td>
                            <td><span style="color: #ceff00;">Active</span></td>
                            <td><button class="btn-action btn-view">Update</button><button
                                    class="btn-action btn-delete">Suspend</button></td>
                        </tr>
                        <tr>
                            <td>John Member</td>
                            <td>Member</td>
                            <td>john@gmail.com</td>
                            <td><span style="color: #ceff00;">Active</span></td>
                            <td><button class="btn-action btn-view">Manage</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Plans -->
        <div id="plans" class="dashboard-section">
            <div class="card">
                <div class="card-header">
                    <h3>Membership Plans</h3>
                    <button class="btn-add">New Plan</button>
                </div>
                <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                    <div class="stat-card">
                        <h4>Basic</h4>
                        <p>₹399/mo</p>
                        <button class="btn-action btn-view" style="margin-top: 10px;">Edit</button>
                    </div>
                    <div class="stat-card">
                        <h4>Standard</h4>
                        <p>₹899/mo</p>
                        <button class="btn-action btn-view" style="margin-top: 10px;">Edit</button>
                    </div>
                    <div class="stat-card">
                        <h4>Premium</h4>
                        <p>₹999/mo</p>
                        <button class="btn-action btn-view" style="margin-top: 10px;">Edit</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financials -->
        <div id="financials" class="dashboard-section">
            <div class="card">
                <div class="card-header">
                    <h3>Financial Records</h3>
                    <button class="btn-add">Export Report</button>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Member</th>
                            <th>Plan</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#TXN9827</td>
                            <td>John Member</td>
                            <td>Standard</td>
                            <td>₹899.00</td>
                            <td>Dec 18, 2025</td>
                            <td>Credit Card</td>
                        </tr>
                        <tr>
                            <td>#TXN9826</td>
                            <td>Jane Member</td>
                            <td>Premium</td>
                            <td>₹999.00</td>
                            <td>Dec 17, 2025</td>
                            <td>UPI</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Schedule -->
        <div id="schedule" class="dashboard-section">
            <div class="card">
                <div class="card-header">
                    <h3>Fitness Class Schedule</h3>
                    <button class="btn-add">Add Class</button>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Trainer</th>
                            <th>Time</th>
                            <th>Days</th>
                            <th>Capacity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Yoga Basics</td>
                            <td>Sarah J.</td>
                            <td>07:00 AM</td>
                            <td>Mon, Wed, Fri</td>
                            <td>15 / 20</td>
                        </tr>
                        <tr>
                            <td>Power Lifting</td>
                            <td>Alex R.</td>
                            <td>06:00 PM</td>
                            <td>Tue, Thu, Sat</td>
                            <td>8 / 10</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Inventory -->
        <div id="inventory" class="dashboard-section">
            <div class="card">
                <div class="card-header">
                    <h3>Gym Inventory & Equipment</h3>
                </div>

                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 0 15px;">
                    <div style="position: relative; width: 300px;">
                        <i class="fa-solid fa-magnifying-glass"
                            style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-gray); font-size: 0.9rem;"></i>
                        <input type="text" id="inventory-search" onkeyup="searchInventory()" placeholder="Search items"
                            style="width: 100%; padding: 10px 15px 10px 40px; border-radius: 30px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff; font-size: 0.9rem; outline: none; transition: 0.3s;">
                    </div>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span
                            style="background: #000; color: #fff; padding: 12px 25px; border-radius: 10px; font-weight: bold; font-size: 1rem; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 4px 10px rgba(0,0,0,0.5);">Total
                            Items: <?php echo $inventory_count; ?></span>
                        <button class="btn-add" onclick="openAddInventoryModal()"
                            style="margin: 0; float: none; padding: 12px 25px; border-radius: 10px;">+ Add Item</button>
                    </div>
                </div>

                <div style="overflow-x:auto;">
                    <table class="data-table" id="inventory-table">
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
                            <?php if (mysqli_num_rows($inventory_query) > 0): ?>
                                <?php
                                mysqli_data_seek($inventory_query, 0); // Reset pointer
                                while ($item = mysqli_fetch_assoc($inventory_query)):
                                    $status_color = ($item['status'] == 'Functional' || $item['status'] == 'Good') ? '#ceff00' : '#ff4d4d';
                                    ?>
                                    <tr>
                                        <td style="font-weight: bold;"><?php echo htmlspecialchars($item['item_name']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td style="color: <?php echo $status_color; ?>">
                                            <?php echo htmlspecialchars($item['status']); ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($item['last_maintenance'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($item['next_service'])); ?></td>
                                        <td>
                                            <button class="btn-action btn-view"
                                                onclick='openEditInventoryModal(<?php echo json_encode($item); ?>)'>Edit</button>
                                            <a href="?delete_inventory=<?php echo $item['id']; ?>" class="btn-action btn-delete"
                                                onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-gray);">No inventory items
                                        found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <!-- Trainers -->
        <div id="trainers" class="dashboard-section">
            <div class="card">
                <div class="card-header">
                    <h3>Trainer Management</h3>
                    <button class="btn-add"
                        onclick="document.getElementById('add-trainer-modal').style.display='flex'">+ Add
                        Trainer</button>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Date Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($trainers_query) > 0): ?>
                            <?php while ($trainer = mysqli_fetch_assoc($trainers_query)): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo $trainer['image'] ? $trainer['image'] : 'https://ui-avatars.com/api/?name=' . urlencode($trainer['name']) . '&background=ceff00&color=1a1a2e'; ?>"
                                            style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-color);">
                                    </td>
                                    <td><?php echo htmlspecialchars($trainer['name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($trainer['created_at'])); ?></td>
                                    <td>
                                        <button class="btn-action btn-view"
                                            onclick="openEditTrainerModal(<?php echo $trainer['id']; ?>, '<?php echo addslashes(htmlspecialchars($trainer['name'])); ?>')">Edit</button>
                                        <a href="?delete_trainer=<?php echo $trainer['id']; ?>" class="btn-action btn-delete"
                                            onclick="return confirm('Are you sure you want to delete this trainer?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-gray);">No trainers found. Add
                                    your first trainer!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Announcements Section -->
        <div id="announcements" class="dashboard-section">
            <div class="card">
                <div class="card-header">
                    <h3>Announcements Management</h3>
                    <button class="btn-add"
                        onclick="document.getElementById('add-announcement-modal').style.display='flex'">+ Post
                        Announcement</button>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Message</th>
                            <th>Date Posted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($ann_query) > 0): ?>
                            <?php while ($ann = mysqli_fetch_assoc($ann_query)): ?>
                                <tr>
                                    <td style="font-weight: bold; color: var(--primary-color);">
                                        <?php echo htmlspecialchars($ann['title']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($ann['message'], 0, 50)) . '...'; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($ann['created_at'])); ?></td>
                                    <td>
                                        <a href="?delete_announcement=<?php echo $ann['id']; ?>" class="btn-action btn-delete"
                                            onclick="return confirm('Delete this announcement?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-gray);">No announcements found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Announcement Modal -->
        <div id="add-announcement-modal"
            style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1100; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
            <div class="card"
                style="width:100%; max-width:500px; background: var(--secondary-color); border: 1px solid rgba(255,255,255,0.1);">
                <div class="card-header">
                    <h3>Post New Announcement</h3>
                    <button onclick="document.getElementById('add-announcement-modal').style.display='none'"
                        style="background:none; border:none; color:#fff; cursor:pointer; font-size:1.5rem;">&times;</button>
                </div>
                <form method="POST">
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Title</label>
                        <input type="text" name="title" required placeholder="e.g., New Equipment Arrival!"
                            style="width:100%; padding:12px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px;">
                    </div>
                    <div style="margin-bottom: 25px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Message</label>
                        <textarea name="message" rows="4" required placeholder="Enter announcement details..."
                            style="width:100%; padding:12px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px; resize: vertical; font-family: inherit;"></textarea>
                    </div>
                    <button type="submit" name="add_announcement" class="btn-add" style="width:100%;">Post
                        Now</button>
                </form>
            </div>
        </div>


        <!-- Add Inventory Modal -->
        <div id="add-inventory-modal" class="modal">
            <div class="modal-content">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="color: var(--primary-color); font-family: 'Oswald', sans-serif; font-size: 1.8rem;">Add
                        Inventory Item</h3>
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
                        <select name="status" class="form-control">
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
                    <button type="submit" class="btn-action-modal">Add Item</button>
                </form>
            </div>
        </div>

        <!-- Edit Inventory Modal -->
        <div id="edit-inventory-modal" class="modal">
            <div class="modal-content">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="color: var(--primary-color); font-family: 'Oswald', sans-serif; font-size: 1.8rem;">Edit
                        Inventory Item</h3>
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
                        <input type="number" name="quantity" id="edit-inventory-qty" class="form-control" required
                            min="1">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="edit-inventory-status" class="form-control">
                            <option value="Functional">Functional</option>
                            <option value="Good">Good</option>
                            <option value="Service Due">Service Due</option>
                            <option value="Broken">Broken</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Last Maintenance</label>
                        <input type="text" name="last_maintenance" id="edit-inventory-last"
                            class="form-control date-picker" required placeholder="Select Date">
                    </div>
                    <div class="form-group">
                        <label>Next Service</label>
                        <input type="text" name="next_service" id="edit-inventory-next" class="form-control date-picker"
                            required placeholder="Select Date">
                    </div>
                    <button type="submit" class="btn-action-modal">Update Item</button>
                </form>
            </div>
        </div>

        <!-- Add Trainer Modal -->
        <div id="add-trainer-modal"
            style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1100; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
            <div class="card"
                style="width:100%; max-width:450px; background: var(--secondary-color); border: 1px solid rgba(255,255,255,0.1);">
                <div class="card-header">
                    <h3>Add New Trainer</h3>
                    <button onclick="document.getElementById('add-trainer-modal').style.display='none'"
                        style="background:none; border:none; color:#fff; cursor:pointer; font-size:1.5rem;">&times;</button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Trainer Name</label>
                        <input type="text" name="trainer_name" required
                            style="width:100%; padding:12px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px;">
                    </div>
                    <div style="margin-bottom: 25px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Trainer Image</label>
                        <input type="file" name="trainer_image" accept="image/*" style="width:100%; color:#fff;">
                        <small style="color: var(--text-gray); display:block; margin-top:5px;">Upload a professional
                            photo for the trainer profile.</small>
                    </div>
                    <button type="submit" name="add_trainer" class="btn-add" style="width:100%;">Save Trainer</button>
                </form>
            </div>
        </div>

        <!-- Member Queries Section -->
        <div id="queries" class="dashboard-section">
            <div class="card">
                <div class="card-header">
                    <h3>Member Inquiries</h3>
                </div>
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
        <div id="reply-modal"
            style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1100; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
            <div class="card"
                style="width:100%; max-width:600px; background: var(--secondary-color); border: 1px solid rgba(255,255,255,0.1);">
                <div class="card-header">
                    <h3>Reply to Member</h3>
                    <button onclick="document.getElementById('reply-modal').style.display='none'"
                        style="background:none; border:none; color:#fff; cursor:pointer; font-size:1.5rem;">&times;</button>
                </div>
                <div style="margin-bottom: 15px; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                    <p style="color: var(--text-gray); font-size: 0.85rem; margin-bottom: 5px;">Member's Question:</p>
                    <p id="reply-question"
                        style="font-size: 0.9rem; line-height: 1.4; font-style: italic; color: #fff;"></p>
                </div>
                <form method="POST">
                    <input type="hidden" name="reply_query" value="1">
                    <input type="hidden" name="query_id" id="reply-id">
                    <input type="hidden" name="user_email" id="reply-email">
                    <input type="hidden" name="user_name" id="reply-name">
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Your Reply</label>
                        <textarea name="reply_content" rows="5" required
                            style="width: 100%; padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid #333; color: #fff; border-radius: 8px; font-family: inherit;"
                            placeholder="Type your response here..."></textarea>
                    </div>
                    <button type="submit" class="btn-add" style="width: 100%;">Send Reply</button>
                </form>
            </div>
        </div>

        <div id="edit-trainer-modal"
            style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1100; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
            <div class="card"
                style="width:100%; max-width:450px; background: var(--secondary-color); border: 1px solid rgba(255,255,255,0.1);">
                <div class="card-header">
                    <h3>Edit Trainer</h3>
                    <button onclick="document.getElementById('edit-trainer-modal').style.display='none'"
                        style="background:none; border:none; color:#fff; cursor:pointer; font-size:1.5rem;">&times;</button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="trainer_id" id="edit-trainer-id">
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Trainer Name</label>
                        <input type="text" name="trainer_name" id="edit-trainer-name" required
                            style="width:100%; padding:12px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px;">
                    </div>
                    <div style="margin-bottom: 25px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Trainer Image
                            (Optional)</label>
                        <input type="file" name="trainer_image" accept="image/*" style="width:100%; color:#fff;">
                        <small style="color: var(--text-gray); display:block; margin-top:5px;">Upload a new photo only
                            if you want to change the current one.</small>
                    </div>
                    <button type="submit" name="edit_trainer" class="btn-add" style="width:100%;">Update
                        Trainer</button>
                </form>
            </div>
        </div>

    </div>

    <script>
        function showSection(sectionId) {
            document.querySelectorAll('.dashboard-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.sidebar-menu a').forEach(a => a.classList.remove('active'));
            document.getElementById(sectionId).classList.add('active');

            // Handle if the call came from a sidebar click (has currentTarget)
            if (event && event.currentTarget) {
                event.currentTarget.classList.add('active');
            }
        }

        function openEditTrainerModal(id, name) {
            document.getElementById('edit-trainer-id').value = id;
            document.getElementById('edit-trainer-name').value = name;
            document.getElementById('edit-trainer-modal').style.display = 'flex';
        }

        function openAddInventoryModal() {
            document.getElementById('add-inventory-modal').style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function openEditInventoryModal(item) {
            document.getElementById('edit-inventory-id').value = item.id;
            document.getElementById('edit-inventory-name').value = item.item_name;
            document.getElementById('edit-inventory-qty').value = item.quantity;
            document.getElementById('edit-inventory-status').value = item.status;

            // Set values for date inputs using flatpickr instance if available
            const lastInput = document.getElementById('edit-inventory-last');
            const nextInput = document.getElementById('edit-inventory-next');

            if (lastInput._flatpickr) lastInput._flatpickr.setDate(item.last_maintenance);
            else lastInput.value = item.last_maintenance;

            if (nextInput._flatpickr) nextInput._flatpickr.setDate(item.next_service);
            else nextInput.value = item.next_service;

            document.getElementById('edit-inventory-modal').style.display = 'flex';
        }

        function searchInventory() {
            let input = document.getElementById('inventory-search');
            let filter = input.value.toLowerCase();
            let table = document.getElementById('inventory-table');
            let tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                let td = tr[i].getElementsByTagName('td')[0];
                if (td) {
                    let textValue = td.textContent || td.innerText;
                    if (textValue.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }

        // Initialize Flatpickr
        document.addEventListener('DOMContentLoaded', function () {
            flatpickr(".date-picker", {
                theme: "dark",
                altInput: true,
                altFormat: "M j, Y",
                dateFormat: "Y-m-d"
            });
        });

        function openReplyModal(data) {
            document.getElementById('reply-id').value = data.id;
            document.getElementById('reply-email').value = data.email;
            document.getElementById('reply-name').value = data.name;
            document.getElementById('reply-question').innerText = `"${data.message}"`;
            document.getElementById('reply-modal').style.display = 'flex';
        }

        // Initialize Revenue Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Revenue (in Thousands ₹)',
                    data: [800, 950, 900, 1100, 1050, 1200],
                    borderColor: '#ceff00',
                    backgroundColor: 'rgba(206, 255, 0, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: false, grid: { color: 'rgba(255,255,255,0.05)' }, border: { display: false } },
                    x: { grid: { display: false } }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
</body>

</html>