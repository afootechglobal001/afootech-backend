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
    $categoryId = trim($_GET['categoryId'] ?? '');
    $categoryName = trim($data['categoryName'] ?? '');
    $statusId = trim($data['statusId'] ?? '');

    ////////////////// Validation //////////////////
    validateEmptyField($categoryName, 'CATEGORY NAME');
    validateEmptyField($statusId, 'STATUS ID');

    ////////////////// Check Duplicate //////////////////
    $checkQuery = "SELECT categoryId FROM INFORMATION_CATEGORY_TAB WHERE categoryName = ? AND categoryId != ?";
    $existing = selectQuery($conn, $checkQuery, "ss", [$categoryName, $categoryId]);
    if (!empty($existing)) {
        throw new ConflictException("CATEGORY EXIST! Category already exists by name. Check and try again.");
    }

    ////////////////// Update Category //////////////////
    $updateQuery = "UPDATE INFORMATION_CATEGORY_TAB
        SET categoryName = ?, statusId = ?, updatedBy = ?, updatedTime = NOW()
        WHERE categoryId = ?
    ";
    $updateParams = [$categoryName, $statusId, $loginStaffId, $categoryId];
    updateQuery($conn, $updateQuery, "siss", $updateParams);

    ////////////////// Fetch Created Category //////////////////
    $selectQuery = "SELECT * FROM INFORMATION_CATEGORY_TAB WHERE categoryId = ?";
    $selectParams = [$categoryId];
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
        'message' => "INFORMATION CATEGORY UPDATED SUCCESSFULLY!",
        'data' => $categoryData
    ];

} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>