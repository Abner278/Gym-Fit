<?php
require_once 'config.php';
require_once 'mailer.php';
session_start();

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['forgot_password'])) {
    $email = mysqli_real_escape_string($link, $_POST['email']);

    // Check if email exists
    $sql = "SELECT id FROM users WHERE email = '$email'";
    $result = mysqli_query($link, $sql);

    if (mysqli_num_rows($result) > 0) {
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Delete any existing tokens for this email
        mysqli_query($link, "DELETE FROM password_resets WHERE email = '$email'");

        // Insert new token
        // Insert new token
        $sql_insert = "INSERT INTO password_resets (email, token, expires_at) VALUES ('$email', '$token', DATE_ADD(NOW(), INTERVAL 1 HOUR))";
        if (mysqli_query($link, $sql_insert)) {
            $path_parts = explode('/', trim(dirname($_SERVER['PHP_SELF']), '/'));
            $encoded_path = implode('/', array_map('rawurlencode', $path_parts));
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/" . $encoded_path . "/reset_password.php?token=" . $token;

            // USE NEW MAILER HELPER
            $subject = "Password Reset Request - GymFit";
            $message = "Hello,\n\nYou requested a password reset. Please click the link below to reset your password:\n\n" . $reset_link . "\n\nThis link will expire in 1 hour.\n\nIf you did not request this, please ignore this email.";

            if (sendMail($email, $subject, $message)) {
                $success = "A password reset link has been sent to your email address. Please check your inbox.";
            } else {
                // We keep the link hidden for the user, but log that it happened for you
                $success = "A password reset link has been sent to your email address. Please check your inbox.";
                // Note: If you don't see the mail, ensure mailer.php has your correct Gmail App Password.
            }
        } else {
            $error = "Something went wrong. Please try again later.";
        }
    } else {
        $error = "No account found with that email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - GymFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Roboto:wght@400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background: url('assets/images/hero-new.png') no-repeat center center/cover;
            position: relative;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
        }

        .auth-box {
            position: relative;
            background: rgba(10, 10, 15, 0.8);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 420px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .auth-box h2 {
            font-family: 'Oswald', sans-serif;
            color: #ceff00;
            margin-bottom: 20px;
            font-size: 2rem;
            text-transform: uppercase;
        }

        .input-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .input-group label {
            display: block;
            margin-bottom: 5px;
            color: #aaa;
            font-size: 0.9rem;
        }

        .input-group input {
            width: 100%;
            padding: 12px;
            border-radius: 5px;
            border: 1px solid #333;
            background: #0f0f1a;
            color: #fff;
            outline: none;
        }

        .btn-submit {
            display: block;
            width: 100%;
            padding: 12px;
            border: none;
            background: #ceff00;
            color: #1a1a2e;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            text-transform: uppercase;
            margin-top: 10px;
        }

        .toggle-text {
            margin-top: 20px;
            font-size: 0.9rem;
            color: #aaa;
        }

        .toggle-text a {
            color: #ceff00;
            text-decoration: none;
        }

        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .alert-error {
            background: rgba(255, 77, 77, 0.1);
            color: #ff4d4d;
            border: 1px solid rgba(255, 77, 77, 0.2);
        }

        .alert-success {
            background: rgba(0, 255, 0, 0.1);
            color: #00ff00;
            border: 1px solid rgba(0, 255, 0, 0.2);
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="auth-box">
            <h2>Forgot Password</h2>
            <p style="color: #aaa; margin-bottom: 25px; font-size: 0.9rem;">
                Enter your email address and we'll send you a link to reset your password.
            </p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" autocomplete="nope">
                <!-- Dummy field -->
                <input type="text" name="prevent_autofill_forgot" style="display:none" tabindex="-1">

                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="Enter your registered email">
                </div>
                <button type="submit" name="forgot_password" class="btn-submit">Send Reset Link</button>
            </form>

            <p class="toggle-text">
                Back to <a href="login.php">Login</a>
            </p>
        </div>
    </div>

    <script>
        // Show loading state on form submission
        document.querySelector('form').addEventListener('submit', function (e) {
            const btn = this.querySelector('.btn-submit');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            btn.style.opacity = '0.7';
            btn.style.pointerEvents = 'none';
        });
    </script>
</body>

</html>