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
    $crFlag = trim($_GET['crFlag'] ?? ''); // can be CONTACT or REVIEW
    $crId = trim($_GET['crId'] ?? '');
    $statusId = trim($_GET['statusId'] ?? '');
    $limit = trim($_GET['limit'] ?? '');

    if (empty($crFlag) || !in_array($crFlag, ['CONTACT', 'REVIEW'])) {
        throw new BadRequestException("Invalid crFlag. It must be either 'CONTACT' or 'REVIEW'.");
    }



    ////////////////// Build Query //////////////////
    $conditions = [];
    $params = [];
    $types = '';

    if (!empty($crId)) {
        $conditions[] = "crId = ?";
        $params[] = $crId;
        $types .= "s";
    }
    if (!empty($crFlag)) {
        $conditions[] = "crFlag = ?";
        $params[] = $crFlag;
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
            fullName LIKE ?
            OR emailAddress LIKE ?
            OR phoneNumber LIKE ?
            OR subject LIKE ?
            OR message LIKE ?
        )
    ";

    $searchValue = "%{$q}%";

    $params = array_merge([$searchValue, $searchValue, $searchValue, $searchValue, $searchValue], $params);
    $types = "sssss" . $types;

    if (!empty($limit)) {
        $whereLimit = "LIMIT $limit";
    }

    $selectQuery = "SELECT * FROM CONTACTS_REVIEWS_TAB WHERE $searchClause $extraWhere ORDER BY createdTime DESC $whereLimit";
    $selectParams = array_merge($params);

    $allContactReviewData = selectQuery($conn, $selectQuery, $types, $selectParams);
    $allRecordCount = count($allContactReviewData);
    if (empty($allContactReviewData)) {
        throw new NotFoundException("No Contact Reviews found with the provided ID.");
    }

    foreach ($allContactReviewData as &$contactReviewData) {
        $statusId = $contactReviewData['statusId'];
        $updatedBy = $contactReviewData['updatedBy'];
        /// get statusData
        $statusData = _get_status_details($conn, $statusId);
        $contactReviewData['statusData'] = $statusData;

        /// get updatedByData
        $updatedByData = _action_performed_by($conn, $updatedBy);
        $contactReviewData['updatedByData'] = $updatedByData;
    }


    ///////////////// Response //////////////////
    $response = [
        'response' => 200,
        'success' => true,
        'message' => "Contact Reviews fetched successfully.",
        'allRecordCount' => $allRecordCount,
        'data' => $allContactReviewData,
    ];


} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>