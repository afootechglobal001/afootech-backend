<?php
require_once '../config/connection.php';
try {
	if (!$checkBasicSecurity) {
		throw new ForbiddenException("Unauthorized access! Permission denied to access this resource.");
	}

	//////////////////declaration of variables//////////////////////////////////////
	$paymentId = $_GET['paymentId'];

	validateEmptyField($paymentId, "PAYMENT ID");

	//// update PAYMENTS_TAB
	$updateQuery = "UPDATE `PAYMENTS_TAB` SET `statusId` = 4, `payDate` = NOW() WHERE paymentId = '$paymentId'";
	updateQuery($conn, $updateQuery);

	$response = [
		'response' => 200,
		'success' => true,
		'message' => "PAYMENT CANCELLED! You can start the payment process again by clicking on the 'Proceed to Payment' button.",
	];

} catch (Throwable $e) {
	ErrorHandler::handle($e);
}
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>