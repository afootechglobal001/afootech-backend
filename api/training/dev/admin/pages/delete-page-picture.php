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
    $sn = trim($_GET['sn'] ?? '');

    ////////////////// Validation //////////////////
    validateEmptyField($sn, 'ID');
    // get pagePix from PAGE_PICTURES_TAB
    $selectQuery = "SELECT pagePix FROM PAGE_PICTURES_TAB WHERE sn = ?";
    $selectParams = [$sn];
    $pagePixData = selectQuery($conn, $selectQuery, "i", $selectParams);
    if (empty($pagePixData)) {
        throw new NotFoundException("No Record found for the provided SN.");
    }
    $pagePix = $pagePixData[0]['pagePix'];

    // delete the pagePix from PAGE_PICTURES_TAB
    $deleteQuery = "DELETE FROM PAGE_PICTURES_TAB WHERE sn = ?";
    $deleteParams = [$sn];
    deleteQuery($conn, $deleteQuery, "i", $deleteParams);


    ////////////////// Response //////////////////
    $response = [
        'response' => 200,
        'success' => true,
        'message' => "PAGE PICTURE DELETED SUCCESSFULLY!",
        'pagePix' => $pagePix
    ];

} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>