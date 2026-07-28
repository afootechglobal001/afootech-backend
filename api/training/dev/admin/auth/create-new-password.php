<?php
require_once '../../config/connection.php';
try {
	if (!$checkBasicSecurity) {
		throw new UnauthorizedException("Unauthorized access! Please log in and try again.");
	}

	// ////// get all input parameters
	$accessKey = trim($_GET['accessKey']);
	$password = $data['password'];
	$confirmPassword = $data['confirmPassword'];

	//// validate input parameters
	validateEmptyField($accessKey, "ACCESS KEY");
	validateEmptyField($password, "PASSWORD");
	validateEmptyField($confirmPassword, "CONFIRM PASSWORD");
	if ($password !== $confirmPassword) {
		throw new BadRequestException("PASSWORD NOT MATCH! Check the Passwords and try again.");
	}
	///validate this accessKey combination
	$selectQuery = "SELECT staffId FROM STAFF_TAB WHERE accessKey = ?";
	$selectParams = [$accessKey];
	$userData = selectQuery($conn, $selectQuery, 's', $selectParams);

	if (empty($userData)) {
		throw new UnauthorizedException("ACCESS DENIED! session expired. Please try again.");
	}

	/* Secure password hashing */
	$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
	/* Update with prepared statement */
	$updateQuery = "UPDATE STAFF_TAB SET password = ?, accessKey = NULL, otp = NULL, updatedTime = NOW() WHERE accessKey = ?";
	$updateParams = [$hashedPassword, $accessKey];
	updateQuery($conn, $updateQuery, 'ss', $updateParams);

	$response = [
		'response' => 200,
		'success' => true,
		'message' => "PASSWORD RESET SUCCESSFUL! You can now login with your new password.",
	];

} catch (Throwable $e) {
	ErrorHandler::handle($e);
}
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>