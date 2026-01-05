<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== "admin") {
    header("location: login.php");
    exit;
}
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    </style>
</head>

<body>

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
            <li><a href="#" onclick="showSection('financials')"><i class="fa-solid fa-money-bill-trend-up"></i>
                    Financial Records</a></li>
            <li><a href="#" onclick="showSection('schedule')"><i class="fa-solid fa-calendar-check"></i> Class
                    Schedule</a></li>
            <li><a href="#" onclick="showSection('inventory')"><i class="fa-solid fa-boxes-stacked"></i> Inventory</a>
            </li>
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
                    <button class="btn-add">Add Equipment</button>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Last Maintenance</th>
                            <th>Next Service</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Treadmill Elite T10</td>
                            <td>6</td>
                            <td><span style="color: #ceff00;">Functional</span></td>
                            <td>Oct 20, 2025</td>
                            <td>Jan 20, 2026</td>
                        </tr>
                        <tr>
                            <td>Adjustable Dumbbells</td>
                            <td>40</td>
                            <td><span style="color: #ceff00;">Good</span></td>
                            <td>Nov 05, 2025</td>
                            <td>May 05, 2026</td>
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

            // Handle if the call came from a sidebar click (has currentTarget)
            if (event && event.currentTarget) {
                event.currentTarget.classList.add('active');
            }
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