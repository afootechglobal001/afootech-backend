<?php
function _staff_accesskey_validation($conn, $accessKey)
{
    $getQuery = "SELECT * FROM STAFF_VIEW WHERE accessKey=? AND statusId=?";
    $getParams = [$accessKey, 1];
    $getResult = selectQuery($conn, $getQuery, 'si', $getParams);
    $count = count($getResult);
    if ($count > 0) {
        $userData = $getResult[0];
        $firstName = $userData['firstName'];
        $lastName = $userData['lastName'];
        $response = [
            "checkSession" => true,
            "loginStaffId" => $userData['staffId'],
            "loginFullname" => "$firstName $lastName",
            "loginRoleid" => $userData['roleId']
        ];
    } else {
        $response = [
            "checkSession" => false
        ];
    }
    return json_encode($response);
}

///////////////////////////////////////////////////////////////////////////////////////////////////
function _get_sequence_count($conn, $counterId)
{
    $getQuery = "SELECT counterValue FROM SETUP_COUNTER_TAB WHERE counterId = ? FOR UPDATE";
    $getParams = [$counterId];
    $getResult = selectQuery($conn, $getQuery, 's', $getParams);
    $count = $getResult[0]['counterValue'];
    $num = $count + 1;
    ///// update the counter value in the database
    $updateQuery = "UPDATE `SETUP_COUNTER_TAB` SET `counterValue` = ? WHERE counterId = ?";
    $updateParams = [$num, $counterId];
    updateQuery($conn, $updateQuery, 'is', $updateParams);
    if ($num < 10) {
        $no = '00' . $num;
    } elseif ($num >= 10 && $num < 100) {
        $no = '0' . $num;
    } else {
        $no = $num;
    }
    $response = ["no" => $no];
    return ($response);
}

function _action_performed_by($conn, $staffId)
{
    $getQuery = "SELECT CONCAT(firstName,' ',lastName) AS fullname, emailAddress FROM STAFF_TAB WHERE staffId = ?";
    $getParams = [$staffId];
    $getResult = selectQuery($conn, $getQuery, 's', $getParams);
    return ($getResult[0]);
}
////// get STATUS details
function _get_status_details($conn, $statusId)
{
    $getQuery = "SELECT statusId, statusName FROM SETUP_STATUS_TAB WHERE statusId = ?";
    $getParams = [$statusId];
    $getResult = selectQuery($conn, $getQuery, 'i', $getParams);
    return ($getResult[0]);
}

function _get_setup_backend_settings_detail($conn, $settingsId)
{
    $getQuery = "SELECT * FROM SETUP_BACKEND_SETTINGS_TAB WHERE settingsId = ?";
    $getParams = [$settingsId];
    $getResult = selectQuery($conn, $getQuery, 's', $getParams);
    return ($getResult[0]);
}

/// get institution type details
function _get_institution_type_details($conn, $institutionTypeId)
{
    $getQuery = "SELECT * FROM SETUP_INSTITUTION_TYPE_TAB WHERE institutionTypeId = ?";
    $getParams = [$institutionTypeId];
    $getResult = selectQuery($conn, $getQuery, 's', $getParams);
    return ($getResult[0]);
}

/// get institution level details
function _get_institution_level_details($conn, $institutionTypeId, $levelId)
{
    $getQuery = "SELECT * FROM SETUP_INSTITUTION_LEVEL_TAB WHERE institutionTypeId = ? AND levelId = ?";
    $getParams = [$institutionTypeId, $levelId];
    $getResult = selectQuery($conn, $getQuery, 'ss', $getParams);
    return ($getResult[0]);
}

/// get program details
function _get_program_details($conn, $programId)
{
    $getQuery = "SELECT * FROM SETUP_PROGRAM_TAB WHERE programId = ?";
    $getParams = [$programId];
    $getResult = selectQuery($conn, $getQuery, 's', $getParams);
    return ($getResult[0]);
}

/// get course details
function _get_course_details($conn, $courseId)
{
    $getQuery = "SELECT * FROM COURSES_TAB WHERE courseId = ?";
    $getParams = [$courseId];
    $getResult = selectQuery($conn, $getQuery, 's', $getParams);
    return ($getResult[0]);
}

/// get program course duration details
function _get_program_course_duration_details($conn, $programId, $courseId, $durationId)
{
    $getQuery = "SELECT * FROM PROGRAM_COURSE_DURATION_TAB WHERE programId = ? AND courseId = ? AND durationId = ?";
    $getParams = [$programId, $courseId, $durationId];
    $getResult = selectQuery($conn, $getQuery, 'sss', $getParams);
    return ($getResult[0]);
}

// get payment purpose details
function _get_payment_purpose_details($conn, $paymentPurposeId)
{
    $getQuery = "SELECT * FROM SETUP_PAYMENT_PURPOSE_TAB WHERE paymentPurposeId = ?";
    $getParams = [$paymentPurposeId];
    $getResult = selectQuery($conn, $getQuery, 's', $getParams);
    return ($getResult[0]);
}

// get payment method details
function _get_payment_method_details($conn, $paymentMethodId)
{
    $getQuery = "SELECT * FROM SETUP_PAYMENT_METHOD_TAB WHERE paymentMethodId = ?";
    $getParams = [$paymentMethodId];
    $getResult = selectQuery($conn, $getQuery, 's', $getParams);
    return ($getResult[0]);
}