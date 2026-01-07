<?php
$plan = isset($_GET['plan']) ? htmlspecialchars($_GET['plan']) : 'Standard';
$amt = isset($_GET['amt']) ? htmlspecialchars($_GET['amt']) : '899';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Pay</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --gpay-blue: #1a73e8;
            --gpay-bg: #ffffff;
            --gpay-text: #202124;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Roboto', sans-serif;
            background-color: var(--gpay-bg);
            color: var(--gpay-text);
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        .header {
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header i {
            font-size: 1.2rem;
            color: #5f6368;
        }

        .merchant-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 40px;
        }

        .avatar {
            width: 80px;
            height: 80px;
            background: #ceff00;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            color: #1a1a2e;
            margin-bottom: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .merchant-name {
            font-size: 1.4rem;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .vpa {
            color: #5f6368;
            font-size: 0.9rem;
        }

        .amount-container {
            margin-top: 50px;
            text-align: center;
        }

        .currency {
            font-size: 1.5rem;
            vertical-align: middle;
            margin-right: 5px;
        }

        .amount {
            font-size: 3.5rem;
            font-weight: 500;
        }

        .footer {
            margin-top: auto;
            padding: 20px;
            border-top: 1px solid #f1f3f4;
        }

        .btn-pay {
            width: 100%;
            padding: 16px;
            background-color: var(--gpay-blue);
            color: white;
            border: none;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
        }

        /* Success screen */
        #success-screen {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: white;
            z-index: 100;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .check-circle {
            width: 120px;
            height: 120px;
            background: #008577;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4rem;
            margin-bottom: 20px;
            animation: popIn 0.5s ease;
        }

        @keyframes popIn {
            0% {
                transform: scale(0);
                opacity: 0;
            }

            80% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .processing #pay-btn-text {
            display: none;
        }

        .processing .spinner {
            display: inline-block;
        }

        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <i class="fa-solid fa-xmark"></i>
        <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/Google_Pay_%28GPay%29_Logo.svg" alt="GPay"
            height="24">
    </div>

    <div class="merchant-info">
        <div class="avatar">G</div>
        <div class="merchant-name">GymFit Membership</div>
        <div class="vpa">fitness@okaxis</div>
        <div
            style="margin-top:10px; padding:5px 12px; background:#f1f3f4; border-radius:15px; font-size:0.8rem; color:#5f6368;">
            <i class="fa-solid fa-shield-check" style="color:#1e8e3e;"></i> Verified Merchant
        </div>
    </div>

    <div class="amount-container">
        <span class="currency">₹</span>
        <span class="amount">
            <?php echo $amt; ?>
        </span>
        <p style="color:#5f6368; margin-top:10px;">For
            <?php echo $plan; ?> Plan
        </p>
    </div>

    <div class="footer">
        <button class="btn-pay" id="pay-button" onclick="handlePay()">
            <span id="pay-btn-text">Proceed to pay</span>
            <div class="spinner"></div>
        </button>
        <p style="text-align:center; font-size:0.75rem; color:#5f6368; margin-top:15px;">
            <i class="fa-solid fa-lock" style="font-size:0.7rem;"></i> Encrypted by Google Standard
        </p>
    </div>

    <div id="success-screen">
        <div class="check-circle">
            <i class="fa-solid fa-check"></i>
        </div>
        <h2 style="margin-bottom:5px;">₹
            <?php echo $amt; ?>
        </h2>
        <p style="font-size:1.1rem; color:#5f6368; margin-bottom:30px;">Paid to GymFit Membership</p>
        <div style="color:#5f6368; font-size:0.9rem;">
            <?php echo date('M d, Y h:i A'); ?> • Ref:
            <?php echo rand(100000, 999999); ?>
        </div>
        <button onclick="window.close()"
            style="margin-top:50px; padding:12px 30px; border-radius:30px; border:1px solid #dadce0; background:white; font-weight:500; cursor:pointer;">
            Done
        </button>
    </div>

    <script>
        function handlePay() {
            const btn = document.getElementById('pay-button');
            const success = document.getElementById('success-screen');

            btn.classList.add('processing');
            btn.disabled = true;

            // Mock processing delay
            setTimeout(() => {
                success.style.display = 'flex';
                // You could add an AJAX call here to notify the dashboard if needed
            }, 2000);
        }
    </script>
</body>

</html>