<?php
require_once '../config/connection.php';
try {
	if (!$checkBasicSecurity) {
		throw new ForbiddenException("Unauthorized access! Permission denied to access this resource.");
	}

	///// fetch from SETUP_PROGRAM_TAB
	$selectQuery = "SELECT  * FROM SETUP_PROGRAM_TAB";
	$programData = selectQuery($conn, $selectQuery);
	$response = [
		'response' => 200,
		'success' => true,
		'message' => "PROGRAMS FETCHED SUCCESSFULLY!",
		'data' => $programData
	];

} catch (Throwable $e) {
	ErrorHandler::handle($e);
}
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>