<?php
require_once '../config/connection.php';
require_once '../config/staff-session-check.php';
try {
    if (!$checkBasicSecurity) {
        throw new ForbiddenException("Unauthorized access! Please log in and try again.");
    }
    if (!$checkSession) {
        throw new UnauthorizedException("SESSION EXPIRED! Please LogIn Again.");
    }

    //////////////////declaration of variables//////////////////////////////////////
    $q = $_GET['q'];
    $statusIds = $_GET['statusId'];
    if ($statusIds != '') {
        $whareClause = "AND  statusId IN ($statusIds)";
    }
    $selectQuery = "SELECT * FROM SETUP_STATUS_TAB WHERE statusName LIKE ? $whareClause";
    $selectParams = ["%{$q}%"];
    $statusData = selectQuery($conn, $selectQuery, 's', $selectParams);
    $allRecordCount = count($statusData);
    if ($allRecordCount == 0) {
        throw new NotFoundException("No Record found");
    }
    $response = [
        'response' => 200,
        'success' => true,
        'message' => "STATUS FETCH SUCCESFFULY!",
        'allRecordCount' => $allRecordCount,
        'data' => $statusData
    ];
 }catch (Throwable $e) {
    ErrorHandler::handle($e);
}
http_response_code($response['response']); // sets HTTP status
echo json_encode($response);
?>