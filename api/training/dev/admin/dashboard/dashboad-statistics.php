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

	$selectQuery = "SELECT
	(SELECT COUNT(*) FROM STAFF_TAB WHERE statusId=1) AS totalActiveStaffCount,
	(SELECT COUNT(*) FROM PAGES_TAB WHERE pageCategory='SERVICE' AND statusId=1) AS totalActiveServiceCount,
	(SELECT COUNT(*) FROM PAGES_TAB WHERE pageCategory='PORTFOLIO' AND statusId=1) AS totalActivePortfolioCount,
	(SELECT COUNT(*) FROM PAGES_TAB WHERE pageCategory='BLOG' AND statusId=1) AS totalActiveBlogCount,
	(SELECT COUNT(*) FROM FAQ_TAB WHERE statusId=1) AS totalActiveFaqCount,
	(SELECT COUNT(*) FROM CONTACTS_REVIEWS_TAB WHERE statusId=1 AND crFlag='REVIEW') AS totalActiveReviewCount";

	$allStatistics = selectQuery($conn, $selectQuery);

	///////////////// Response //////////////////
	$response = [
		'response' => 200,
		'success' => true,
		'message' => "Dashboard statistics fetched successfully.",
		'data' => $allStatistics,
	];
} catch (Throwable $e) {
	ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>