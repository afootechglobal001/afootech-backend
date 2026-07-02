<?php
require_once '../config/connection.php';
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
		throw new NotFoundException("STUDENT RECORD NOT FOUND! The specified email address does not exist.");
	}
	$studentData = $studentData[0];
	$studentId = $studentData['studentId'];

	/* Send OTP email */
	require_once('../mail/students/registration-payment-success-email.php');

	$response = [
		'response' => 200,
		'success' => true,
		'message' => "ACKNOWLEDGMENT EMAIL RESENT SUCCESSFULLY! Please check your email INBOX or SPAM folder for the acknowledgment slip.",
	];

} catch (Throwable $e) {
	ErrorHandler::handle($e);
}
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>