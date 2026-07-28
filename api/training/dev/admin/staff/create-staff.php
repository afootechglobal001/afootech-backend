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

    $firstName = strtoupper(trim($data['firstName'] ?? ''));
    $lastName = strtoupper(trim($data['lastName'] ?? ''));
    $emailAddress = trim($data['emailAddress'] ?? '');
    $phoneNumber = trim($data['phoneNumber'] ?? '');
    $roleId = trim($data['roleId'] ?? '');
    $statusId = trim($data['statusId'] ?? '');

    ////////////////// Validation //////////////////

    validateEmptyField($firstName, 'FIRST NAME');
    validateEmptyField($lastName, 'LAST NAME');
    validateEmptyField($emailAddress, 'EMAIL');
    validateEmptyField($phoneNumber, 'PHONE NUMBER');
    validateEmptyField($roleId, 'STAFF ROLE');
    validateEmptyField($statusId, 'STATUS');

    /////// other validation ///////
    validateEmailField($emailAddress, 'EMAIL');

    ////////////////// Check Existing Email //////////////////

    $checkEmailQuery = "SELECT staffId FROM STAFF_TAB WHERE emailAddress = ?";
    $existingStaff = selectQuery($conn, $checkEmailQuery, "s", [$emailAddress]);

    if (!empty($existingStaff)) {
        throw new ConflictException("ACCOUNT EXIST! Account already exists with this email.");
    }

    ////////////////// Generate Staff ID //////////////////
    $sequence = _get_sequence_count($conn, 'STF');
    $staffId = 'STF' . $sequence['no'] . date("Ymdhis");

    ////////////////// Secure Password //////////////////
    $password = password_hash($staffId, PASSWORD_DEFAULT);

    ////////////////// Insert Staff //////////////////
    $insertQuery = "INSERT INTO `STAFF_TAB`
    (`staffId`, `firstName`, `lastName`, `emailAddress`, `password`, `phoneNumber`, `roleId`, `statusId`, `createdBy`, `createdTime`) VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $insertParams = [
        $staffId,
        $firstName,
        $lastName,
        $emailAddress,
        $password,
        $phoneNumber,
        $roleId,
        $statusId,
        $loginStaffId
    ];
    insertQuery($conn, $insertQuery, "sssssssis", $insertParams);

    ////////////////// Fetch Created Staff //////////////////
    $selectQuery = "SELECT 
	staffId, 
	accessKey, 
	firstName, 
	lastName, 
	emailAddress, 
	phoneNumber, 
	roleId, 
	statusId, 
	lastLoginTime, 
	createdBy, 
	updatedBy, 
	createdTime, 
	updatedTime 
	FROM STAFF_TAB 
    WHERE staffId = ?";
    $selectParams = [$staffId];
    $staffData = selectQuery($conn, $selectQuery, 's', $selectParams)[0];
    $roleId = $staffData['roleId'];
    $statusId = $staffData['statusId'];
    $createdBy = $staffData['createdBy'];
    $updatedBy = $staffData['updatedBy'];

    /// get roleData
    $roleData = _get_role_details($conn, $roleId);
    $staffData['roleData'] = $roleData;
    /// get statusData
    $statusData = _get_status_details($conn, $statusId);
    $staffData['statusData'] = $statusData;
    /// get createdByData
    $createdByData = _action_performed_by($conn, $createdBy);
    $staffData['createdByData'] = $createdByData;
    /// get updatedByData
    $updatedByData = _action_performed_by($conn, $updatedBy);
    $staffData['updatedByData'] = $updatedByData;

    $response = [
        'response' => 200,
        'success' => true,
        'message' => "STAFF CREATED SUCCESSFULLY!",
        'data' => $staffData
    ];


} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>