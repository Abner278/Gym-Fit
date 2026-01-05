<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== "staff") {
    header("location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - BeFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Roboto:wght@400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
    </style>
</head>

<body>

    <div class="sidebar">
        <a href="index.php" class="logo"><i class="fa-solid fa-dumbbell"></i>GymFit</a>
        <ul class="sidebar-menu">
            <li><a href="#" class="active" onclick="showSection('members')"><i class="fa-solid fa-users"></i>
                    Members</a></li>
            <li><a href="#" onclick="showSection('queries')"><i class="fa-solid fa-comments"></i> Member Queries</a>
            </li>
            <li><a href="#" onclick="showSection('content')"><i class="fa-solid fa-cloud-arrow-up"></i> Upload
                    Content</a></li>
            <li><a href="#" onclick="showSection('payments')"><i class="fa-solid fa-file-invoice-dollar"></i>
                    Payments</a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header-top">
            <div class="welcome-text">
                <h1>Staff Portal</h1>
                <p>Manage daily operations and member engagement.</p>
            </div>
            <div>
                <span style="color: var(--text-gray);">Welcome,
                    <strong><?php echo $_SESSION['full_name']; ?></strong></span>
            </div>
        </div>

        <!-- Members Section -->
        <div id="members" class="dashboard-section active">
            <div class="dashboard-card">
                <h3>Member Directory</h3>
                <a href="#" class="btn-add" onclick="alert('Open Add Member Modal')">+ Add New Member</a>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Join Date</th>
                            <th>Plan</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>John Doe</td>
                            <td>john@example.com</td>
                            <td>Dec 01, 2025</td>
                            <td>Standard</td>
                            <td>
                                <button class="btn-sm btn-edit">Edit</button>
                                <button class="btn-sm btn-delete">Remove</button>
                            </td>
                        </tr>
                        <tr>
                            <td>Jane Rose</td>
                            <td>jane@example.com</td>
                            <td>Nov 15, 2025</td>
                            <td>Premium</td>
                            <td>
                                <button class="btn-sm btn-edit">Edit</button>
                                <button class="btn-sm btn-delete">Remove</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Queries Section -->
        <div id="queries" class="dashboard-section">
            <div class="dashboard-card">
                <h3>Member Inquiries</h3>
                <div class="video-list" style="display: flex; flex-direction: column; gap: 15px;">
                    <div
                        style="background: rgba(255,255,255,0.02); padding: 15px; border-radius: 10px; border-left: 4px solid var(--primary-color);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <strong>Mike Smith</strong>
                            <span class="badge badge-warning">Pending</span>
                        </div>
                        <p style="font-size: 0.9rem; color: var(--text-gray);">"Hey, can I freeze my membership for 2
                            weeks in January?"</p>
                        <button class="btn-sm btn-edit" style="margin-top: 10px;">Reply</button>
                    </div>
                    <div
                        style="background: rgba(255,255,255,0.02); padding: 15px; border-radius: 10px; border-left: 4px solid #00ff00;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <strong>Sarah J.</strong>
                            <span class="badge badge-success">Resolved</span>
                        </div>
                        <p style="font-size: 0.9rem; color: var(--text-gray);">"Need help with the mobile login."</p>
                        <p style="font-size: 0.8rem; margin-top: 5px; color: var(--primary-color);">Staff: Resolved by
                            reset.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Management -->
        <div id="content" class="dashboard-section">
            <div class="dashboard-card">
                <h3>Upload Fitness Content</h3>
                <form onsubmit="event.preventDefault(); alert('Content uploaded successfully!')">
                    <div class="form-group">
                        <label>Content Title</label>
                        <input type="text" class="form-control" placeholder="e.g. Advanced Leg Workout">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select class="form-control">
                            <option>Workouts</option>
                            <option>Nutrition</option>
                            <option>Tips & Motivation</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Video URL / File Path</label>
                        <input type="text" class="form-control" placeholder="URL or Browse...">
                    </div>
                    <button type="submit"
                        style="background: var(--primary-color); border: none; padding: 12px 25px; border-radius: 5px; font-weight: bold; cursor: pointer;">Upload
                        Content</button>
                </form>
            </div>
        </div>

        <!-- Payments Section -->
        <div id="payments" class="dashboard-section">
            <div class="dashboard-card">
                <h3>Recent Member Payments</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Invoice</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>John Doe</td>
                            <td>₹899.00</td>
                            <td>Today</td>
                            <td><span class="badge badge-success">Paid</span></td>
                            <td><a href="#" style="color: var(--primary-color);"><i
                                        class="fa-solid fa-download"></i></a></td>
                        </tr>
                        <tr>
                            <td>Jane Rose</td>
                            <td>₹999.00</td>
                            <td>Dec 17</td>
                            <td><span class="badge badge-success">Paid</span></td>
                            <td><a href="#" style="color: var(--primary-color);"><i
                                        class="fa-solid fa-download"></i></a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        function showSection(sectionId) {
            document.querySelectorAll('.dashboard-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.sidebar-menu a').forEach(a => a.classList.remove('active'));
            document.getElementById(sectionId).classList.add('active');
            event.currentTarget.classList.add('active');
        }
    </script>
</body>

</html>