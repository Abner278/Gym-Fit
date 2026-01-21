<?php
$plan = isset($_GET['plan']) ? htmlspecialchars($_GET['plan']) : 'Standard';
$amt = isset($_GET['amt']) ? htmlspecialchars($_GET['amt']) : '899';
$uid = isset($_GET['uid']) ? (int) $_GET['uid'] : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GymFit Secure Pay</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Oswald:wght@400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #141424;
            --card-bg: #1a1a2e;
            --text-color: #ffffff;
            --accent-color: #ceff00;
            --success-green: #00c853;
            --subtext-color: #a0a0ba;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Roboto', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        /* Initial Pay Screen */
        #pay-screen {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 20px;
            text-align: center;
        }

        .gym-logo {
            font-family: 'Oswald', sans-serif;
            font-size: 2rem;
            color: var(--text-color);
            margin-bottom: 10px;
        }

        .gym-logo i {
            color: var(--accent-color);
        }

        .pay-card {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 20px;
            width: 100%;
            max-width: 320px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            margin-bottom: 30px;
        }

        .amount-display {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 20px 0;
        }

        .btn-proceed {
            background: var(--accent-color);
            color: #000;
            border: none;
            padding: 15px 40px;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            max-width: 300px;
            font-family: 'Oswald', sans-serif;
            letter-spacing: 1px;
            transition: 0.3s;
        }

        .btn-proceed:active {
            transform: scale(0.98);
        }

        /* Success Screen */
        #success-screen {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 20px;
            background: var(--bg-color);
        }

        .check-circle {
            width: 100px;
            height: 100px;
            background: #009688;
            /* Teal color from image */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 0 20px rgba(0, 150, 136, 0.4);
            animation: popIn 0.5s ease;
        }

        .check-circle i {
            font-size: 3.5rem;
            color: white;
        }

        .success-amount {
            font-size: 2.2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .success-status {
            color: var(--success-green);
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 30px;
            letter-spacing: 0.5px;
        }

        .details-box {
            background: #1e1e2d;
            border-radius: 12px;
            padding: 20px;
            width: 100%;
            max-width: 320px;
            margin-bottom: 40px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }

        .detail-row:last-child {
            margin-bottom: 0;
        }

        .detail-label {
            color: var(--subtext-color);
        }

        .detail-value {
            font-weight: 500;
            text-align: right;
        }

        .btn-done {
            background: var(--accent-color);
            color: #000;
            border: none;
            padding: 15px 0;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            max-width: 320px;
            font-family: 'Oswald', sans-serif;
            letter-spacing: 1px;
            box-shadow: 0 5px 15px rgba(206, 255, 0, 0.2);
        }

        .footer-logo {
            margin-top: auto;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--subtext-color);
            font-size: 0.85rem;
            padding-bottom: 20px;
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

        /* Spinner for loading */
        .spinner {
            display: none;
            width: 25px;
            height: 25px;
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top-color: #000;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        button.processing span {
            display: none;
        }

        button.processing .spinner {
            display: inline-block;
        }
    </style>
</head>

<body>

    <!-- Initial Pay Screen -->
    <div id="pay-screen">
        <div class="gym-logo"><i class="fa-solid fa-dumbbell"></i> GYMFIT</div>
        <p style="color:var(--subtext-color); margin-top:-5px;">Secure Checkout</p>

        <div class="pay-card">
            <div style="color:var(--subtext-color); font-size:0.9rem;">Payment Request</div>
            <div class="amount-display">₹ <?php echo $amt; ?>.00</div>
            <div
                style="background:rgba(255,255,255,0.05); padding:8px 15px; border-radius:8px; display:inline-block; font-size:0.9rem;">
                <?php echo $plan; ?> Membership
            </div>
        </div>

        <button class="btn-proceed" id="pay-btn" onclick="processPayment()">
            <span>PROCEED TO PAY</span>
            <div class="spinner"></div>
        </button>

        <div style="margin-top:20px; font-size:0.8rem; color:var(--subtext-color);">
            <i class="fa-solid fa-lock"></i> Secured by GymFit Pay
        </div>
    </div>

    <!-- Success Screen -->
    <div id="success-screen">
        <div class="check-circle">
            <i class="fa-solid fa-check"></i>
        </div>

        <div class="success-amount">₹ <?php echo $amt; ?>.00</div>
        <div class="success-status">PAYMENT SUCCESSFUL</div>

        <div class="details-box">
            <div class="detail-row">
                <span class="detail-label">To:</span>
                <span class="detail-value">GymFit Membership</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Ref No:</span>
                <span class="detail-value"><?php echo strtoupper(substr(md5(time()), 0, 8)); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Method:</span>
                <span class="detail-value">GPay</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Time:</span>
                <span class="detail-value"><?php echo date('d M, h:i A'); ?></span>
            </div>
        </div>

        <button class="btn-done" onclick="window.close()">DONE</button>

        <div class="footer-logo">
            <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/Google_Pay_%28GPay%29_Logo.svg" alt=""
                height="14" style="filter: grayscale(1) invert(1) opacity(0.6);">
            <span>Securely processed by GPay Demo</span>
        </div>
    </div>

    <script>
        function processPayment() {
            const btn = document.getElementById('pay-btn');
            const payScreen = document.getElementById('pay-screen');
            const successScreen = document.getElementById('success-screen');

            const userId = <?php echo $uid; ?>;
            const amount = <?php echo $amt; ?>;
            const plan = "<?php echo $plan; ?>";

            // Show loading state
            btn.classList.add('processing');

            // Backend call to record payment
            fetch('record_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    uid: userId,
                    amt: amount,
                    plan: plan
                })
            })
                .then(response => response.json())
                .then(data => {
                    // Simulate processing delay a bit longer for effect
                    setTimeout(() => {
                        // Switch screens
                        payScreen.style.display = 'none';
                        successScreen.style.display = 'flex';
                        if (navigator.vibrate) navigator.vibrate([100, 50, 100]);
                    }, 1000);
                })
                .catch((error) => {
                    console.error('Error:', error);
                    alert('Connection error. Please try again.');
                    btn.classList.remove('processing');
                });
        }
    </script>
</body>

</html>