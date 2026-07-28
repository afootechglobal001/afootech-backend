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
    $pageId = trim($_GET['pageId'] ?? '');
    $pageCategory = trim($_GET['pageCategory'] ?? ''); //// can be BLOG, PORTFOLIO, SERVICE
    $statusId = trim($_GET['statusId'] ?? '');
    ////////////////// Validation //////////////////
    validateEmptyField($pageCategory, 'PAGE CATEGORY');

    $conditions = [];
    $params = [];
    $types = '';

    if (!empty($pageId)) {
        $conditions[] = "pageId = ?";
        $params[] = $pageId;
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
            pageTitle LIKE ?
            OR pageUrl LIKE ?
            OR seoKeywords LIKE ?
            OR seoDescription LIKE ?
            OR pageContent LIKE ?
        )
    ";

    $searchValue = "%{$q}%";

    $params = array_merge([$searchValue, $searchValue, $searchValue, $searchValue, $searchValue], $params);
    $types = "sssss" . $types;

    $selectQuery = "SELECT * FROM PAGES_TAB WHERE $searchClause $extraWhere AND pageCategory = ?";
    $selectParams = array_merge($params, [$pageCategory]);
    $allPageData = selectQuery($conn, $selectQuery, $types . "s", $selectParams);
    $allRecordCount = count($allPageData);
    if (empty($allPageData)) {
        throw new NotFoundException("No page found with the provided PAGE ID.");
    }



    foreach ($allPageData as &$pageData) {
        $pagePicturesData = [];
        $pageId = $pageData['pageId'];
        $categoryId = $pageData['categoryId'];
        $projectCategoryId = $pageData['projectCategoryId'];
        $projectStageId = $pageData['projectStageId'];
        $statusId = $pageData['statusId'];
        $createdBy = $pageData['createdBy'];
        $updatedBy = $pageData['updatedBy'];

        /// get statusData
        $statusData = _get_status_details($conn, $statusId);
        $pageData['statusData'] = $statusData;
        /// get createdByData
        $createdByData = _action_performed_by($conn, $createdBy);
        $pageData['createdByData'] = $createdByData;
        /// get updatedByData
        $updatedByData = _action_performed_by($conn, $updatedBy);
        $pageData['updatedByData'] = $updatedByData;
        if ($pageCategory === 'BLOG') {
            /// get categoryData
            $categoryData = _get_category_details($conn, $categoryId);
            $pageData['categoryData'] = $categoryData;
        }
        if ($pageCategory === 'PORTFOLIO') {
            /// get categoryData
            $projectStageData = _get_project_stage_details($conn, $projectStageId);
            $pageData['projectStageData'] = $projectStageData;

            /// get projectCategoryData
            $projectCategoryData = _get_project_category_details($conn, $projectCategoryId);
            $pageData['projectCategoryData'] = $projectCategoryData;
        }

        /// get pagePicturesData
        $selectQuery = "SELECT * FROM PAGE_PICTURES_TAB WHERE pageId = ? ORDER BY sn ASC";
        $selectParams = [$pageId];
        $pagePicturesData = selectQuery($conn, $selectQuery, "s", $selectParams);
        $pageData['pagePicturesData'] = $pagePicturesData;
    }

    ////////////////// Response //////////////////
    $response = [
        'response' => 200,
        'success' => true,
        'message' => "PAGE FETCHED SUCCESSFULLY!",
        'allRecordCount' => $allRecordCount,
        'data' => $allPageData
    ];

} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>