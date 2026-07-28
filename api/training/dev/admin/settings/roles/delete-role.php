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

    ////////////////// Validation //////////////////
    validateEmptyField($roleId, 'ROLE ID');

    ////////////////// Check if role has users //////////////////
    $userCountQuery = "SELECT COUNT(*) AS totalUsers FROM STAFF_TAB WHERE roleId = ?";
    $userCountResult = selectQuery($conn, $userCountQuery, "s", [$roleId]);
    $userCount = $userCountResult[0]['totalUsers'] ?? 0;

    if ($userCount > 0) {
        throw new ConflictException("ROLE CANNOT BE DELETED! There are users attached to this role.");
    }

    ////////////////// Delete Role //////////////////
    $deleteQuery = "DELETE FROM ROLE_TAB WHERE roleId = ?";
    insertQuery($conn, $deleteQuery, "s", [$roleId]);

    ////////////////// Response //////////////////
    $response = [
        'response' => 200,
        'success'  => true,
        'message'  => "ROLE DELETED SUCCESSFULLY!"
    ];

} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>