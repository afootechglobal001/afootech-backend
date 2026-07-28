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
    $staffId = trim($_GET['staffId'] ?? '');
    $statusId = trim($_GET['statusId'] ?? '');

    ////////////////// Dynamic Conditions //////////////////

    $conditions = [];
    $params = [];
    $types = '';

    if (!empty($staffId)) {
        $conditions[] = "staffId = ?";
        $params[] = $staffId;
        $types .= "s";
    }

    if (!empty($statusId)) {
        $conditions[] = "statusId IN ($statusId)";
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
            OR phoneNumber LIKE ?
        )
    ";

    $searchValue = "%{$q}%";

    $params = array_merge([$searchValue, $searchValue, $searchValue], $params);
    $types = "sss" . $types;

    $selectQuery = "
        SELECT *
        FROM STAFF_VIEW
        WHERE $searchClause
        $extraWhere
        ORDER BY firstName ASC
    ";

    $staffData = selectQuery($conn, $selectQuery, $types, $params);
    $allRecordCount = count($staffData);

    if ($allRecordCount === 0) {
        throw new NotFoundException("No Record found");
    }

    ////////////////// Process Data //////////////////

    foreach ($staffData as &$staff) {
        $roleId = $staff['roleId'];
        $statusId = $staff['statusId'];
        $createdBy = $staff['createdBy'];
        $updatedBy = $staff['updatedBy'];

        $staff['fullName'] = $staff['firstName'] . " " . $staff['lastName'];
        /// get roleData
        $roleData = _get_role_details($conn, $roleId);
        $staff['roleData'] = $roleData;
        /// get statusData
        $statusData = _get_status_details($conn, $statusId);
        $staff['statusData'] = $statusData;
        /// get createdByData
        $createdByData = _action_performed_by($conn, $createdBy);
        $staff['createdByData'] = $createdByData;
        /// get updatedByData
        $updatedByData = _action_performed_by($conn, $updatedBy);
        $staff['updatedByData'] = $updatedByData;
    }

    ////////////////// Response //////////////////

    $response = [
        'response' => 200,
        'success' => true,
        'message' => "STAFF FETCH SUCCESSFULLY!",
        'allRecordCount' => $allRecordCount,
        'data' => $staffData
    ];

} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>