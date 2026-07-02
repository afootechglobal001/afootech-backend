<?php
require_once '../config/connection.php';
try {
	if (!$checkBasicSecurity) {
		throw new ForbiddenException("Unauthorized access! Permission denied to access this resource.");
	}

	//////////////////declaration of variables//////////////////////////////////////
	$paymentId = $data['paymentId'];
	$paystackId = $data['paystackId'];
	$paystackCharges = ((float) ($data['paystackCharges'] ?? 0)) / 100;

	validateEmptyField($paymentId, "PAYMENT ID");
	validateEmptyField($paystackId, "PAYSTACK ID");
	validateEmptyField($paystackCharges, "PAYSTACK CHARGES");

	/// check payment details from PAYMENTS_TAB
	$selectQuery = "SELECT * FROM PAYMENTS_TAB WHERE paymentId = ?";
	$params = [$paymentId];
	$dataTypes = "s"; // 'i' for integer, 's' for string, etc.
	$paymentData = selectQuery($conn, $selectQuery, $dataTypes, $params);
	if (empty($paymentData)) {
		throw new NotFoundException("PAYMENT RECORD NOT FOUND! The specified payment ID does not exist.");
	}
	$paymentData = $paymentData[0];
	$paymentStatusId = $paymentData['statusId'];
	$studentId = $paymentData['studentId'];
	$emailAddress = $paymentData['emailAddress'];
	if ($paymentStatusId != 3) {
		throw new BadRequestException("INVALID PAYMENT STATUS! The payment status is not pending.");
	}

	/// get student details from STUDENTS_TEMP_TAB
	$selectQuery = "SELECT * FROM STUDENTS_TEMP_TAB WHERE studentId = ? AND emailAddress = ?";
	$params = [$studentId, $emailAddress];
	$dataTypes = "ss"; // 'i' for integer, 's' for string
	$studentData = selectQuery($conn, $selectQuery, $dataTypes, $params);
	if (empty($studentData)) {
		throw new NotFoundException("STUDENT RECORD NOT FOUND! The specified student ID does not exist.");
	}
	$studentData = $studentData[0];
	$firstName = $studentData['firstName'];
	$lastName = $studentData['lastName'];
	$phoneNumber = $studentData['phoneNumber'];
	$passport = $studentData['passport'];
	/* Secure password hashing */
	$password = password_hash($studentId, PASSWORD_DEFAULT);
	$statusId = 1; // active

	/// insert into STUDENTS_TAB
	$insertQuery = "INSERT INTO `STUDENTS_TAB`
	(`studentId`, `firstName`, `lastName`, `emailAddress`, `phoneNumber`, `passport`, `password`, `statusId`, `createdTime`, `updatedTime`) VALUES
	(?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
	$params = [$studentId, $firstName, $lastName, $emailAddress, $phoneNumber, $passport, $password, $statusId];
	$dataTypes = "sssssssi"; // 'i' for integer, 's' for string, etc.
	insertQuery($conn, $insertQuery, $dataTypes, $params);

	///insert into STUDENTS_INSTITUTION_DETAILS_TAB
	$institutionTypeId = $studentData['institutionTypeId'];
	$institutionName = $studentData['institutionName'];
	$departmentName = $studentData['departmentName'];
	$levelId = $studentData['levelId'];
	$matricNumber = $studentData['matricNumber'];

	$insertQuery = "INSERT INTO `STUDENTS_INSTITUTION_DETAILS_TAB`
	(`studentId`, `institutionTypeId`, `institutionName`, `departmentName`, `levelId`, `matricNumber`, `createdTime`) VALUES 
	(?, ?, ?, ?, ?, ?, NOW())";
	$params = [$studentId, $institutionTypeId, $institutionName, $departmentName, $levelId, $matricNumber];
	$dataTypes = "ssssss"; // 'i' for integer, 's' for string, etc.
	insertQuery($conn, $insertQuery, $dataTypes, $params);


	//// insert into STUDENTS_PROGRAM_DETAILS_TAB
	$programId = $studentData['programId'];
	$courseId = $studentData['courseId'];
	$durationId = $studentData['durationId'];
	$trainingYear = date("Y");
	$certificateUrl = "$websiteUrl/certificate?id=$studentId";
	$certificateStatusId = 3; // pending
	$trainingStatusId = 3; // pending

	/// get program course duration details
	$programCourseDurationData = _get_program_course_duration_details($conn, $durationId);
	$tuitionFee = $programCourseDurationData['tuitionFee'];

	$insertQuery = "INSERT INTO `STUDENTS_PROGRAM_DETAILS_TAB`
	(`studentId`, `programId`, `courseId`, `durationId`, `trainingYear`, `certificateUrl`, `certificateStatusId`, `trainingStatusId`, `startDate`, `endDate`, `expectedTuitionFee`, `totalTuitionFeesBalance`) VALUES 
	(?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?)";
	$params = [$studentId, $programId, $courseId, $durationId, $trainingYear, $certificateUrl, $certificateStatusId, $trainingStatusId, $tuitionFee, $tuitionFee];
	$dataTypes = "ssssisiidd"; // 'i' for integer, 's' for string, etc.
	insertQuery($conn, $insertQuery, $dataTypes, $params);

	//// update PAYMENTS_TAB
	$updateQuery = "UPDATE `PAYMENTS_TAB` SET `paystackId` = ?, `paystackCharges` = ?, `paystackRemittance` = amount - $paystackCharges, `statusId` = 5, `payDate` = NOW() WHERE paymentId = ?";
	$params = [$paystackId, $paystackCharges, $paymentId];
	$dataTypes = "sds"; // 'i' for integer, 's' for string, etc.
	updateQuery($conn, $updateQuery, $dataTypes, $params);


	/// delete from STUDENTS_TEMP_TAB
	$deleteQuery = "DELETE FROM STUDENTS_TEMP_TAB WHERE studentId = '$studentId' AND emailAddress = '$emailAddress'";
	deleteQuery($conn, $deleteQuery);

	/* Send email */
	require_once('../mail/students/registration-payment-success-email.php');

	$response = [
		'response' => 200,
		'success' => true,
		'message' => "PAYMENT SUCCESSFUL! Check your email for confirmation.",
	];

} catch (Throwable $e) {
	ErrorHandler::handle($e);
}
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>