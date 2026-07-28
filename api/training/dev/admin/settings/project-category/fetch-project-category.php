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
    $q = trim($_GET['q'] ?? '');
    $projectCategoryId = trim($_GET['projectCategoryId'] ?? '');
    $statusId = trim($_GET['statusId'] ?? '');

    ////////////////// Build Filters //////////////////
    $filters = [];
    $params = [];
    $types = '';

    if (!empty($projectCategoryId)) {
        $filters[] = "projectCategoryId = ?";
        $params[] = $projectCategoryId;
        $types .= 's';
    }

    if (!empty($statusId)) {
        $statusArray = explode(',', $statusId);
        $placeholders = implode(',', array_fill(0, count($statusArray), '?'));
        $filters[] = "statusId IN ($placeholders)";
        foreach ($statusArray as $s) {
            $params[] = trim($s);
            $types .= 's';
        }
    }

    if (!empty($q)) {
        $filters[] = "projectCategoryName LIKE ?";
        $params[] = "%$q%";
        $types .= 's';
    }

    $whereClause = '';
    if (!empty($filters)) {
        $whereClause = 'WHERE ' . implode(' AND ', $filters);
    }

    ////////////////// Fetch Categories //////////////////
    $select = "SELECT * FROM PROJECT_CATEGORY_TAB $whereClause ORDER BY projectCategoryName ASC";
    $categories = selectQuery($conn, $select, $types, $params);

    if (empty($categories)) {
        throw new NotFoundException("No Record found");
    }

    foreach ($categories as &$categoryData) {
        $statusId = $categoryData['statusId'];
        $createdBy = $categoryData['createdBy'];
        $updatedBy = $categoryData['updatedBy'];


        /// get statusData
        $statusData = _get_status_details($conn, $statusId);
        $categoryData['statusData'] = $statusData;
        /// get createdByData
        $createdByData = _action_performed_by($conn, $createdBy);
        $categoryData['createdByData'] = $createdByData;
        /// get updatedByData
        $updatedByData = _action_performed_by($conn, $updatedBy);
        $categoryData['updatedByData'] = $updatedByData;
    }

    $response = [
        'response' => 200,
        'success' => true,
        'message' => "PROJECt CATEGORY FETCHED SUCCESSFULLY!",
        'allRecordCount' => count($categories),
        'data' => $categories
    ];


} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>