<?php
require_once '../config/connection.php';
try {
	if (!$checkBasicSecurity) {
		throw new ForbiddenException("Unauthorized access! Permission denied to access this resource.");
	}

	//////////////////declaration of variables//////////////////////////////////////
	$programId = $_GET['programId'];
	$courseId = $_GET['courseId'];
	////// validate empty field
	validateEmptyField($programId, "PROGRAM ID REQUIRED! Select a program and try again");
	validateEmptyField($courseId, "COURSE ID REQUIRED! Select a course and try again");

	//// get program details from SETUP_PROGRAM_TAB
	$selectQuery = "SELECT programId, programName FROM SETUP_PROGRAM_TAB WHERE programId = ?";
	$params = [$programId];
	$dataTypes = "s"; // 'i' for integer, 's' for string, etc.
	$programData = selectQuery($conn, $selectQuery, $dataTypes, $params);
	if (empty($programData)) {
		throw new NotFoundException("PROGRAM NOT FOUND! The selected program does not exist.");
	}

	//// get course details from COURSES_TAB
	$selectQuery = "SELECT courseId, courseName FROM COURSES_TAB WHERE courseId = ?";
	$params = [$courseId];
	$dataTypes = "s"; // 'i' for integer, 's' for string, etc.
	$courseData = selectQuery($conn, $selectQuery, $dataTypes, $params);
	if (empty($courseData)) {
		throw new NotFoundException("COURSE NOT FOUND! The selected course does not exist.");
	}

	///// fetch from PROGRAM_COURSE_DURATION_TAB
	$selectQuery = "SELECT  * FROM PROGRAM_COURSE_DURATION_TAB WHERE programId = ? AND courseId = ?";
	$params = [$programId, $courseId];
	$dataTypes = "ss"; // 'i' for integer, 's' for string, etc.
	$programCourseDurationData = selectQuery($conn, $selectQuery, $dataTypes, $params);
	$response = [
		'response' => 200,
		'success' => true,
		'message' => "PROGRAM COURSE DURATION FETCHED SUCCESSFULLY!",
		'programData' => $programData,
		'courseData' => $courseData,
		'data' => $programCourseDurationData
	];

} catch (Throwable $e) {
	ErrorHandler::handle($e);
}
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>