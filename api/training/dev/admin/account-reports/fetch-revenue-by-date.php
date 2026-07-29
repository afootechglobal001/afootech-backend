<?php
require_once '../../config/connection.php';
require_once '../../config/staff-session-check.php';
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
$date = date('Y-m-d', strtotime(trim($_GET['date'])));
$dateFormatted = date('F d Y', strtotime($date));
$statusId = trim($_GET['statusId']);
///get sum total amount paid on that date
$totalAmountQuery = mysqli_query($conn, "
    SELECT IFNULL(SUM(totalFeesPaid), 0) AS totalAmount 
    FROM PAYMENTS_TAB 
    WHERE $clientIds AND DATE(payDate) = '$date' AND session LIKE '%$session%' AND termId LIKE '%$termId%'
    AND statusId=5  AND paymentMethodId!='PM005'
");
$totalAmount = mysqli_fetch_assoc($totalAmountQuery)['totalAmount'];

$dataQuery = mysqli_query($conn, "
    SELECT paymentId, studentId, email, branchId, session, termId, totalFeesPaid, paymentMethodId, statusId, payDate, departmentId, classId, armId
    FROM PAYMENTS_TAB 
    WHERE $clientIds AND DATE(payDate) = '$date' AND session LIKE '%$session%' AND termId LIKE '%$termId%'
    AND statusId='$statusId' AND paymentMethodId!='PM005'
    ORDER BY DATE(payDate) DESC
");
$allRecordCount = mysqli_num_rows($dataQuery);

$response = [
    'response' => 200,
    'success' => true,
    'message' => "REVENUE FETCHED SUCCESSFULLY",
    'allRecordCount' => $allRecordCount,
    'date' => $dateFormatted,
    'totalAmount' => $totalAmount,
    'data' => [],
];



while ($fetchDataQuery = mysqli_fetch_assoc($dataQuery)) {
    $paymentId = $fetchDataQuery['paymentId'];
    $studentId = $fetchDataQuery['studentId'];
    $branchId = $fetchDataQuery['branchId'];
    $email = $fetchDataQuery['email'];

    ///// confirm if this payment has be viewed by the admin before, if not mark as viewed
    $viewedQuery = mysqli_query($conn, "SELECT paymentId FROM PAYMENTS_TAB WHERE $clientIds AND paymentId='$paymentId' AND viewedBy LIKE '%$loginStaffId%'");
    $viewedData = mysqli_num_rows($viewedQuery);
    $fetchDataQuery['paymentViewed'] = $viewedData > 0 ? true : false;
    //get student details
    $studentQuery = mysqli_query($conn, "SELECT studentId, surName, firstName, otherNames, passport FROM STUDENTS_TAB WHERE $clientIds AND branchId='$branchId' AND studentId='$studentId'");
    $studentData = mysqli_fetch_assoc($studentQuery);
    $fetchDataQuery['studentData'] = $studentData;
    //get parent details
    $parentQuery = mysqli_query($conn, "SELECT email, titleId, surName, otherNames, mobileNumber, recordFor FROM PARENTS_TAB WHERE $clientIds AND branchId='$branchId' AND studentId='$studentId' AND email='$email' LIMIT 1");
    $parentData = mysqli_fetch_assoc($parentQuery);
    $fetchDataQuery['parentData'] = $parentData;

    //get branch details
    $branchQuery = mysqli_query($conn, "SELECT branchId, name AS branchName, mobileNumber FROM BRANCHES_TAB WHERE $clientIds AND branchId='$branchId'");
    $branchData = mysqli_fetch_assoc($branchQuery);
    $fetchDataQuery['branchData'] = $branchData;

    //get term details
    $termId = $fetchDataQuery['termId'];
    $termQuery = mysqli_query($conn, "SELECT termId, termName FROM SETUP_TERM_TAB WHERE termId='$termId'");
    $termData = mysqli_fetch_assoc($termQuery);
    $fetchDataQuery['termData'] = $termData;

    //get status details
    $statusId = $fetchDataQuery['statusId'];
    $statusQuery = mysqli_query($conn, "SELECT statusId, statusName FROM SETUP_STATUS_TAB WHERE statusId='$statusId'");
    $statusData = mysqli_fetch_assoc($statusQuery);
    $fetchDataQuery['statusData'] = $statusData;

    //get class details
    $classId = $fetchDataQuery['classId'];
    $classQuery = mysqli_query($conn, "SELECT classId, className FROM CLASSES_TAB WHERE $clientIds AND classId='$classId'");
    $classData = mysqli_fetch_assoc($classQuery);
    $fetchDataQuery['classData'] = $classData;

    //get arm details
    $armId = $fetchDataQuery['armId'];
    $armQuery = mysqli_query($conn, "SELECT armId, armName FROM ARMS_TAB WHERE $clientIds AND armId='$armId'");
    $armData = mysqli_fetch_assoc($armQuery);
    $fetchDataQuery['armData'] = $armData;

    $response['data'][] = $fetchDataQuery;
}

end:
echo json_encode($response);
?>