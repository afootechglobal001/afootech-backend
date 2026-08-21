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

    validateEmptyField($studentId, "STUDENT ID");
    validateEmptyField($studentProgramId, "STUDENT PROGRAM ID");

    require_once '../../mail/students/reprint-program-student-profile-success-email.php';

    $response = [
        'response' => 200,
        'success' => true,
        'message' => "PROFILE SENT TO STUDENT EMAIL!",
    ];

} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>