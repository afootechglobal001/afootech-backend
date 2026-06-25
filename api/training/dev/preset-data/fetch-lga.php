<?php
require_once '../config/connection.php';
try {
    if (!$checkBasicSecurity) {
        throw new ForbiddenException("Unauthorized access! Please log in and try again.");
    }

    //////////////////declaration of variables//////////////////////////////////////
    $stateId = $_GET['stateId'];

    validateEmptyField($stateId, "STATE REQUIRED! Select a state and try again");
	///// fetch from SETUP_STATE_LGA_TAB
	$selectQuery="SELECT  * FROM SETUP_STATE_LGA_TAB WHERE stateId=?";
    $selectParams = [$stateId];
	$stateData = selectQuery($conn, $selectQuery, 's', $selectParams);
	$response = [
		'response' => 200,
		'success' => true,
		'message' => "STATE LAGs FETCH SUCCESSFUL!",
		'data' => $stateData
	];
	
 }catch (Throwable $e) {
    ErrorHandler::handle($e);
}
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>