<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] != "shop_staff") {
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

// HANDLE TOKEN ACCEPTANCE
if (isset($_GET['accept_token'])) {
    $id = (int) $_GET['accept_token'];
    if (mysqli_query($link, "UPDATE transactions SET token_accepted = 1 WHERE id = $id")) {
        $_SESSION['message'] = "Token accepted. Item status updated to Released.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating token status.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_shop_staff.php");
    exit;
}

// HANDLE STORE ORDER DELETION
if (isset($_GET['delete_store_order'])) {
    $tid = (int) $_GET['delete_store_order'];
    if (mysqli_query($link, "DELETE FROM transactions WHERE id = $tid AND token_number IS NOT NULL")) {
        $_SESSION['message'] = "Store order deleted successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting store order.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_shop_staff.php");
    exit;
}


// Fetch Store Orders
$store_orders_res = mysqli_query($link, "SELECT t.*, u.full_name, u.email as user_email 
                                         FROM transactions t 
                                         JOIN users u ON t.user_id = u.id 
                                         WHERE t.token_number IS NOT NULL 
                                         ORDER BY t.created_at DESC");

// Fetch current user data
$user_id = $_SESSION["id"];
$user_stmt = mysqli_prepare($link, "SELECT full_name, email, profile_image, created_at FROM users WHERE id = ?");
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user_data = mysqli_stmt_get_result($user_stmt)->fetch_assoc();

// Handle Profile Update
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
            $user_data['profile_image'] = $target_file;
        }
    }

    // Handle Password
    $pass_query = "";
    if (!empty($_POST['new_password'])) {
        $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $visible_password = mysqli_real_escape_string($link, $_POST['new_password']);
        $pass_query = ", password = '$new_pass', visible_password = '$visible_password'";
    }

    // Check if email is available
    $check_email = mysqli_query($link, "SELECT id FROM users WHERE email = '$email' AND id != $user_id");
    if (mysqli_num_rows($check_email) > 0) {
        $_SESSION['message'] = "Error: Email is already registered to another account.";
        $_SESSION['message_type'] = "error";
    } else {
        $update_sql = "UPDATE users SET full_name = '$full_name', email = '$email' $pass_query WHERE id = $user_id";
        if (mysqli_query($link, $update_sql)) {
            $_SESSION['full_name'] = $full_name;
            $_SESSION['message'] = "Profile updated successfully!";
            $_SESSION['message_type'] = "success";
            header("Location: dashboard_shop_staff.php#profile");
            exit;
        } else {
            $_SESSION['message'] = "Error updating profile.";
            $_SESSION['message_type'] = "error";
        }
    }
}

// --- STORE CATEGORY HANDLERS ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_store_category'])) {
    $name = mysqli_real_escape_string($link, trim($_POST['cat_name']));
    $desc = mysqli_real_escape_string($link, trim($_POST['cat_desc']));
    $image_path = "assets/images/default_category.png";

    if (isset($_FILES['cat_image']) && $_FILES['cat_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $file_ext = strtolower(pathinfo($_FILES["cat_image"]["name"], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed)) {
            $_SESSION['message'] = "Invalid category image format.";
            $_SESSION['message_type'] = "error";
            header("Location: dashboard_shop_staff.php#gym-store-mgmt");
            exit;
        }

        $target_dir = "assets/images/store/";
        if (!file_exists($target_dir))
            mkdir($target_dir, 0777, true);
        $file_name = "cat_" . time() . "_" . uniqid() . "." . $file_ext;
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["cat_image"]["tmp_name"], $target_file)) {
            $image_path = $target_file;
        }
    }

    $sql = "INSERT INTO store_categories (name, description, image) VALUES ('$name', '$desc', '$image_path')";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Category added successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error adding category.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: dashboard_shop_staff.php#gym-store-mgmt");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_store_category'])) {
    $id = (int) $_POST['cat_id'];
    $name = mysqli_real_escape_string($link, trim($_POST['cat_name']));
    $desc = mysqli_real_escape_string($link, trim($_POST['cat_desc']));
    $image_update = "";

    if (isset($_FILES['cat_image']) && $_FILES['cat_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $file_ext = strtolower(pathinfo($_FILES["cat_image"]["name"], PATHINFO_EXTENSION));
        if (in_array($file_ext, $allowed)) {
            $target_dir = "assets/images/store/";
            if (!file_exists($target_dir))
                mkdir($target_dir, 0777, true);
            $file_name = "cat_" . time() . "_" . uniqid() . "." . $file_ext;
            $target_file = $target_dir . $file_name;
            if (move_uploaded_file($_FILES["cat_image"]["tmp_name"], $target_file)) {
                $image_update = ", image = '$target_file'";
            }
        }
    }

    $sql = "UPDATE store_categories SET name='$name', description='$desc' $image_update WHERE id=$id";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Category updated successfully!";
        $_SESSION['message_type'] = "success";
    }
    header("Location: dashboard_shop_staff.php#gym-store-mgmt");
    exit;
}

if (isset($_GET['delete_store_category'])) {
    $id = (int) $_GET['delete_store_category'];
    if (mysqli_query($link, "DELETE FROM store_categories WHERE id = $id")) {
        $_SESSION['message'] = "Category deleted successfully!";
        $_SESSION['message_type'] = "success";
    }
    header("Location: dashboard_shop_staff.php#gym-store-mgmt");
    exit;
}

// --- STORE PRODUCT HANDLERS ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_store_product'])) {
    $cat_id = (int) $_POST['prod_cat_id'];
    $name = mysqli_real_escape_string($link, trim($_POST['prod_name']));
    $price = (float) $_POST['prod_price'];
    $stock = (int) $_POST['stock_count'];
    $image_path = "assets/images/default_product.png";

    if (isset($_FILES['prod_image']) && $_FILES['prod_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $file_ext = strtolower(pathinfo($_FILES["prod_image"]["name"], PATHINFO_EXTENSION));
        if (in_array($file_ext, $allowed)) {
            $target_dir = "assets/images/store/";
            if (!file_exists($target_dir))
                mkdir($target_dir, 0777, true);
            $file_name = "prod_" . time() . "_" . uniqid() . "." . $file_ext;
            $target_file = $target_dir . $file_name;
            if (move_uploaded_file($_FILES["prod_image"]["tmp_name"], $target_file)) {
                $image_path = $target_file;
            }
        }
    }

    $sql = "INSERT INTO store_products (category_id, name, price, stock_count, image) VALUES ($cat_id, '$name', $price, $stock, '$image_path')";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Product added successfully!";
        $_SESSION['message_type'] = "success";
    }
    header("Location: dashboard_shop_staff.php#gym-store-mgmt");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_store_product'])) {
    $id = (int) $_POST['prod_id'];
    $cat_id = (int) $_POST['prod_cat_id'];
    $name = mysqli_real_escape_string($link, trim($_POST['prod_name']));
    $price = (float) $_POST['prod_price'];
    $stock = (int) $_POST['stock_count'];
    $image_update = "";

    if (isset($_FILES['prod_image']) && $_FILES['prod_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $file_ext = strtolower(pathinfo($_FILES["prod_image"]["name"], PATHINFO_EXTENSION));
        if (in_array($file_ext, $allowed)) {
            $target_dir = "assets/images/store/";
            if (!file_exists($target_dir))
                mkdir($target_dir, 0777, true);
            $file_name = "prod_" . time() . "_" . uniqid() . "." . $file_ext;
            $target_file = $target_dir . $file_name;
            if (move_uploaded_file($_FILES["prod_image"]["tmp_name"], $target_file)) {
                $image_update = ", image = '$target_file'";
            }
        }
    }

    $sql = "UPDATE store_products SET category_id=$cat_id, name='$name', price=$price, stock_count=$stock $image_update WHERE id=$id";
    if (mysqli_query($link, $sql)) {
        $_SESSION['message'] = "Product updated successfully!";
        $_SESSION['message_type'] = "success";
    }
    header("Location: dashboard_shop_staff.php#gym-store-mgmt");
    exit;
}

if (isset($_GET['delete_store_product'])) {
    $id = (int) $_GET['delete_store_product'];
    if (mysqli_query($link, "DELETE FROM store_products WHERE id = $id")) {
        $_SESSION['message'] = "Product deleted successfully!";
        $_SESSION['message_type'] = "success";
    }
    header("Location: dashboard_shop_staff.php#gym-store-mgmt");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title>Shop Staff Dashboard - BeFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Roboto:wght@400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #ceff00;
            --secondary-color: #1a1a2e;
            --bg-dark: #080810;
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

        .main-content {
            flex-grow: 1;
            padding: 30px;
            overflow-y: auto;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .card-header {
            margin-bottom: 20px;
        }

        .card-header h3 {
            font-family: 'Oswald', sans-serif;
            font-weight: 500;
            color: #fff;
            font-size: 1.2rem;
            letter-spacing: 0.5px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .data-table th {
            text-align: left;
            padding: 15px 10px;
            color: var(--text-gray);
            font-weight: 500;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .data-table td {
            padding: 15px 10px;
            color: #fff;
        }

        .btn-action {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.2s;
            border: none;
            font-size: 0.85rem;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
        }

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-success {
            background: rgba(0, 255, 136, 0.15);
            color: #00ff88;
        }

        .badge-warning {
            background: rgba(255, 193, 7, 0.15);
            color: #ffc107;
        }

        .dashboard-section {
            display: none;
        }

        .slider-table {
            max-height: 450px;
            overflow-y: auto;
            border-radius: 12px;
            padding-right: 5px;
        }

        .slider-table::-webkit-scrollbar {
            width: 8px;
        }

        .slider-table::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .slider-table::-webkit-scrollbar-thumb {
            background: rgba(206, 255, 0, 0.2);
            border-radius: 10px;
            border: 2px solid transparent;
            background-clip: content-box;
        }

        .slider-table::-webkit-scrollbar-thumb:hover {
            background: var(--primary-color);
        }

        .dashboard-section.active {
            display: block;
            animation: fadeIn 0.4s ease-out;
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
            bottom: 5px;
            right: 5px;
            background: var(--primary-color);
            color: #000;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.3s;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.3);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.1);
            outline: none;
        }

        .form-control:focus {
            border-color: var(--primary-color);
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <?php if (!empty($message)): ?>
        <div style="position: fixed; top: 20px; right: 20px; background: <?php echo $message_type == 'success' ? '#27ae60' : '#e74c3c'; ?>; color: #fff; padding: 15px 25px; border-radius: 8px; z-index: 10000; box-shadow: 0 5px 15px rgba(0,0,0,0.3); animation: slideIn 0.5s ease-out;"
            id="toast">
            <i class="fa-solid <?php echo $message_type == 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"
                style="margin-right: 10px;"></i>
            <?php echo $message; ?>
        </div>
        <script>setTimeout(() => { document.getElementById('toast').style.opacity = '0'; setTimeout(() => document.getElementById('toast').remove(), 500); }, 3000);</script>
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
        <a href="index.php" class="logo"><i class="fa-solid fa-dumbbell"></i> GYMFIT SHOP</a>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fa-solid fa-house"></i> Back to Website</a></li>
            <li style="border-bottom: 1px solid rgba(255,255,255,0.05); margin-bottom: 10px; padding-bottom: 10px;">
            </li>
            <li><a href="#" class="active" onclick="showSection('orders')"><i class="fa-solid fa-cart-shopping"></i>
                    Store Orders</a></li>
            <li><a href="#" onclick="showSection('gym-store-mgmt')"><i class="fa-solid fa-shop"></i> Manage Store</a>
            </li>
            <li><a href="#" onclick="showSection('profile')"><i class="fa-solid fa-user-gear"></i> Profile Settings</a>
            </li>

        </ul>
        <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
            <a href="logout.php"
                style="color: #ff4d4d; text-decoration: none; display: flex; align-items: center; gap: 12px; padding: 12px 15px; border-radius: 8px; transition: 0.3s; opacity: 0.8;"
                onmouseover="this.style.opacity='1'; this.style.background='rgba(255, 77, 77, 0.1)';"
                onmouseout="this.style.opacity='0.8'; this.style.background='transparent';">
                <i class="fa-solid fa-power-off"></i> Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="dashboard-header">
            <h2 style="font-family: 'Oswald'; font-size: 1.8rem; letter-spacing: 1px;">DASHBOARD</h2>
            <div style="text-align: right;">
                <p style="color: var(--text-gray);">Welcome back, <strong
                        style="color: #fff;"><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></p>
            </div>
        </div>

        <div id="orders" class="dashboard-section active">
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3>Store Orders & Pickup Verification</h3>
                    <div style="position: relative; width: 300px;">
                        <i class="fa-solid fa-magnifying-glass"
                            style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-gray); font-size: 0.9rem;"></i>
                        <input type="text" id="store-order-search" onkeyup="searchStoreOrders()"
                            placeholder="Search tokens or members..."
                            style="width: 100%; padding: 10px 15px 10px 40px; border-radius: 30px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff; font-size: 0.9rem; outline: none; transition: 0.3s;">
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table class="data-table" id="store-orders-table">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Item</th>
                                <th>Amount</th>
                                <th>Token</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($store_orders_res) > 0): ?>
                                <?php while ($order = mysqli_fetch_assoc($store_orders_res)):
                                    $item_name = str_replace('Store: ', '', $order['plan_name']);
                                    ?>
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);" class="order-row">
                                        <td>
                                            <div style="display: flex; flex-direction: column;">
                                                <span style="font-weight: bold; color: #fff;"
                                                    class="order-member"><?php echo htmlspecialchars($order['full_name']); ?></span>
                                                <small
                                                    style="color: var(--text-gray); font-size: 0.75rem;"><?php echo htmlspecialchars($order['user_email']); ?></small>
                                            </div>
                                        </td>
                                        <td style="font-weight: 500;" class="order-item">
                                            <?php echo htmlspecialchars($item_name); ?>
                                        </td>
                                        <td style="color: var(--primary-color); font-weight: 700; font-size: 1rem;">
                                            ₹<?php echo number_format($order['amount'], 2); ?>
                                        </td>
                                        <td>
                                            <span
                                                style="font-family: 'Oswald'; color: var(--primary-color); font-weight: bold; letter-spacing: 1px; font-size: 1.1rem; background: rgba(206, 255, 0, 0.1); padding: 4px 8px; border-radius: 4px;"
                                                class="order-token">
                                                <?php echo $order['token_number']; ?>
                                            </span>
                                        </td>
                                        <td style="font-size: 0.85rem; color: var(--text-gray);">
                                            <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($order['token_accepted']): ?>
                                                <span class="badge badge-success" style="font-size: 0.7rem;">
                                                    <i class="fa-solid fa-check"></i> Released
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-warning" style="font-size: 0.7rem;">
                                                    <i class="fa-solid fa-clock"></i> Pending Pickup
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: right;">
                                            <div
                                                style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                                                <?php if (!$order['token_accepted']): ?>
                                                    <a href="invoice.php?tid=<?php echo $order['id']; ?>" target="_blank"
                                                        title="Verify Payment — open invoice before releasing"
                                                        style="background: rgba(255,159,0,0.1); color: #ff9f00; border: 1px solid rgba(255,159,0,0.35); text-decoration: none; padding: 7px 11px; border-radius: 4px; font-size: 0.8rem; transition: 0.2s; display:inline-flex; align-items:center; gap:5px; white-space:nowrap;"
                                                        onmouseenter="this.style.background='rgba(255,159,0,0.22)'"
                                                        onmouseleave="this.style.background='rgba(255,159,0,0.1)'">
                                                        <i class="fa-solid fa-receipt"></i> Verify Payment
                                                    </a>
                                                    <a href="dashboard_shop_staff.php?accept_token=<?php echo $order['id']; ?>"
                                                        class="btn-action"
                                                        style="background: var(--primary-color); color: #000; text-decoration: none; font-weight: bold; padding: 8px 15px;"
                                                        onclick="return confirm('Verify token and release product?')">
                                                        Accept & Release
                                                    </a>
                                                <?php else: ?>
                                                    <span
                                                        style="color: #00ff88; font-size: 0.8rem; font-weight: bold; font-style: italic;">Released</span>
                                                <?php endif; ?>
                                                <a href="dashboard_shop_staff.php?delete_store_order=<?php echo $order['id']; ?>"
                                                    style="background: rgba(231,76,60,0.15); color: #e74c3c; border: 1px solid rgba(231,76,60,0.3); text-decoration: none; padding: 7px 10px; border-radius: 4px; font-size: 0.8rem; transition: 0.2s;"
                                                    title="Delete this order"
                                                    onclick="return confirm('Permanently delete this store order?')">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-gray); padding: 40px;">
                                        <i class="fa-solid fa-cart-flatbed"
                                            style="font-size: 2.5rem; display: block; margin-bottom: 15px; opacity: 0.2;"></i>
                                        No store orders found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Gym Store Management -->
        <div id="gym-store-mgmt" class="dashboard-section">
            <div class="user-tabs"
                style="display: flex; gap: 15px; margin-bottom: 30px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding-bottom: 2px;">
                <div class="user-tab active" onclick="switchStoreTab('categories', this)"
                    style="padding: 12px 25px; color: var(--text-gray); cursor: pointer; font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid transparent; transition: 0.3s;">
                    Categories</div>
                <div class="user-tab" onclick="switchStoreTab('products', this)"
                    style="padding: 12px 25px; color: var(--text-gray); cursor: pointer; font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid transparent; transition: 0.3s;">
                    Products</div>
            </div>

            <!-- Categories Section -->
            <div id="store-categories-tab" class="store-tab-content active">
                <div class="card">
                    <div class="card-header"
                        style="display: flex; justify-content: space-between; align-items: center;">
                        <h3>Product Categories</h3>
                        <button class="btn-action"
                            style="background: var(--primary-color); color: #000; font-weight: bold;"
                            onclick="document.getElementById('add-cat-modal').style.display='flex'">+ Add
                            Category</button>
                    </div>
                    <div style="overflow-x: auto;" class="slider-table">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $categories = mysqli_query($link, "SELECT * FROM store_categories ORDER BY name ASC");
                                while ($cat = mysqli_fetch_assoc($categories)):
                                    ?>
                                    <tr>
                                        <td><img src="<?php echo $cat['image']; ?>"
                                                style="width:50px; height:50px; border-radius:5px; object-fit:cover;"></td>
                                        <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($cat['description']); ?></td>
                                        <td>
                                            <button class="btn-action"
                                                style="background: rgba(255,255,255,0.05); color: #fff; margin-right: 5px;"
                                                onclick='openEditCatModal(<?php echo json_encode($cat); ?>)'>Edit</button>
                                            <a href="?delete_store_category=<?php echo $cat['id']; ?>" class="btn-action"
                                                style="background: rgba(255,77,77,0.1); color: #ff4d4d;"
                                                onclick="return confirm('Deleting a category will also delete its products. Continue?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Products Section -->
            <div id="store-products-tab" class="store-tab-content" style="display:none;">
                <div class="card">
                    <div class="card-header"
                        style="display: flex; justify-content: space-between; align-items: center;">
                        <h3>Store Products</h3>
                        <button class="btn-action"
                            style="background: var(--primary-color); color: #000; font-weight: bold;"
                            onclick="document.getElementById('add-prod-modal').style.display='flex'">+ Add
                            Product</button>
                    </div>
                    <div style="overflow-x: auto;" class="slider-table">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Category</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $products = mysqli_query($link, "SELECT p.*, c.name as cat_name FROM store_products p JOIN store_categories c ON p.category_id = c.id ORDER BY cat_name ASC, p.name ASC");
                                while ($prod = mysqli_fetch_assoc($products)):
                                    ?>
                                    <tr>
                                        <td><img src="<?php echo $prod['image']; ?>"
                                                style="width:50px; height:50px; border-radius:5px; object-fit:cover;"></td>
                                        <td><span class="badge"
                                                style="background: rgba(255,193,7,0.15); color: #ffc107;"><?php echo htmlspecialchars($prod['cat_name']); ?></span>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($prod['name']); ?></strong></td>
                                        <td style="color: var(--primary-color);">
                                            ₹<?php echo number_format($prod['price'], 2); ?></td>
                                        <td>
                                            <span class="badge" id="stock-val-<?php echo $prod['id']; ?>"
                                                style="background: <?php echo $prod['stock_count'] > 0 ? 'rgba(206, 255, 0, 0.1)' : 'rgba(255, 77, 77, 0.1)'; ?>; color: <?php echo $prod['stock_count'] > 0 ? 'var(--primary-color)' : '#ff4d4d'; ?>;">
                                                <?php echo $prod['stock_count'] > 0 ? $prod['stock_count'] . ' in stock' : 'Sold Out'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn-action"
                                                style="background: rgba(255,255,255,0.05); color: #fff; margin-right: 5px;"
                                                onclick='openEditProdModal(<?php echo json_encode($prod); ?>)'>Edit</button>
                                            <a href="?delete_store_product=<?php echo $prod['id']; ?>" class="btn-action"
                                                style="background: rgba(255,77,77,0.1); color: #ff4d4d;"
                                                onclick="return confirm('Are you sure?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Section -->
        <div id="profile" class="dashboard-section">
            <div class="card" style="max-width: 650px; margin: 0 auto;">
                <div class="card-header">
                    <h3 style="text-align: center; font-size: 1.5rem;">Profile Settings</h3>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="profile-img-container">
                        <img id="profile-preview"
                            src="<?php echo $user_data['profile_image'] ? $user_data['profile_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($user_data['full_name']) . '&background=ceff00&color=1a1a2e'; ?>">
                        <label for="profile_image_file" class="upload-overlay">
                            <i class="fa-solid fa-camera"></i>
                        </label>
                        <input type="file" id="profile_image_file" name="profile_image_file" accept="image/*"
                            style="display:none;" onchange="previewImage(this)">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label
                            style="display: block; font-size: 0.85rem; color: var(--text-gray); margin-bottom: 8px;">Full
                            Name</label>
                        <input type="text" name="full_name"
                            value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required
                            class="form-control">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label
                            style="display: block; font-size: 0.85rem; color: var(--text-gray); margin-bottom: 8px;">Email
                            Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>"
                            required class="form-control">
                    </div>

                    <div style="margin-bottom: 25px;">
                        <label
                            style="display: block; font-size: 0.85rem; color: var(--text-gray); margin-bottom: 8px;">Change
                            Password (leave blank to keep current)</label>
                        <div style="position: relative;">
                            <input type="password" name="new_password" id="new_password_input"
                                placeholder="Enter new password" class="form-control" style="padding-right: 40px;">
                            <i class="fa-solid fa-eye" id="toggle-password-shop"
                                style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #aaa;"></i>
                        </div>
                    </div>

                    <button type="submit" name="update_profile" class="btn-action"
                        style="width: 100%; background: var(--primary-color); color: #000; font-weight: bold; padding: 12px; font-size: 1rem;">
                        Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>

    </div>

    <!-- Store Modals -->
    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: var(--secondary-color);
            width: 100%;
            max-width: 500px;
            padding: 25px;
            border-radius: 15px;
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
    </style>

    <div id="add-cat-modal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Add Category</h3>
                <button onclick="closeModal('add-cat-modal')"
                    style="background:none; border:none; color:#fff; cursor:pointer; font-size:1.5rem;">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_store_category" value="1">
                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" name="cat_name" class="form-control" required maxlength="50"
                        placeholder="e.g. Proteins, Equipment">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="cat_desc" class="form-control" rows="3" maxlength="255"
                        placeholder="Brief description"></textarea>
                </div>
                <div class="form-group">
                    <label>Image</label>
                    <input type="file" name="cat_image" class="form-control" accept="image/*">
                </div>
                <button type="submit" class="btn-action"
                    style="width: 100%; background: var(--primary-color); color: #000; font-weight: bold;">Add
                    Category</button>
            </form>
        </div>
    </div>

    <div id="edit-cat-modal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Edit Category</h3>
                <button onclick="closeModal('edit-cat-modal')"
                    style="background:none; border:none; color:#fff; cursor:pointer; font-size:1.5rem;">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="edit_store_category" value="1">
                <input type="hidden" name="cat_id" id="edit-cat-id">
                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" name="cat_name" id="edit-cat-name" class="form-control" required maxlength="50">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="cat_desc" id="edit-cat-desc" class="form-control" rows="3"
                        maxlength="255"></textarea>
                </div>
                <div class="form-group">
                    <label>Image (Optional)</label>
                    <input type="file" name="cat_image" class="form-control" accept="image/*">
                </div>
                <button type="submit" class="btn-action"
                    style="width: 100%; background: var(--primary-color); color: #000; font-weight: bold;">Update
                    Category</button>
            </form>
        </div>
    </div>

    <div id="add-prod-modal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Add Product</h3>
                <button onclick="closeModal('add-prod-modal')"
                    style="background:none; border:none; color:#fff; cursor:pointer; font-size:1.5rem;">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_store_product" value="1">
                <div class="form-group">
                    <label>Category</label>
                    <select name="prod_cat_id" class="form-control" required>
                        <option value="" disabled selected>Select Category</option>
                        <?php
                        $mod_cats = mysqli_query($link, "SELECT * FROM store_categories ORDER BY name ASC");
                        while ($c = mysqli_fetch_assoc($mod_cats))
                            echo "<option value='" . $c['id'] . "'>" . $c['name'] . "</option>";
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="prod_name" class="form-control" required maxlength="100">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Price (₹)</label>
                        <input type="number" step="0.01" name="prod_price" class="form-control" required min="0.01">
                    </div>
                    <div class="form-group">
                        <label>Stock Count</label>
                        <input type="number" name="stock_count" class="form-control" required min="0" value="0">
                    </div>
                </div>
                <div class="form-group">
                    <label>Image</label>
                    <input type="file" name="prod_image" class="form-control" accept="image/*">
                </div>
                <button type="submit" class="btn-action"
                    style="width: 100%; background: var(--primary-color); color: #000; font-weight: bold;">Add
                    Product</button>
            </form>
        </div>
    </div>

    <div id="edit-prod-modal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Edit Product</h3>
                <button onclick="closeModal('edit-prod-modal')"
                    style="background:none; border:none; color:#fff; cursor:pointer; font-size:1.5rem;">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="edit_store_product" value="1">
                <input type="hidden" name="prod_id" id="edit-prod-id">
                <div class="form-group">
                    <label>Category</label>
                    <select name="prod_cat_id" id="edit-prod-cat" class="form-control" required>
                        <?php
                        mysqli_data_seek($mod_cats, 0);
                        while ($c = mysqli_fetch_assoc($mod_cats))
                            echo "<option value='" . $c['id'] . "'>" . $c['name'] . "</option>";
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="prod_name" id="edit-prod-name" class="form-control" required
                        maxlength="100">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Price (₹)</label>
                        <input type="number" step="0.01" name="prod_price" id="edit-prod-price" class="form-control"
                            required min="0.01">
                    </div>
                    <div class="form-group">
                        <label>Stock Count</label>
                        <input type="number" name="stock_count" id="edit-prod-stock" class="form-control" required
                            min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label>Image (Optional)</label>
                    <input type="file" name="prod_image" class="form-control" accept="image/*">
                </div>
                <button type="submit" class="btn-action"
                    style="width: 100%; background: var(--primary-color); color: #000; font-weight: bold;">Update
                    Product</button>
            </form>
        </div>
    </div>

    <script>
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function switchStoreTab(tab, el) {
            document.querySelectorAll('.store-tab-content').forEach(c => c.style.display = 'none');
            document.querySelectorAll('.user-tab').forEach(t => t.classList.remove('active'));
            document.getElementById('store-' + tab + '-tab').style.display = 'block';
            el.classList.add('active');
        }

        function openEditCatModal(cat) {
            document.getElementById('edit-cat-id').value = cat.id;
            document.getElementById('edit-cat-name').value = cat.name;
            document.getElementById('edit-cat-desc').value = cat.description;
            document.getElementById('edit-cat-modal').style.display = 'flex';
        }

        function openEditProdModal(prod) {
            document.getElementById('edit-prod-id').value = prod.id;
            document.getElementById('edit-prod-cat').value = prod.category_id;
            document.getElementById('edit-prod-name').value = prod.name;
            document.getElementById('edit-prod-price').value = prod.price;
            document.getElementById('edit-prod-stock').value = prod.stock_count;
            document.getElementById('edit-prod-modal').style.display = 'flex';
        }
        function showSection(sectionId, updateHistory = true) {
            if (typeof event !== 'undefined' && event) event.preventDefault();
            else if (window.event) window.event.preventDefault();

            document.querySelectorAll('.dashboard-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.sidebar-menu a').forEach(a => a.classList.remove('active'));

            const target = document.getElementById(sectionId);
            if (target) {
                target.classList.add('active');

                // Update URL to include the section hash for copy-pasting
                if (updateHistory) {
                    try {
                        if (history.pushState) {
                            history.pushState(null, null, '#' + sectionId);
                        } else {
                            window.location.hash = sectionId;
                        }
                    } catch (e) {
                        window.location.hash = sectionId;
                    }
                }
            }

            // Find and activate sidebar link
            document.querySelectorAll('.sidebar-menu a').forEach(a => {
                const onclick = a.getAttribute('onclick');
                if (onclick && onclick.includes(`'${sectionId}'`)) {
                    a.classList.add('active');
                }
            });
        }

        // Initialize from URL hash or section parameter
        window.addEventListener('DOMContentLoaded', function () {
            const hash = window.location.hash.substring(1);
            const urlParams = new URLSearchParams(window.location.search);
            const sectionParam = urlParams.get('section');

            if (sectionParam) {
                showSection(sectionParam, false);
            } else if (hash) {
                showSection(hash, false);
            } else {
                // Default section
                showSection('orders', false);
            }
        });

        // Handle browser back/forward buttons
        window.addEventListener('popstate', function () {
            const hash = window.location.hash.substring(1);
            if (hash) {
                showSection(hash, false);
            } else {
                showSection('orders', false);
            }
        });

        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('profile-preview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        const togglePass = document.getElementById('toggle-password-shop');
        if (togglePass) {
            togglePass.addEventListener('click', function () {
                const input = document.getElementById('new_password_input');
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }

        function searchStoreOrders() {
            let input = document.getElementById('store-order-search').value.toLowerCase();
            let rows = document.querySelectorAll('.order-row');

            rows.forEach(row => {
                let member = row.querySelector('.order-member').innerText.toLowerCase();
                let token = row.querySelector('.order-token').innerText.toLowerCase();

                if (member.includes(input) || token.includes(input)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        // --- REAL-TIME INVENTORY SYNC ---
        setInterval(async () => {
            try {
                const response = await fetch('get_inventory.php');
                const result = await response.json();
                if (result.status === 'success') {
                    result.data.forEach(item => {
                        const stockBadge = document.getElementById('stock-val-' + item.id);
                        if (stockBadge) {
                            stockBadge.innerText = item.stock > 0 ? item.stock + ' in stock' : 'Sold Out';
                            stockBadge.style.background = item.stock > 0 ? 'rgba(206, 255, 0, 0.1)' : 'rgba(255, 77, 77, 0.1)';
                            stockBadge.style.color = item.stock > 0 ? 'var(--primary-color)' : '#ff4d4d';
                        }
                    });
                }
            } catch (err) {
                // Silently fail
            }
        }, 5000); // Sync every 5 seconds
    </script>
</body>

</html>