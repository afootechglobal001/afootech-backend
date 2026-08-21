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
    $paymentId = $data['paymentId'];
    //// validate input parameters
    validateEmptyField($paymentId, 'PAYMENT ID');

    $dataQuery = mysqli_query($conn, "
    SELECT paymentId, paymentPurposeId, createdTime
    FROM PAYMENTS_TAB 
    WHERE paymentId='$paymentId'
");

    if (!$dataQuery || mysqli_num_rows($dataQuery) == 0) {
        throw new BadRequestException("INVALID PAYMENT ID! Check payment ID and try again.");
    }

    ///// update PAYMENTS_TAB to verify the staff that is verifying the payment
    $updateQuery = "UPDATE PAYMENTS_TAB SET statusId=3 WHERE paymentId=?";
    $updateParams = [$paymentId];
    updateQuery($conn, $updateQuery, 's', $updateParams);

    $response = [
        'response' => 200,
        'success' => true,
        'message' => "PAYMENT RECORD UPDATED! Payment record has been updated for reconciliation.",

    ];


} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);