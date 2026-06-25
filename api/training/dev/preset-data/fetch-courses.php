<?php
require_once '../config/connection.php';
try {
	if (!$checkBasicSecurity) {
		throw new ForbiddenException("Unauthorized access! Permission denied to access this resource.");
	}

	///// fetch from COURSES_TAB
	$selectQuery = "SELECT  courseId, courseName FROM COURSES_TAB WHERE statusId = 1 ORDER BY courseName ASC";
	$courseData = selectQuery($conn, $selectQuery);
	if (count($courseData) === 0) {
		throw new NotFoundException("NO COURSE FOUND!");
	}

	$response = [
		'response' => 200,
		'success' => true,
		'message' => "COURSES FETCHED SUCCESSFULLY!",
		'data' => $courseData
	];

} catch (Throwable $e) {
	ErrorHandler::handle($e);
}
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>