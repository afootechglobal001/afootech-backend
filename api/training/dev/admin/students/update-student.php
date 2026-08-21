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
    $firstName = strtoupper(trim($data['firstName'] ?? ''));
    $lastName = strtoupper(trim($data['lastName'] ?? ''));
    $emailAddress = trim($data['emailAddress'] ?? '');
    $phoneNumber = trim($data['phoneNumber'] ?? '');
    $statusId = trim($data['statusId'] ?? '');

    ////////////////// Validation //////////////////

    validateEmptyField($studentId, 'STUDENT ID');
    validateEmptyField($firstName, 'FIRST NAME');
    validateEmptyField($lastName, 'LAST NAME');
    validateEmptyField($emailAddress, 'EMAIL');
    validateEmptyField($phoneNumber, 'PHONE NUMBER');
    validateEmptyField($statusId, 'STATUS');
    validateEmailField($emailAddress, 'EMAIL');

    ////////////////// Check Existing Email //////////////////

    $checkEmailQuery = "SELECT studentId FROM STUDENTS_TAB WHERE emailAddress = ? AND studentId != ?";
    $existingStudent = selectQuery($conn, $checkEmailQuery, "ss", [$emailAddress, $studentId]);

    if (!empty($existingStudent)) {
        throw new ConflictException("ACCOUNT EXIST! Account already exists with this email.");
    }

    ////////////////// Update Student //////////////////

    $updateQuery = "
        UPDATE STUDENTS_TAB SET
            firstName = ?,
            lastName = ?,
            emailAddress = ?,
            phoneNumber = ?,
            statusId = ?,
            updatedBy = ?,
            updatedTime = NOW()
        WHERE studentId = ?
    ";
    $updateParams = [
        $firstName,
        $lastName,
        $emailAddress,
        $phoneNumber,
        $statusId,
        $loginStaffId,
        $studentId
    ];
    updateQuery($conn, $updateQuery, "ssssiss", $updateParams);

    $response = [
        'response' => 200,
        'success' => true,
        'message' => "STUDENT UPDATED SUCCESSFULLY!"
    ];

} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>