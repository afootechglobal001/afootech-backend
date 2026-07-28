<?php
require_once '../../config/connection.php';
try {
    if (!$checkBasicSecurity) {
        throw new ForbiddenException("Unauthorized access! Please log in and try again.");
    }

    // ////// get all input parameters
    $emailAddress = trim($data['emailAddress'] ?? '');

    //// validate input parameters
    validateEmptyField($emailAddress, "EMAIL");
    validateEmailField($emailAddress, "EMAIL");

    /* Check staff status*/
    $selectQuery = "SELECT staffId, CONCAT(firstName, ' ', lastName) AS fullName, emailAddress, statusId FROM STAFF_TAB WHERE emailAddress = ?";
    $selectParams = [$emailAddress];
    $userData = selectQuery($conn, $selectQuery, 's', $selectParams)[0];
    $staffId = $userData['staffId'] ?? null;
    $statusId = $userData['statusId'] ?? null;
    $fullName = $userData['fullName'] ?? null;

    if (empty($userData)) {
        throw new ForbiddenException("INVALID EMAIL ADDRESS! Enter a valid email address and try again");
    }
    if ($statusId === 2) {
        throw new ForbiddenException("ACCOUNT SUSPENDED! Contact the administrator for more info.");
    }
    if ($statusId !== 1) {
        throw new ForbiddenException("ACCOUNT UNDER REVIEW! Contact the administrator for more info.");
    }

    /* Generate secure OTP */
    $otp = random_int(100000, 999999);

    /// update OTP in database using prepared statement
    $updateQuery = "UPDATE STAFF_TAB SET otp=? WHERE staffId = ?";
    $updateParams = [$otp, $staffId];
    updateQuery($conn, $updateQuery, 'is', $updateParams);
    /* Send OTP email */
    require_once('../../mail/admin/reset-password.php');
    $response = [
        'response' => 200,
        'success' => true,
        'message' => "OTP SENT! An OTP has been sent to your email address. Use it to reset your password.",
        'staffId' => $staffId,
        'fullName' => $fullName,
        'emailAddress' => $emailAddress,
    ];

} catch (Throwable $e) {
    ErrorHandler::handle($e);
}
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>