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

    $selectQuery = " SELECT
        IFNULL((SELECT SUM(amount) FROM PAYMENTS_TAB WHERE DATE(payDate) BETWEEN '$dateFrom' AND '$dateTo' AND statusId=5 AND paymentMethodId='PM001'), 0) AS sumCreditCardPayments,
        IFNULL((SELECT SUM(amount) FROM PAYMENTS_TAB WHERE DATE(payDate) BETWEEN '$dateFrom' AND '$dateTo' AND statusId=5 AND paymentMethodId='PM002'), 0) AS sumBankTransferPayments,
        IFNULL((SELECT SUM(paystackCharges) FROM PAYMENTS_TAB WHERE DATE(payDate) BETWEEN '$dateFrom' AND '$dateTo' AND statusId=5), 0) AS sumPaystackCharges,
        IFNULL((SELECT SUM(paystackRemittance) FROM PAYMENTS_TAB WHERE DATE(payDate) BETWEEN '$dateFrom' AND '$dateTo' AND statusId=5), 0) AS sumPaystackRemittance,

        IFNULL((SELECT COUNT(paymentId) FROM PAYMENTS_TAB WHERE DATE(payDate) BETWEEN '$dateFrom' AND '$dateTo' AND statusId=5 AND paymentMethodId='PM001'), 0) AS countCreditCardPayments,
        IFNULL((SELECT COUNT(paymentId) FROM PAYMENTS_TAB WHERE DATE(payDate) BETWEEN '$dateFrom' AND '$dateTo' AND statusId=5 AND paymentMethodId='PM002'), 0) AS countBankTransferPayments
   ";
    $statisticsData = selectQuery($conn, $selectQuery)[0];
    $totalRevenue = ($statisticsData['sumCreditCardPayments'] ?? 0) + ($statisticsData['sumBankTransferPayments'] ?? 0);



    $selectQuery = "
        SELECT 
            DATE(payDate) AS payDate,
            SUM(CASE WHEN statusId = 5 THEN amount ELSE 0 END) AS totalSuccessfulFees,
            SUM(CASE WHEN statusId = 3 THEN amount ELSE 0 END) AS totalPendingFees,
            SUM(CASE WHEN statusId = 4 THEN amount ELSE 0 END) AS totalCancelledFees
        FROM PAYMENTS_TAB
        WHERE DATE(payDate) BETWEEN '$dateFrom' AND '$dateTo' AND paymentMethodId!='PM005'
        GROUP BY DATE(payDate)
        ORDER BY DATE(payDate) DESC
    ";
    $dataQuery = selectQuery($conn, $selectQuery);

    foreach ($dataQuery as &$fetchDataQuery) {
        $payDate = $fetchDataQuery['payDate'];

        //// get each date details of the revenue
        $eachDateQuery = " SELECT
        IFNULL((SELECT SUM(amount) FROM PAYMENTS_TAB WHERE DATE(payDate) = '$payDate' AND statusId=5 AND paymentMethodId='PM001'), 0) AS sumCreditCardPayments,
        IFNULL((SELECT SUM(amount) FROM PAYMENTS_TAB WHERE DATE(payDate) = '$payDate' AND statusId=5 AND paymentMethodId='PM002'), 0) AS sumBankTransferPayments,
        IFNULL((SELECT SUM(paystackCharges) FROM PAYMENTS_TAB WHERE DATE(payDate) = '$payDate' AND statusId=5), 0) AS sumPaystackCharges,
        IFNULL((SELECT SUM(paystackRemittance) FROM PAYMENTS_TAB WHERE DATE(payDate) = '$payDate' AND statusId=5), 0) AS sumPaystackRemittance,

        IFNULL((SELECT COUNT(paymentId) FROM PAYMENTS_TAB WHERE DATE(payDate) = '$payDate' AND statusId=5 AND paymentMethodId='PM001'), 0) AS countCreditCardPayments,
        IFNULL((SELECT COUNT(paymentId) FROM PAYMENTS_TAB WHERE DATE(payDate) = '$payDate' AND statusId=5 AND paymentMethodId='PM002'), 0) AS countBankTransferPayments,
        IFNULL((SELECT COUNT(paymentId) FROM PAYMENTS_TAB WHERE DATE(payDate) = '$payDate' AND statusId=5 AND (
                viewedBy IS NULL 
                OR viewedBy = '' 
                OR FIND_IN_SET('$loginStaffId', viewedBy) = 0
            )), 0) AS countUnviewedPayments
   ";
        $eachDateData = selectQuery($conn, $eachDateQuery)[0];
        $fetchDataQuery['eachDateData'] = $eachDateData;

        /// TRUE if there is at least one unviewed record
        $unviewedData = $eachDateData['countUnviewedPayments'] ?? 0;
        $fetchDataQuery['paymentViewed'] = !($unviewedData > 0);
    }


    $response = [
        'response' => 200,
        'success' => true,
        'message' => "DASHBOARD REVENUE FETCHED SUCCESSFULLY",
        'dateFrom' => $dateFromFormatted,
        'dateTo' => $dateToFormatted,
        'totalRevenue' => $totalRevenue,
        'statistics' => $statisticsData,
        'data' => $dataQuery,
    ];

} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);