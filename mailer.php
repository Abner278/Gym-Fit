<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

function sendMail($to, $subject, $message)
{
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host = 'ssl://smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'abnersamjose2028@mca.ajce.in'; // CORRECT EMAIL                     
        $mail->Password = 'tuazqeemnumuoyou'; // App Password (No Spaces)                               
        $mail->Port = 465;

        //Recipients
        $mail->setFrom('abnersamjose2028@mca.ajce.in', 'GymFit Team');
        $mail->addAddress($to);

        //Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = nl2br($message);
        $mail->AltBody = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error safely if needed
        return false;
    }
}
?>