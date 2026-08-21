<?php
require_once '../../config/connection.php';
require_once '../../config/staff-session-check.php';

try {

    if (!$checkBasicSecurity) {
        throw new ForbiddenException("Unauthorized access! Please log in.");
    }

    if (!$checkSession) {
        throw new UnauthorizedException("SESSION EXPIRED! Please LogIn Again.");
    }

    ////////////////// Variables //////////////////

    $q = trim($_GET['q'] ?? '');
    $studentId = trim($_GET['studentId'] ?? '');
    $statusId = trim($_GET['statusId'] ?? '');

    ////////////////// Dynamic Conditions //////////////////

    $conditions = [];
    $params = [];
    $types = '';

    if (!empty($studentId)) {
        $conditions[] = "studentId = ?";
        $params[] = $studentId;
        $types .= "s";
    }

    if (!empty($statusId)) {
        $conditions[] = "statusId = ?";
        $params[] = $statusId;
        $types .= "i";
    }

    $extraWhere = '';
    if (!empty($conditions)) {
        $extraWhere = " AND " . implode(" AND ", $conditions);
    }

    ////////////////// Search Query //////////////////

    $searchClause = "
        (
            firstName LIKE ?
            OR lastName LIKE ?
            OR emailAddress LIKE ?
            OR phoneNumber LIKE ?
            
        )
    ";

    $searchValue = "%{$q}%";

    $params = array_merge([$searchValue, $searchValue, $searchValue, $searchValue], $params);
    $types = "ssss" . $types;

    $selectQuery = "SELECT 
	studentId,
	firstName, 
	lastName, 
	emailAddress, 
	phoneNumber, 
    passport,
	statusId, 
    updatedBy,
	createdTime, 
	updatedTime,
    lastLoginTime
	FROM STUDENTS_TAB 
	WHERE $searchClause
        $extraWhere
        ORDER BY firstName ASC
    ";

    $studentData = selectQuery($conn, $selectQuery, $types, $params);
    $allRecordCount = count($studentData);

    if ($allRecordCount === 0) {
        throw new NotFoundException("No Record found");
    }

    ////////////////// Process Data //////////////////

    foreach ($studentData as &$student) {
        $statusId = $student['statusId'];
        $updatedBy = $student['updatedBy'];
        $student['fullName'] = $student['firstName'] . " " . $student['lastName'];
        /// get statusData
        $statusData = _get_status_details($conn, $statusId);
        $student['statusData'] = $statusData;
        /// get updatedByData
        $updatedByData = _action_performed_by($conn, $updatedBy);
        $student['updatedByData'] = $updatedByData;
    }

    ////////////////// Response //////////////////

    $response = [
        'response' => 200,
        'success' => true,
        'message' => "STUDENT FETCH SUCCESSFULLY!",
        'allRecordCount' => $allRecordCount,
        'data' => $studentData
    ];

} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>