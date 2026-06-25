<?php
require_once '../config/connection.php';
try {
	if (!$checkBasicSecurity) {
		throw new ForbiddenException("Unauthorized access! Permission denied to access this resource.");
	}

	///// fetch from SETUP_INSTITUTION_TYPE_TAB
	$selectQuery = "SELECT  * FROM SETUP_INSTITUTION_TYPE_TAB";
	$institutionTypeData = selectQuery($conn, $selectQuery);
	$response = [
		'response' => 200,
		'success' => true,
		'message' => "INSTITUTION TYPES FETCHED SUCCESSFULLY!",
		'data' => $institutionTypeData
	];

} catch (Throwable $e) {
	ErrorHandler::handle($e);
}
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>