<?php
require_once '../config/connection.php';
require_once '../config/staff-session-check.php';

try {
    // Basic security check
    if (!$checkBasicSecurity) {
        throw new ForbiddenException("Unauthorized access! Please log in and try again.");
    }

    // Session check
    if (!$checkSession) {
        throw new UnauthorizedException("SESSION EXPIRED! Please LogIn Again.");
    }

    /////////////////////// Declaration of variables /////////////////////////
    $roleId = $_GET['roleId'] ?? null;
    $rolePermissionArray = [];

    // Fetch role permissions if roleId is provided
    if ($roleId) {
        $selectRoleQuery = "SELECT rolePermissionIds FROM ROLE_TAB WHERE roleId = ?";
        $roleResult = selectQuery($conn, $selectRoleQuery, 's', [$roleId]);

        if (!empty($roleResult)) {
            $rolePermissionIds = $roleResult[0]['rolePermissionIds'];
            $rolePermissionArray = explode(',', $rolePermissionIds);
        }
    }

    // Fetch all role permissions
    $selectPermissionsQuery = "SELECT * FROM SETUP_ROLE_PERMISSION_TAB";
    $permissionsData = selectQuery($conn, $selectPermissionsQuery);

    $allRecordCount = count($permissionsData);

    if ($allRecordCount === 0) {
        throw new NotFoundException("No Record found");
    }

    // Mark checked permissions based on role
    foreach ($permissionsData as &$permission) {
        $permission['checked'] = in_array($permission['rolePermissionId'], $rolePermissionArray);
    }

    /////////////////////// Response /////////////////////////
    $response = [
        'response' => 200,
        'success' => true,
        'message' => "ROLE FETCHED SUCCESSFULLY!",
        'allRecordCount' => $allRecordCount,
        'data' => $permissionsData
    ];

} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

// Send response
http_response_code($response['response'] ?? 500);
echo json_encode($response);