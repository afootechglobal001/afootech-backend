<?php
require_once '../../config/connection.php';
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

	//// update PAYMENTS_TAB
	$updateQuery = "UPDATE `PAYMENTS_TAB` SET `paystackId` = ?, `paystackCharges` = ?, `paystackRemittance` = amount - $paystackCharges, `statusId` = 5, `payDate` = NOW() WHERE paymentId = ?";
	$params = [$paystackId, $paystackCharges, $paymentId];
	$dataTypes = "sds"; // 'i' for integer, 's' for string, etc.
	updateQuery($conn, $updateQuery, $dataTypes, $params);

	/// update STUDENTS_PROGRAM_DETAILS_TAB
	$updateQuery = "UPDATE `STUDENTS_PROGRAM_DETAILS_TAB` SET `totalTuitionFeesPaid` = `expectedTuitionFee`, `totalTuitionFeesBalance` = 0 WHERE studentId = ?";
	$params = [$studentId];
	$dataTypes = "s"; // 'i' for integer, 's' for string, etc.
	updateQuery($conn, $updateQuery, $dataTypes, $params);

	/* Send email */
	require_once('../../mail/students/tuition-payment-success-email.php');

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