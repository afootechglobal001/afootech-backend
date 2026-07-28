<?php
require_once '../../config/connection.php';
require_once '../../config/staff-session-check.php';

try {
    if (!$checkBasicSecurity) {
        throw new ForbiddenException("Unauthorized access! Please log in and try again.");
    }
    if (!$checkSession) {
        throw new UnauthorizedException("SESSION EXPIRED! Please LogIn Again.");
    }

    ////////////////// Variables //////////////////
    $crId = trim($_GET['crId'] ?? '');
    $fullName = trim($data['fullName'] ?? '');
    $emailAddress = trim($data['emailAddress'] ?? '');
    $phoneNumber = trim($data['phoneNumber'] ?? '');
    $subject = trim($data['subject'] ?? '');
    $message = trim($data['message'] ?? '');
    $statusId = trim($data['statusId'] ?? '');

    ///// validation
    validateEmptyField($crId, "CR ID");
    validateEmptyField($fullName, "FULL NAME");
    validateEmptyField($emailAddress, "EMAIL ADDRESS");
    validateEmailField($emailAddress, "EMAIL ADDRESS");
    validateEmptyField($phoneNumber, "PHONE NUMBER");
    validateEmptyField($message, "MESSAGE");
    validateEmptyField($statusId, "STATUS ID");


    ////////////////// update Contact //////////////////
    $updateQuery = "UPDATE `CONTACTS_REVIEWS_TAB` SET
    `fullName` = ?,
    `emailAddress` = ?,
    `phoneNumber` = ?,
    `subject` = ?,
    `message` = ?,
    `statusId` = ?,
    `updatedBy` = ?,
    `updatedTime` = NOW()
    WHERE `crId` = ?";
    $updateParams = [$fullName, $emailAddress, $phoneNumber, $subject, $message, $statusId, $loginStaffId, $crId];
    updateQuery($conn, $updateQuery, "sssssiss", $updateParams);


    $response = [
        'response' => 200,
        'success' => true,
        'message' => "The contact review has been updated successfully!",
    ];


} catch (Throwable $e) {
    ErrorHandler::handle($e);
}
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>