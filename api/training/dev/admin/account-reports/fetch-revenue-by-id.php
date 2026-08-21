<?php
require_once '../../config/connection.php';
require_once '../../config/staff-session-check.php';
try {
    if (!$checkBasicSecurity) {
        throw new ForbiddenException("Unauthorized access! Please log in.");
    }

    if (!$checkSession) {
        throw new UnauthorizedException("SESSION EXPIRED! Please LogIn Again.");
    }
    // ////// get all input parameters  
    $paymentId = $_GET['paymentId'];
    //// validate input parameters
    validateEmptyField($paymentId, 'PAYMENT ID');


    $select = "SELECT 
        paymentId, 
        studentId, 
        emailAddress, 
        phoneNumber, 
        paymentPurposeId, 
        amount, 
        paymentMethodId, 
        paystackId, 
        paystackCharges, 
        paystackRemittance, 
        statusId, 
        confirmedBy, 
        createdTime, 
        payDate, 
        updatedTime
        FROM PAYMENTS_TAB
        WHERE paymentId='$paymentId'
    ";
    $fetchDataQuery = selectQuery($conn, $select)[0];

    $studentId = $fetchDataQuery['studentId'];
    $paymentPurposeId = $fetchDataQuery['paymentPurposeId'];
    $paymentMethodId = $fetchDataQuery['paymentMethodId'];
    $statusId = $fetchDataQuery['statusId'];
    $confirmedBy = $fetchDataQuery['confirmedBy'];

    $studentData = ($paymentPurposeId === 'form' && $statusId !== 5)
        ? _get_prospective_details($conn, $studentId)
        : _get_registered_details($conn, $studentId);

    $fetchDataQuery['studentData'] = $studentData;
    /// get payment purpose details
    $paymentPurposeData = _get_payment_purpose_details($conn, $paymentPurposeId);
    $fetchDataQuery['paymentPurposeData'] = $paymentPurposeData;
    /// get payment method details
    $paymentMethodData = _get_payment_method_details($conn, $paymentMethodId);
    $fetchDataQuery['paymentMethodData'] = $paymentMethodData;
    /// get status details
    $statusData = _get_status_details($conn, $statusId);
    $fetchDataQuery['statusData'] = $statusData;
    /// get confirmed by details
    $confirmedByData = _action_performed_by($conn, $confirmedBy);
    $fetchDataQuery['confirmedByData'] = $confirmedByData;



    ///// update PAYMENTS_TAB to mark this payment as viewed by the admin
    mysqli_query($conn, "
    UPDATE PAYMENTS_TAB
    SET viewedBy = CONCAT(IFNULL(viewedBy, ''), '$loginStaffId,')
    WHERE paymentId = '$paymentId'
    AND CONCAT(',', IFNULL(viewedBy, ''), ',') NOT LIKE '%,$loginStaffId,%'
    ") or die(mysqli_error($conn));

    $response = [
        'response' => 200,
        'success' => true,
        'message' => "PAYMENT TRANSACTION FETCHED SUCCESSFULLY",
        'data' => $fetchDataQuery,
    ];


} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);