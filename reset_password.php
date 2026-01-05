<?php
require_once 'config.php';
session_start();

$error = "";
$success = "";
$token = isset($_GET['token']) ? mysqli_real_escape_string($link, $_GET['token']) : (isset($_POST['token']) ? mysqli_real_escape_string($link, $_POST['token']) : "");

if (empty($token)) {
    header("location: login.php");
    exit;
}

// Check if token is valid and not expired
$sql = "SELECT email FROM password_resets WHERE token = '$token' AND expires_at > NOW()";
$result = mysqli_query($link, $sql);
$reset_data = mysqli_fetch_assoc($result);

if (!$reset_data) {
    $error = "This password reset link is invalid or has expired. Please request a new one.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password']) && $reset_data) {
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (!preg_match("/[A-Z]/", $new_password)) {
        $error = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match("/[a-z]/", $new_password)) {
        $error = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match("/[0-9]/", $new_password)) {
        $error = "Password must contain at least one number.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $email = $reset_data['email'];

        // Update user password
        $sql_update = "UPDATE users SET password = '$hashed_password' WHERE email = '$email'";
        if (mysqli_query($link, $sql_update)) {
            // Delete the used token
            mysqli_query($link, "DELETE FROM password_resets WHERE email = '$email'");
            $success = "Your password has been reset successfully. You can now login.";
        } else {
            $error = "Failed to reset password. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - GymFit</title>
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

        .password-requirements {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: left;
            border-left: 3px solid #ceff00;
        }

        .password-requirements p {
            margin: 0 0 10px 0;
            font-size: 0.85rem;
            color: #ceff00;
            font-weight: 500;
        }

        .requirement-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .requirement-list li {
            font-size: 0.8rem;
            color: #aaa;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }

        .requirement-list li i {
            margin-right: 8px;
            width: 12px;
            text-align: center;
        }

        .requirement-list li.valid {
            color: #00ff00;
        }

        .requirement-list li.invalid {
            color: #ff4d4d;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="login-container">
        <div class="auth-box">
            <h2>Reset Password</h2>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <p class="toggle-text"><a href="login.php" class="btn-submit"
                        style="display:inline-block; text-decoration:none;">Go to Login</a></p>
            <?php elseif ($reset_data): ?>
                <div class="password-requirements">
                    <p><i class="fas fa-shield-alt"></i> Password Requirements:</p>
                    <ul class="requirement-list" id="requirement-list">
                        <li data-req="length"><i class="fas fa-circle"></i> At least 8 characters</li>
                        <li data-req="uppercase"><i class="fas fa-circle"></i> At least one uppercase letter (A-Z)</li>
                        <li data-req="lowercase"><i class="fas fa-circle"></i> At least one lowercase letter (a-z)</li>
                        <li data-req="number"><i class="fas fa-circle"></i> At least one number (0-9)</li>
                    </ul>
                </div>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?token=<?php echo urlencode($token); ?>"
                    method="POST" id="resetForm">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="input-group">
                        <label>New Password</label>
                        <input type="password" name="password" id="password" required placeholder="Enter strong password">
                        <!-- Strength Meter -->
                        <div class="strength-meter-container" style="margin-top: 10px;">
                            <div class="strength-bar"
                                style="height: 4px; width: 100%; background: #333; border-radius: 2px; overflow: hidden;">
                                <div id="strength-fill"
                                    style="height: 100%; width: 0%; background: red; transition: width 0.3s, background 0.3s;">
                                </div>
                            </div>
                            <p id="strength-text"
                                style="font-size: 0.8rem; margin-top: 5px; color: #aaa; text-align: right;">Strength: Too
                                Short</p>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" required
                            placeholder="Repeat new password">
                    </div>
                    <button type="submit" name="reset_password" class="btn-submit">Update Password</button>
                </form>
            <?php endif; ?>

            <?php if (!$success): ?>
                <p class="toggle-text">
                    Back to <a href="login.php">Login</a>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const requirements = {
            length: document.querySelector('[data-req="length"]'),
            uppercase: document.querySelector('[data-req="uppercase"]'),
            lowercase: document.querySelector('[data-req="lowercase"]'),
            number: document.querySelector('[data-req="number"]')
        };

        passwordInput.addEventListener('input', () => {
            const val = passwordInput.value;

            // Length
            updateStatus(requirements.length, val.length >= 8);
            // Uppercase
            updateStatus(requirements.uppercase, /[A-Z]/.test(val));
            // Lowercase
            updateStatus(requirements.lowercase, /[a-z]/.test(val));
            // Number
            updateStatus(requirements.number, /[0-9]/.test(val));
        });

        function updateStatus(el, isValid) {
            const icon = el.querySelector('i');
            if (isValid) {
                el.classList.add('valid');
                el.classList.remove('invalid');
                icon.className = 'fas fa-check-circle';
            } else if (passwordInput.value.length > 0) {
                el.classList.add('invalid');
                el.classList.remove('valid');
                icon.className = 'fas fa-times-circle';
            } else {
                el.classList.remove('valid', 'invalid');
                icon.className = 'fas fa-circle';
            }
        }

        // Strength Meter Logic
        const strengthFill = document.getElementById('strength-fill');
        const strengthText = document.getElementById('strength-text');

        passwordInput.addEventListener('input', () => {
            const val = passwordInput.value;
            let score = 0;

            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[a-z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++; // Special char bonus

            let width = 0;
            let color = 'red';
            let text = 'Too Short';

            if (val.length < 1) {
                width = 0; text = '';
            } else if (val.length < 8) {
                width = 10; text = 'Too Short'; color = 'red';
            } else {
                // Calculate strength based on score (max 5)
                if (score <= 2) {
                    width = 33; text = 'Weak'; color = '#ff4d4d';
                } else if (score <= 4) {
                    width = 66; text = 'Medium'; color = '#ceff00';
                } else {
                    width = 100; text = 'Strong'; color = '#00ff00';
                }
            }

            strengthFill.style.width = width + '%';
            strengthFill.style.background = color;
            strengthText.innerText = 'Strength: ' + text;
            strengthText.style.color = color;
        });
    </script>
</body>

</html>