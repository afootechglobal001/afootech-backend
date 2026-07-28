<?php
require_once '../../config/connection.php';
try {
	if (!$checkBasicSecurity) {
		throw new UnauthorizedException("Unauthorized access! Please log in and try again.");
	}

	// ////// get all input parameters
	$staffId = trim($_GET['staffId']);
	$otp = trim($data['otp']);

	//// validate input parameters
	validateEmptyField($staffId, "STAFF ID");
	validateEmptyField($otp, "OTP");
	validateNumericField($otp, "OTP");

	/* Use prepared statement for SELECT */
	$selectQuery = "SELECT staffId FROM STAFF_TAB WHERE staffId = ? AND otp = ?";
	$selectParams = [$staffId, $otp];
	$userData = selectQuery($conn, $selectQuery, 'is', $selectParams);

	if (empty($userData)) {
		throw new BadRequestException("INVALID OTP! Check the OTP and try again.");
	}
	////generate access key and update database
	$accessKey = trim(md5($staffId . date("Ymdhis")));
	/// update OTP in database using prepared statement
	$updateQuery = "UPDATE STAFF_TAB SET accessKey = ? WHERE staffId = ?";
	$updateParams = [$accessKey, $staffId];
	updateQuery($conn, $updateQuery, 'ss', $updateParams);

	$response = [
		'response' => 200,
		'success' => true,
		'message' => "OTP VERIFIED SUCCESSFULLY! You can now proceed with resetting your password.",
		'accessKey' => $accessKey
	];

} catch (Throwable $e) {
	ErrorHandler::handle($e);
}
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>