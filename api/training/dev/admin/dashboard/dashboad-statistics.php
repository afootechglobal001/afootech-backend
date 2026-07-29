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
	$dateFrom = date('Y-m-d', strtotime(trim($_GET['dateFrom'])));
	$dateTo = date('Y-m-d', strtotime(trim($_GET['dateTo'])));

	$dateFromFormatted = date('F d Y', strtotime($dateFrom));
	$dateToFormatted = date('F d Y', strtotime($dateTo));



	$selectQuery = "SELECT
	(SELECT COUNT(*) FROM STAFF_TAB WHERE statusId=1) AS totalActiveStaffCount,
	(SELECT COUNT(*) FROM STUDENTS_PROGRAM_DETAILS_TAB WHERE trainingStatusId=3) AS totalAwaitingActivationStudentsCount,
	(SELECT COUNT(*) FROM STUDENTS_PROGRAM_DETAILS_TAB WHERE trainingStatusId=1) AS totalActiveStudentsCount,
	(SELECT COUNT(*) FROM STUDENTS_PROGRAM_DETAILS_TAB WHERE trainingStatusId=8 AND certificateStatusId=1) AS totalCertifiedStudentsCount
	";
	$systemStatistics = selectQuery($conn, $selectQuery)[0];

	$selectQuery = "SELECT
		IFNULL((SELECT SUM(amount) FROM PAYMENTS_TAB WHERE DATE(payDate) BETWEEN '$dateFrom' AND '$dateTo' AND statusId=5 AND paymentMethodId='PM001'), 0) AS sumCreditCardPayments,
		IFNULL((SELECT SUM(amount) FROM PAYMENTS_TAB WHERE DATE(payDate) BETWEEN '$dateFrom' AND '$dateTo' AND statusId=5 AND paymentMethodId='PM002'), 0) AS sumBankTransferPayments,
		IFNULL((SELECT SUM(paystackCharges) FROM PAYMENTS_TAB WHERE DATE(payDate) BETWEEN '$dateFrom' AND '$dateTo' AND statusId=5), 0) AS sumPaystackCharges,
		IFNULL((SELECT SUM(paystackRemittance) FROM PAYMENTS_TAB WHERE DATE(payDate) BETWEEN '$dateFrom' AND '$dateTo' AND statusId=5), 0) AS sumPaystackRemittance,

		IFNULL((SELECT COUNT(paymentId) FROM PAYMENTS_TAB WHERE DATE(payDate) BETWEEN '$dateFrom' AND '$dateTo' AND statusId=5 AND paymentMethodId='PM001'), 0) AS countCreditCardPayments,
		IFNULL((SELECT COUNT(paymentId) FROM PAYMENTS_TAB WHERE DATE(payDate) BETWEEN '$dateFrom' AND '$dateTo' AND statusId=5 AND paymentMethodId='PM002'), 0) AS countBankTransferPayments
	";
	$financialStatistics = selectQuery($conn, $selectQuery)[0];
	$totalRevenue = ($financialStatistics['sumCreditCardPayments'] ?? 0) + ($financialStatistics['sumBankTransferPayments'] ?? 0);


	///// fetch from SETUP_PROGRAM_TAB
	$selectQuery = "SELECT  * FROM SETUP_PROGRAM_TAB";
	$programData = selectQuery($conn, $selectQuery);

	foreach ($programData as &$program) {
		$programId = $program['programId'];
		// Fetch the count of active students for this program
		$activeStudentsCountQuery = "SELECT COUNT(*) AS activeStudentsCount FROM STUDENTS_PROGRAM_DETAILS_TAB WHERE programId = ? AND trainingStatusId IN (1,8)";
		$activeStudentsCountResult = selectQuery($conn, $activeStudentsCountQuery, "s", [$programId])[0];
		$program['activeStudentsCount'] = $activeStudentsCountResult['activeStudentsCount'] ?? 0;
	}


	///////////////// Response //////////////////
	$response = [
		'response' => 200,
		'success' => true,
		'message' => "Dashboard statistics fetched successfully.",
		'dateFrom' => $dateFromFormatted,
		'dateTo' => $dateToFormatted,
		'totalRevenue' => $totalRevenue,
		'data' => [
			'systemStatistics' => $systemStatistics,
			'financialStatistics' => $financialStatistics,
			'programData' => $programData,
		],
	];
} catch (Throwable $e) {
	ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);
?>