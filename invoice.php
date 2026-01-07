<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION["loggedin"])) {
    header("location: login.php");
    exit;
}

if (!isset($_GET['tid'])) {
    die("Transaction ID is missing.");
}

$tid = (int) $_GET['tid'];

// Fetch transaction details
$sql = "SELECT t.*, u.full_name, u.email 
        FROM transactions t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.id = $tid";

$result = mysqli_query($link, $sql);
$transaction = mysqli_fetch_assoc($result);

if (!$transaction) {
    die("Transaction not found.");
}

// Security: If member, can only see their own invoice. Staff/Admin can see all.
if ($_SESSION['role'] === 'member' && $_SESSION['id'] != $transaction['user_id']) {
    die("Unauthorized access.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - #GF-
        <?php echo str_pad($transaction['id'], 5, '0', STR_PAD_LEFT); ?>
    </title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Roboto:wght@400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #a1d423;
            --dark-bg: #0a0a0a;
            --text-gray: #888;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 40px;
            color: #333;
        }

        .invoice-box {
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
            text-decoration: none;
        }

        .logo i {
            color: var(--primary-color);
            margin-right: 10px;
        }

        .invoice-info {
            text-align: right;
        }

        .invoice-info h2 {
            margin: 0;
            font-family: 'Oswald', sans-serif;
            color: var(--primary-color);
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
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

        .total-section {
            display: flex;
            justify-content: flex-end;
        }

        .total-box {
            width: 250px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
        }

        .total-row.grand-total {
            border-top: 2px solid #333;
            font-weight: bold;
            font-size: 1.2rem;
            color: #000;
        }

        .footer {
            text-align: center;
            margin-top: 50px;
            color: var(--text-gray);
            font-size: 0.85rem;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .no-print {
            position: fixed;
            bottom: 30px;
            right: 30px;
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

            .invoice-box {
                box-shadow: none;
                border: none;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="invoice-box">
        <div class="header">
            <div class="logo">
                <i class="fa-solid fa-dumbbell"></i>GymFit
            </div>
            <div class="invoice-info">
                <h2>INVOICE</h2>
                <p><strong>Invoice #: </strong> GF-
                    <?php echo str_pad($transaction['id'], 5, '0', STR_PAD_LEFT); ?>
                </p>
                <p><strong>Date: </strong>
                    <?php echo date('M d, Y', strtotime($transaction['created_at'])); ?>
                </p>
            </div>
        </div>

        <div class="details-grid">
            <div class="details-col">
                <h3>From</h3>
                <p><strong>GymFit Fitness Center</strong></p>
                <p>123 Fitness Avenue</p>
                <p>Ponkunnam, Kerala 682016</p>
                <p>Email: GymFit@gmail.com</p>
            </div>
            <div class="details-col">
                <h3>Bill To</h3>
                <p><strong>
                        <?php echo htmlspecialchars($transaction['full_name']); ?>
                    </strong></p>
                <p>
                    <?php echo htmlspecialchars($transaction['email']); ?>
                </p>
                <p>Member ID: #MEM-
                    <?php echo str_pad($transaction['user_id'], 4, '0', STR_PAD_LEFT); ?>
                </p>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Payment Method</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>
                            <?php echo htmlspecialchars($transaction['plan_name']); ?> Membership
                        </strong><br>
                        <small style="color: var(--text-gray);">Full access to gym facilities and daily
                            workouts.</small>
                    </td>
                    <td style="text-transform: capitalize;">
                        <?php echo htmlspecialchars($transaction['payment_method']); ?>
                    </td>
                    <td style="text-align: right;">₹
                        <?php echo number_format($transaction['amount'], 2); ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-box">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>₹
                        <?php echo number_format($transaction['amount'], 2); ?>
                    </span>
                </div>
                <div class="total-row">
                    <span>Tax (0%):</span>
                    <span>₹0.00</span>
                </div>
                <div class="total-row grand-total">
                    <span>Total Paid:</span>
                    <span>₹
                        <?php echo number_format($transaction['amount'], 2); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="status-stamp" style="margin-top: 20px;">
            <span style="border: 3px solid <?php echo $transaction['status'] == 'completed' ? '#8bc34a' : '#ff4d4d'; ?>; 
                     color: <?php echo $transaction['status'] == 'completed' ? '#8bc34a' : '#ff4d4d'; ?>; 
                     padding: 10px 20px; 
                     font-weight: bold; 
                     font-size: 1.5rem; 
                     display: inline-block; 
                     transform: rotate(-10deg); 
                     text-transform: uppercase;
                     border-radius: 5px;
                     opacity: 0.8;">
                <?php echo $transaction['status']; ?>
            </span>
        </div>

        <div class="footer">
            <p>Thank you for choosing GymFit! Stay strong, stay healthy.</p>
            <p>This is a computer-generated invoice and does not require a physical signature.</p>
        </div>
    </div>

    <div class="no-print" style="display: flex; gap: 15px;">
        <button onclick="downloadInvoice()" class="btn-print" style="background: #333; color: #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
            <i class="fa-solid fa-download"></i> Download PDF
        </button>
        <button onclick="window.print()" class="btn-print">
            <i class="fa-solid fa-print"></i> Print Invoice
        </button>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadInvoice() {
            const element = document.querySelector('.invoice-box');
            const opt = {
                margin: 0.5,
                filename: 'GymFit_Invoice_<?php echo str_pad($transaction['id'], 5, '0', STR_PAD_LEFT); ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
            };
            
            // New Promise-based usage:
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>

</html>