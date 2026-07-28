<?php
require_once '../../config/connection.php';
try {
	if (!$checkBasicSecurity) {
		throw new UnauthorizedException("Unauthorized access! Please log in and try again.");
	}

	// ////// get all input parameters
	$userName = trim($data['userName']);
	$password = $data['password'];

	//// validate input parameters
	validateEmptyField($userName, "USERNAME");
	validateEmptyField($password, "PASSWORD");
	validateEmailField($userName, "USERNAME");

	/* Secure SELECT using prepared statement */
	$selectQuery = "SELECT * FROM STAFF_TAB WHERE emailAddress = ?";
	$selectParams = [$userName];
	$userData = selectQuery($conn, $selectQuery, 's', $selectParams)[0];
	$staffId = $userData['staffId'];
	$statusId = $userData['statusId'];
	$passwordHash = $userData['password'];

	if (empty($userData)) {
		throw new BadRequestException("INVALID USERNAME! Kindly check the username and try again.");
	}
	if (!password_verify($password, $passwordHash)) {
		throw new BadRequestException("INVALID PASSWORD! Kindly check the password and try again.");
	}
	if ($statusId === 2) {
		throw new ForbiddenException("ACCOUNT SUSPENDED! Contact the administrator for more info.");
	}
	if ($statusId !== 1) {
		throw new ForbiddenException("ACCOUNT UNDER REVIEW! Contact the administrator for more info.");
	}
	////generate access key and update database
	$accessKey = trim(md5($staffId . date("Ymdhis")));
	$updateQuery = "UPDATE STAFF_TAB SET accessKey = ?,  lastLoginTime = NOW() WHERE staffId = ?";
	$updateParams = [$accessKey, $staffId];
	updateQuery($conn, $updateQuery, 'ss', $updateParams);

	///// fetch staff view
	$selectQuery = "SELECT 
	staffId, 
	accessKey, 
	firstName, 
	lastName, 
	emailAddress, 
	phoneNumber, 
	roleId, 
	statusId, 
	lastLoginTime, 
	createdBy, 
	updatedBy, 
	createdTime, 
	updatedTime 
	FROM STAFF_TAB 
	WHERE staffId = ?";

	$selectParams = [$staffId];
	$userData = selectQuery($conn, $selectQuery, 's', $selectParams)[0];
	$roleId = $userData['roleId'];
	$statusId = $userData['statusId'];
	$createdBy = $userData['createdBy'];
	$updatedBy = $userData['updatedBy'];

	/// get roleData
	$roleData = _get_role_details($conn, $roleId);
	$userData['roleData'] = $roleData;
	/// get statusData
	$statusData = _get_status_details($conn, $statusId);
	$userData['statusData'] = $statusData;
	/// get createdByData
	$createdByData = _action_performed_by($conn, $createdBy);
	$userData['createdByData'] = $createdByData;
	/// get updatedByData
	$updatedByData = _action_performed_by($conn, $updatedBy);
	$userData['updatedByData'] = $updatedByData;

	$response = [
		'response' => 200,
		'success' => true,
		'message' => "LOGIN SUCCESSFUL!",
		'data' => $userData
	];

} catch (Throwable $e) {
	ErrorHandler::handle($e);
}
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>