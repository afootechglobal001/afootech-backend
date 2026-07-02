<?php
require_once '../../config/connection.php';
try {
	if (!$checkBasicSecurity) {
		throw new ForbiddenException("Unauthorized access! Permission denied to access this resource.");
	}

	//////////////////declaration of variables//////////////////////////////////////
	$studentId = trim($_GET['studentId']);
	$durationId = trim($_GET['durationId']);
	$paymentMethodId = trim($data['paymentMethodId']);

	////// validate empty field
	validateEmptyField($studentId, "STUDENT ID");
	validateEmptyField($durationId, "DURATION ID");
	validateEmptyField($paymentMethodId, "PAYMENT METHOD");

	if ($paymentMethodId == 'PM001') {
		$paymentChannel = 'card';
	} else if ($paymentMethodId == 'PM002') {
		$paymentChannel = 'bank_transfer';
	} else {
		throw new BadRequestException("INVALID PAYMENT METHOD! The selected payment method is not supported.");
	}

	/// get student details from STUDENTS_TEMP_TAB
	$selectQuery = "SELECT * FROM STUDENTS_TAB WHERE studentId = ?";
	$params = [$studentId];
	$dataTypes = "s"; // 'i' for integer, 's' for string
	$studentData = selectQuery($conn, $selectQuery, $dataTypes, $params);
	if (empty($studentData)) {
		throw new NotFoundException("STUDENT RECORD NOT FOUND! Proceed with your registration first and provide the required details to access your acceptance letter.");
	}
	$studentData = $studentData[0];
	$firstName = $studentData['firstName'];
	$lastName = $studentData['lastName'];
	$emailAddress = $studentData['emailAddress'];
	$phoneNumber = $studentData['phoneNumber'];

	/// get if student training status is active from STUDENTS_PROGRAM_DETAILS_TAB
	$selectQuery = "SELECT * FROM STUDENTS_PROGRAM_DETAILS_TAB WHERE studentId = ? AND durationId = ? AND trainingStatusId = 1";
	$params = [$studentId, $durationId];
	$dataTypes = "ss"; // 'i' for integer, 's' for string
	$studentProgramData = selectQuery($conn, $selectQuery, $dataTypes, $params);
	$expectedTuitionFees = $studentProgramData[0]['expectedTuitionFee'];
	$totalTuitionFeesBalance = $studentProgramData[0]['totalTuitionFeesBalance'];
	if ($totalTuitionFeesBalance <= 0) {
		require_once('../../mail/students/tuition-payment-success-email.php');
		throw new ConflictException("PAYMENT ALREADY MADE! Check your email for your payment confirmation and receipt. If you have not received any email, please contact our support team for assistance.");
	}


	////////////////// Generate payment ID //////////////////
	$sequence = _get_sequence_count($conn, 'PAY');
	$paymentId = 'PAY' . $sequence['no'] . date("Ymdhis");

	/// get paystackKey from SETUP_BACKEND_SETTINGS_TAB
	$sesstingsData = _get_setup_backend_settings_detail($conn);
	$paystackPaymentKey = $sesstingsData['paystackPaymentKey'];
	$paystackSecretKey = $sesstingsData['paystackSecretKey'];

	/// insert into PAYMENTS_TAB
	$paymentPurposeId = 'tuition'; // tuition fees
	$statusId = 3; // pending
	$insertQuery = "INSERT INTO `PAYMENTS_TAB`
	(`paymentId`, `studentId`, `emailAddress`, `phoneNumber`, `paymentPurposeId`, `paystackPaymentKey`, `amount`, `paymentMethodId`, `statusId`, `createdTime`, `payDate`) VALUES
	(?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
	$params = [$paymentId, $studentId, $emailAddress, $phoneNumber, $paymentPurposeId, $paystackPaymentKey, $expectedTuitionFees, $paymentMethodId, $statusId];
	$dataTypes = "ssssssdsi"; // 'i' for integer, 's' for string, etc.
	insertQuery($conn, $insertQuery, $dataTypes, $params);

	//// payment attempt email notification
	require_once('../../mail/students/tuition-payment-attempt-email.php');

	$response = [
		'response' => 200,
		'success' => true,
		'message' => "PROCEED TO PAYMENT SUCCESSFUL!",
		'data' => [
			'paystackPaymentKey' => $paystackPaymentKey,
			'paystackSecretKey' => $paystackSecretKey,
			'paymentId' => $paymentId,
			'amount' => $expectedTuitionFees * 100, // convert to kobo
			'currency' => 'NGN',
			'paymentChannel' => $paymentChannel,
			'studentId' => $studentId,
			'fullName' => "$firstName $lastName",
			'emailAddress' => $emailAddress,
			'phoneNumber' => $phoneNumber,
		]
	];

} catch (Throwable $e) {
	ErrorHandler::handle($e);
}
end:
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>