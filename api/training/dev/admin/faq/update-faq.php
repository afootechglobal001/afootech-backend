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
    $faqId = trim($_GET['faqId'] ?? '');
    $categoryId = trim($data['categoryId'] ?? '');
    $faqQuestion = trim($data['faqQuestion'] ?? '');
    $faqAnswer = trim($data['faqAnswer'] ?? '');
    $statusId = trim($data['statusId'] ?? '');

    ////////////////// Validation //////////////////
    validateEmptyField($faqId, 'FAQ ID');
    validateEmptyField($categoryId, 'FAQ CATEGORY');
    validateEmptyField($faqQuestion, 'FAQ QUESTION');
    validateEmptyField($faqAnswer, 'FAQ ANSWER');
    validateEmptyField($statusId, 'STATUS');

    // Check if FAQ question already exists for another FAQ
    ////////////////// Check Duplicate FAQ //////////////////
    $checkQuery = "SELECT faqId FROM FAQ_TAB WHERE faqQuestion = ? AND faqId != ?";
    $existingFAQ = selectQuery($conn, $checkQuery, "ss", [$faqQuestion, $faqId]);
    if (!empty($existingFAQ)) {
        throw new ConflictException("This FAQ with this question already exists. Please try another Question.");
    }

    ////////////////// Update FAQ //////////////////
    $updateSQL = "UPDATE FAQ_TAB SET categoryId = ?, faqQuestion = ?, faqAnswer = ?, statusId = ?, updatedBy = ?, updatedTime = NOW() WHERE faqId = ?";
    $updateParams = [$categoryId, $faqQuestion, $faqAnswer, $statusId, $loginStaffId, $faqId];
    updateQuery($conn, $updateSQL, "sssiss", $updateParams);

    ////////////////// Fetch Created FAQ //////////////////
    $selectQuery = "SELECT * FROM FAQ_TAB WHERE faqId = ?";
    $faqData = selectQuery($conn, $selectQuery, "s", [$faqId])[0];
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

    ////////////////// Response //////////////////
    $response = [
        'response' => 200,
        'success' => true,
        'message' => "FAQ UPDATED SUCCESSFULLY!",
        'data' => $faqData
    ];

} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>