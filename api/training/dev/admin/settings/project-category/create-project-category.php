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
    $projectCategoryName = trim($data['projectCategoryName'] ?? '');
    $statusId = trim($data['statusId'] ?? '');

    ////////////////// Validation //////////////////
    validateEmptyField($projectCategoryName, 'PROJECT CATEGORY NAME');
    validateEmptyField($statusId, 'STATUS ID');

    ////////////////// Check Duplicate //////////////////
    $selectQuery = "SELECT projectCategoryId FROM PROJECT_CATEGORY_TAB WHERE projectCategoryName = ?";
    $categoryData = selectQuery($conn, $selectQuery, "s", [$projectCategoryName]);
    if (!empty($categoryData)) {
        throw new ConflictException("PROJECT CATEGORY EXIST! Project category already exists by name. Check and try again.");
    }

    ////////////////// Generate Category ID //////////////////
    $sequence = _get_sequence_count($conn, 'PC');
    $projectCategoryId = 'PC' . $sequence['no'] . date("Ymdhis");

    ////////////////// Insert Category //////////////////
    $insertQuery = "INSERT INTO PROJECT_CATEGORY_TAB
        (projectCategoryId, projectCategoryName, statusId, createdBy, createdTime)
        VALUES (?, ?, ?, ?, NOW())
    ";
    $insertParams = [$projectCategoryId, $projectCategoryName, $statusId, $loginStaffId];
    insertQuery($conn, $insertQuery, "ssss", $insertParams);

    ////////////////// Fetch Created Category //////////////////
    $selectQuery = "SELECT * FROM PROJECT_CATEGORY_TAB WHERE projectCategoryId = ?";
    $selectParams = [$projectCategoryId];
    $categoryData = selectQuery($conn, $selectQuery, "s", $selectParams)[0];
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

    ////////////////// Response //////////////////
    $response = [
        'response' => 200,
        'success' => true,
        'message' => "PROJECT CATEGORY CREATED SUCCESSFULLY!",
        'data' => $categoryData
    ];

} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>