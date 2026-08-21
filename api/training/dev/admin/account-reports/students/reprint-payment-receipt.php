<?php
require_once '../../../config/connection.php';
require_once '../../../config/staff-session-check.php';
try {
    if (!$checkBasicSecurity) {
        throw new ForbiddenException("Unauthorized access! Please log in.");
    }

    if (!$checkSession) {
        throw new UnauthorizedException("SESSION EXPIRED! Please LogIn Again.");
    }
    $studentId = trim($_GET['studentId'] ?? '');
    $studentProgramId = trim($_GET['studentProgramId'] ?? '');
    $paymentId = trim($_GET['paymentId'] ?? '');

    validateEmptyField($studentId, "STUDENT ID");
    validateEmptyField($studentProgramId, "STUDENT PROGRAM ID");
    validateEmptyField($paymentId, "PAYMENT ID");

    /// confirm if this payment was successful and completed
    $selectQuery = "SELECT * FROM PAYMENTS_TAB WHERE paymentId = ? AND studentId = ? AND studentProgramId = ? AND statusId = 5";
    $params = [$paymentId, $studentId, $studentProgramId];
    $dataTypes = "sss"; // 'i' for integer, 's' for string, etc.
    $paymentData = selectQuery($conn, $selectQuery, $dataTypes, $params);
    if (empty($paymentData)) {
        throw new NotFoundException("PAYMENT RECORD NOT FOUND! The specified payment ID does not exist or the payment was not successful.");
    }

    require_once '../../../mail/students/reprint-student-payment-receipt-email.php';

    $response = [
        'response' => 200,
        'success' => true,
        'message' => "PAYMENT RECEIPT REPRINTED SUCCESSFULLY! Check your email for your payment receipt. If you have not received any email, please contact our support team for assistance.",

    ];


} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);