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


// HANDLE ANNOUNCEMENT EDITING
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_announcement'])) {
    $id = (int) $_POST['announcement_id'];
    $title = mysqli_real_escape_string($link, $_POST['title']);
    $msg_content = mysqli_real_escape_string($link, $_POST['message']);

    $sql = "UPDATE announcements SET title = '$title', message = '$msg_content' WHERE id = $id";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Announcement updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating announcement: " . mysqli_error($link);
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

// --- MEMBER MANAGEMENT ---

// Add Member
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_member'])) {
    $full_name = trim(mysqli_real_escape_string($link, $_POST['full_name']));
    $email = trim(mysqli_real_escape_string($link, $_POST['email']));
    $password_raw = $_POST['password'];

    // Validation
    if (empty($full_name) || empty($email) || empty($password_raw)) {
        $_SESSION['message'] = "Error: All fields are required.";
        $_SESSION['message_type'] = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Error: Invalid email format.";
        $_SESSION['message_type'] = "error";
    } elseif (strlen($password_raw) < 6) {
        $_SESSION['message'] = "Error: Password must be at least 6 characters.";
        $_SESSION['message_type'] = "error";
    } else {
        // Check if email exists
        $check_email = mysqli_query($link, "SELECT id FROM users WHERE email = '$email'");
        if (mysqli_num_rows($check_email) > 0) {
            $_SESSION['message'] = "Error: Email is already registered.";
            $_SESSION['message_type'] = "error";
        } else {
            $password = password_hash($password_raw, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (full_name, email, password, role) VALUES ('$full_name', '$email', '$password', 'member')";
            if (mysqli_query($link, $sql)) {
                $_SESSION['message'] = "New member added successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error adding member: " . mysqli_error($link);
                $_SESSION['message_type'] = "error";
            }
        }
    }
    header("Location: dashboard_admin.php");
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
        $_SESSION['message'] = "Error: Name and email are required.";
        $_SESSION['message_type'] = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Error: Invalid email format.";
        $_SESSION['message_type'] = "error";
    } elseif (!empty($password_raw) && strlen($password_raw) < 6) {
        $_SESSION['message'] = "Error: New password must be at least 6 characters.";
        $_SESSION['message_type'] = "error";
    } else {
        // Check if email exists for other users
        $check_email = mysqli_query($link, "SELECT id FROM users WHERE email = '$email' AND id != $id");
        if (mysqli_num_rows($check_email) > 0) {
            $_SESSION['message'] = "Error: Email is already in use by another user.";
            $_SESSION['message_type'] = "error";
        } else {
            $pass_query = "";
            if (!empty($password_raw)) {
                $password = password_hash($password_raw, PASSWORD_DEFAULT);
                $pass_query = ", password='$password'";
            }

            $sql = "UPDATE users SET full_name='$full_name', email='$email' $pass_query WHERE id=$id AND role='member'";
            if (mysqli_query($link, $sql)) {
                $_SESSION['message'] = "Member updated successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error updating member: " . mysqli_error($link);
                $_SESSION['message_type'] = "error";
            }
        }
    }
    header("Location: dashboard_admin.php");
    exit;
}

// Delete Member
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_member'])) {
    $id = (int) $_POST['member_id'];
    if (mysqli_query($link, "DELETE FROM users WHERE id = $id AND role='member'")) {
        $_SESSION['message'] = "Member removed successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error removing member.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// Fetch all members
$members_res = mysqli_query($link, "SELECT * FROM users WHERE role = 'member' ORDER BY created_at DESC");

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

// Handle Add Plan
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_plan'])) {
    $name = mysqli_real_escape_string($link, $_POST['name']);
    $price_monthly = (float) $_POST['price_monthly'];
    $price_yearly = (float) $_POST['price_yearly'];
    $is_popular = isset($_POST['is_popular']) ? 1 : 0;

    // Default values for a new plan
    $sql = "INSERT INTO membership_plans (
        name, price_monthly, price_yearly, is_popular, 
        gym_access, free_locker, group_class, personal_trainer, 
        protein_drinks_monthly, protein_drinks_yearly, customized_workout_plan, 
        diet_consultation_yearly, personal_locker_yearly, guest_pass_yearly, nutrition_guide_yearly,
        custom_attributes, feature_labels, hidden_features
    ) VALUES (
        '$name', '$price_monthly', '$price_yearly', '$is_popular',
        1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
        '[]', '{}', '[]'
    )";

    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "New plan created successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error creating plan.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// Handle Plan Deletion
if (isset($_GET['delete_plan'])) {
    $plan_id = (int) $_GET['delete_plan'];
    if (mysqli_query($link, "DELETE FROM membership_plans WHERE id = $plan_id")) {
        $_SESSION['message'] = "Plan deleted successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting plan.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// Handle Plan Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_plan'])) {
    $id = (int) $_POST['plan_id'];
    $name = mysqli_real_escape_string($link, $_POST['name']);
    $price_monthly = (float) $_POST['price_monthly'];
    $price_yearly = (float) $_POST['price_yearly'];
    $is_popular = isset($_POST['is_popular']) ? 1 : 0;

    // Features
    $gym_access = isset($_POST['gym_access']) ? 1 : 0;
    $free_locker = isset($_POST['free_locker']) ? 1 : 0;
    $group_class = isset($_POST['group_class']) ? 1 : 0;
    $personal_trainer = isset($_POST['personal_trainer']) ? 1 : 0;
    $protein_drinks_monthly = isset($_POST['protein_drinks_monthly']) ? 1 : 0;
    $protein_drinks_yearly = isset($_POST['protein_drinks_yearly']) ? 1 : 0;
    $customized_workout_plan = isset($_POST['customized_workout_plan']) ? 1 : 0;
    $diet_consultation_yearly = isset($_POST['diet_consultation_yearly']) ? 1 : 0;
    $personal_locker_yearly = isset($_POST['personal_locker_yearly']) ? 1 : 0;
    $guest_pass_yearly = isset($_POST['guest_pass_yearly']) ? 1 : 0;
    $nutrition_guide_yearly = isset($_POST['nutrition_guide_yearly']) ? 1 : 0;
    $custom_attributes = mysqli_real_escape_string($link, $_POST['custom_attributes'] ?? '');
    $hidden_features = mysqli_real_escape_string($link, $_POST['hidden_features'] ?? '[]');

    // Labels for standard features
    $labels = [
        'gym_access' => $_POST['lab_gym_access'] ?? 'Gym Access',
        'free_locker' => $_POST['lab_free_locker'] ?? 'Free Locker',
        'group_class' => $_POST['lab_group_class'] ?? 'Group Class',
        'personal_trainer' => $_POST['lab_personal_trainer'] ?? 'Personal Trainer',
        'protein_drinks_monthly' => $_POST['lab_protein_drinks_monthly'] ?? 'Protein Drinks (Monthly)',
        'protein_drinks_yearly' => $_POST['lab_protein_drinks_yearly'] ?? 'Protein Drinks (Yearly)',
        'customized_workout_plan' => $_POST['lab_customized_workout_plan'] ?? 'Customized Workout Plan',
        'diet_consultation_yearly' => $_POST['lab_diet_consultation_yearly'] ?? 'Diet Consultation (Yearly)',
        'personal_locker_yearly' => $_POST['lab_personal_locker_yearly'] ?? 'Personal Locker (Yearly)',
        'guest_pass_yearly' => $_POST['lab_guest_pass_yearly'] ?? '1 Guest Pass/Mo (Yearly)',
        'nutrition_guide_yearly' => $_POST['lab_nutrition_guide_yearly'] ?? 'Nutrition Guide (Yearly)'
    ];
    $feature_labels = mysqli_real_escape_string($link, json_encode($labels));

    $update_sql = "UPDATE membership_plans SET 
        name='$name', 
        price_monthly='$price_monthly', 
        price_yearly='$price_yearly', 
        is_popular='$is_popular',
        gym_access='$gym_access',
        free_locker='$free_locker',
        group_class='$group_class',
        personal_trainer='$personal_trainer',
        protein_drinks_monthly='$protein_drinks_monthly',
        protein_drinks_yearly='$protein_drinks_yearly',
        customized_workout_plan='$customized_workout_plan',
        diet_consultation_yearly='$diet_consultation_yearly',
        personal_locker_yearly='$personal_locker_yearly',
        guest_pass_yearly='$guest_pass_yearly',
        nutrition_guide_yearly='$nutrition_guide_yearly',
        custom_attributes='$custom_attributes',
        feature_labels='$feature_labels',
        hidden_features='$hidden_features'
        WHERE id = $id";

    if (mysqli_query($link, $update_sql)) {
        $_SESSION['message'] = "Plan updated successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating plan.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_admin.php");
    exit;
}

// Fetch all plans
$plans_res = mysqli_query($link, "SELECT * FROM membership_plans ORDER BY id ASC");

// --- OVERVIEW STATS (DYNAMIC) ---
// 1. Total Members
$total_members_query = mysqli_query($link, "SELECT COUNT(*) as count FROM users WHERE role = 'member'");
$total_members = mysqli_fetch_assoc($total_members_query)['count'];

// 2. Monthly Revenue
$current_month = date('Y-m');
$revenue_query = mysqli_query($link, "SELECT SUM(amount) as total FROM transactions WHERE status = 'completed' AND DATE_FORMAT(created_at, '%Y-%m') = '$current_month'");
$monthly_revenue = mysqli_fetch_assoc($revenue_query)['total'] ?? 0;

// 3. Active Staff
$total_staff_query = mysqli_query($link, "SELECT COUNT(*) as count FROM users WHERE role = 'staff'");
$total_staff = mysqli_fetch_assoc($total_staff_query)['count'];

// 4. Equipment Status (Simple average/percentage of "Functional" or "Good" items)
$inventory_stats_query = mysqli_query($link, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status IN ('Functional', 'Good') THEN 1 ELSE 0 END) as healthy
    FROM inventory");
$iv_stats = mysqli_fetch_assoc($inventory_stats_query);
$equipment_status = ($iv_stats['total'] > 0) ? round(($iv_stats['healthy'] / $iv_stats['total']) * 100) : 100;

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
            font-family: inherit;
            letter-spacing: 1px;
            transition: 0.3s;
        }

        .btn-action-modal:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(206, 255, 0, 0.3);
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

        /* Sub-navigation Tabs */
        .user-tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 2px;
        }

        .user-tab {
            padding: 12px 25px;
            color: var(--text-gray);
            cursor: pointer;
            font-family: 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid transparent;
            transition: 0.3s;
        }

        .user-tab:hover {
            color: #fff;
        }

        .user-tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .user-tab-content {
            display: none;
            animation: fadeIn 0.4s ease-out;
        }

        .user-tab-content.active {
            display: block;
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
            <li><a href="#" onclick="showSection('users')"><i class="fa-solid fa-user-shield"></i>Users</a>
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
            <div></div> <!-- Spacer for alignment -->
            <div style="text-align: right;">
                <p>Admin</p>
            </div>
        </div>

        <div id="overview" class="dashboard-section active">
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Total Members</h4>
                    <div class="value"><?php echo number_format($total_members); ?> <i class="fa-solid fa-users"
                            style="color: var(--primary-color);"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <h4>Monthly Revenue</h4>
                    <div class="value">
                        ₹<?php echo $monthly_revenue >= 1000 ? number_format($monthly_revenue / 1000, 1) . 'K' : number_format($monthly_revenue); ?>
                        <i class="fa-solid fa-indian-rupee-sign" style="color: var(--primary-color);"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <h4>Active Staff</h4>
                    <div class="value"><?php echo number_format($total_staff); ?> <i class="fa-solid fa-user-ninja"
                            style="color: var(--primary-color);"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <h4>Equipment Status</h4>
                    <div class="value"><?php echo $equipment_status; ?>% <i class="fa-solid fa-check-double"
                            style="color: var(--primary-color);"></i>
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

        <!-- Member Directory & Payments -->
        <div id="users" class="dashboard-section">
            <div class="user-tabs">
                <div class="user-tab active" onclick="switchUserTab('directory', this)">Member Directory</div>
                <div class="user-tab" onclick="switchUserTab('payments', this)">Member Payment History</div>
            </div>

            <div id="tab-directory" class="user-tab-content active">
                <div class="card">
                    <div class="card-header">
                        <h3>Member Directory</h3>
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <div style="position: relative; width: 300px;">
                                <i class="fa-solid fa-magnifying-glass"
                                    style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-gray); font-size: 0.9rem;"></i>
                                <input type="text" id="member-search" onkeyup="searchMembers()"
                                    placeholder="Search members"
                                    style="width: 100%; padding: 10px 15px 10px 40px; border-radius: 30px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff; font-size: 0.9rem; outline: none; transition: 0.3s;">
                            </div>
                            <button class="btn-add" onclick="openAddMemberModal()" style="margin:0;">+ Add New
                                Member</button>
                        </div>
                    </div>
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
                                                <button class="btn-action btn-view"
                                                    onclick='openEditMemberModal(<?php echo json_encode($member); ?>)'>Edit</button>
                                                <form method="POST"
                                                    onsubmit="return confirm('Remove this member permanently?');"
                                                    style="margin:0;">
                                                    <input type="hidden" name="delete_member" value="1">
                                                    <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                                    <button type="submit" class="btn-action btn-delete">Remove</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: var(--text-gray);">No members found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="tab-payments" class="user-tab-content">
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; font-family: 'Oswald', sans-serif;">Member Payment History</h3>
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
                                            <button onclick="toggleHistory(<?php echo $uid; ?>)" class="btn-action btn-view"
                                                style="display: inline-flex; align-items: center; gap: 5px; cursor: pointer; width: auto;">
                                                <i class="fa-solid fa-clock-rotate-left"></i> History
                                                (<?php echo $count; ?>) <i class="fa-solid fa-chevron-down"
                                                    id="icon-<?php echo $uid; ?>"></i>
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
                                                                        ₹<?php echo number_format($payment['amount'], 2); ?>
                                                                    </td>
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


        <!-- Plans -->
        <div id="plans" class="dashboard-section">
            <div class="card">
                <div class="card-header">
                    <h3>Membership Plans</h3>
                    <button class="btn-add" onclick="document.getElementById('add-plan-modal').style.display='flex'">+
                        Add Plan</button>
                </div>
                <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                    <?php
                    mysqli_data_seek($plans_res, 0);
                    while ($plan = mysqli_fetch_assoc($plans_res)):
                        ?>
                        <div class="stat-card"
                            style="position: relative; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s;">
                            <?php if ($plan['is_popular']): ?>
                                <span class="badge badge-success"
                                    style="position: absolute; top: 15px; right: 15px; background: var(--primary-color); color: #000;">Popular</span>
                            <?php endif; ?>
                            <h4 style="color: var(--primary-color); font-size: 1.4rem; margin-bottom: 5px;">
                                <?php echo htmlspecialchars($plan['name']); ?>
                            </h4>
                            <div style="margin: 15px 0; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 10px;">
                                <p style="font-size: 1.8rem; font-weight: bold; margin: 0;">
                                    ₹<?php echo number_format($plan['price_monthly']); ?><span
                                        style="font-size: 0.9rem; color: var(--text-gray); font-weight: normal;">/mo</span>
                                </p>
                                <p style="font-size: 1.2rem; color: var(--text-gray); margin-top: 5px;">
                                    ₹<?php echo number_format($plan['price_yearly']); ?><span
                                        style="font-size: 0.8rem;">/yr</span></p>
                            </div>
                            <div style="display: flex; gap: 10px; margin-top: 10px;">
                                <button class="btn-action btn-view"
                                    style="flex: 1; padding: 12px; font-weight: bold; border-radius: 8px;"
                                    onclick='openEditPlanModal(<?php echo json_encode($plan); ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i> Edit Attributes
                                </button>
                                <a href="?delete_plan=<?php echo $plan['id']; ?>" class="btn-action btn-delete"
                                    style="flex: 1; padding: 12px; font-weight: bold; border-radius: 8px; text-align: center; display: inline-block;"
                                    onclick="return confirm('Are you sure you want to delete this plan?')">
                                    <i class="fa-solid fa-trash"></i> Delete Plan
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
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
                            style="margin: 0; float: none; padding: 12px 25px; border-radius: 10px;">+ Add
                            Item</button>
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
                                        <td style="font-weight: bold;"><?php echo htmlspecialchars($item['item_name']); ?>
                                        </td>
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
                                    <td colspan="6" style="text-align: center; color: var(--text-gray);">No inventory
                                        items
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
                        onclick="document.getElementById('add-trainer-modal').style.display='flex'">+
                        Add
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
                                <td colspan="4" style="text-align: center; color: var(--text-gray);">No trainers found.
                                    Add
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
                                    <td><?php echo nl2br(htmlspecialchars($ann['message'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($ann['created_at'])); ?></td>
                                    <td>
                                        <button class="btn-action btn-view"
                                            onclick='openEditAnnouncementModal(<?php echo htmlspecialchars(json_encode($ann), ENT_QUOTES, "UTF-8"); ?>)'>Edit</button>
                                        <a href="?delete_announcement=<?php echo $ann['id']; ?>" class="btn-action btn-delete"
                                            onclick="return confirm('Delete this announcement?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-gray);">No announcements
                                    found.
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


        <!-- Edit Announcement Modal -->
        <div id="edit-announcement-modal"
            style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1100; align-items:center; justify-content:center; backdrop-filter: blur(5px);">
            <div class="card"
                style="width:100%; max-width:500px; background: var(--secondary-color); border: 1px solid rgba(255,255,255,0.1);">
                <div class="card-header">
                    <h3>Edit Announcement</h3>
                    <button onclick="document.getElementById('edit-announcement-modal').style.display='none'"
                        style="background:none; border:none; color:#fff; cursor:pointer; font-size:1.5rem;">&times;</button>
                </div>
                <form method="POST">
                    <input type="hidden" name="announcement_id" id="edit-ann_id">
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Title</label>
                        <input type="text" name="title" id="edit-ann_title" required
                            style="width:100%; padding:12px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px;">
                    </div>
                    <div style="margin-bottom: 25px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Message</label>
                        <textarea name="message" id="edit-ann_message" rows="6" required
                            style="width:100%; padding:12px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px; resize: vertical; font-family: inherit;"></textarea>
                    </div>
                    <button type="submit" name="edit_announcement" class="btn-add" style="width:100%;">Update
                        Announcement</button>
                </form>
            </div>
        </div>


        <!-- Add Inventory Modal -->
        <div id="add-inventory-modal" class="modal">
            <div class="modal-content">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="color: var(--primary-color); font-family: 'Oswald', sans-serif; font-size: 1.8rem;">
                        Add
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
                    <h3 style="color: var(--primary-color); font-family: 'Oswald', sans-serif; font-size: 1.8rem;">
                        Edit
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
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Trainer
                            Name</label>
                        <input type="text" name="trainer_name" required
                            style="width:100%; padding:12px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px;">
                    </div>
                    <div style="margin-bottom: 25px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Trainer
                            Image</label>
                        <input type="file" name="trainer_image" accept="image/*" style="width:100%; color:#fff;">
                        <small style="color: var(--text-gray); display:block; margin-top:5px;">Upload a professional
                            photo for the trainer profile.</small>
                    </div>
                    <button type="submit" name="add_trainer" class="btn-add" style="width:100%;">Save
                        Trainer</button>
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
                    <p style="color: var(--text-gray); font-size: 0.85rem; margin-bottom: 5px;">Member's Question:
                    </p>
                    <p id="reply-question"
                        style="font-size: 0.9rem; line-height: 1.4; font-style: italic; color: #fff;">
                    </p>
                </div>
                <form method="POST">
                    <input type="hidden" name="reply_query" value="1">
                    <input type="hidden" name="query_id" id="reply-id">
                    <input type="hidden" name="user_email" id="reply-email">
                    <input type="hidden" name="user_name" id="reply-name">
                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Your
                            Reply</label>
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
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Trainer
                            Name</label>
                        <input type="text" name="trainer_name" id="edit-trainer-name" required
                            style="width:100%; padding:12px; background:rgba(0,0,0,0.3); border:1px solid #333; color:#fff; border-radius:8px;">
                    </div>
                    <div style="margin-bottom: 25px;">
                        <label style="display:block; margin-bottom: 8px; color: var(--text-gray);">Trainer Image
                            (Optional)</label>
                        <input type="file" name="trainer_image" accept="image/*" style="width:100%; color:#fff;">
                        <small style="color: var(--text-gray); display:block; margin-top:5px;">Upload a new photo
                            only
                            if you want to change the current one.</small>
                    </div>
                    <button type="submit" name="edit_trainer" class="btn-add" style="width:100%;">Update
                        Trainer</button>
                </form>
            </div>
        </div>

        <!-- Add Member Modal -->
        <div id="add-member-modal" class="modal">
            <div class="modal-content"
                style="background: #1a1a2e; border: 1px solid rgba(255,255,255,0.05); padding: 40px; border-radius: 20px;">
                <h3 style="margin-bottom: 35px; text-align: center; color: #fff; font-size: 2rem; font-weight: 700;">
                    Add New Member</h3>
                <form method="POST" autocomplete="off">
                    <input type="hidden" name="add_member" value="1">

                    <!-- Dummy fields to trick browser autofill -->
                    <input type="text" style="display:none">
                    <input type="password" style="display:none">

                    <div class="form-group">
                        <label style="color: #fff; font-weight: 500; margin-bottom: 10px; display: block;">Full
                            Name</label>
                        <input type="text" name="full_name" class="form-control" required
                            placeholder="Member's full name" autocomplete="off"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 15px; border-radius: 10px;">
                    </div>
                    <div class="form-group">
                        <label style="color: #fff; font-weight: 500; margin-bottom: 10px; display: block;">Email
                            Address</label>
                        <input type="email" name="email" class="form-control" required placeholder="member@gmail.com"
                            autocomplete="off"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 15px; border-radius: 10px;">
                    </div>
                    <div class="form-group">
                        <label
                            style="color: #fff; font-weight: 500; margin-bottom: 10px; display: block;">Password</label>
                        <div class="pass-wrapper">
                            <input type="password" name="password" id="add-pass" class="form-control" required
                                placeholder="Minimum 6 characters" autocomplete="new-password" minlength="6"
                                style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 15px; border-radius: 10px;">
                            <i class="fa-solid fa-eye pass-toggle" onclick="togglePass('add-pass', this)"
                                style="right: 15px;"></i>
                        </div>
                    </div>
                    <div style="display: flex; gap: 15px; margin-top: 40px;">
                        <button type="submit" class="btn-action-modal"
                            style="flex: 2; font-size: 1.1rem; padding: 18px; border-radius: 15px;">Create
                            Account</button>
                        <button type="button" class="btn-action-modal"
                            style="flex: 1; background: #333; color: #fff; font-size: 1.1rem; padding: 18px; border-radius: 15px;"
                            onclick="closeModal('add-member-modal')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Plan Modal -->
        <div id="add-plan-modal" class="modal">
            <div class="modal-content" style="max-width: 500px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="color: var(--primary-color); font-family: 'Oswald', sans-serif;">Add New Plan</h3>
                    <span onclick="closeModal('add-plan-modal')"
                        style="cursor:pointer; font-size:1.5rem; color:#fff;">&times;</span>
                </div>
                <form method="POST">
                    <input type="hidden" name="add_plan" value="1">

                    <div class="form-group">
                        <label>Plan Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Pro"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; color: #fff;">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Monthly Price (₹)</label>
                            <input type="number" name="price_monthly" class="form-control" required step="0.01"
                                style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; color: #fff;">
                        </div>
                        <div class="form-group">
                            <label>Yearly Price (₹)</label>
                            <input type="number" name="price_yearly" class="form-control" required step="0.01"
                                style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px; color: #fff;">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 10px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" name="is_popular" style="width: 18px; height: 18px;">
                            <span style="color: #fff;">Mark as "Popular"</span>
                        </label>
                    </div>

                    <div style="margin-top: 20px; text-align: right;">
                        <button type="submit" class="btn-action-modal">Create Plan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Plan Modal -->
        <div id="edit-plan-modal" class="modal">
            <div class="modal-content"
                style="max-width: 850px; width: 95%; max-height: 90vh; overflow-y: auto; background: #1a1a2e; padding: 40px; border-radius: 20px; scrollbar-width: thin; scrollbar-color: var(--primary-color) #1a1a2e;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
                    <h3 id="edit-plan-title"
                        style="color: var(--primary-color); font-family: 'Oswald', sans-serif; font-size: 2rem;">Edit
                        Plan</h3>
                    <span onclick="closeModal('edit-plan-modal')"
                        style="cursor:pointer; font-size:1.8rem; color:#fff;">&times;</span>
                </div>
                <form method="POST" onsubmit="checkPendingAttribute()">
                    <input type="hidden" name="update_plan" value="1">
                    <input type="hidden" name="plan_id" id="edit-plan-id">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label style="color: #fff; display: block; margin-bottom: 8px;">Plan Name</label>
                            <input type="text" name="name" id="edit-plan-name" class="form-control" required
                                style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px;">
                        </div>
                        <div class="form-group" style="display: flex; align-items: flex-end; padding-bottom: 12px;">
                            <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; color: #fff;">
                                <input type="checkbox" name="is_popular" id="edit-plan-popular"
                                    style="width: 22px; height: 22px; cursor: pointer;">
                                Set as Popular (Badge)
                            </label>
                        </div>
                        <div class="form-group">
                            <label style="color: #fff; display: block; margin-bottom: 8px;">Monthly Price (₹)</label>
                            <input type="number" name="price_monthly" id="edit-plan-monthly" class="form-control"
                                required
                                style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px;">
                        </div>
                        <div class="form-group">
                            <label style="color: #fff; display: block; margin-bottom: 8px;">Yearly Price (₹)</label>
                            <input type="number" name="price_yearly" id="edit-plan-yearly" class="form-control" required
                                style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 12px;">
                        </div>
                    </div>

                    <h4
                        style="margin: 30px 0 15px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; color: #fff; font-family: 'Oswald', sans-serif; letter-spacing: 1px;">
                        PLAN ATTRIBUTES & FEATURES</h4>

                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 30px;">
                        <input type="hidden" name="hidden_features" id="edit-plan-hidden-json">

                        <div id="div-gym_access"
                            style="display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position:relative;">
                            <input type="checkbox" name="gym_access" id="feat-gym"
                                style="width: 18px; height: 18px; cursor:pointer;">
                            <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                                <input type="text" name="lab_gym_access" id="lab-gym" value="Gym Access"
                                    style="background:transparent; border:none; color:#eee; font-size:0.85rem; width:100%; outline:none;">
                                <div style="display:flex; gap:5px;">
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Monthly</small>
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Yearly</small>
                                </div>
                            </div>
                            <div style="display:flex; gap:4px;">
                                <button type="button" onclick="document.getElementById('lab-gym').focus()"
                                    style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button type="button" id="btn-del-gym_access"
                                    onclick="toggleStandardFeature('gym_access')"
                                    style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div id="div-free_locker"
                            style="display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position:relative;">
                            <input type="checkbox" name="free_locker" id="feat-locker"
                                style="width: 18px; height: 18px; cursor:pointer;">
                            <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                                <input type="text" name="lab_free_locker" id="lab-locker" value="Free Locker"
                                    style="background:transparent; border:none; color:#eee; font-size:0.85rem; width:100%; outline:none;">
                                <div style="display:flex; gap:5px;">
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Monthly</small>
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Yearly</small>
                                </div>
                            </div>
                            <div style="display:flex; gap:4px;">
                                <button type="button" onclick="document.getElementById('lab-locker').focus()"
                                    style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button type="button" id="btn-del-free_locker"
                                    onclick="toggleStandardFeature('free_locker')"
                                    style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div id="div-group_class"
                            style="display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position:relative;">
                            <input type="checkbox" name="group_class" id="feat-group"
                                style="width: 18px; height: 18px; cursor:pointer;">
                            <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                                <input type="text" name="lab_group_class" id="lab-group" value="Group Class"
                                    style="background:transparent; border:none; color:#eee; font-size:0.85rem; width:100%; outline:none;">
                                <div style="display:flex; gap:5px;">
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Monthly</small>
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Yearly</small>
                                </div>
                            </div>
                            <div style="display:flex; gap:4px;">
                                <button type="button" onclick="document.getElementById('lab-group').focus()"
                                    style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button type="button" id="btn-del-group_class"
                                    onclick="toggleStandardFeature('group_class')"
                                    style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div id="div-personal_trainer"
                            style="display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position:relative;">
                            <input type="checkbox" name="personal_trainer" id="feat-trainer"
                                style="width: 18px; height: 18px; cursor:pointer;">
                            <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                                <input type="text" name="lab_personal_trainer" id="lab-trainer" value="Personal Trainer"
                                    style="background:transparent; border:none; color:#eee; font-size:0.85rem; width:100%; outline:none;">
                                <div style="display:flex; gap:5px;">
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Monthly</small>
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Yearly</small>
                                </div>
                            </div>
                            <div style="display:flex; gap:4px;">
                                <button type="button" onclick="document.getElementById('lab-trainer').focus()"
                                    style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button type="button" id="btn-del-personal_trainer"
                                    onclick="toggleStandardFeature('personal_trainer')"
                                    style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div id="div-protein_drinks_monthly"
                            style="display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position:relative;">
                            <input type="checkbox" name="protein_drinks_monthly" id="feat-protein-m"
                                style="width: 18px; height: 18px; cursor:pointer;">
                            <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                                <input type="text" name="lab_protein_drinks_monthly" id="lab-protein-m"
                                    value="Protein Drinks (Monthly)"
                                    style="background:transparent; border:none; color:#eee; font-size:0.85rem; width:100%; outline:none;">
                                <div style="display:flex; gap:5px;">
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Monthly</small>
                                </div>
                            </div>
                            <div style="display:flex; gap:4px;">
                                <button type="button" onclick="document.getElementById('lab-protein-m').focus()"
                                    style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button type="button" id="btn-del-protein_drinks_monthly"
                                    onclick="toggleStandardFeature('protein_drinks_monthly')"
                                    style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div id="div-protein_drinks_yearly"
                            style="display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position:relative;">
                            <input type="checkbox" name="protein_drinks_yearly" id="feat-protein-y"
                                style="width: 18px; height: 18px; cursor:pointer;">
                            <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                                <input type="text" name="lab_protein_drinks_yearly" id="lab-protein-y"
                                    value="Protein Drinks (Yearly)"
                                    style="background:transparent; border:none; color:#eee; font-size:0.85rem; width:100%; outline:none;">
                                <div style="display:flex; gap:5px;">
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Yearly</small>
                                </div>
                            </div>
                            <div style="display:flex; gap:4px;">
                                <button type="button" onclick="document.getElementById('lab-protein-y').focus()"
                                    style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button type="button" id="btn-del-protein_drinks_yearly"
                                    onclick="toggleStandardFeature('protein_drinks_yearly')"
                                    style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div id="div-customized_workout_plan"
                            style="display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position:relative;">
                            <input type="checkbox" name="customized_workout_plan" id="feat-workout"
                                style="width: 18px; height: 18px; cursor:pointer;">
                            <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                                <input type="text" name="lab_customized_workout_plan" id="lab-workout"
                                    value="Customized Workout Plan"
                                    style="background:transparent; border:none; color:#eee; font-size:0.85rem; width:100%; outline:none;">
                                <div style="display:flex; gap:5px;">
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Monthly</small>
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Yearly</small>
                                </div>
                            </div>
                            <div style="display:flex; gap:4px;">
                                <button type="button" onclick="document.getElementById('lab-workout').focus()"
                                    style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button type="button" id="btn-del-customized_workout_plan"
                                    onclick="toggleStandardFeature('customized_workout_plan')"
                                    style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div id="div-diet_consultation_yearly"
                            style="display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position:relative;">
                            <input type="checkbox" name="diet_consultation_yearly" id="feat-diet"
                                style="width: 18px; height: 18px; cursor:pointer;">
                            <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                                <input type="text" name="lab_diet_consultation_yearly" id="lab-diet"
                                    value="Diet Consultation (Yearly)"
                                    style="background:transparent; border:none; color:#eee; font-size:0.85rem; width:100%; outline:none;">
                                <div style="display:flex; gap:5px;">
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Yearly</small>
                                </div>
                            </div>
                            <div style="display:flex; gap:4px;">
                                <button type="button" onclick="document.getElementById('lab-diet').focus()"
                                    style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button type="button" id="btn-del-diet_consultation_yearly"
                                    onclick="toggleStandardFeature('diet_consultation_yearly')"
                                    style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div id="div-personal_locker_yearly"
                            style="display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position:relative;">
                            <input type="checkbox" name="personal_locker_yearly" id="feat-p-locker"
                                style="width: 18px; height: 18px; cursor:pointer;">
                            <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                                <input type="text" name="lab_personal_locker_yearly" id="lab-p-locker"
                                    value="Personal Locker (Yearly)"
                                    style="background:transparent; border:none; color:#eee; font-size:0.85rem; width:100%; outline:none;">
                                <div style="display:flex; gap:5px;">
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Yearly</small>
                                </div>
                            </div>
                            <div style="display:flex; gap:4px;">
                                <button type="button" onclick="document.getElementById('lab-p-locker').focus()"
                                    style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button type="button" id="btn-del-personal_locker_yearly"
                                    onclick="toggleStandardFeature('personal_locker_yearly')"
                                    style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div id="div-guest_pass_yearly"
                            style="display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position:relative;">
                            <input type="checkbox" name="guest_pass_yearly" id="feat-guest"
                                style="width: 18px; height: 18px; cursor:pointer;">
                            <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                                <input type="text" name="lab_guest_pass_yearly" id="lab-guest"
                                    value="1 Guest Pass/Mo (Yearly)"
                                    style="background:transparent; border:none; color:#eee; font-size:0.85rem; width:100%; outline:none;">
                                <div style="display:flex; gap:5px;">
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Yearly</small>
                                </div>
                            </div>
                            <div style="display:flex; gap:4px;">
                                <button type="button" onclick="document.getElementById('lab-guest').focus()"
                                    style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button type="button" id="btn-del-guest_pass_yearly"
                                    onclick="toggleStandardFeature('guest_pass_yearly')"
                                    style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div id="div-nutrition_guide_yearly"
                            style="display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position:relative;">
                            <input type="checkbox" name="nutrition_guide_yearly" id="feat-nutrition"
                                style="width: 18px; height: 18px; cursor:pointer;">
                            <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                                <input type="text" name="lab_nutrition_guide_yearly" id="lab-nutrition"
                                    value="Nutrition Guide (Yearly)"
                                    style="background:transparent; border:none; color:#eee; font-size:0.85rem; width:100%; outline:none;">
                                <div style="display:flex; gap:5px;">
                                    <small
                                        style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Yearly</small>
                                </div>
                            </div>
                            <div style="display:flex; gap:4px;">
                                <button type="button" onclick="document.getElementById('lab-nutrition').focus()"
                                    style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button type="button" id="btn-del-nutrition_guide_yearly"
                                    onclick="toggleStandardFeature('nutrition_guide_yearly')"
                                    style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i
                                        class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </div>
                        <div id="custom-attributes-container" style="display: contents;">
                            <!-- Custom attributes will be injected here -->
                        </div>
                    </div>

                    <input type="hidden" name="custom_attributes" id="edit-plan-custom-json">

                    <div
                        style="background: rgba(255,255,255,0.02); padding: 15px; border-radius: 12px; border: 1px dashed rgba(255,255,255,0.1);">
                        <p style="color: var(--text-gray); font-size: 0.85rem; margin-bottom: 10px;">Add New Attribute
                        </p>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="text" id="new-attr-name" placeholder="Feature name (e.g. Free WiFi)"
                                class="form-control"
                                style="flex: 2; height: 40px; font-size: 0.9rem; background: rgba(0,0,0,0.2);">
                            <label
                                style="color: #eee; font-size: 0.8rem; display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                <input type="checkbox" id="new-attr-monthly" style="width: 16px; height: 16px;"> Monthly
                            </label>
                            <label
                                style="color: #eee; font-size: 0.8rem; display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                <input type="checkbox" id="new-attr-yearly" style="width: 16px; height: 16px;"> Yearly
                            </label>
                            <button type="button" id="btn-add-attr" onclick="addNewAttribute()"
                                style="background: var(--primary-color); color: #000; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 0.8rem;">Add</button>
                        </div>
                    </div>

                    <button type="submit" class="btn-action-modal"
                        style="margin-top: 35px; background: var(--primary-color); color: #000; font-size: 1.15rem; padding: 18px;">Update
                        Plan Attributes</button>
                </form>
            </div>
        </div>

        <!-- Edit Member Modal -->
        <div id="edit-member-modal" class="modal">
            <div class="modal-content"
                style="background: #1a1a2e; border: 1px solid rgba(255,255,255,0.05); padding: 40px;">
                <h3 style="margin-bottom: 25px; text-align: center; color: #fff; font-size: 1.8rem; font-weight: 700;">
                    Edit Member Details</h3>
                <img id="edit-member-preview" src="" alt="Profile" class="edit-member-img"
                    style="width: 80px; height: 80px; margin-bottom: 20px;">
                <form method="POST" autocomplete="off">
                    <input type="hidden" name="update_member" value="1">
                    <input type="hidden" name="member_id" id="edit-member-id">
                    <div class="form-group">
                        <label style="color: #fff; font-weight: 500; margin-bottom: 10px;">Full Name</label>
                        <input type="text" name="full_name" id="edit-member-name" class="form-control" required
                            style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); padding: 15px;">
                    </div>
                    <div class="form-group">
                        <label style="color: #fff; font-weight: 500; margin-bottom: 10px;">Email Address</label>
                        <input type="email" name="email" id="edit-member-email" class="form-control" required
                            style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); padding: 15px;">
                    </div>
                    <div class="form-group">
                        <label style="color: #fff; font-weight: 500; margin-bottom: 10px;">Reset Password (blank to
                            keep
                            current)</label>
                        <div class="pass-wrapper">
                            <input type="password" name="password" id="edit-pass" class="form-control"
                                placeholder="New password" minlength="6"
                                style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); padding: 15px;">
                            <i class="fa-solid fa-eye pass-toggle" onclick="togglePass('edit-pass', this)"
                                style="right: 15px;"></i>
                        </div>
                    </div>
                    <div style="display: flex; gap: 15px; margin-top: 40px;">
                        <button type="submit" class="btn-action-modal"
                            style="flex: 2.5; font-size: 1.1rem; padding: 15px;">Save Changes</button>
                        <button type="button" class="btn-action-modal"
                            style="flex: 1; background: #333; color: #fff; font-size: 1.1rem; padding: 15px;"
                            onclick="closeModal('edit-member-modal')">Cancel</button>
                    </div>
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

        function openEditAnnouncementModal(ann) {
            document.getElementById('edit-ann_id').value = ann.id;
            document.getElementById('edit-ann_title').value = ann.title;
            document.getElementById('edit-ann_message').value = ann.message;
            document.getElementById('edit-announcement-modal').style.display = 'flex';
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

        function openAddMemberModal() {
            document.getElementById('add-member-modal').style.display = 'flex';
        }

        function openEditMemberModal(member) {
            document.getElementById('edit-member-id').value = member.id;
            document.getElementById('edit-member-name').value = member.full_name;
            document.getElementById('edit-member-email').value = member.email;
            document.getElementById('edit-member-preview').src = member.profile_image ? member.profile_image : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(member.full_name) + '&background=ceff00&color=1a1a2e';
            document.getElementById('edit-member-modal').style.display = 'flex';
        }

        let currentCustomAttributes = [];

        function openEditPlanModal(plan) {
            document.getElementById('edit-plan-id').value = plan.id;
            document.getElementById('edit-plan-name').value = plan.name;
            document.getElementById('edit-plan-title').innerText = 'Edit Plan: ' + plan.name;
            document.getElementById('edit-plan-monthly').value = plan.price_monthly;
            document.getElementById('edit-plan-yearly').value = plan.price_yearly;
            document.getElementById('edit-plan-popular').checked = plan.is_popular == 1;

            // Features
            document.getElementById('feat-gym').checked = plan.gym_access == 1;
            document.getElementById('feat-locker').checked = plan.free_locker == 1;
            document.getElementById('feat-group').checked = plan.group_class == 1;
            document.getElementById('feat-trainer').checked = plan.personal_trainer == 1;
            document.getElementById('feat-protein-m').checked = plan.protein_drinks_monthly == 1;
            document.getElementById('feat-protein-y').checked = plan.protein_drinks_yearly == 1;
            document.getElementById('feat-workout').checked = plan.customized_workout_plan == 1;
            document.getElementById('feat-diet').checked = plan.diet_consultation_yearly == 1;
            document.getElementById('feat-p-locker').checked = plan.personal_locker_yearly == 1;
            document.getElementById('feat-guest').checked = plan.guest_pass_yearly == 1;
            document.getElementById('feat-nutrition').checked = plan.nutrition_guide_yearly == 1;

            let currentHiddenFeatures = new Set();

            function toggleStandardFeature(featureId) {
                const container = document.getElementById('div-' + featureId);
                const btn = document.getElementById('btn-del-' + featureId);

                if (currentHiddenFeatures.has(featureId)) {
                    // Restore
                    currentHiddenFeatures.delete(featureId);
                    container.style.opacity = '1';
                    container.style.filter = 'none';
                    btn.innerHTML = '<i class="fa-solid fa-trash-can"></i>';
                    btn.style.color = '#ff4444';
                } else {
                    // Hide
                    currentHiddenFeatures.add(featureId);
                    container.style.opacity = '0.4';
                    container.style.filter = 'grayscale(1)';
                    btn.innerHTML = '<i class="fa-solid fa-rotate-left"></i>';
                    btn.style.color = 'var(--primary-color)';
                }
                document.getElementById('edit-plan-hidden-json').value = JSON.stringify(Array.from(currentHiddenFeatures));
            }

            // Custom Features
            try {
                currentCustomAttributes = plan.custom_attributes ? JSON.parse(plan.custom_attributes) : [];
            } catch (e) {
                currentCustomAttributes = [];
            }
            renderCustomAttributes();

            // Feature Labels
            try {
                const labels = plan.feature_labels ? JSON.parse(plan.feature_labels) : {};
                const hidden = plan.hidden_features ? JSON.parse(plan.hidden_features) : [];
                currentHiddenFeatures = new Set(hidden);
                document.getElementById('edit-plan-hidden-json').value = JSON.stringify(Array.from(currentHiddenFeatures));

                const standardFeatures = [
                    'gym_access', 'free_locker', 'group_class', 'personal_trainer',
                    'protein_drinks_monthly', 'protein_drinks_yearly', 'customized_workout_plan',
                    'diet_consultation_yearly', 'personal_locker_yearly', 'guest_pass_yearly', 'nutrition_guide_yearly'
                ];

                standardFeatures.forEach(feat => {
                    const labelVal = labels[feat] || formatLabel(feat);
                    const el = document.getElementById('lab-' + getShortId(feat));
                    if (el) el.value = labelVal;

                    // Update visual state
                    const container = document.getElementById('div-' + feat);
                    const btn = document.getElementById('btn-del-' + feat);
                    if (container && btn) {
                        if (currentHiddenFeatures.has(feat)) {
                            container.style.opacity = '0.4';
                            container.style.filter = 'grayscale(1)';
                            btn.innerHTML = '<i class="fa-solid fa-rotate-left"></i>';
                            btn.style.color = 'var(--primary-color)';
                        } else {
                            container.style.opacity = '1';
                            container.style.filter = 'none';
                            btn.innerHTML = '<i class="fa-solid fa-trash-can"></i>';
                            btn.style.color = '#ff4444';
                        }
                    }
                });

            } catch (e) {
                console.error("Error parsing feature data", e);
            }

            document.getElementById('edit-plan-modal').style.display = 'flex';
        }

        function formatLabel(slug) {
            return slug.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
        }

        function getShortId(slug) {
            const map = {
                'gym_access': 'gym', 'free_locker': 'locker', 'group_class': 'group',
                'personal_trainer': 'trainer', 'protein_drinks_monthly': 'protein-m',
                'protein_drinks_yearly': 'protein-y', 'customized_workout_plan': 'workout',
                'diet_consultation_yearly': 'diet', 'personal_locker_yearly': 'p-locker',
                'guest_pass_yearly': 'guest', 'nutrition_guide_yearly': 'nutrition'
            };
            return map[slug] || slug;
        }

        let editingCustomIndex = -1;

        function renderCustomAttributes() {
            const container = document.getElementById('custom-attributes-container');
            container.innerHTML = '';

            currentCustomAttributes.forEach((attr, index) => {
                const div = document.createElement('div');
                div.style.cssText = 'display:flex; align-items:center; gap:8px; padding: 8px; background: rgba(255,255,255,0.03); border-radius: 8px; transition: 0.3s; position: relative;';
                div.innerHTML = `
                    <input type="checkbox" ${attr.included !== 0 ? 'checked' : ''} onchange="toggleCustomInclude(${index}, this.checked)" style="width: 18px; height: 18px; cursor:pointer;">
                    <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
                        <span style="color:#eee; font-size:0.85rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="${attr.name}">${attr.name}</span>
                        <div style="display:flex; gap:5px;">
                            ${attr.monthly ? '<small style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Monthly</small>' : ''}
                            ${attr.yearly ? '<small style="color:var(--primary-color); font-size:0.6rem; opacity:0.8;">Yearly</small>' : ''}
                        </div>
                    </div>
                    <div style="display:flex; gap:4px;">
                        <button type="button" onclick="editCustomAttribute(${index})" style="background:transparent; border:none; color:var(--primary-color); cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i class="fa-solid fa-pen"></i></button>
                        <button type="button" onclick="removeCustomAttribute(${index})" style="background:transparent; border:none; color:#ff4444; cursor:pointer; padding:2px; font-size:0.8rem; opacity:0.6; transition:0.3s;"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                `;

                // Add hover effect for buttons
                div.onmouseover = () => {
                    div.querySelectorAll('button').forEach(b => b.style.opacity = '1');
                };
                div.onmouseout = () => {
                    div.querySelectorAll('button').forEach(b => b.style.opacity = '0.6');
                };

                container.appendChild(div);
            });

            document.getElementById('edit-plan-custom-json').value = JSON.stringify(currentCustomAttributes);
        }

        function toggleCustomInclude(index, isChecked) {
            currentCustomAttributes[index].included = isChecked ? 1 : 0;
            document.getElementById('edit-plan-custom-json').value = JSON.stringify(currentCustomAttributes);
        }

        function editCustomAttribute(index) {
            const attr = currentCustomAttributes[index];
            document.getElementById('new-attr-name').value = attr.name;
            document.getElementById('new-attr-monthly').checked = attr.monthly == 1;
            document.getElementById('new-attr-yearly').checked = attr.yearly == 1;

            editingCustomIndex = index;
            const btn = document.getElementById('btn-add-attr');
            if (btn) {
                btn.innerText = 'Update';
                btn.style.background = '#fff';
            }
            document.getElementById('new-attr-name').focus();
        }

        function addNewAttribute() {
            const nameInput = document.getElementById('new-attr-name');
            const monthlyCheck = document.getElementById('new-attr-monthly');
            const yearlyCheck = document.getElementById('new-attr-yearly');
            const btn = document.getElementById('btn-add-attr');

            if (!nameInput.value.trim()) return alert('Please enter a feature name');

            const newAttr = {
                name: nameInput.value.trim(),
                monthly: monthlyCheck.checked ? 1 : 0,
                yearly: yearlyCheck.checked ? 1 : 0,
                included: 1
            };

            if (editingCustomIndex > -1) {
                // Update existing
                newAttr.included = currentCustomAttributes[editingCustomIndex].included;
                currentCustomAttributes[editingCustomIndex] = newAttr;
                editingCustomIndex = -1;
                if (btn) {
                    btn.innerText = 'Add';
                    btn.style.background = 'var(--primary-color)';
                }
            } else {
                // Add new
                currentCustomAttributes.push(newAttr);
            }

            nameInput.value = '';
            monthlyCheck.checked = false;
            yearlyCheck.checked = false;
            renderCustomAttributes();
        }

        function removeCustomAttribute(index) {
            currentCustomAttributes.splice(index, 1);
            if (index === editingCustomIndex) {
                editingCustomIndex = -1;
                document.getElementById('new-attr-name').value = '';
                document.getElementById('new-attr-monthly').checked = false;
                document.getElementById('new-attr-yearly').checked = false;
                const btn = document.getElementById('btn-add-attr');
                if (btn) {
                    btn.innerText = 'Add';
                    btn.style.background = 'var(--primary-color)';
                }
            }
            renderCustomAttributes();
        }

        function checkPendingAttribute() {
            const nameInput = document.getElementById('new-attr-name');
            if (nameInput && nameInput.value.trim() !== '') {
                addNewAttribute();
            }
        }

        function togglePass(id, icon) {
            const input = document.getElementById(id);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        function searchMembers() {
            let input = document.getElementById('member-search');
            let filter = input.value.toLowerCase();
            let table = document.querySelector('#users .card:first-child .data-table');
            let tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                let td1 = tr[i].getElementsByTagName('td')[0];
                let td2 = tr[i].getElementsByTagName('td')[1];
                if (td1 || td2) {
                    let txtValue1 = td1.textContent || td1.innerText;
                    let txtValue2 = td2.textContent || td2.innerText;
                    if (txtValue1.toLowerCase().indexOf(filter) > -1 || txtValue2.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }

        function toggleHistory(uid) {
            const row = document.getElementById(`history-${uid}`);
            const icon = document.getElementById(`icon-${uid}`);
            if (row.style.display === 'none') {
                row.style.display = 'table-row';
                icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
            } else {
                row.style.display = 'none';
                icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            }
        }

        function searchPayments() {
            let input = document.getElementById('payment-search');
            let filter = input.value.toLowerCase();
            let table = document.querySelector('#users .card:last-child .data-table');
            let tr = table.querySelectorAll('tr[style*="border-bottom"]');

            tr.forEach(row => {
                let name = row.querySelector('span').innerText.toLowerCase();
                let email = row.querySelector('small').innerText.toLowerCase();
                if (name.includes(filter) || email.includes(filter)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        function switchUserTab(tabName, btn) {
            // Remove active from all tabs
            document.querySelectorAll('.user-tab').forEach(t => t.classList.remove('active'));
            // Remove active from all contents
            document.querySelectorAll('.user-tab-content').forEach(c => c.classList.remove('active'));

            // Add active to current
            btn.classList.add('active');
            document.getElementById('tab-' + tabName).classList.add('active');
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