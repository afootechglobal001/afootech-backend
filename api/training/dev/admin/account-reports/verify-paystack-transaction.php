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

$paymentId = trim($_GET['paymentId']);
validateEmptyField($paymentId, 'PAYMENT ID');

$dataQuery = mysqli_query($conn, "
    SELECT branchId, createdTime
    FROM PAYMENTS_TAB 
    WHERE $clientIds AND paymentId='$paymentId'
");

if (!$dataQuery || mysqli_num_rows($dataQuery) == 0) {
    $response = [
        'response' => 100,
        'success' => false,
        'message' => "INVALID PAYMENT ID! Check payment ID and try again.",
    ];
    goto end;
}

$fetchDataQuery = mysqli_fetch_assoc($dataQuery);
$branchId = $fetchDataQuery['branchId'];
$createdTime = $fetchDataQuery['createdTime'];

// Convert to timestamps
$createdTimestamp = strtotime($createdTime);
$currentTimestamp = time();

// Difference in seconds
$timeDifference = $currentTimestamp - $createdTimestamp;

// 1 hour = 3600 seconds
if ($timeDifference < 3600) {
    $response = [
        'response' => 101,
        'success' => false,
        'message' => "PAYMENT IS STILL PROCESSING! Please wait at least 1 hour before verifying.",
    ];
    goto end;
}


/// get secret key for the branch
$secretKeyQuery = mysqli_query($conn, "
    SELECT secretKey
    FROM BRANCHES_TAB 
    WHERE $clientIds AND branchId='$branchId'
");
$secretKeyData = mysqli_fetch_assoc($secretKeyQuery);
$secretKey = $secretKeyData['secretKey'];

///// update PAYMENTS_TAB to verify the staff that is verifying the payment
$updateQuery = mysqli_query($conn, "
    UPDATE PAYMENTS_TAB 
    SET confirmedBy='$loginStaffId'
    WHERE $clientIds AND paymentId='$paymentId'
");

$response = [
    'response' => 200,
    'success' => true,
    'message' => "PROCEED TO PAYMENT VERIFICATION",
    'branchId' => $branchId,
    'paymentId' => $paymentId,
    'secretKey' => $secretKey,
];

end:
echo json_encode($response);
?>