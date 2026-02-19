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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title>Shop Staff Dashboard - BeFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
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

        .badge-success { background: rgba(0, 255, 136, 0.15); color: #00ff88; }
        .badge-warning { background: rgba(255, 193, 7, 0.15); color: #ffc107; }

        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; padding: 20px; }
        }
    </style>
</head>
<body>
    <?php if (!empty($message)): ?>
        <div style="position: fixed; top: 20px; right: 20px; background: <?php echo $message_type == 'success' ? '#27ae60' : '#e74c3c'; ?>; color: #fff; padding: 15px 25px; border-radius: 8px; z-index: 10000; box-shadow: 0 5px 15px rgba(0,0,0,0.3); animation: slideIn 0.5s ease-out;" id="toast">
            <i class="fa-solid <?php echo $message_type == 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>" style="margin-right: 10px;"></i>
            <?php echo $message; ?>
        </div>
        <script>setTimeout(() => { document.getElementById('toast').style.opacity = '0'; setTimeout(() => document.getElementById('toast').remove(), 500); }, 3000);</script>
        <style>@keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }</style>
    <?php endif; ?>

    <div class="sidebar">
        <a href="#" class="logo">GYMFIT SHOP</a>
        <ul class="sidebar-menu">
          <li><a href="index.php"><i class="fa-solid fa-arrow-left"></i> Back to Website</a></li>
        <li><a href="#" class="active"><i class="fa-solid fa-cart-shopping"></i> Store Orders</a></li>
            
        </ul>
        <div style="margin-top: auto;">
            <a href="logout.php" style="color: #ff4d4d; text-decoration: none; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-power-off"></i> Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="dashboard-header">
            <div></div> 
            <div style="text-align: right;">
                <p>Staff <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></p>
            </div>
        </div>

        <div id="store-orders" class="dashboard-section">
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3>Store Orders & Pickup Verification</h3>
                    <div style="position: relative; width: 300px;">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-gray); font-size: 0.9rem;"></i>
                        <input type="text" id="store-order-search" onkeyup="searchStoreOrders()" placeholder="Search tokens or members..." style="width: 100%; padding: 10px 15px 10px 40px; border-radius: 30px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff; font-size: 0.9rem; outline: none; transition: 0.3s;">
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table class="data-table" id="store-orders-table">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Item</th>
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
                                                <span style="font-weight: bold; color: #fff;" class="order-member"><?php echo htmlspecialchars($order['full_name']); ?></span>
                                                <small style="color: var(--text-gray); font-size: 0.75rem;"><?php echo htmlspecialchars($order['user_email']); ?></small>
                                            </div>
                                        </td>
                                        <td style="font-weight: 500;" class="order-item"><?php echo htmlspecialchars($item_name); ?></td>
                                        <td>
                                            <span style="font-family: 'Oswald'; color: var(--primary-color); font-weight: bold; letter-spacing: 1px; font-size: 1.1rem; background: rgba(206, 255, 0, 0.1); padding: 4px 8px; border-radius: 4px;" class="order-token">
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
                                            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                                                <?php if (!$order['token_accepted']): ?>
                                                    <a href="dashboard_shop_staff.php?accept_token=<?php echo $order['id']; ?>" class="btn-action"
                                                       style="background: var(--primary-color); color: #000; text-decoration: none; font-weight: bold; padding: 8px 15px;"
                                                       onclick="return confirm('Verify token and release product?')">
                                                        Accept & Release
                                                    </a>
                                                <?php else: ?>
                                                    <span style="color: #00ff88; font-size: 0.8rem; font-weight: bold; font-style: italic;">Released</span>
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
                                    <td colspan="6" style="text-align: center; color: var(--text-gray); padding: 40px;">
                                        <i class="fa-solid fa-cart-flatbed" style="font-size: 2.5rem; display: block; margin-bottom: 15px; opacity: 0.2;"></i>
                                        No store orders found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function searchStoreOrders() {
            let input = document.getElementById('store-order-search').value.toLowerCase();
            let rows = document.querySelectorAll('.order-row');
            
            rows.forEach(row => {
               let member = row.querySelector('.order-member').innerText.toLowerCase();
               let token = row.querySelector('.order-token').innerText.toLowerCase();
               
               if(member.includes(input) || token.includes(input)) {
                   row.style.display = "";
               } else {
                   row.style.display = "none";
               }
            });
        }

    </script>
</body>
</html>
