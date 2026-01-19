<?php
/**
 * Local Email Viewer
 * Allows you to see the emails sent by your local server.
 */
session_start();
$log_file = 'local_emails.json';
$emails = [];

if (file_exists($log_file)) {
    $emails = json_decode(file_get_contents($log_file), true) ?: [];
}

// Handle clearing
if (isset($_GET['clear'])) {
    file_put_contents($log_file, json_encode([]));
    header("Location: view_emails.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Email Inbox - GymFit</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&family=Oswald:wght@500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #0c0c14;
            color: #fff;
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 40px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        h1 {
            font-family: 'Oswald', sans-serif;
            color: #ceff00;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .controls {
            margin-bottom: 20px;
            display: flex;
            justify-content: flex-end;
        }

        .btn-clear {
            background: #ff4d4d;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .email-card {
            background: #161625;
            border: 1px solid #333;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: 0.3s;
        }

        .email-card:hover {
            border-color: #ceff00;
        }

        .meta {
            font-size: 0.85rem;
            color: #aaa;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }

        .subject {
            font-size: 1.1rem;
            font-weight: 500;
            color: #ceff00;
            margin-bottom: 15px;
        }

        .message {
            background: #0c0c14;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            white-space: pre-wrap;
            margin-bottom: 20px;
            border: 1px solid #222;
        }

        .action {
            text-align: right;
        }

        .btn-reset {
            background: #ceff00;
            color: #000;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
        }

        .empty {
            text-align: center;
            padding: 60px;
            background: #161625;
            border-radius: 10px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1><i class="fas fa-inbox"></i> Local Test Inbox</h1>

        <div class="controls">
            <?php if (!empty($emails)): ?>
                <a href="?clear=1" class="btn-clear"><i class="fas fa-trash"></i> Clear Inbox</a>
            <?php endif; ?>
        </div>

        <?php if (empty($emails)): ?>
            <div class="empty">
                <i class="fas fa-envelope-open fa-3x" style="margin-bottom: 20px;"></i>
                <p>No test emails found. Try requesting a password reset!</p>
            </div>
        <?php else: ?>
            <?php foreach ($emails as $email): ?>
                <div class="email-card">
                    <div class="meta">
                        <span>To: <strong><?php echo htmlspecialchars($email['to']); ?></strong></span>
                        <span><?php echo $email['time']; ?></span>
                    </div>
                    <div class="subject"><?php echo htmlspecialchars($email['subject']); ?></div>
                    <div class="message"><?php echo htmlspecialchars($email['message']); ?></div>
                    <?php if ($email['link']): ?>
                        <div class="action">
                            <a href="<?php echo htmlspecialchars($email['link']); ?>" class="btn-reset">Click to Reset Password</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div style="margin-top: 40px; text-align: center;">
            <a href="forgot_password.php" style="color: #aaa; text-decoration: none;"><i class="fas fa-arrow-left"></i>
                Back to Site</a>
        </div>
    </div>
</body>

</html>