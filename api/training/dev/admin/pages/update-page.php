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
    $pageId = trim($_GET['pageId'] ?? '');
    $pageCategory = trim($_GET['pageCategory'] ?? ''); //// can be BLOG, PORTFOLIO, SERVICE
    $categoryId = trim($data['categoryId'] ?? ''); /// 
    $projectStageId = trim($data['projectStageId'] ?? ''); /// optional
    $projectCategoryId = trim($data['projectCategoryId'] ?? ''); /// optional
    $pageTitle = trim($data['pageTitle'] ?? '');
    $pageUrl = trim($data['pageUrl'] ?? '');
    $seoKeywords = trim($data['seoKeywords'] ?? '');
    $seoDescription = trim($data['seoDescription'] ?? '');
    $pageContent = trim($data['pageContent'] ?? '');
    $location = trim($data['location'] ?? ''); /// optional
    $statusId = trim($data['statusId'] ?? '');

    ////////////////// Validation //////////////////
    validateEmptyField($pageId, 'PAGE ID');
    validateEmptyField($pageCategory, 'PAGE CATEGORY');
    validateEmptyField($pageTitle, 'PAGE TITLE');
    validateEmptyField($pageUrl, 'PAGE URL');
    validateEmptyField($seoKeywords, 'SEO KEYWORDS');
    validateEmptyField($seoDescription, 'SEO DESCRIPTION');
    validateEmptyField($pageContent, 'PAGE CONTENT');
    validateEmptyField($statusId, 'STATUS ID');

    if ($pageCategory === 'BLOG') {
        validateEmptyField($categoryId, 'CATEGORY ID');
    }
    if ($pageCategory === 'PORTFOLIO') {
        validateEmptyField($projectStageId, 'PROJECT STAGE ID');
        validateEmptyField($projectCategoryId, 'PROJECT CATEGORY ID');
        validateEmptyField($location, 'LOCATION');
    }

    ////////////////// Check Duplicate //////////////////
    $selectQuery = "SELECT * FROM PAGES_TAB WHERE pageCategory = ? AND pageUrl = ? AND pageId != ?";
    $pageData = selectQuery($conn, $selectQuery, "sss", [$pageCategory, $pageUrl, $pageId]);
    if (!empty($pageData)) {
        throw new ConflictException("A page with the same category and URL already exists. Please choose a different URL.");
    }

    /// get previous pageUrl
    $selectQuery = "SELECT pageUrl FROM PAGES_TAB WHERE pageId = ?";
    $selectParams = [$pageId];
    $oldPageUrl = selectQuery($conn, $selectQuery, "s", $selectParams)[0]['pageUrl'];

    //// update page
    $updateQuery = "UPDATE PAGES_TAB SET pageTitle = ?, pageUrl = ?, seoKeywords = ?, seoDescription = ?, pageContent = ?, statusId = ?, updatedBy = ?, updatedTime = NOW() WHERE pageId = ?";
    $updateParams = [$pageTitle, $pageUrl, $seoKeywords, $seoDescription, $pageContent, $statusId, $loginStaffId, $pageId];
    updateQuery($conn, $updateQuery, "sssssiss", $updateParams);

    if ($pageCategory === 'BLOG') {
        /// update categoryId in PAGES_TAB
        $updateQuery = "UPDATE PAGES_TAB SET categoryId = ? WHERE pageId = ?";
        $updateParams = [$categoryId, $pageId];
        updateQuery($conn, $updateQuery, "ss", $updateParams);
    }
    if ($pageCategory === 'PORTFOLIO') {
        /// update projectStageId, projectCategoryId, and location in PAGES_TAB
        $updateQuery = "UPDATE PAGES_TAB SET projectStageId = ?, projectCategoryId = ?, location = ? WHERE pageId = ?";
        $updateParams = [$projectStageId, $projectCategoryId, $location, $pageId];
        updateQuery($conn, $updateQuery, "ssss", $updateParams);
    }

    ////////////////// Fetch Updated Page //////////////////
    $selectQuery = "SELECT * FROM PAGES_TAB WHERE pageId = ?";
    $selectParams = [$pageId];
    $pageData = selectQuery($conn, $selectQuery, "s", $selectParams)[0];
    $categoryId = $pageData['categoryId'];
    $projectStageId = $pageData['projectStageId'];
    $projectCategoryId = $pageData['projectCategoryId'];
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
        /// get projectStageData
        $projectStageData = _get_project_stage_details($conn, $projectStageId);
        $pageData['projectStageData'] = $projectStageData;
        /// get projectCategoryData
        $projectCategoryData = _get_project_category_details($conn, $projectCategoryId);
        $pageData['projectCategoryData'] = $projectCategoryData;
    }

    ////////////////// Response //////////////////
    $response = [
        'response' => 200,
        'success' => true,
        'message' => "PAGE UPDATED SUCCESSFULLY",
        'oldPageUrl' => $oldPageUrl != $pageUrl ? $oldPageUrl : null,
        'data' => $pageData
    ];

} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>