<?php
/**
 * EMAIL DIAGNOSTIC TOOL
 * Run this to see why emails are failing.
 */

// Enable Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>GymFit Email System Test</h1>";
echo "<div style='font-family: monospace; background: #eee; padding: 20px;'>";

// 1. Check OpenSSL
echo "<strong>Checking OpenSSL...</strong> ";
if (extension_loaded('openssl')) {
    echo "<span style='color:green'>INSTALLED (OK)</span><br>";
} else {
    echo "<span style='color:red'>MISSING! (Critical Error)</span><br>";
    echo "Please enable 'extension=openssl' in php.ini<br>";
    exit; // Stop here
}

// 2. Load Libraries
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 3. Configure Mailer with DEBUGGING ON
$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 2; // Enable verbose debug output
    $mail->Debugoutput = 'html'; // Show in browser

    $mail->isSMTP();
    $mail->Host = 'ssl://smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'abnersamjose2028@mca.ajce.in'; // CORRECT EMAIL
    $mail->Password = 'tuazqeemnumuoyou'; // App Password
    $mail->Port = 465;

    $mail->setFrom('abnersamjose2028@mca.ajce.in', 'GymFit Test');
    $mail->addAddress('abnersamjose2028@mca.ajce.in'); // Send to yourself for testing

    $mail->Subject = 'GymFit Connection Test';
    $mail->Body = 'If you see this, the connection is working!';

    echo "<hr><strong>Attempting to Connect to Gmail...</strong><br>";
    $mail->send();
    echo "<hr><strong style='color:green'>SUCCESS! Email sent. Check your inbox.</strong>";

} catch (Exception $e) {
    echo "<hr><strong style='color:red'>FAILURE! Could not send.</strong><br>";
    echo "Error: " . $mail->ErrorInfo;
}
echo "</div>";
?>