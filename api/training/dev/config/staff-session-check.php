<?php
$headers = getallheaders();
$accessKey = isset($headers['Authorization']) ? trim(str_replace('Bearer ', '', $headers['Authorization'])) : null;
///////////auth/////////////////////////////////////////
validateEmptyField($accessKey, 'ACCESS KEY');

$response = _staff_accesskey_validation($conn, $accessKey);
$authData = json_decode($response, true);
$checkSession = $authData['checkSession'];
$loginStaffId = $authData['loginStaffId']; 
$loginStaffFullname = $authData['loginFullname'];
$loginRoleId = $authData['loginRoleid'];