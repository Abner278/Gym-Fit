<?php
require_once 'config.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    if ($_SESSION["role"] == "admin") {
        header("location: dashboard_admin.php");
    } elseif ($_SESSION["role"] == "staff") {
        header("location: dashboard_staff.php");
    } else {
        header("location: dashboard_member.php");
    }
    exit;
}

$error = "";

// Handle Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = mysqli_real_escape_string($link, $_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT id, full_name, email, password, role FROM users WHERE email = '$email'";
    $result = mysqli_query($link, $sql);
    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            $_SESSION["loggedin"] = true;
            $_SESSION["id"] = $row['id'];
            $_SESSION["full_name"] = $row['full_name'];
            $_SESSION["email"] = $row['email'];
            $_SESSION["role"] = $row['role'];

            if ($row['role'] == "admin") {
                header("location: dashboard_admin.php");
            } elseif ($row['role'] == "staff") {
                header("location: dashboard_staff.php");
            } else {
                header("location: dashboard_member.php");
            }
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No account found with that email.";
    }
}

// Handle Registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $full_name = mysqli_real_escape_string($link, $_POST['full_name']);
    $email = mysqli_real_escape_string($link, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'member'; // Default role for new signups

    // Check if email already exists to avoid fatal error
    $check_email = mysqli_query($link, "SELECT id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        $error = "This email is already registered. Please sign in instead.";
    } else {
        $sql = "INSERT INTO users (full_name, email, password, role) VALUES ('$full_name', '$email', '$password', '$role')";
        if (mysqli_query($link, $sql)) {
            // Automatically login after registration
            $last_id = mysqli_insert_id($link);
            $_SESSION["loggedin"] = true;
            $_SESSION["id"] = $last_id;
            $_SESSION["full_name"] = $full_name;
            $_SESSION["email"] = $email;
            $_SESSION["role"] = $role;
            header("location: dashboard_member.php");
            exit;
        } else {
            $error = "Registration failed. Please try again later.";
        }
    }
}

// Handle Google Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['google_credential'])) {
    $id_token = $_POST['google_credential'];

    // Verify the ID token with Google's API
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token;
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    if (isset($data['email']) && $data['aud'] == GOOGLE_CLIENT_ID) {
        $email = mysqli_real_escape_string($link, $data['email']);
        $full_name = mysqli_real_escape_string($link, $data['name']);
        $profile_image = mysqli_real_escape_string($link, $data['picture']);

        // Check if user exists
        $sql = "SELECT id, full_name, email, role FROM users WHERE email = '$email'";
        $result = mysqli_query($link, $sql);

        if ($row = mysqli_fetch_assoc($result)) {
            // User exists, log them in
            $_SESSION["loggedin"] = true;
            $_SESSION["id"] = $row['id'];
            $_SESSION["full_name"] = $row['full_name'];
            $_SESSION["email"] = $row['email'];
            $_SESSION["role"] = $row['role'];

            // Update profile image if they don't have one
            mysqli_query($link, "UPDATE users SET profile_image = '$profile_image' WHERE id = " . $row['id'] . " AND (profile_image IS NULL OR profile_image = '')");
        } else {
            // New user, register as member
            $pass = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT);
            $sql_insert = "INSERT INTO users (full_name, email, password, role, profile_image) 
                           VALUES ('$full_name', '$email', '$pass', 'member', '$profile_image')";

            if (mysqli_query($link, $sql_insert)) {
                $_SESSION["loggedin"] = true;
                $_SESSION["id"] = mysqli_insert_id($link);
                $_SESSION["full_name"] = $full_name;
                $_SESSION["email"] = $email;
                $_SESSION["role"] = 'member';
            }
        }

        // Final redirect
        if ($_SESSION["role"] == "admin")
            header("location: dashboard_admin.php");
        elseif ($_SESSION["role"] == "staff")
            header("location: dashboard_staff.php");
        else
            header("location: dashboard_member.php");
        exit;
    } else {
        $error = "Google authentication failed. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login / Register - BeFit</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Roboto:wght@400;500&display=swap"
        rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <!-- Google Identity Services -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
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
            overflow: hidden;
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
            transition: 0.3s;
        }

        .input-group input:focus {
            border-color: #ceff00;
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
            margin-bottom: 15px;
            transition: background 0.3s;
            text-transform: uppercase;
        }

        .btn-submit:hover {
            background: #a1d423;
            box-shadow: 0 0 15px #ceff00;
        }

        .btn-google {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 12px;
            border: 1px solid #333;
            background: #fff;
            color: #333;
            font-weight: 500;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .toggle-text {
            font-size: 0.9rem;
            color: #aaa;
        }

        .toggle-text a {
            color: #ceff00;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
        }

        .toggle-text a:hover {
            text-decoration: underline;
        }

        /* Hide/Show logic */
        .form-section {
            transition: 0.3s ease;
        }

        .hidden {
            display: none;
        }
    </style>
</head>

<body>

    <div class="login-container">
        <div class="auth-box">

            <!-- LOGIN FORM -->
            <div id="login-form" class="form-section">
                <h2>Welcome Back</h2>
                <?php if ($error): ?>
                    <div style="color: #ff4d4d; margin-bottom: 15px; font-size: 0.9rem;"><?php echo $error; ?></div>
                <?php endif; ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" autocomplete="nope">
                    <!-- Dummy fields to capture browser autofill -->
                    <input type="text" name="prevent_autofill" style="display:none" tabindex="-1">
                    <input type="password" name="password_fake" style="display:none" tabindex="-1">

                    <div class="input-group">
                        <label>Email Address</label>
                        <input type="email" id="login-email" name="email" required placeholder="Enter your email"
                            autocomplete="new-email" readonly onfocus="this.removeAttribute('readonly');">
                    </div>
                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" id="login-password" name="password" required
                            placeholder="Enter your password" autocomplete="new-password" readonly
                            onfocus="this.removeAttribute('readonly');">
                    </div>

                    <button type="submit" name="login" class="btn-submit">Sign In</button>
                    <div style="text-align: right; margin-bottom: 15px;">
                        <a href="forgot_password.php"
                            style="color: #aaa; font-size: 0.85rem; text-decoration: none;">Forgot Password?</a>
                    </div>

                    <div style="position: relative; margin-bottom: 20px;">
                        <hr style="border-color: #333;">
                        <span
                            style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: #1a1a2e; padding: 0 10px; color: #555; font-size: 0.8rem;">OR</span>
                    </div>

                    <div id="g_id_onload" data-client_id="<?php echo GOOGLE_CLIENT_ID; ?>"
                        data-callback="handleCredentialResponse" data-auto_prompt="false">
                    </div>
                    <div style="display: flex; justify-content: center; margin-top: 15px;">
                        <div class="g_id_signin" data-type="standard" data-size="large" data-theme="outline"
                            data-text="continue_with" data-shape="rectangular" data-logo_alignment="left"
                            data-width="340">
                        </div>
                    </div>

                    <p class="toggle-text" style="margin-top: 20px;">
                        Don't have an account? <a onclick="toggleForms()">Register Now</a>
                    </p>
                </form>

                <!-- Hidden form for Google logic (now handled via autofill) -->
                <form id="google-login-form" method="POST"
                    action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="display:none;">
                    <input type="hidden" name="google_credential" id="google_credential">
                </form>
            </div>

            <!-- REGISTER FORM -->
            <div id="register-form" class="form-section hidden">
                <h2>Create Account</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" autocomplete="nope">
                    <!-- Dummy fields to capture browser autofill -->
                    <input type="text" name="prevent_autofill_reg" style="display:none" tabindex="-1">
                    <input type="password" name="password_fake_reg" style="display:none" tabindex="-1">

                    <div class="input-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" required placeholder="Enter your name" autocomplete="off">
                    </div>
                    <div class="input-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required placeholder="Enter your email"
                            autocomplete="new-email" readonly onfocus="this.removeAttribute('readonly');">
                    </div>
                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" name="password" required placeholder="Create a password"
                            autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');">
                    </div>

                    <button type="submit" name="register" class="btn-submit">Sign Up</button>

                    <div style="position: relative; margin-bottom: 20px;">
                        <hr style="border-color: #333;">
                        <span
                            style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: #1a1a2e; padding: 0 10px; color: #555; font-size: 0.8rem;">OR</span>
                    </div>

                    <div style="display: flex; justify-content: center; margin-top: 15px;">
                        <div class="g_id_signin" data-type="standard" data-size="large" data-theme="outline"
                            data-text="continue_with" data-shape="rectangular" data-logo_alignment="left"
                            data-width="340">
                        </div>
                    </div>

                    <p class="toggle-text">
                        Already have an account? <a onclick="toggleForms()">Login Here</a>
                    </p>
                </form>
            </div>

        </div>
    </div>

    <script>
        function toggleForms() {
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');

            if (loginForm.classList.contains('hidden')) {
                loginForm.classList.remove('hidden');
                registerForm.classList.add('hidden');
            } else {
                loginForm.classList.add('hidden');
                registerForm.classList.remove('hidden');
            }
        }

        function handleCredentialResponse(response) {
            // Send the credential to the server for full login and dashboard redirection
            const loginForm = document.getElementById('google-login-form');
            const credentialInput = document.getElementById('google_credential');

            if (loginForm && credentialInput) {
                credentialInput.value = response.credential;
                loginForm.submit();
            }
        }
    </script>

</body>

</html>