<?php
require_once '../../config/connection.php';
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

	/// get if student training status is active from STUDENTS_PROGRAM_DETAILS_TAB
	$selectQuery = "SELECT * FROM STUDENTS_PROGRAM_DETAILS_TAB WHERE studentId = ? AND trainingStatusId = 1";
	$params = [$studentId];
	$dataTypes = "s"; // 'i' for integer, 's' for string
	$studentProgramData = selectQuery($conn, $selectQuery, $dataTypes, $params);
	if (empty($studentProgramData)) {
		throw new ForbiddenException("ACCESS DENIED! Your training status is not active. Please contact our support team for assistance.");
	}
	$totalTuitionFeesBalance = $studentProgramData[0]['totalTuitionFeesBalance'];
	if ($totalTuitionFeesBalance <= 0) {
		require_once('../../mail/students/tuition-payment-success-email.php');
		throw new ConflictException("PAYMENT ALREADY MADE! Check your email for your payment confirmation and receipt. If you have not received any email, please contact our support team for assistance.");
	}


	/// get student program details
	$selectQuery = "SELECT * FROM STUDENTS_PROGRAM_DETAILS_TAB WHERE studentId = ?";
	$params = [$studentId];
	$dataTypes = "s"; // 'i' for integer, 's' for string, etc.
	$studentProgramData = selectQuery($conn, $selectQuery, $dataTypes, $params)[0];

	$programId = $studentProgramData['programId'];
	$courseId = $studentProgramData['courseId'];
	$durationId = $studentProgramData['durationId'];
	$startDate = $studentProgramData['startDate'];
	$endDate = $studentProgramData['endDate'];
	/// get program details
	$programData = _get_program_details($conn, $programId);
	$programName = $programData['programName'];

	/// get course details
	$courseData = _get_course_details($conn, $courseId);
	$courseName = $courseData['courseName'];


	/// get program course duration details
	$programCourseDurationData = _get_program_course_duration_details($conn, $durationId);
	$durationName = $programCourseDurationData['durationName'];
	$tuitionFee = $programCourseDurationData['tuitionFee'];


	/* Send OTP email */
	require_once('../../mail/students/verify-student-activation-otp-email.php');

	$response = [
		'response' => 200,
		'success' => true,
		'message' => "STUDENT VERIFICATION OTP EMAIL SENT SUCCESSFULLY! Please check your email INBOX or SPAM folder for the OTP.",
		'data' => [
			'studentData' => [
				'studentId' => $studentId,
				'emailAddress' => $emailAddress,
				'fullName' => $studentData['firstName'] . ' ' . $studentData['lastName'],
				'phoneNumber' => $studentData['phoneNumber'],
			],
			'programData' => [
				'durationId' => $durationId,
				'programName' => $programName,
				'courseName' => $courseName,
				'durationName' => $durationName,
				'startDate' => $startDate,
				'endDate' => $endDate,
				'tuitionFee' => $tuitionFee,
			]
		]
	];

} catch (Throwable $e) {
	ErrorHandler::handle($e);
}
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>