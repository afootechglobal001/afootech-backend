<?php
require_once '../config/connection.php';
try {
	if (!$checkBasicSecurity) {
		throw new ForbiddenException("Unauthorized access! Permission denied to access this resource.");
	}

	//////////////////declaration of variables//////////////////////////////////////
	$firstName = strtoupper(trim($data['firstName']));
	$lastName = strtoupper(trim($data['lastName']));
	$emailAddress = strtolower(trim($data['emailAddress']));
	$phoneNumber = trim($data['phoneNumber']);
	$institutionTypeId = trim($data['institutionTypeId']);
	$institutionName = strtoupper(trim($data['institutionName']));
	$departmentName = strtoupper(trim($data['departmentName']));
	$levelId = trim($data['levelId']);
	$matricNumber = strtoupper(trim($data['matricNumber']));
	$programId = trim($data['programId']);
	$courseId = trim($data['courseId']);
	$durationId = trim($data['durationId']);
	$paymentMethodId = trim($data['paymentMethodId']);

	////// validate empty field
	validateEmptyField($firstName, "FIRST NAME");
	validateEmptyField($lastName, "LAST NAME");
	validateEmptyField($emailAddress, "EMAIL ADDRESS");
	validateEmailField($emailAddress, "EMAIL ADDRESS");
	validateEmptyField($phoneNumber, "PHONE NUMBER");
	validateEmptyField($institutionTypeId, "INSTITUTION TYPE");
	validateEmptyField($institutionName, "INSTITUTION NAME");
	validateEmptyField($departmentName, "DEPARTMENT");
	validateEmptyField($levelId, "LEVEL");
	validateEmptyField($matricNumber, "MATRIC NUMBER");
	validateEmptyField($programId, "PROGRAM");
	validateEmptyField($courseId, "COURSE");
	validateEmptyField($durationId, "DURATION");
	validateEmptyField($paymentMethodId, "PAYMENT METHOD");

	if ($paymentMethodId == 'PM001') {
		$paymentChannel = 'card';
	} else if ($paymentMethodId == 'PM002') {
		$paymentChannel = 'bank_transfer';
	} else {
		throw new BadRequestException("INVALID PAYMENT METHOD! The selected payment method is not supported.");
	}
	//// confirm if email address already exists in the database
	$selectQuery = "SELECT * FROM STUDENTS_TAB WHERE emailAddress = ?";
	$params = [$emailAddress];
	$dataTypes = "s"; // 'i' for integer, 's' for string, etc.
	$emailData = selectQuery($conn, $selectQuery, $dataTypes, $params);
	if (!empty($emailData)) {
		throw new ConflictException("USER ALREADY EXISTS BY EMAIL ADDRESS! Check your INBOX or SPAM to confirm your acknowledgment slip.");
	}

	//// get trainingAmount from PROGRAM_COURSE_DURATION_TAB
	$selectQuery = "SELECT formFee FROM PROGRAM_COURSE_DURATION_TAB WHERE programId = ? AND courseId = ? AND durationId = ?";
	$params = [$programId, $courseId, $durationId];
	$dataTypes = "sss"; // 'i' for integer, 's' for string, etc.
	$programCourseDurationData = selectQuery($conn, $selectQuery, $dataTypes, $params);
	if (empty($programCourseDurationData)) {
		throw new NotFoundException("PROGRAM COURSE DURATION NOT FOUND! The selected program, course, and duration combination does not exist.");
	}
	$formAmount = $programCourseDurationData[0]['formFee'];

	/// confirm if email address already exists in the temporary database
	$selectQuery = "SELECT * FROM STUDENTS_TEMP_TAB WHERE emailAddress = ?";
	$params = [$emailAddress];
	$dataTypes = "s"; // 'i' for integer, 's' for string, etc.
	$userTempData = selectQuery($conn, $selectQuery, $dataTypes, $params);
	if (!empty($userTempData)) {
		/// get paymentId from STUDENTS_TEMP_TAB.
		$studentId = $userTempData[0]['studentId'];
		$paymentId = $userTempData[0]['paymentId'];

		/// delete from PAYMENTS_TAB
		// $deleteQuery = "DELETE FROM PAYMENTS_TAB WHERE paymentId = '$paymentId' AND studentId = '$studentId'";
		// deleteQuery($conn, $deleteQuery);

		// delete from STUDENTS_TEMP_TAB 
		$deleteQuery = "DELETE FROM STUDENTS_TEMP_TAB WHERE studentId = '$studentId'";
		deleteQuery($conn, $deleteQuery);
	}

	////////////////// Generate user ID //////////////////
	$sequence = _get_sequence_count($conn, 'SID');
	$studentId = 'SID' . $sequence['no'] . date("Ymdhis");

	$passport = "$studentId.jpg";

	////////////////// Generate payment ID //////////////////
	$sequence = _get_sequence_count($conn, 'PAY');
	$paymentId = 'PAY' . $sequence['no'] . date("Ymdhis");

	/// insert into STUDENTS_TEMP_TAB
	$insertQuery = "INSERT INTO `STUDENTS_TEMP_TAB`
	(`studentId`, `paymentId`, `firstName`, `lastName`, `emailAddress`, `phoneNumber`, `passport`, `institutionTypeId`, `institutionName`, `departmentName`, `levelId`, `matricNumber`, `programId`, `courseId`, `durationId`, `createdTime`) VALUES
	(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
	$params = [$studentId, $paymentId, $firstName, $lastName, $emailAddress, $phoneNumber, $passport, $institutionTypeId, $institutionName, $departmentName, $levelId, $matricNumber, $programId, $courseId, $durationId];
	$dataTypes = "sssssssssssssss"; // 'i' for integer, 's' for string, etc.
	insertQuery($conn, $insertQuery, $dataTypes, $params);



	/// get paystackKey from SETUP_BACKEND_SETTINGS_TAB
	$sesstingsData = _get_setup_backend_settings_detail($conn);
	$paystackPaymentKey = $sesstingsData['paystackPaymentKey'];
	$paystackSecretKey = $sesstingsData['paystackSecretKey'];

	/// insert into PAYMENTS_TAB
	$paymentPurposeId = 'form';
	$statusId = 3; // pending
	$insertQuery = "INSERT INTO `PAYMENTS_TAB`
	(`paymentId`, `studentId`, `emailAddress`, `phoneNumber`, `paymentPurposeId`, `paystackPaymentKey`, `amount`, `paymentMethodId`, `statusId`, `createdTime`, `payDate`) VALUES
	(?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
	$params = [$paymentId, $studentId, $emailAddress, $phoneNumber, $paymentPurposeId, $paystackPaymentKey, $formAmount, $paymentMethodId, $statusId];
	$dataTypes = "ssssssdsi"; // 'i' for integer, 's' for string, etc.
	insertQuery($conn, $insertQuery, $dataTypes, $params);

	//// payment attempt email notification
	require_once('../mail/students/registration-payment-attempt-email.php');

	$response = [
		'response' => 200,
		'success' => true,
		'message' => "PROCEED TO PAYMENT SUCCESSFUL!",
		'data' => [
			'paystackPaymentKey' => $paystackPaymentKey,
			'paystackSecretKey' => $paystackSecretKey,
			'paymentId' => $paymentId,
			'amount' => $formAmount * 100, // convert to kobo
			'currency' => 'NGN',
			'paymentChannel' => $paymentChannel,
			'studentId' => $studentId,
			'fullName' => "$firstName $lastName",
			'emailAddress' => $emailAddress,
			'phoneNumber' => $phoneNumber,
			'passport' => $passport,
		]
	];

} catch (Throwable $e) {
	ErrorHandler::handle($e);
}
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>