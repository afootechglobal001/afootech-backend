<?php
require_once '../../config/connection.php';
try {
	if (!$checkBasicSecurity) {
		throw new ForbiddenException("Unauthorized access! Permission denied to access this resource.");
	}

	//////////////////declaration of variables//////////////////////////////////////
	$emailAddress = $_GET['emailAddress'];

	validateEmptyField($emailAddress, "EMAIL ADDRESS");

	/// get student details from STUDENTS_TEMP_TAB
	$selectQuery = "SELECT * FROM STUDENTS_TAB WHERE emailAddress = ?";
	$params = [$emailAddress];
	$dataTypes = "s"; // 'i' for integer, 's' for string
	$studentData = selectQuery($conn, $selectQuery, $dataTypes, $params);
	if (empty($studentData)) {
		throw new NotFoundException("STUDENT RECORD NOT FOUND! Proceed with your registration first and provide the required details to access your acceptance letter.");
	}
	$studentData = $studentData[0];
	$studentId = $studentData['studentId'];

	/// get if student training status is active from STUDENTS_PROGRAM_DETAILS_TAB
	$selectQuery = "SELECT * FROM STUDENTS_PROGRAM_DETAILS_TAB WHERE studentId = ? AND trainingStatusId = 1";
	$params = [$studentId];
	$dataTypes = "s"; // 'i' for integer, 's' for string
	$studentProgramData = selectQuery($conn, $selectQuery, $dataTypes, $params);
	if (empty($studentProgramData)) {
		throw new ForbiddenException("ACCESS DENIED! Your training status is not active. Please contact our support team for assistance.");
	}

	/// generate OTP and update STUDENTS_TAB with the generated OTP
	$otp = random_int(100000, 999999);

	$updateQuery = "UPDATE `STUDENTS_TAB` SET `otp` = ? WHERE studentId= ? AND emailAddress = ?";
	$params = [$otp, $studentId, $emailAddress];
	$dataTypes = "iss"; // 'i' for integer, 's' for string
	updateQuery($conn, $updateQuery, $dataTypes, $params);


	/* Send OTP email */
	require_once('../../mail/students/verify-student-activation-otp-email.php');

	$response = [
		'response' => 200,
		'success' => true,
		'message' => "STUDENT VERIFICATION OTP EMAIL SENT SUCCESSFULLY! Please check your email INBOX or SPAM folder for the OTP.",
		'data' => [
			'studentId' => $studentId,
			'emailAddress' => $emailAddress,
			'fullName' => $studentData['firstName'] . ' ' . $studentData['lastName'],
			'phoneNumber' => $studentData['phoneNumber'],
		],
	];

} catch (Throwable $e) {
	ErrorHandler::handle($e);
}
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>