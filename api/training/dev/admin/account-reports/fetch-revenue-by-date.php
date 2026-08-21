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

    $date = date('Y-m-d', strtotime(trim($_GET['date'])));
    $dateFormatted = date('F d Y', strtotime($date));
    $statusId = trim($_GET['statusId']);

    //// get each date statistics of the revenue
    $eachDateStatisticsQuery = " SELECT
        IFNULL((SELECT SUM(amount) FROM PAYMENTS_TAB WHERE DATE(payDate) = '$date' AND statusId='$statusId' AND paymentMethodId='PM001'), 0) AS sumCreditCardPayments,
        IFNULL((SELECT SUM(amount) FROM PAYMENTS_TAB WHERE DATE(payDate) = '$date' AND statusId='$statusId' AND paymentMethodId='PM002'), 0) AS sumBankTransferPayments,
        IFNULL((SELECT SUM(paystackCharges) FROM PAYMENTS_TAB WHERE DATE(payDate) = '$date' AND statusId='$statusId'), 0) AS sumPaystackCharges,
        IFNULL((SELECT SUM(paystackRemittance) FROM PAYMENTS_TAB WHERE DATE(payDate) = '$date' AND statusId='$statusId'), 0) AS sumPaystackRemittance,

        IFNULL((SELECT COUNT(paymentId) FROM PAYMENTS_TAB WHERE DATE(payDate) = '$date' AND statusId='$statusId' AND paymentMethodId='PM001'), 0) AS countCreditCardPayments,
        IFNULL((SELECT COUNT(paymentId) FROM PAYMENTS_TAB WHERE DATE(payDate) = '$date' AND statusId='$statusId' AND paymentMethodId='PM002'), 0) AS countBankTransferPayments
   ";
    $eachDateStatisticsData = selectQuery($conn, $eachDateStatisticsQuery)[0];


    $data = [];
    $select = "SELECT 
        paymentId, 
        studentId, 
        emailAddress, 
        phoneNumber, 
        paymentPurposeId, 
        amount, 
        paymentMethodId, 
        paystackId, 
        paystackCharges, 
        paystackRemittance, 
        statusId, 
        confirmedBy, 
        createdTime, 
        payDate, 
        updatedTime        
        FROM PAYMENTS_TAB
        WHERE DATE(payDate) = '$date' AND statusId='$statusId'
        ORDER BY DATE(payDate) DESC
    ";
    $eachDateData = selectQuery($conn, $select);
    foreach ($eachDateData as &$fetchDataQuery) {

        $studentId = $fetchDataQuery['studentId'];
        $paymentPurposeId = $fetchDataQuery['paymentPurposeId'];
        $paymentMethodId = $fetchDataQuery['paymentMethodId'];
        $statusId = $fetchDataQuery['statusId'];
        $confirmedBy = $fetchDataQuery['confirmedBy'];

        $studentData = ($paymentPurposeId === 'form' && $statusId !== 5)
            ? _get_prospective_details($conn, $studentId)
            : _get_registered_details($conn, $studentId);

        $fetchDataQuery['studentData'] = $studentData;

        /// get payment purpose details
        $paymentPurposeData = _get_payment_purpose_details($conn, $paymentPurposeId);
        $fetchDataQuery['paymentPurposeData'] = $paymentPurposeData;
        /// get payment method details
        $paymentMethodData = _get_payment_method_details($conn, $paymentMethodId);
        $fetchDataQuery['paymentMethodData'] = $paymentMethodData;
        /// get status details
        $statusData = _get_status_details($conn, $statusId);
        $fetchDataQuery['statusData'] = $statusData;
        /// get confirmed by details
        $confirmedByData = _action_performed_by($conn, $confirmedBy);
        $fetchDataQuery['confirmedByData'] = $confirmedByData;

        $data[] = $fetchDataQuery;
    }

    $response = [
        'response' => 200,
        'success' => true,
        'message' => "REVENUE FETCHED SUCCESSFULLY",
        'allRecordCount' => count($data),
        'date' => $dateFormatted,
        'statistics' => $eachDateStatisticsData,
        'totalAmount' => $eachDateStatisticsData['sumCreditCardPayments'] + $eachDateStatisticsData['sumBankTransferPayments'],
        'data' => $data,
    ];


} catch (Throwable $e) {
    ErrorHandler::handle($e);
}

http_response_code($response['response'] ?? 500);
echo json_encode($response);