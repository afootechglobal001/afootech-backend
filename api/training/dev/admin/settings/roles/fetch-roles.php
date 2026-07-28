<?php
require_once '../../../config/connection.php';
require_once '../../../config/staff-session-check.php';

try {

    if (!$checkBasicSecurity) {
        throw new ForbiddenException("Unauthorized access! Please log in.");
    }

    if (!$checkSession) {
        throw new UnauthorizedException("SESSION EXPIRED! Please LogIn Again.");
    }

    ////////////////// Variables //////////////////
    $q = trim($_GET['q'] ?? '');
    $roleIdParam = trim($_GET['roleId'] ?? '');

    ////////////////// Prepare roleId filter //////////////////
    $roleIds = '';
    $params = [];
    $paramTypes = '';

    if (!empty($roleIdParam)) {
        $roleIdArray = explode(',', $roleIdParam);
        $placeholders = implode(',', array_fill(0, count($roleIdArray), '?'));
        $roleIds = "AND roleId IN ($placeholders)";
        $params = array_merge($params, $roleIdArray);
        $paramTypes .= str_repeat('s', count($roleIdArray));
    }

    ////////////////// Select roles //////////////////
    $selectQuery = "SELECT * FROM ROLE_TAB WHERE (roleName LIKE CONCAT('%', ?, '%') OR roleDescription LIKE CONCAT('%', ?, '%')) $roleIds ORDER BY roleName ASC";
    $params = array_merge([$q, $q], $params);
    $paramTypes = 'ss' . $paramTypes;

    $roles = selectQuery($conn, $selectQuery, $paramTypes, $params);

    if (empty($roles)) {
        $response = [
            'response' => 200,
            'success'  => false,
            'message'  => "No Record found",
            'data'     => []
        ];
        echo json_encode($response);
        exit;
    }

    ////////////////// Enrich roles //////////////////
    foreach ($roles as &$role) {

        $roleId = $role['roleId'];

        // Created By
        $role['createdBy'] = _action_performed_by($conn, $role['createdBy']) ?? null;
        // Updated By
        $role['updatedBy'] = _action_performed_by($conn, $role['updatedBy']) ?? null;

        // Role Permissions
        $rolePermissionIds = $role['rolePermissionIds'];
        if (!empty($rolePermissionIds)) {
            $rolePermissionIdsArray = explode(',', $rolePermissionIds);
            $placeholders = implode(',', array_fill(0, count($rolePermissionIdsArray), '?'));
            $rolePermissionsQuery = "SELECT * FROM SETUP_ROLE_PERMISSION_TAB WHERE rolePermissionId IN ($placeholders)";
            $role['rolePermissions'] = selectQuery($conn, $rolePermissionsQuery, str_repeat('s', count($rolePermissionIdsArray)), $rolePermissionIdsArray);
        } else {
            $role['rolePermissions'] = [];
        }

        // User Count
        $userCountQuery = "SELECT COUNT(*) AS count FROM STAFF_TAB WHERE roleId = ?";
        $userCountFetch = selectQuery($conn, $userCountQuery, "s", [$roleId]);
        $role['userCount'] = $userCountFetch[0]['count'] ?? 0;
    }

    ////////////////// Response //////////////////
    $response = [
        'response' => 200,
        'success'  => true,
        'message'  => "ROLES FETCHED SUCCESSFULLY!",
        'allRecordCount' => count($roles),
        'data' => $roles
    ];

} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>