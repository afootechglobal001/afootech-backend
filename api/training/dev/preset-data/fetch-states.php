<?php
require_once '../config/connection.php';
try {
    if (!$checkBasicSecurity) {
        throw new ForbiddenException("Unauthorized access! Please log in and try again.");
    }

	///// fetch from SETUP_STATES_TAB
	$selectQuery="SELECT  * FROM SETUP_STATES_TAB";
	$stateData = selectQuery($conn, $selectQuery);
	$response = [
		'response' => 200,
		'success' => true,
		'message' => "STATE FETCH SUCCESSFUL!",
		'data' => $stateData
	];
	
 }catch (Throwable $e) {
    ErrorHandler::handle($e);
}
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>