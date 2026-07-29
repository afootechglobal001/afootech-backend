<?php
require_once '../../config/connection.php';
require_once '../../config/staff-session-check.php';

$response = ['success' => false]; // Initialize

if (!$checkBasicSecurity) {
    goto end;
}
if (!$checkSession) {
    $response = [
        'response' => 99,
        'success' => false,
        'message' => "SESSION EXPIRED! Please LogIn Again."
    ];
    goto end;
}

$session = trim($_GET['session']);
$termId = trim($_GET['termId']);

$statisticsQuery = mysqli_query($conn, "
    SELECT
        IFNULL((SELECT SUM(totalFeesPaid) FROM PAYMENTS_TAB WHERE $clientIds AND session='$session' AND termId='$termId' AND statusId=5 AND paymentMethodId='PM001'), 0) AS sumCreditCardPayments,
        IFNULL((SELECT SUM(totalFeesPaid) FROM PAYMENTS_TAB WHERE $clientIds AND session='$session' AND termId='$termId' AND statusId=5 AND paymentMethodId='PM002'), 0) AS sumBankTransferPayments,
        IFNULL((SELECT SUM(totalFeesPaid) FROM PAYMENTS_TAB WHERE $clientIds AND session='$session' AND termId='$termId' AND statusId=5 AND paymentMethodId='PM004'), 0) AS sumManualPayments,
        
        IFNULL((SELECT COUNT(paymentId) FROM PAYMENTS_TAB WHERE $clientIds AND session='$session' AND termId='$termId' AND statusId=5 AND paymentMethodId='PM001'), 0) AS countCreditCardPayments,
        IFNULL((SELECT COUNT(paymentId) FROM PAYMENTS_TAB WHERE $clientIds AND session='$session' AND termId='$termId' AND statusId=5 AND paymentMethodId='PM002'), 0) AS countBankTransferPayments,
        IFNULL((SELECT COUNT(paymentId) FROM PAYMENTS_TAB WHERE $clientIds AND session='$session' AND termId='$termId' AND statusId=5 AND paymentMethodId='PM004'), 0) AS countManualPayments
");


if (!$statisticsQuery) {
    die("Data Query Failed: " . mysqli_error($conn));
}

//get term details
$termQuery = mysqli_query($conn, "SELECT termId, termName FROM SETUP_TERM_TAB WHERE termId='$termId'");
$termData = mysqli_fetch_assoc($termQuery);

$response = [
    'response' => 200,
    'success' => true,
    'message' => "TERM REVENUE FETCHED SUCCESSFULLY",
    'session' => $session,
    'termData' => $termData,
    'statistics' => [],
    'totalRevenue' => 0, // Initialize total revenue
    'data' => [],
];

while ($statisticsDataQuery = mysqli_fetch_assoc($statisticsQuery)) {
    $response['statistics'][] = $statisticsDataQuery;
    $response['totalRevenue'] += $statisticsDataQuery['sumCreditCardPayments'] + $statisticsDataQuery['sumBankTransferPayments'] + $statisticsDataQuery['sumManualPayments'];

}

$dataQuery = mysqli_query($conn, "
    SELECT SUM(totalFeesPaid) AS totalFeesPaid, DATE(payDate) AS payDate 
    FROM PAYMENTS_TAB 
    WHERE $clientIds AND session='$session' AND termId='$termId' 
    AND statusId=5 
    GROUP BY DATE(payDate) 
    ORDER BY DATE(payDate) DESC
");

while ($fetchDataQuery = mysqli_fetch_assoc($dataQuery)) {
    $response['data'][] = $fetchDataQuery;
}

end:
echo json_encode($response);
?>