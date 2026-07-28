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
    $faqId = trim($_GET['faqId'] ?? '');
    $statusId = trim($_GET['statusId'] ?? '');

    ////////////////// Build Query //////////////////
    $conditions = [];
    $params = [];
    $types = '';

    if (!empty($faqId)) {
        $conditions[] = "faqId = ?";
        $params[] = $faqId;
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
            faqQuestion LIKE ?
            OR faqAnswer LIKE ?
        )
    ";

    $searchValue = "%{$q}%";

    $params = array_merge([$searchValue, $searchValue], $params);
    $types = "ss" . $types;

    $selectQuery = "SELECT * FROM FAQ_TAB WHERE $searchClause $extraWhere";
    $selectParams = array_merge($params);

    $allFAQData = selectQuery($conn, $selectQuery, $types, $selectParams);
    $allRecordCount = count($allFAQData);
    if (empty($allFAQData)) {
        throw new NotFoundException("No FAQ found with the provided FAQ ID.");
    }

    foreach ($allFAQData as &$faqData) {
        $categoryId = $faqData['categoryId'];
        $statusId = $faqData['statusId'];
        $createdBy = $faqData['createdBy'];
        $updatedBy = $faqData['updatedBy'];

        /// get categoryData
        $categoryData = _get_category_details($conn, $categoryId);
        $faqData['categoryData'] = $categoryData;
        /// get statusData
        $statusData = _get_status_details($conn, $statusId);
        $faqData['statusData'] = $statusData;
        /// get createdByData
        $createdByData = _action_performed_by($conn, $createdBy);
        $faqData['createdByData'] = $createdByData;
        /// get updatedByData
        $updatedByData = _action_performed_by($conn, $updatedBy);
        $faqData['updatedByData'] = $updatedByData;
    }


    ///////////////// Response //////////////////
    $response = [
        'response' => 200,
        'success' => true,
        'message' => "FAQ fetched successfully.",
        'allRecordCount' => $allRecordCount,
        'data' => $allFAQData,
    ];


} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>