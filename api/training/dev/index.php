<?php
require_once 'config/connection.php';

////// print result /////////////////////////////////
$response = [
    'response' => 200,
    'success' => true,
    'userIpAddress' => $userIpAddress,
    'frontEndApiKey' => $frontEndApiKey,
    'backEndApiKey' => $backEndApiKey,
];
echo json_encode($response);