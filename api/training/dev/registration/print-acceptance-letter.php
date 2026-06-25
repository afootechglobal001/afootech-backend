<?php
require_once '../config/connection.php';
try {
	if (!$checkBasicSecurity) {
		throw new ForbiddenException("Unauthorized access! Permission denied to access this resource.");
	}

	//////////////////declaration of variables//////////////////////////////////////
	$studentId = $_GET['studentId'];
	$otp = $data['otp'];

	validateEmptyField($studentId, "STUDENT ID");
	validateEmptyField($otp, "OTP");
	validateNumericField($otp, "OTP");

	/// get student details from STUDENTS_TEMP_TAB
	$selectQuery = "SELECT * FROM STUDENTS_TAB WHERE studentId = ? AND otp = ?";
	$params = [$studentId, $otp];
	$dataTypes = "si"; // 'i' for integer, 's' for string
	$studentData = selectQuery($conn, $selectQuery, $dataTypes, $params);
	if (empty($studentData)) {
		throw new BadRequestException("INVALID OTP! The provided OTP does not match the one sent to your email address. Please check your email INBOX or SPAM folder for the correct OTP.");
	}
	$studentData = $studentData[0];
	$studentId = $studentData['studentId'];
	$studentName = $studentData['firstName'] . ' ' . $studentData['lastName'];

	//// get student institution details
	$selectQuery = "SELECT * FROM STUDENTS_INSTITUTION_DETAILS_TAB WHERE studentId = ?";
	$params = [$studentId];
	$dataTypes = "s"; // 'i' for integer, 's' for string, etc.
	$studentInstitutionData = selectQuery($conn, $selectQuery, $dataTypes, $params)[0];

	$institutionName = $studentInstitutionData['institutionName'];
	$departmentName = $studentInstitutionData['departmentName'];
	$matricNumber = $studentInstitutionData['matricNumber'];


	/// get student program details
	$selectQuery = "SELECT * FROM STUDENTS_PROGRAM_DETAILS_TAB WHERE studentId = ?";
	$params = [$studentId];
	$dataTypes = "s"; // 'i' for integer, 's' for string, etc.
	$studentProgramData = selectQuery($conn, $selectQuery, $dataTypes, $params)[0];

	$programId = $studentProgramData['programId'];
	$courseId = $studentProgramData['courseId'];
	$startDate = $studentProgramData['startDate'];
	$endDate = $studentProgramData['endDate'];
	/// get program details
	$programData = _get_program_details($conn, $programId);
	$programName = $programData['programName'];

	/// get course details
	$courseData = _get_course_details($conn, $courseId);
	$courseName = $courseData['courseName'];

	$response = [
		'response' => 200,
		'success' => true,
		'message' => "REGISTRATION VERIFICATION OTP EMAIL SENT SUCCESSFULLY! Please check your email INBOX or SPAM folder for the OTP.",
		'data' => [
			'studentId' => $studentId,
			'studentName' => $studentName,
			'institutionName' => $institutionName,
			'departmentName' => $departmentName,
			'matricNumber' => $matricNumber,
			'programName' => $programName,
			'courseName' => $courseName,
			'startDate' => $startDate,
			'endDate' => $endDate
		]
	];

} catch (Throwable $e) {
	ErrorHandler::handle($e);
}
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>