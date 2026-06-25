<?php
require_once '../config/connection.php';
try {
	if (!$checkBasicSecurity) {
		throw new ForbiddenException("Unauthorized access! Permission denied to access this resource.");
	}

	///// fetch from SETUP_PAYMENT_METHOD_TAB
	$selectQuery = "SELECT  * FROM SETUP_PAYMENT_METHOD_TAB";
	$paymentMethodData = selectQuery($conn, $selectQuery);
	$response = [
		'response' => 200,
		'success' => true,
		'message' => "PAYMENT METHODS FETCHED SUCCESSFULLY!",
		'data' => $paymentMethodData
	];

} catch (Throwable $e) {
	ErrorHandler::handle($e);
}
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>