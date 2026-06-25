<?php
require_once '../config/connection.php';
try {
	if (!$checkBasicSecurity) {
		throw new ForbiddenException("Unauthorized access! Permission denied to access this resource.");
	}

	//////////////////declaration of variables//////////////////////////////////////
	$institutionTypeId = $_GET['institutionTypeId'];
	////// validate empty field
	validateEmptyField($institutionTypeId, "INSTITUTION TYPE REQUIRED! Select an institution type and try again");

	//// get institution type details from SETUP_INSTITUTION_TYPE_TAB
	$selectQuery = "SELECT * FROM SETUP_INSTITUTION_TYPE_TAB WHERE institutionTypeId = ?";
	$params = [$institutionTypeId];
	$dataTypes = "s"; // 'i' for integer, 's' for string, etc.
	$institutionTypeData = selectQuery($conn, $selectQuery, $dataTypes, $params);
	if (empty($institutionTypeData)) {
		throw new NotFoundException("INSTITUTION TYPE NOT FOUND! The selected institution type does not exist.");
	}

	///// fetch from SETUP_INSTITUTION_LEVEL_TAB
	$selectQuery = "SELECT  * FROM SETUP_INSTITUTION_LEVEL_TAB WHERE institutionTypeId = ?";
	$params = [$institutionTypeId];
	$dataTypes = "s"; // 'i' for integer, 's' for string, etc.
	$institutionLevelData = selectQuery($conn, $selectQuery, $dataTypes, $params);
	$response = [
		'response' => 200,
		'success' => true,
		'message' => "INSTITUTION LEVELS FETCHED SUCCESSFULLY!",
		'institutionType' => $institutionTypeData,
		'data' => $institutionLevelData
	];

} catch (Throwable $e) {
	ErrorHandler::handle($e);
}
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>