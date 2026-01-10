<?php
require_once 'config.php';
require_once 'dompdf_setup.php'; // We might need a PDF library, but relying on html2pdf front-end or similar is easier. 
// However, the prompt implies "seen by staff as pdf", likely meaning download.
// Since we used html2pdf in the member report, let's stick to that pattern for consistency if possible, 
// OR create a print-friendly page. 

// Actually, let's make a dedicated page for this that can be printed/downloaded via browser print or html2pdf.

session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== "staff") {
    header("location: login.php");
    exit;
}

$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$display_date = date('F d, Y', strtotime($date_filter));

// Fetch all members to list them, and check their attendance status for the specific date
$sql = "SELECT u.full_name, u.email, u.id, a.status 
        FROM users u 
        LEFT JOIN attendance a ON u.id = a.user_id AND a.date = '$date_filter'
        WHERE u.role = 'member' 
        ORDER BY u.full_name ASC";

$result = mysqli_query($link, $sql);

$present_count = 0;
$absent_count = 0;
$members = [];

while ($row = mysqli_fetch_assoc($result)) {
    if ($row['status'] == 'present') {
        $present_count++;
    } else {
        $absent_count++;
    }
    $members[] = $row;
}

$total_members = count($members);
$attendance_rate = ($total_members > 0) ? round(($present_count / $total_members) * 100) : 0;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Attendance Report - <?php echo $display_date; ?></title>
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
            max-width: 900px;
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
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
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
            .no-print { display: none; }
            body { padding: 0; background: #fff; }
            .report-box { box-shadow: none; padding: 0; }
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
                <h2>DAILY MEMBER ATTENDANCE</h2>
                <p>Date: <?php echo $display_date; ?></p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h4>Total Members</h4>
                <div class="val"><?php echo $total_members; ?></div>
            </div>
            <div class="stat-card">
                <h4>Present Today</h4>
                <div class="val" style="color: #008000;"><?php echo $present_count; ?></div>
            </div>
            <div class="stat-card">
                <h4>Absent Today</h4>
                <div class="val" style="color: #cc0000;"><?php echo $absent_count; ?></div>
            </div>
            <div class="stat-card">
                <h4>Attendance Rate</h4>
                <div class="val"><?php echo $attendance_rate; ?>%</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Member Name</th>
                    <th>Email Address</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $mem): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($mem['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($mem['email']); ?></td>
                        <td>
                            <?php if ($mem['status'] == 'present'): ?>
                                <span class="status-badge status-present">Present</span>
                            <?php else: ?>
                                <span class="status-badge status-absent">Absent</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($members)): ?>
                    <tr><td colspan="3" style="text-align:center;">No members found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div style="margin-top: 40px; text-align: center; color: #999; font-size: 0.8rem; border-top: 1px solid #eee; padding-top: 20px;">
            <p>GymFit Staff Report • Generated on <?php echo date('Y-m-d H:i:s'); ?></p>
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
                filename: 'GymFit_Daily_<?php echo $date_filter; ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
