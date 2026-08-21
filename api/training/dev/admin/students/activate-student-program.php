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


    $studentId = trim($_GET['studentId'] ?? '');
    $studentProgramId = trim($_GET['studentProgramId'] ?? '');
    $startDate = trim($data['startDate'] ?? '');
    $endDate = trim($data['endDate'] ?? '');


    validateEmptyField($studentId, "STUDENT ID");
    validateEmptyField($studentProgramId, "STUDENT PROGRAM ID");
    validateEmptyField($startDate, "START DATE");
    validateEmptyField($endDate, "END DATE");

    $selectQuery = "UPDATE STUDENTS_PROGRAM_DETAILS_TAB SET trainingStatusId = 1, startDate = ?, endDate = ?, updatedBy = ? WHERE studentId = ? AND studentProgramId = ?";
    $types = "sssss";
    $params = [$startDate, $endDate, $loginStaffId, $studentId, $studentProgramId];
    updateQuery($conn, $selectQuery, $types, $params);


    $response = [
        'response' => 200,
        'success' => true,
        'message' => "STUDENT PROGRAM ACTIVATED SUCCESSFULLY!",
    ];

} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>