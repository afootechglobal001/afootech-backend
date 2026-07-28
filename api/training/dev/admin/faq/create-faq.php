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
    $categoryId = trim($data['categoryId'] ?? '');
    $faqQuestion = trim($data['faqQuestion'] ?? '');
    $faqAnswer = trim($data['faqAnswer'] ?? '');
    $statusId = trim($data['statusId'] ?? '');

    ////////////////// Validation //////////////////
    validateEmptyField($categoryId, 'FAQ CATEGORY');
    validateEmptyField($faqQuestion, 'FAQ QUESTION');
    validateEmptyField($faqAnswer, 'FAQ ANSWER');
    validateEmptyField($statusId, 'STATUS');

    ////////////////// Check Duplicate FAQ //////////////////
    $checkQuery = "SELECT faqId FROM FAQ_TAB WHERE faqQuestion = ?";
    $existingFAQ = selectQuery($conn, $checkQuery, "s", [$faqQuestion]);
    if (!empty($existingFAQ)) {
        throw new ConflictException("This FAQ with this question already exists. Please try another Question.");
    }

    ////////////////// Generate FAQ ID //////////////////
    $sequence = _get_sequence_count($conn, 'FAQ');
    $faqId = 'FAQ' . $sequence['no'] . date("Ymdhis");

    ////////////////// Insert FAQ //////////////////
    $insertQuery = "INSERT INTO FAQ_TAB 
    (categoryId, faqId, faqQuestion, faqAnswer, statusId, createdBy, createdTime ) VALUES 
    (?,?,?,?,?,?,NOW())";
    $insertParams = [$categoryId, $faqId, $faqQuestion, $faqAnswer, $statusId, $loginStaffId];
    insertQuery($conn, $insertQuery, "ssssss", $insertParams);

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
        'message' => "FAQ CREATED SUCCESSFULLY!",
        'data' => $faqData
    ];

} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>