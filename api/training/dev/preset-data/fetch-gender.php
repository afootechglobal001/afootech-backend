<?php
require_once '../config/connection.php';
try {
    if (!$checkBasicSecurity) {
        throw new ForbiddenException("Unauthorized access! Please log in and try again.");
    }

	///// fetch from SETUP_GENDER_TAB
	$selectQuery="SELECT  * FROM SETUP_GENDER_TAB";
	$genderData = selectQuery($conn, $selectQuery);
	$response = [
		'response' => 200,
		'success' => true,
		'message' => "LOGIN SUCCESSFUL!",
		'data' => $genderData
	];
	
 }catch (Throwable $e) {
    ErrorHandler::handle($e);
}
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>