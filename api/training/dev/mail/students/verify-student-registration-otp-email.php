<?php

//// getstudent details
$selectQuery = "SELECT * FROM STUDENTS_TAB WHERE studentId = ?";
$params = [$studentId];
$dataTypes = "s"; // 'i' for integer, 's' for string, etc.
$studentData = selectQuery($conn, $selectQuery, $dataTypes, $params)[0];
$otp = $studentData['otp'];
$firstName = $studentData['firstName'];
$lastName = $studentData['lastName'];
$emailAddress = $studentData['emailAddress'];
$phoneNumber = $studentData['phoneNumber'];


/// get all setup backend settings details
$sesstingsData = _get_setup_backend_settings_detail($conn, 'S001');
$smtpHost = $sesstingsData['smtpHost'];
$smtpUsername = $sesstingsData['smtpUsername'];
$smtpPassword = $sesstingsData['smtpPassword'];
$smtpPort = $sesstingsData['smtpPort'];
$senderName = $sesstingsData['senderName'];
$supportEmail = $sesstingsData['supportEmail'];
$currentDate = date("l, d F Y");


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../mail/PHPMailer/src/PHPMailer.php';
require '../mail/PHPMailer/src/SMTP.php';
require '../mail/PHPMailer/src/Exception.php';

$mail = new PHPMailer(true);

try {

    $mail->SMTPDebug = SMTP::DEBUG_OFF;  // Disable verbose debug output
    $mail->isSMTP();  // Set mailer to use SMTP
    $mail->Host = $smtpHost;  // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;  // Enable SMTP authentication
    $mail->Username = $smtpUsername;  // SMTP username
    $mail->Password = $smtpPassword;  // SMTP password
    $mail->SMTPSecure = 'ssl';  // Enable SSL encryption
    $mail->Port = $smtpPort;  // TCP port to connect to

    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];
    $mail->isHTML(true);
    //// sender and replyTo
    $mail->setFrom($smtpUsername, $senderName);
    $mail->addReplyTo($supportEmail, $senderName); // Reply-to address


    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    $sendTo = $emailAddress;
    $recieverName = $firstName . ' ' . $lastName;
    $subject = "Registration Verification OTP -- $otp";

    $message = '
<div style="width:100%; background:#f4f6f8; padding:30px 0; font-family:Arial, Helvetica, sans-serif;">
  <div style="max-width:600px; margin:auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,0.08);">

    <img src="cid:mail_header" width="100%" style="display:block;">

    <div style="padding:30px; color:#333333;">

        <h2 style="color:#07418d; margin-top:0;">Email Verification Required</h2>

        <p>Dear <strong>' . $firstName . ' ' . $lastName . '</strong>,</p>

        <p>We received a request to verify your email address for your <strong>' . $appName . '</strong> administrative account.</p>

        <p>Please use the One-Time Password (OTP) below to complete your verification:</p>

        <div style="text-align:center; margin:30px 0;">
            <div style="
                display:inline-block;
                font-size:32px;
                letter-spacing:6px;
                font-weight:bold;
                color:#07418d;
                background:#f1f5ff;
                padding:15px 30px;
                border-radius:8px;
                border:1px dashed #07418d;">
                ' . $otp . '
            </div>
        </div>

        <p style="text-align:center; font-weight:bold; color:#555;">
            This code expires in 15 minutes
        </p>

        <p>If you did not request this verification, please ignore this email or contact our support team immediately.</p>

        <p style="margin-top:30px;">
            Need help? Contact us at 
            <a href="mailto:' . $supportEmail . '" style="color:#07418d;">' . $supportEmail . '</a>
        </p>

        <p style="margin-top:30px;">
            Best regards,<br>
            <strong>' . $senderName . '</strong><br>
            ' . $websiteUrl . '
        </p>

    </div>

    <div style="background:#07418d; color:#ffffff; text-align:center; padding:15px; font-size:13px;">
        &copy; ' . date("Y") . ' ' . $senderName . '. All Rights Reserved.
    </div>

  </div>
</div>
';


    $mail->Subject = $subject;
    $mail->Body = $message;
    $mail->AltBody = strip_tags($message);  // Fallback for non-HTML clients

    /// copy this emails
    $mail->addAddress($sendTo, $recieverName);  // Recipient email and name
    $mail->addAddress($supportEmail, $senderName); // Reply-to address

    // Attach images
    $mail->addEmbeddedImage('../mail/img/mail_header.jpg', 'mail_header');

    // Send the email
    if (!$mail->send()) {
        echo 'Not Working';
    }

} catch (Exception $e) {
    // Handle PHPMailer exceptions
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>