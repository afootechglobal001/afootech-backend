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
    // ////// get all input parameters  
    $studentId = $_GET['studentId'];
    //// validate input parameters
    validateEmptyField($studentId, 'STUDENT ID');

    $dataQuery = mysqli_query($conn, " SELECT 
    `paymentId`, 
    `studentId`,
    `studentProgramId`, 
    `emailAddress`, 
    `phoneNumber`, 
    `paymentPurposeId`, 
    `amount`, 
    `paymentMethodId`, 
    `statusId`, 
    `confirmedBy`, 
    `createdTime`, 
    `payDate`, 
    `updatedTime` 
    FROM PAYMENTS_TAB 
    WHERE studentId='$studentId'");

    if (!$dataQuery || mysqli_num_rows($dataQuery) == 0) {
        throw new BadRequestException("NO PAYMENTS FOUND FOR THIS STUDENT!");
    }

    $paymentDatafetch = mysqli_fetch_all($dataQuery, MYSQLI_ASSOC);

    foreach ($paymentDatafetch as &$payment) {
        $paymentPurposeId = $payment['paymentPurposeId'];
        $paymentMethodId = $payment['paymentMethodId'];
        $statusId = $payment['statusId'];
        $confirmedBy = $payment['confirmedBy'];

        $paymentPurposeData = _get_payment_purpose_details($conn, $paymentPurposeId);
        $payment['paymentPurposeData'] = $paymentPurposeData;

        $paymentMethodData = _get_payment_method_details($conn, $paymentMethodId);
        $payment['paymentMethodData'] = $paymentMethodData;

        $statusData = _get_status_details($conn, $statusId);
        $payment['statusData'] = $statusData;

        $confirmedByData = _action_performed_by($conn, $confirmedBy);
        $payment['confirmedByData'] = $confirmedByData;

    }

    $response = [
        'response' => 200,
        'success' => true,
        'message' => "PROCEED TO PAYMENT VERIFICATION",
        'paymentData' => $paymentDatafetch,
    ];


} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);