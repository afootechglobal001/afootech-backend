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
    $roleId = $data['roleId'] ?? '';
    $roleName = strtoupper(trim($data['roleName'] ?? ''));
    $roleDescription = strtoupper(trim($data['roleDescription'] ?? ''));
    $rolePermissionIds = $data['rolePermissionIds'] ?? [];

    ////////////////// Validation //////////////////
    validateEmptyField($roleId, 'ROLE ID');
    validateEmptyField($roleName, 'ROLE NAME');
    validateEmptyField($roleDescription, 'ROLE DESCRIPTION');

    if (count($rolePermissionIds) === 0) {
        throw new BadRequestException("ROLE PERMISSIONS REQUIRED! Check the fields and try again");
    }

    // Check if role name exists for another role
    $checkQuery = "SELECT roleId FROM ROLE_TAB WHERE roleName = ? AND roleId != ?";
    $existingRole = selectQuery($conn, $checkQuery, "ss", [$roleName, $roleId]);

    if (!empty($existingRole)) {
        throw new ConflictException("ROLE EXIST! Role already exists by name. Check and try again.");
    }

    ////////////////// Update Role //////////////////
    $rolePermissionIdsArray = array_column($rolePermissionIds, 'rolePermissionId');
    $rolePermissionIdsStr = implode(',', $rolePermissionIdsArray);

    $updateQuery = "
        UPDATE ROLE_TAB
        SET roleName = ?, roleDescription = ?, rolePermissionIds = ?, updatedBy = ?
        WHERE roleId = ?
    ";
    $updateParams = [$roleName, $roleDescription, $rolePermissionIdsStr, $loginStaffId, $roleId];
    insertQuery($conn, $updateQuery, "sssss", $updateParams);

    ////////////////// Fetch Updated Role //////////////////
    $roleQuery = "SELECT * FROM ROLE_TAB WHERE roleId = ?";
    $roles = selectQuery($conn, $roleQuery, "s", [$roleId]);

    foreach ($roles as &$role) {
        // Created By
        $role['createdBy'] = _action_performed_by($conn, $role['createdBy']) ?? null;
        // Updated By
        $role['updatedBy'] = _action_performed_by($conn, $role['updatedBy']) ?? null;

        // Role Permissions
        if (!empty($role['rolePermissionIds'])) {
            $rolePermissionIdsArray = explode(',', $role['rolePermissionIds']);
            $placeholders = implode(',', array_fill(0, count($rolePermissionIdsArray), '?'));
            $rolePermissionsQuery = "SELECT * FROM SETUP_ROLE_PERMISSION_TAB WHERE rolePermissionId IN ($placeholders)";
            $role['rolePermissions'] = selectQuery($conn, $rolePermissionsQuery, str_repeat('s', count($rolePermissionIdsArray)), $rolePermissionIdsArray);
        } else {
            $role['rolePermissions'] = [];
        }
    }

    ////////////////// Response //////////////////
    $response = [
        'response' => 200,
        'success'  => true,
        'message'  => "ROLE UPDATED SUCCESSFULLY!",
        'data'     => $roles
    ];

} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>