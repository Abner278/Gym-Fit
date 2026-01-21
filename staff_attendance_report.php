<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== "staff") {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["id"];
$full_name = $_SESSION["full_name"];

// Fetch staff user's join date
$user_query = mysqli_query($link, "SELECT created_at FROM users WHERE id = $user_id");
$user_data = mysqli_fetch_assoc($user_query);
$join_date = date('Y-m-d', strtotime($user_data['created_at']));

// Default to current month/year or get from GET
$month = isset($_GET['m']) ? (int) $_GET['m'] : (int) date('n');
$year = isset($_GET['y']) ? (int) $_GET['y'] : (int) date('Y');

// Validate month/year
if ($month < 1 || $month > 12)
    $month = (int) date('n');
if ($year < 2000 || $year > 3000)
    $year = (int) date('Y');

$month_name = date('F', mktime(0, 0, 0, $month, 10));
$days_in_month = date('t', mktime(0, 0, 0, $month, 1, $year));

// Fetch attendance for this month
$start_date = "$year-" . sprintf('%02d', $month) . "-01";
$end_date = "$year-" . sprintf('%02d', $month) . "-" . $days_in_month;

$sql = "SELECT date, status FROM attendance WHERE user_id = $user_id AND date BETWEEN '$start_date' AND '$end_date' ORDER BY date ASC";
$result = mysqli_query($link, $sql);

$attendance_map = [];
while ($row = mysqli_fetch_assoc($result)) {
    $attendance_map[$row['date']] = $row['status'];
}

$total_present = count($attendance_map);

// Calculate total trackable days (only count days after joining)
$join_timestamp = strtotime($join_date);
$total_days = 0;
for ($d = 1; $d <= $days_in_month; $d++) {
    $check_date = "$year-" . sprintf('%02d', $month) . "-" . sprintf('%02d', $d);
    $check_ts = strtotime($check_date);
    // Only count if: after join date AND not in future
    if ($check_ts >= $join_timestamp && $check_ts <= time()) {
        $total_days++;
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Attendance Report - <?php echo "$month_name $year"; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Roboto:wght@400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #ceff00;
            --dark-bg: #1a1a2e;
            --text-gray: #666;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 40px;
            color: #333;
        }

        .report-box {
            max-width: 800px;
            margin: auto;
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .logo {
            font-family: 'Oswald', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: #000;
            text-transform: uppercase;
        }

        .logo i {
            color: #8bc34a;
            margin-right: 10px;
        }

        .report-info h2 {
            margin: 0;
            font-family: 'Oswald', sans-serif;
            font-size: 1.5rem;
            color: #333;
            text-align: right;
        }

        .report-info p {
            margin: 5px 0 0;
            color: var(--text-gray);
            text-align: right;
            font-size: 0.9rem;
        }

        .member-details {
            margin-bottom: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 5px solid #ceff00;
        }

        .member-details h3 {
            margin: 0 0 10px;
            font-family: 'Oswald', sans-serif;
        }

        .stats-grid {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            flex: 1;
            background: #fff;
            border: 1px solid #eee;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-card h4 {
            margin: 0 0 5px;
            color: var(--text-gray);
            font-size: 0.9rem;
        }

        .stat-card .val {
            font-family: 'Oswald', sans-serif;
            font-size: 1.5rem;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            text-align: left;
            background: #333;
            color: #fff;
            padding: 12px;
            font-family: 'Oswald', sans-serif;
            text-transform: uppercase;
            font-size: 0.9rem;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 0.95rem;
        }

        tr:nth-child(even) {
            background: #fcfcfc;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-present {
            background: rgba(0, 255, 0, 0.1);
            color: #008000;
            border: 1px solid rgba(0, 255, 0, 0.2);
        }

        .status-absent {
            background: rgba(255, 0, 0, 0.05);
            color: #cc0000;
            border: 1px solid rgba(255, 0, 0, 0.1);
        }

        .status-future {
            background: #f0f0f0;
            color: #999;
        }

        .no-print {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            gap: 15px;
        }

        .btn-print {
            background: #333;
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
        }

        .btn-print.primary {
            background: #ceff00;
            color: #000;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                padding: 0;
                background: #fff;
            }

            .report-box {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>

<body>

    <div class="report-box" id="report-content">
        <div class="header">
            <div class="logo">
                <i class="fa-solid fa-dumbbell"></i> GymFit
            </div>
            <div class="report-info">
                <h2>STAFF ATTENDANCE REPORT</h2>
                <p><?php echo "$month_name $year"; ?></p>
            </div>
        </div>

        <div class="member-details">
            <h3>Staff Profile</h3>
            <div style="display:flex; justify-content:space-between;">
                <div>
                    <strong>Name:</strong> <?php echo htmlspecialchars($full_name); ?><br>
                    <strong>Staff ID:</strong> #<?php echo str_pad($user_id, 4, '0', STR_PAD_LEFT); ?>
                </div>
                <div style="text-align:right;">
                    <strong>Date Generated:</strong> <?php echo date('M d, Y'); ?>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h4>Total Days Tracked</h4>
                <div class="val"><?php echo $total_days; ?></div>
            </div>
            <div class="stat-card">
                <h4>Days Present</h4>
                <div class="val" style="color: #008000;"><?php echo $total_present; ?></div>
            </div>
            <div class="stat-card">
                <h4>Attendance Rate</h4>
                <div class="val"><?php echo ($total_days > 0) ? round(($total_present / $total_days) * 100) : 0; ?>%
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Day</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                for ($d = 1; $d <= $days_in_month; $d++) {
                    $current_date_str = "$year-" . sprintf('%02d', $month) . "-" . sprintf('%02d', $d);
                    $timestamp = strtotime($current_date_str);
                    $day_name = date('l', $timestamp);
                    $display_date = date('M d, Y', $timestamp);

                    $is_present = isset($attendance_map[$current_date_str]);
                    $is_future = $timestamp > time();
                    $is_before_join = $current_date_str < $join_date;

                    $status_label = 'Absent';
                    $status_class = 'status-absent';

                    if ($is_before_join) {
                        $status_label = 'Not Joined';
                        $status_class = 'status-future'; // Reuse future styling for not-joined
                    } elseif ($is_present) {
                        $status_label = 'Present';
                        $status_class = 'status-present';
                    } elseif ($is_future) {
                        $status_label = '-';
                        $status_class = 'status-future';
                    }
                    ?>
                    <tr>
                        <td><?php echo $display_date; ?></td>
                        <td><?php echo $day_name; ?></td>
                        <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>

        <div
            style="margin-top: 40px; text-align: center; color: #999; font-size: 0.8rem; border-top: 1px solid #eee; padding-top: 20px;">
            <p>Generated by GymFit Staff Portal.</p>
        </div>
    </div>

    <div class="no-print">
        <button onclick="downloadPDF()" class="btn-print primary">
            <i class="fa-solid fa-download"></i> Download PDF
        </button>
        <button onclick="window.close()" class="btn-print">
            Close
        </button>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            const element = document.getElementById('report-content');
            const opt = {
                margin: 0.3,
                filename: 'Staff_Attendance_<?php echo $month_name; ?>_<?php echo $year; ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>

</html>