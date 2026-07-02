<?php
//// getstudent details
$selectQuery = "SELECT * FROM STUDENTS_TEMP_TAB WHERE studentId = ?";
$params = [$studentId];
$dataTypes = "s"; // 'i' for integer, 's' for string, etc.
$studentData = selectQuery($conn, $selectQuery, $dataTypes, $params)[0];

$firstName = $studentData['firstName'];
$lastName = $studentData['lastName'];
$emailAddress = $studentData['emailAddress'];
$phoneNumber = $studentData['phoneNumber'];
$institutionTypeId = $studentData['institutionTypeId'];
$institutionName = $studentData['institutionName'];
$departmentName = $studentData['departmentName'];
$levelId = $studentData['levelId'];
$matricNumber = $studentData['matricNumber'];
$programId = $studentData['programId'];
$courseId = $studentData['courseId'];
$durationId = $studentData['durationId'];
$registrationDate = $studentData['createdTime'];


/// get institution type details
$institutionTypeData = _get_institution_type_details($conn, $institutionTypeId);
$institutionTypeName = $institutionTypeData['institutionTypeName'];

/// get institution level details
$institutionLevelData = _get_institution_level_details($conn, $institutionTypeId, $levelId);
$institutionLevelName = $institutionLevelData['levelName'];

/// get program details
$programData = _get_program_details($conn, $programId);
$programName = $programData['programName'];

/// get course details
$courseData = _get_course_details($conn, $courseId);
$courseName = $courseData['courseName'];
/// get program course duration details
$programCourseDurationData = _get_program_course_duration_details($conn, $durationId);
$durationName = $programCourseDurationData['durationName'];

// get payment details
$selectQuery = "SELECT * FROM PAYMENTS_TAB WHERE studentId = ? AND paymentPurposeId = 'form'";
$params = [$studentId];
$dataTypes = "s"; // 'i' for integer, 's' for string, etc.
$paymentData = selectQuery($conn, $selectQuery, $dataTypes, $params)[0];
$paymentId = $paymentData['paymentId'];
$paymentPurposeId = $paymentData['paymentPurposeId'];
$paymentMethodId = $paymentData['paymentMethodId'];
$paymentAmount = $paymentData['amount'];
$paymentStatusId = $paymentData['statusId'];
$paymentDate = $paymentData['payDate'];

//get payment purpose details
$paymentPurposeData = _get_payment_purpose_details($conn, $paymentPurposeId);
$paymentPurposeName = $paymentPurposeData['paymentPurposeName'];

//get payment method details
$paymentMethodData = _get_payment_method_details($conn, $paymentMethodId);
$paymentMethodName = $paymentMethodData['paymentMethodName'];

/// get payment status details
$paymentStatusData = _get_status_details($conn, $paymentStatusId);
$paymentStatusName = $paymentStatusData['statusName'];

/// get all setup backend settings details
$sesstingsData = _get_setup_backend_settings_detail($conn);
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

    $recieverName = $firstName . ' ' . $lastName;
    $sendTo = "afootech2016@gmail.com";  // Recipient email address
    $subject = "$recieverName Attempted Payment for $programName Training Registration on $currentDate";


    $message = '
<div style="width:100%; background:#f4f6f8; padding:30px 0; font-family:Arial, Helvetica, sans-serif;">
    <div style="max-width:700px; margin:auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,0.08);">

        <img src="cid:mail_header" width="100%" style="display:block;">

        <div style="padding:30px; color:#333;">

            <h2 style="color:#002B71; margin-top:0;">Payment Attempted</h2>

            <p>Dear <strong>' . $recieverName . '</strong>,</p>

            <p>Your attempt to register for the <strong>' . $programName . '</strong> program has been recorded. Below are your submitted details:</p>

            <!-- ================= BIO DATA ================= -->
            <h3 style="color:#002B71; margin-top:30px;">Bio Data Details</h3>
            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse; font-size:14px;">
                <tr style="background:#f9fafb;">
                    <td style="border:1px solid #eee;"><strong>Student ID</strong></td>
                    <td style="border:1px solid #eee;">' . $studentId . '</td>
                </tr>
                <tr style="background:#f9fafb;">
                    <td style="border:1px solid #eee;"><strong>First Name</strong></td>
                    <td style="border:1px solid #eee;">' . $firstName . '</td>
                </tr>
                <tr style="background:#f9fafb;">
                    <td style="border:1px solid #eee;"><strong>Last Name</strong></td>
                    <td style="border:1px solid #eee;">' . $lastName . '</td>
                </tr>
                <tr>
                    <td style="border:1px solid #eee;"><strong>Email</strong></td>
                    <td style="border:1px solid #eee;">' . $emailAddress . '</td>
                </tr>
                <tr style="background:#f9fafb;">
                    <td style="border:1px solid #eee;"><strong>Phone Number</strong></td>
                    <td style="border:1px solid #eee;">' . $phoneNumber . '</td>
                </tr>
                <tr style="background:#f9fafb;">
                    <td style="border:1px solid #eee;"><strong>Registration Date</strong></td>
                    <td style="border:1px solid #eee;">' . $registrationDate . '</td>
                </tr>                
            </table>

            <!-- ================= INSTITUTION DETAILS ================= -->
            <h3 style="color:#002B71; margin-top:30px;">Student Institution Details</h3>
            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse; font-size:14px;">
                <tr style="background:#f9fafb;">
                    <td style="border:1px solid #eee;"><strong>Institution Class</strong></td>
                    <td style="border:1px solid #eee;">' . $institutionTypeName . '</td>
                </tr>
                <tr style="background:#f9fafb;">
                    <td style="border:1px solid #eee;"><strong>Institution Name</strong></td>
                    <td style="border:1px solid #eee;">' . $institutionName . '</td>
                </tr>
                <tr style="background:#f9fafb;">
                    <td style="border:1px solid #eee;"><strong>Department</strong></td>
                    <td style="border:1px solid #eee;">' . $departmentName . '</td>
                </tr>
                <tr style="background:#f9fafb;">
                    <td style="border:1px solid #eee;"><strong>Student Level</strong></td>
                    <td style="border:1px solid #eee;">' . $institutionLevelName . '</td>
                </tr>
                <tr>
                    <td style="border:1px solid #eee;"><strong>Matric Number</strong></td>
                    <td style="border:1px solid #eee;">' . $matricNumber . '</td>
                </tr>
            </table>

            <!-- ================= TRAINING DETAILS ================= -->
            <h3 style="color:#002B71; margin-top:30px;">Training Details</h3>
            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse; font-size:14px;">
                <tr style="background:#f9fafb;">
                    <td style="border:1px solid #eee;"><strong>Program</strong></td>
                    <td style="border:1px solid #eee;">' . $programName . '</td>
                </tr>
                <tr style="background:#f9fafb;">
                    <td style="border:1px solid #eee;"><strong>Course</strong></td>
                    <td style="border:1px solid #eee;">' . $courseName . '</td>
                </tr>
                <tr style="background:#f9fafb;">
                    <td style="border:1px solid #eee;"><strong>Training Duration</strong></td>
                    <td style="border:1px solid #eee;">' . $durationName . '</td>
                </tr>
            </table>

            <!-- ================= PAYMENT DETAILS ================= -->
            <h3 style="color:#002B71; margin-top:30px;">Payment Details</h3>
            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse; font-size:14px;">
                <tr style="background:#f9fafb;">
                    <td style="border:1px solid #eee;"><strong>Transaction ID</strong></td>
                    <td style="border:1px solid #eee;">' . $paymentId . '</td>
                </tr>
                <tr style="background:#f9fafb;">
                    <td style="border:1px solid #eee;"><strong>Amount</strong></td>
                    <td style="border:1px solid #eee;">NGN ' . $paymentAmount . '</td>
                </tr>
                <tr style="background:#f9fafb;">
                    <td style="border:1px solid #eee;"><strong>Payment Purpose</strong></td>
                    <td style="border:1px solid #eee;">' . $paymentPurposeName . '</td>
                </tr>
                <tr style="background:#f9fafb;">
                    <td style="border:1px solid #eee;"><strong>Payment Method</strong></td>
                    <td style="border:1px solid #eee;">' . $paymentMethodName . '</td>
                </tr>
                <tr style="background:#f9fafb;">
                    <td style="border:1px solid #eee;"><strong>Payment Status</strong></td>
                    <td style="border:1px solid #eee;">' . $paymentStatusName . '</td>
                </tr>
                <tr style="background:#f9fafb;">
                    <td style="border:1px solid #eee;"><strong>Payment Date</strong></td>
                    <td style="border:1px solid #eee;">' . $paymentDate . '</td>
                </tr>
            </table>
            
             <p style="margin-top:30px;">
                Please keep this email for your records. If you notice any discrepancy, contact us immediately.
            </p>

            <p style="margin-top:30px;">
                Regards,<br>
                <strong>' . $appName . '</strong>
            </p>

        </div>

        <div style="background:#002B71; color:#ffffff; text-align:center; padding:15px; font-size:13px;">
            &copy; ' . date("Y") . ' ' . $appName . '. All Rights Reserved.
        </div>

    </div>
</div>
';



    $mail->Subject = $subject;
    $mail->Body = $message;
    $mail->AltBody = strip_tags($message);  // Fallback for non-HTML clients

    /// copy this emails
    $mail->addAddress($sendTo, $recieverName);  // Recipient email and name
    $mail->addAddress($supportEmail, $senderName);  // Support email

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