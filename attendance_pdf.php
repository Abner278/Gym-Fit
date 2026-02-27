<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== "admin") {
    die("Unauthorized Access");
}

if (!isset($_GET['uid']) || !isset($_GET['type'])) {
    die("Missing parameters.");
}

$uid = (int) $_GET['uid'];
$type = mysqli_real_escape_string($link, $_GET['type']);

// get name
$name = "";
$email = "";
if ($type === 'trainer') {
    $q = mysqli_query($link, "SELECT name, email FROM trainers WHERE id = $uid");
    if ($r = mysqli_fetch_assoc($q)) {
        $name = $r['name'];
        $email = $r['email'];
    }
} else {
    $q = mysqli_query($link, "SELECT full_name as name, email FROM users WHERE id = $uid");
    if ($r = mysqli_fetch_assoc($q)) {
        $name = $r['name'];
        $email = $r['email'];
    }
}

if (empty($name)) {
    die("User not found.");
}

$att_query = mysqli_query($link, "
    SELECT DATE_FORMAT(date, '%Y-%m') as month_year, 
    COUNT(*) as total_days,
    SUM(CASE WHEN LOWER(status) = 'present' THEN 1 ELSE 0 END) as present_days,
    SUM(CASE WHEN LOWER(status) = 'absent' THEN 1 ELSE 0 END) as absent_days
    FROM attendance
    WHERE user_id = $uid AND user_type = '$type'
    GROUP BY month_year
    ORDER BY month_year DESC
");

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report -
        <?php echo htmlspecialchars($name); ?>
    </title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Roboto:wght@400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #a1d423;
            --text-gray: #888;
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
            align-items: flex-start;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .logo {
            font-family: 'Oswald', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: #000;
            text-transform: uppercase;
        }

        .logo i {
            color: var(--primary-color);
            margin-right: 10px;
        }

        .report-info {
            text-align: right;
        }

        .report-info h2 {
            margin: 0;
            font-family: 'Oswald', sans-serif;
            color: var(--primary-color);
        }

        .details-col h3 {
            font-family: 'Oswald', sans-serif;
            font-size: 1rem;
            text-transform: uppercase;
            color: var(--text-gray);
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        .table th {
            text-align: left;
            background: #f9f9f9;
            padding: 12px;
            border-bottom: 2px solid #eee;
            font-family: 'Oswald', sans-serif;
            text-transform: uppercase;
            font-size: 0.9rem;
        }

        .table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .no-print {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            gap: 15px;
        }

        .btn-print {
            background: var(--primary-color);
            color: #000;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(161, 212, 35, 0.4);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            transition: 0.3s;
        }

        .btn-print:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(161, 212, 35, 0.6);
        }

        @media print {
            body {
                padding: 0;
                background: #fff;
            }

            .report-box {
                box-shadow: none;
                border: none;
                padding: 0;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="report-box" id="pdf-content">
        <div class="header">
            <div class="logo">
                <i class="fa-solid fa-dumbbell"></i>GymFit
            </div>
            <div class="report-info">
                <h2>ATTENDANCE REPORT</h2>
                <p><strong>Generated On: </strong>
                    <?php echo date('M d, Y'); ?>
                </p>
            </div>
        </div>

        <div style="margin-bottom: 40px;">
            <div class="details-col">
                <h3>Staff Details</h3>
                <p><strong>Name:</strong>
                    <?php echo htmlspecialchars($name); ?>
                </p>
                <p><strong>Role:</strong>
                    <?php echo ucfirst(str_replace('_', ' ', $type)); ?>
                </p>
                <?php if ($email): ?>
                    <p><strong>Email:</strong>
                        <?php echo htmlspecialchars($email); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th style="text-align:center;">Total Recorded Days</th>
                    <th style="text-align:center; color:#8bc34a;">Present Days</th>
                    <th style="text-align:center; color:#ff4d4d;">Absent Days</th>
                    <th style="text-align:right;">Attendance %</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($att_query) > 0) {
                    while ($row = mysqli_fetch_assoc($att_query)) {
                        $month_lbl = date('F Y', strtotime($row['month_year'] . '-01'));
                        $pct = $row['total_days'] > 0 ? round(($row['present_days'] / $row['total_days']) * 100, 1) : 0;
                        echo "<tr>";
                        echo "<td><strong>{$month_lbl}</strong></td>";
                        echo "<td style='text-align:center;'>{$row['total_days']}</td>";
                        echo "<td style='text-align:center; font-weight:bold; color:#8bc34a;'>{$row['present_days']}</td>";
                        echo "<td style='text-align:center; font-weight:bold; color:#ff4d4d;'>{$row['absent_days']}</td>";
                        echo "<td style='text-align:right;'>{$pct}%</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center; padding:30px; color:#888;'>No attendance records found.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <div
            style="text-align: center; margin-top: 50px; color: var(--text-gray); font-size: 0.85rem; border-top: 1px solid #eee; padding-top: 20px;">
            <p>GymFit Fitness Center - Official Staff Attendance Record</p>
        </div>
    </div>

    <div class="no-print">
        <button onclick="downloadPDF()" class="btn-print" style="background: #333; color: #fff;">
            <i class="fa-solid fa-download"></i> Download PDF
        </button>
        <button onclick="window.print()" class="btn-print">
            <i class="fa-solid fa-print"></i> Print
        </button>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            const element = document.getElementById('pdf-content');
            const opt = {
                margin: 0.5,
                filename: 'attendance_report_<?php echo preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($name)); ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>

</html>