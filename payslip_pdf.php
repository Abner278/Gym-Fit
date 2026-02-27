<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== "admin") {
    die("Unauthorized Access");
}

if (!isset($_GET['uid']) || !isset($_GET['type']) || !isset($_GET['month'])) {
    die("Missing parameters.");
}

$uid = (int) $_GET['uid'];
$type = mysqli_real_escape_string($link, $_GET['type']);
$month = mysqli_real_escape_string($link, $_GET['month']);

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

// get settlement
$settlement = null;
$s_query = mysqli_query($link, "SELECT * FROM salary_settlements WHERE user_id = $uid AND user_type = '$type' AND month = '$month'");
if ($s_query && mysqli_num_rows($s_query) > 0) {
    $settlement = mysqli_fetch_assoc($s_query);
}

if (!$settlement) {
    die("No settlement found for this month.");
}

function getIndianCurrency(float $number)
{
    if ($number == 0)
        return 'Zero';
    $decimal = round($number - ($no = floor($number)), 2) * 100;
    $hundred = null;
    $digits_length = strlen($no);
    $i = 0;
    $str = array();
    $words = array(
        0 => '',
        1 => 'One',
        2 => 'Two',
        3 => 'Three',
        4 => 'Four',
        5 => 'Five',
        6 => 'Six',
        7 => 'Seven',
        8 => 'Eight',
        9 => 'Nine',
        10 => 'Ten',
        11 => 'Eleven',
        12 => 'Twelve',
        13 => 'Thirteen',
        14 => 'Fourteen',
        15 => 'Fifteen',
        16 => 'Sixteen',
        17 => 'Seventeen',
        18 => 'Eighteen',
        19 => 'Nineteen',
        20 => 'Twenty',
        30 => 'Thirty',
        40 => 'Forty',
        50 => 'Fifty',
        60 => 'Sixty',
        70 => 'Seventy',
        80 => 'Eighty',
        90 => 'Ninety'
    );
    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
    while ($i < $digits_length) {
        $divider = ($i == 2) ? 10 : 100;
        $number_part = floor($no % $divider);
        $no = floor($no / $divider);
        $i += $divider == 10 ? 1 : 2;
        if ($number_part) {
            $plural = (($counter = count($str)) && $number_part > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str[] = ($number_part < 21) ? $words[$number_part] . ' ' . $digits[$counter] . ' ' . $hundred : $words[floor($number_part / 10) * 10] . ' ' . $words[$number_part % 10] . ' ' . $digits[$counter] . ' ' . $hundred;
        } else
            $str[] = null;
    }
    $Rupees = implode('', array_reverse($str));
    $paise = ($decimal > 0) ? " and " . ($words[$decimal / 10] . " " . $words[$decimal % 10]) . ' Paise' : '';
    return ($Rupees ? trim($Rupees) . ' ' : '') . $paise;
}

$display_month = date('F Y', strtotime($month . '-01'));
$generated_date = isset($settlement['created_at']) ? date('M d, Y', strtotime($settlement['created_at'])) : date('M d, Y');
$amount_in_words = getIndianCurrency($settlement['total_amount']);


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip -
        <?php echo htmlspecialchars($name); ?> -
        <?php echo $display_month; ?>
    </title>
    <link
        href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Roboto:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #a1d423;
            --text-gray: #888;
            --border-color: #eee;
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
            padding: 50px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 25px;
            margin-bottom: 30px;
        }

        .logo {
            font-family: 'Oswald', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .logo i {
            color: var(--primary-color);
            margin-right: 10px;
        }

        .report-info {
            text-align: right;
        }

        .report-info h2 {
            margin: 0 0 10px 0;
            font-family: 'Oswald', sans-serif;
            color: #333;
            letter-spacing: 1px;
            font-size: 2rem;
            text-transform: uppercase;
        }

        .details-wrapper {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .details-col h3 {
            font-family: 'Oswald', sans-serif;
            font-size: 1.1rem;
            text-transform: uppercase;
            color: #555;
            margin-top: 0;
            margin-bottom: 15px;
        }

        .details-col p {
            margin: 8px 0;
            font-size: 1rem;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        .table th {
            text-align: left;
            background: #333;
            color: #fff;
            padding: 15px;
            font-family: 'Oswald', sans-serif;
            text-transform: uppercase;
            font-size: 1rem;
            letter-spacing: 0.5px;
        }

        .table td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            font-size: 1.05rem;
        }

        .table .amount {
            text-align: right;
            font-weight: 500;
            font-family: 'Oswald', sans-serif;
        }

        .totals-table {
            width: 100%;
            max-width: 400px;
            margin-left: auto;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: #fdfdfd;
        }

        .totals-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            font-size: 1.05rem;
        }

        .totals-table .label {
            font-weight: bold;
            color: #555;
            white-space: nowrap;
        }

        .totals-table .amount {
            text-align: right;
            font-weight: bold;
            font-family: 'Oswald', sans-serif;
            white-space: nowrap;
        }

        .net-salary-row td {
            background: #f9f9f9;
            font-size: 1.5rem !important;
            color: #000;
            border-top: 2px solid #333;
            border-bottom: 2px solid #333 !important;
        }

        .net-salary-row .amount {
            color: var(--primary-color);
        }

        .footer-note {
            text-align: center;
            margin-top: 60px;
            color: var(--text-gray);
            font-size: 0.9rem;
            border-top: 1px solid var(--border-color);
            padding-top: 25px;
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
            padding: 15px 30px;
            border-radius: 50px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(161, 212, 35, 0.4);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
            transition: 0.3s;
            font-family: 'Oswald', sans-serif;
            text-transform: uppercase;
        }

        .btn-print:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(161, 212, 35, 0.6);
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 8rem;
            color: rgba(0, 0, 0, 0.03);
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            white-space: nowrap;
            z-index: 0;
            pointer-events: none;
        }

        .content-z {
            position: relative;
            z-index: 10;
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
        <div class="watermark">SETTLED</div>
        <div class="content-z">
            <div class="header">
                <div class="logo">
                    <i class="fa-solid fa-dumbbell"></i>GymFit
                </div>
                <div class="report-info">
                    <h2>SALARY PAYSLIP</h2>
                    <p><strong>Payslip For:</strong>
                        <?php echo $display_month; ?>
                    </p>
                    <p style="color: #666; font-size: 0.9em;"><strong>Generated On:</strong>
                        <?php echo $generated_date; ?>
                    </p>
                </div>
            </div>

            <div class="details-wrapper">
                <div class="details-col">
                    <h3>Employee Details</h3>
                    <p><strong>Name:</strong>
                        <?php echo htmlspecialchars($name); ?>
                    </p>
                    <p><strong>Role:</strong>
                        <?php echo ucfirst(str_replace('_', ' ', $type)); ?>
                    </p>
                    <p><strong>Employee ID:</strong> #
                        <?php echo str_pad($uid, 5, '0', STR_PAD_LEFT); ?>
                    </p>
                </div>
                <div class="details-col" style="text-align: right;">
                    <h3>Employer Details</h3>
                    <p><strong>GymFit Fitness Center</strong></p>
                    <p>contact@gymfit.com</p>
                    <p>+91 (123) 456-7890</p>
                </div>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Earnings & Deductions Description</th>
                        <th class="amount" style="text-align:right;">Amount (INR)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Basic Salary</strong></td>
                        <td class="amount">₹
                            <?php echo number_format($settlement['basic_salary'], 2); ?>
                        </td>
                    </tr>
                    <?php if ($settlement['incentives'] > 0): ?>
                        <tr>
                            <td><strong>Incentives & Bonuses</strong><br><small style="color:#777;">Performance /
                                    Appointments Bonus</small></td>
                            <td class="amount" style="color: #28a745;">+ ₹
                                <?php echo number_format($settlement['incentives'], 2); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($settlement['deductions'] > 0): ?>
                        <tr>
                            <td><strong>Deductions</strong><br><small style="color:#777;">Absences / Unpaid Leaves /
                                    Penalties</small></td>
                            <td class="amount" style="color: #dc3545;">- ₹
                                <?php echo number_format($settlement['deductions'], 2); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <table class="totals-table">
                <tr class="net-salary-row">
                    <td class="label">NET SALARY PAYABLE:</td>
                    <td class="amount">₹
                        <?php echo number_format($settlement['total_amount'], 2); ?>
                    </td>
                </tr>
            </table>

            <div style="margin-top: 50px;">
                <p><strong>Amount in Words:</strong> <i style="color: #555;">Rupees
                        <?php echo $amount_in_words; ?> Only.
                    </i></p>
            </div>

            <div class="footer-note">
                <p>This is a computer-generated document. No signature is required.</p>
                <p><strong>Status:</strong> <span style="color:#28a745; font-weight:bold;">PAID & SETTLED</span></p>
            </div>
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
                margin: 0,
                filename: 'Payslip_<?php echo preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($name)); ?>_<?php echo date('My', strtotime($display_month)); ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>

</html>