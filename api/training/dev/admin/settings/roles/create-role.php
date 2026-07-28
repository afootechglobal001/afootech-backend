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
    $roleName          = strtoupper(trim($data['roleName'] ?? ''));
    $roleDescription   = strtoupper(trim($data['roleDescription'] ?? ''));
    $rolePermissionIds = $data['rolePermissionIds'] ?? [];

    ////////////////// Validation //////////////////
    validateEmptyField($roleName, 'ROLE NAME');
    validateEmptyField($roleDescription, 'ROLE DESCRIPTION');
    if (count($rolePermissionIds) === 0) {
        throw new BadRequestException("ROLE PERMISSIONS REQUIRED! Check the fields and try again.");
    }

    ////////////////// Check Existing Role //////////////////
    $checkQuery = "SELECT roleId FROM ROLE_TAB WHERE roleName = ?";
    $existingRole = selectQuery($conn, $checkQuery, "s", [$roleName]);

    if (!empty($existingRole)) {
        throw new ConflictException("ROLE EXIST! Role already exists by name. Check and try again.");
    }

    ////////////////// Generate Role ID //////////////////
    $sequence = _get_sequence_count($conn, 'R');
    $array = json_decode($sequence, true);
    $no = $array[0]['no'];
    $roleId = 'R' . $no;

    ////////////////// Insert Role //////////////////
    // Convert rolePermissionIds array to comma-separated string
    $rolePermissionIdsString = implode(',', array_column($rolePermissionIds, 'rolePermissionId'));

    $insertQuery = "
        INSERT INTO ROLE_TAB (
            roleId,
            roleName,
            roleDescription,
            rolePermissionIds,
            createdBy,
            createdTime
        ) VALUES (?, ?, ?, ?, ?, NOW())
    ";
    $insertParams = [$roleId, $roleName, $roleDescription, $rolePermissionIdsString, $loginStaffId];
    insertQuery($conn, $insertQuery, "sssss", $insertParams);

    ////////////////// Fetch Created Role //////////////////
    $selectQuery = "SELECT * FROM ROLE_TAB WHERE roleId = ?";
    $roleData = selectQuery($conn, $selectQuery, "s", [$roleId]);

    foreach ($roleData as &$role) {

        // Created By
        $role['createdBy'] = _action_performed_by($conn, $role['createdBy']) ?? null;
        // Updated By
        $role['updatedBy'] = _action_performed_by($conn, $role['updatedBy']) ?? null;

        // Role Permissions
        if (!empty($role['rolePermissionIds'])) {
            $permissionIds = explode(',', $role['rolePermissionIds']);
            $placeholders = implode(',', array_fill(0, count($permissionIds), '?'));
            $rolePermissionsQuery = "SELECT * FROM SETUP_ROLE_PERMISSION_TAB WHERE rolePermissionId IN ($placeholders)";
            $role['rolePermissions'] = selectQuery($conn, $rolePermissionsQuery, str_repeat('s', count($permissionIds)), $permissionIds);
        } else {
            $role['rolePermissions'] = [];
        }
    }

    ////////////////// Response //////////////////
    $response = [
        'response' => 200,
        'success' => true,
        'message'  => "ROLE CREATED SUCCESSFULLY!",
        'data'     => $roleData
    ];

} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>